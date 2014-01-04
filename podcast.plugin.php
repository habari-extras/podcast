<?php

/**
 * Habari Podcast Plugin
 *
 * Generates podcast feeds, allows
 * the podcast content type to appear along
 * with entries on the user's site, and  embeds a
 * player in podcast posts so the podcast can be
 * listened to by normal readers.
 *
 * @version $Id$
 * @copyright 2008
 */

require_once( 'mp3info.php' );
require_once( 'podcasthandler.php' );

class Podcast extends Plugin
{

	const PODCAST_ITUNES = 0;
	const OPTIONS_PREFIX = 'podcast__';

	private $default_options = array(
		'player' => 'niftyplayer',
		'nifty_background' => 'FFFFFF',
		'nifty_width' => '165',
		'nifty_height' => '38',
		'xspf_width' => '300',
		'xspf_height' => '20',
		);

	private $current_post = NULL;
	
	private $players = array(
		'niftyplayer' => 'Niftyplayer',
		'xspf' => 'xspf',
		'html5' => 'Html 5',
	);

	private $itunes_rating = array(
		'No' => 'No Rating',
		'Clean' => 'Clean',
		'Yes' => 'Explicit'
	);

	private $itunes_categories = array(
		'',
		'Arts', 
		'Arts:Design', 
		'Arts:Fashion & Beauty', 
		'Arts:Food', 
		'Arts:Literature', 
		'Arts:Performing Arts', 
		'Arts:Visual Arts',
		'Business',
		'Business:Business News', 
		'Business:Careers', 
		'Business:Investing', 
		'Business:Management & Marketing', 
		'Business:Shopping',
		'Comedy',
		'Education', 
		'Education:Education Technology', 
		'Education:Higher Education', 
		'Education:K-12', 
		'Education:Language Courses', 
		'Education:Training',
		'Games & Hobbies',
		'Games & Hobbies:Automotive', 
		'Games & Hobbies:Aviation', 
		'Games & Hobbies:Hobbies', 
		'Games & Hobbies:Other Games', 
		'Games & Hobbies:Video Games',
		'Government & Organizations',
		'Government & Organizations:Local', 
		'Government & Organizations:National', 
		'Government & Organizations:Non-Profit', 
		'Government & Organizations:Regional',
		'Health',
		'Health:Alternative Health', 
		'Health:Fitness & Nutrition', 
		'Health:Self-Help', 
		'Health:Sexuality',
		'Kids & Family',
		'Music',
		'News & Politics',
		'Religion & Spirituality',
		'Religion & Spirituality:Buddhism', 
		'Religion & Spirituality:Christianity', 
		'Religion & Spirituality:Hinduism', 
		'Religion & Spirituality:Islam', 
		'Religion & Spirituality:Judaism', 
		'Religion & Spirituality:Other', 
		'Religion & Spirituality:Spirituality',
		'Science & Medicine', 
		'Science & Medicine:Medicine', 
		'Science & Medicine:Natural Sciences', 
		'Science & Medicine:Social Sciences',
		'Society & Culture', 
		'Society & Culture:History', 
		'Society & Culture:Personal Journals', 
		'Society & Culture:Philosophy', 
		'Society & Culture:Places & Travel',
		'Sports & Recreation',
		'Sports & Recreation:Amateur', 
		'Sports & Recreation:College & High School', 
		'Sports & Recreation:Outdoor', 
		'Sports & Recreation:Professional',
		'Technology', 
		'Technology:Gadgets', 
		'Technology:Tech News', 
		'Technology:Podcasting', 
		'Technology:Software How-To',
		'TV & Film',
	);

	/**
	 * Redirects the link from an embedded player, feed, or an html
	 * download link to the actual file
	 *
	 * @param PluginHandler $handler. Primarily used to get the handler vars
	 * @return Nothing.
	 * @TODO make sure $podcast actually holds a valid feed
	 * @TODO make sure $method is valid
	 */
	public function action_plugin_act_podcast_media( $handler )
	{
		// $podcast is the name of the podcast
		$podcast = $handler->handler_vars['podcast_name'];
		// $post is the post we're using
		$post = Post::get( array( 'slug' => $handler->handler_vars['post_name'] ) );
		// $method is the source of the link
		// embed - from an on-page player
		// download- from a download link under the player
		// feed - from a feed
		$method = $handler->handler_vars['method'];

		$info = $post->info->{"$podcast"};
		if( !empty( $info ) && isset( $info['enclosure'] ) ) {
			$filename = $handler->handler_vars['filename'];
			$url = dirname( $info['enclosure'] ) . '/' . $filename;

			// allow plugins to act. intended for stats
			Plugins::act( 'podcast_redirect', $podcast, $post, $method, rawurldecode( $filename ) );

			header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
			header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
			header( 'Pragma:no-cache'); // HTTP/1.0
			header( 'Content-type: ' . Utils::mimetype( $url ) );
			header('Content-Length: '. $info['size'] );

			Utils::redirect( $url );
		}

	}

	/**
	* Set up the podcast content type on activation
	* @param string $plugin_file The filename of the plugin being activated, compare to this class' filename
	*/
	public function action_plugin_activation( $plugin_file )
	{
		if( Plugins::id_from_file( __FILE__ ) == Plugins::id_from_file( $plugin_file ) ) {
			Post::add_new_type( 'podcast' );
		}
		foreach ( $this->default_options as $name => $value ) {
			$current_value = Options::get( self::OPTIONS_PREFIX . $name );
			if ( !isset( $current_value) ) {
				Options::set( self::OPTIONS_PREFIX . $name, $value );
			}
		}
	}

	public function action_plugin_deactivation( $plugin_file )
	{
		if( Plugins::id_from_file( __FILE__ ) == Plugins::id_from_file( $plugin_file  ) ) {
			Post::deactivate_post_type( 'podcast' );
		}
	}

	public function filter_post_type_display($type, $foruse) 
	{ 
		$names = array( 
			'podcast' => array(
				'singular' => _t( 'Podcast', 'podcast' ),
				'plural' => _t( 'Podcasts', 'podcast' ),
			)
		); 
 		return isset($names[$type][$foruse]) ? $names[$type][$foruse] : $type; 
	}

	/**
	* Actions to be carried out when the site is accessed
	* and the plugin is active.
	*/
	public function action_init()
	{
		$this->load_text_domain( 'podcast' );
		$this->add_template( 'podcast.multiple', dirname( __FILE__ ) . '/templates/rawphp/podcast.multiple.php' );
		$this->add_template( 'podcast.single', dirname( __FILE__ ) . '/templates/rawphp/podcast.single.php' );
		$this->add_template( 'podcast.multiple', dirname( __FILE__ ) . '/templates/hi/podcast.multiple.php' );
		$this->add_template( 'podcast.single', dirname( __FILE__ ) . '/templates/hi/podcast.single.php' );

		$this->add_rule("'podcast'/podcast_name/post_name/method/filename", 'podcast_media');
	}

	/**
	* This function is unfinished. Still trying to decide
	* how to add the media player to the post when it
	* is shown on site.
	*
	* Adds the podcast stylesheet to the admin header,
	* Adds menu items to the Habari silo for mp3 files
	* for each feed so the mp3 can be added to multiple 
	* feeds.
	*
	* @param Theme $theme the current theme being used.
	*/
	public function action_admin_header( $theme )
	{
		$vars = Controller::get_handler_vars();
		if( 'plugins' == $theme->page  && isset( $vars['configure'] ) && $this->plugin_id == $vars['configure']  ) {
			Stack::add( 'admin_stylesheet', array( $this->get_url() . '/podcast.css', 'screen' ), 'podcast', array( 'admin' ) );
		}
		if( 'publish' == $theme->page && $theme->form->content_type->value == Post::type( 'podcast' ) ) {
			Stack::add( 'admin_stylesheet', array( $this->get_url() . '/podcast.css', 'screen' ), 'podcast', array( 'admin' )  );

			$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
			if( isset( $feeds ) ) {
				$output = '';
				foreach( $feeds as $feed => $feedtype ) {
					$feedmd5 = md5( $feed );
					$output .= <<< MEDIAJS
$.extend(habari.media.output.audio_mpeg3, {
	add_to_{$feed}: function(fileindex, fileobj) {
		$('#enclosure_{$feedmd5}').val(fileobj.url);
		habari.editor.insertSelection('<a href="'+fileobj.url+'" rel="enclosure">'+fileobj.title+'</a>');
	}
});
MEDIAJS;
				}
				Stack::add( 'admin_header_javascript', $output, 'podcast', array( 'jquery', 'media' ) );
			}
		}
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$feeds = Options::get( 'podcast__feeds' );
			if( isset( $feeds ) && count( $feeds ) > 0 ) {
				$feed_obj = new ArrayObject( $feeds );
				for( $it = $feed_obj->getIterator(); $it->valid(); $it->next() ) {
					if( $action == 'feed_' .  md5(  $it->key() ) ) {
						switch( $it->current() ) {
							case self::PODCAST_ITUNES:
								$this->itunes_options( $it );
								break;
						}
					}
				}
			}
			switch ( $action ) {
				case 'managefeeds' :
					$ui = new FormUI( 'manage-podcasts' );

					$addfeed = $ui->append( 'fieldset', 'addfeed', _t( 'Add Feed', 'podcast' ) );
					$addfeed->append( 'text', 'feedname', 'null:null', _t( 'New Feed Name:', 'podcast' ) );
					$addfeed->append( 'select', 'feedtype', 'null:null', _t( 'New Feed Type:', 'podcast' ) );
					$addfeed->feedtype->options = array( 'itunes' );

					$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
					$feeddata = array();
					if( isset( $feeds ) ) {
						$feeddata = array_keys(  $feeds );
					}
					if( count( $feeddata ) > 0 ) {
						$editfeed = $ui->append( 'fieldset', 'editfeed', _t( 'Manage Feeds', 'podcast' ) );
						$editfeed->append( 'static', 'managelabel', _t( '<p>Uncheck the feeds that you wish to delete.</p>', 'podcast' ) );
						$feeds = $editfeed->append( 'checkboxes', 'feeds', 'null:null', _t( 'Feeds', 'podcast' ) );
						$feeds->options = array_combine( $feeddata, $feeddata );
						$feeds->value = $feeddata; 
					}

					$ui->append( 'submit', 'submit', _t( 'Submit', 'podcast' ) );

					$ui->on_success( array( $this, 'manage_feeds' ) );
					$ui->out();
					break;
				case 'configure_player':
					$ui = new FormUI( 'configure-players' );
					$players = $ui->append( 'fieldset', 'players', _t( 'Choose Player ', 'podcast' ) );
					$player = $players->append( 'select', 'player', self::OPTIONS_PREFIX . 'player' );
					$player->options = $this->players;
					$nifty = $ui->append( 'fieldset', 'nifty', _t( 'Nifty Player Settings', 'podcast' ) );
					$nifty_bkgrd = $nifty->append( 'text', 'nifty_bkgrd', self::OPTIONS_PREFIX . 'nifty_background', _t( 'Background color (hex value, e.g. ffffff)', 'podcast' ) );
					$nifty_width = $nifty->append( 'text', 'nifty_width', self::OPTIONS_PREFIX . 'nifty_width', _t( 'Player Width (pixels)', 'podcast' ) );
					$nifty_height = $nifty->append( 'text', 'nifty_height', self::OPTIONS_PREFIX . 'nifty_height', _t( 'Player Height (pixels)', 'podcast' ) );
					$xspf = $ui->append( 'fieldset', 'xspf', _t( 'Xspf Player Settings' ) );
					$xspf_width = $xspf->append( 'text', 'xspf_width', self::OPTIONS_PREFIX . 'xspf_width', _t( 'Player Width (pixels)', 'podcast' ) );
					$xspf_height = $xspf->append( 'text', 'xspf_height', self::OPTIONS_PREFIX . 'xspf_height', _t( 'Player Height (pixels)', 'podcast' ) );
					$ui->append( 'submit', 'submit', _t( 'Submit' ) );
					$ui->out();
					break;
			}
		}
	}

	/**
	* Add actions to the plugin page for this plugin
	*
	* @param array $actions An array of actions that apply to this plugin
	* @param string $plugin_id The string id of a plugin, generated by the system
	* @return array The array of actions to attach to the specified $plugin_id
	*/
	public function filter_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
			if( isset( $feeds ) ) {
				foreach( $feeds as $feedname => $feedtype ) {
					$actions['feed_' . md5($feedname)] = _t( 'Configure %s', array( $feedname ), 'podcast' );
				}
			}
			$actions['configure_player'] = _t( 'Configure Player', 'podcast' );
			$actions['managefeeds'] = _t( 'Manage Feeds', 'podcast' );
		}
		return $actions;
	}
	
	/**
	 * Process the manage feeds form submission
	 * 
	 * @param FormUI $form The form with the feed information
	 */
	public function manage_feeds( $form )
	{
		$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
		$feedsout = array();
		if( count( $feeds ) > 0 ) {
			foreach( $feeds as $feedname => $feedtype ) {
				if( in_array( (string)$feedname, $form->feeds->value ) ) {
					$feedsout[$feedname] = $feedtype;
				}
			}
		}
		if( $form->feedname->value != '' ) {
			$feedsout[$form->feedname->value] = $form->feedtype->value;
		}
		Options::set( self::OPTIONS_PREFIX . 'feeds', $feedsout );

		Utils::redirect();
	}

	/**
	* Add fields to the publish page for podcasts
	*
	* @param FormUI $form The publish form
	* @param Post $post 
	* @return array 
	*/
	public function action_form_publish( $form, $post )
	{
		if( $form->content_type->value == Post::type( 'podcast' ) ) {
			$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
			$postfields = $form->publish_controls->append( 'fieldset', 'enclosures', _t( 'Podcasting', 'podcast'  ) );
			if( count( $feeds ) ) {
			foreach( $feeds as $feed => $feedtype ) {
				switch( $feedtype ) {
				case self::PODCAST_ITUNES:
					$this->post_itunes_form( $form, $post, $feed );
					break;
				}
			}
			}
			else {
				$msg = _t( 'You must create a feed first.<ol><li>Go to the plugins page and select \'Manage Feeds\' from the Podcast plugins droplist.</li><li>Create a feed.</li>.</ol>After doing so you can begin to create podcast posts.', 'podcast' );
				$wrapper = $postfields->append( 'wrapper', 'nofeeds' );
				$wrapper->append( 'static', 'no_feeds', $msg );
			}
		}
	}

	/**
	* Replace specified text in the post with the desired text
	*
	* @param string $content The post content
	* @param Post $post The post object to which the content belongs
	*
	* @return string The altered content
	*/

	public function filter_post_content( $content, $post )
	{
		$rule = URL::get_matched_rule();

		if( 'UserThemeHandler' == $rule->handler ) {
			$this->current_post = $post;
			preg_match_all( '%<a href="(.*)(" rel="enclosure">)(.*)</a>%i', $content, $matches );

			$count = count( $matches[1] );
			for( $i = 0; $i < $count; $i++ ){
				$player = $this->embed_player( $matches[1][$i] );
				if( $player ) {
					$content = str_ireplace( $matches[0][$i], $player, $content );
				}
			}

			$this->current_post = NULL;
			$this->title = '';
		}
		return $content;
	}

	/**
	* Callback function used by filter_post_content
	* to embed a media player in the post content
	*
	* @param str $file The file to create a media player for
	*
	* @return string The code for the media player
	*/
	protected function embed_player( $file )
	{
		$options = array();
		$feeds = Options::get( 'podcast__feeds' );
		if( !isset( $feeds ) ) {
			return FALSE;
		}
		foreach( $feeds as $feed => $val ) {
			if( $this->current_post->info->$feed ) {
				$options = $this->current_post->info->$feed;
				if( $options['enclosure'] == $file ) {
					break;
				}
			}
		}

		$parts = pathinfo( $options['enclosure'] );
		$download_url = URL::get( 'podcast_media', array( 'podcast_name' => $feed, 'post_name' => $this->current_post->slug, 'method' => 'download', 'filename' => rawurlencode( basename( $options['enclosure'] ) ) ) );

		if( isset( $options['show_player'] ) && $options['show_player'] === FALSE  ) {
			return _t( '<a href="%s" >Download Postcast</a>', array( $download_url ), 'podcast' );
		}

		if( ! empty( $options['subtitle'] ) ) {
			$title = $options['subtitle'];
		}
		else {
			$title = $parts['filename'];
		}

		$embed_url = URL::get( 'podcast_media', array( 'podcast_name' => $feed, 'post_name' => $this->current_post->slug, 'method' => 'embed', 'filename' => rawurlencode( basename( $options['enclosure'] ) ) ) );

		switch( Options::get( self::OPTIONS_PREFIX . 'player' ) ) {
			case 'xspf':
				$player = '<object width="' . Options::get( self::OPTIONS_PREFIX . 'xspf_width' ) . '" height="' . Options::get( self::OPTIONS_PREFIX . 'xspf_height' ) . '">';
				$player .= '<param name="movie" value="' . $this->get_url() . '/players/xspf/xspf_player_slim.swf?song_url=' . $embed_url . '&song_title=' . $title . '&player_title=' . htmlentities( Options::get( 'title' ), ENT_QUOTES, 'UTF-8' ) . '" />';
				$player .= '<param name="wmode" value="transparent" />';
				$player .= '<embed src="' . $this->get_url() . '/players/xspf/xspf_player_slim.swf?song_url=' . $embed_url . '&song_title=' . $title . '&player_title=' . htmlentities( Options::get( 'title' ), ENT_QUOTES, 'UTF-8' ) . '" type="application/x-shockwave-flash" wmode="transparent" width="' . Options::get( self::OPTIONS_PREFIX . 'xspf_width' ) . '" height="' . Options::get( self::OPTIONS_PREFIX . 'xspf_height' ) . '"></embed>';
				$player .= '</object>';
				break;

			case 'niftyplayer':
				$player = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="165" height="38" id="niftyPlayer1" align="">';
				$player .= '<param name="movie" value="' . $this->get_url() . '/players/niftyplayer/niftyplayer.swf?file=' . $embed_url . '&as=0">';
				$player .= '<param name="quality" value="high">';
				$player .= '<param name="bgcolor" value="#' . Options::get( self::OPTIONS_PREFIX . 'nifty_background' ) . '">';
				$player .= '<embed src="' . $this->get_url() . '/players/niftyplayer/niftyplayer.swf?file=' . $embed_url . '&as=0" quality="high" bgcolor="#' . Options::get( self::OPTIONS_PREFIX . 'nifty_background' ) . '" width="' . Options::get( self::OPTIONS_PREFIX . 'nifty_width' ) . '" height="' . Options::get( self::OPTIONS_PREFIX . 'nifty_height' ) . '" name="niftyPlayer1" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>';
				$player .= '</object>';
				break;

			case 'html5' :
				$player = '<p><audio controls >';
				$player .= '<source src="' . $embed_url . '" type="' . Utils::mimetype( $embed_url ) . '" />';
				if ($parts['extension'] == 'mp3' ) {
					$ogg_url = $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '.ogg';
					$player .= '<source src="' . $ogg_url . '" type="' . Utils::mimetype($ogg_url) . '"  />';
				}
				$player .= '</audio></p>';
				break;
		}

		if( ! isset( $options['show_download'] ) || $options['show_download'] === TRUE ) {
			$player .= _t('<p><a href="%s" ><small>Download Podcast</small></a></p>', array( $download_url ), 'podcast' );
		}

		return $player;
	}

	/*
	* Modify a post before it is updated
	*
	* Called whenever a post is about to be updated or published . 
	*
	* @param Post $post The post being saved, by reference
	* @param FormUI $form The form that was submitted on the publish page
	*/
	public function action_publish_post( $post, $form )
	{
		if( $post->content_type == Post::type( 'podcast' ) ) {
			$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
			foreach( $feeds as $feed => $feedtype ) {
				switch( $feedtype ) {
					case self::PODCAST_ITUNES:
						$this->get_post_itunes_settings( $form, $post, $feed );
					break;
				}
			}

		}
	}

	/**
	* Search for templates that the theme is requesting in  directories other
	* than where the normal theme template files are located. If the template 
	* is found, return it's path so the theme can use it.
	*
	* @param string $template_path The path containing the theme's templates
	* @param string $template_name The name of  the template being searched for
	* @param $class string The theme engine being used by the theme
	*
	* @return string The new path containing the template being searched for
	*/
	public function filter_include_template_file( $template_path, $template_name, $class = null )
	{
		if ( ! isset( $class ) ) {
			return $template_path;
		}
		if ( in_array( $template_name, array( 'podcast.single', 'podcast.multiple' ) ) ) {
			if ( ! file_exists( $template_path ) ) {
				switch ( strtolower($class) ) {
					case 'rawphpengine':
						if( $template_name == 'podcast.single' ) {
							$template_path= dirname( $this->get_file() ) . '/templates/rawphp/podcast.single.php';
						}
						else if( $template_name == 'podcast.multiple' ) {
							$template_path = dirname( $this->get_file() ) . '/templates/rawphp/podcast.multiple.php';
						}
						break;
					case 'hiengine' :
						if( $template_name == 'podcast.single' ) {
							$template_path= dirname( $this->get_file() ) . '/templates/hi/podcast.single.php';
						}
						else if( $template_name == 'podcast.multiple' ) {
							$template_path = dirname( $this->get_file() ) . '/templates/hi/podcast.multiple.php';
						}
						break;
				}
			}
		}
		return $template_path;
	}

	/**
	* Add rewrite rules to map podcast feeds to this plugin
	*
	* @param array $rules An array of RewriteRules
	* @return array The array of new and old rules
	*/
	public function filter_rewrite_rules( $rules )
	{
		$feeds = Options::get( self::OPTIONS_PREFIX . 'feeds' );
		if( !isset( $feeds ) ) {
			return $rules;
		}
		$feed_regex = implode( '|', array_keys( $feeds ) );

		$rules[] = new RewriteRule( array(
			'name' => 'podcast',
			'parse_regex' => '%podcast/(?P<name>' . $feed_regex . ')/(?P<feed_type>rss|atom)/?$%i',
			'build_str' => 'podcast/{$name}/{$feed_type}',
			'handler' => 'PodcastHandler',
			'action' => 'podcast',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Displays the podcast feed',
		));
		$rules[] = new RewriteRule( array(
			'name' => 'display_podcasts',
			'parse_regex' => '%^podcast/(?P<podcast_name>' . $feed_regex . ')(?:/page/(?P<page>\d+))?/?$%i',
			'build_str' => 'podcast/{$podcast_name}(/page/{$page})',
			'handler' => 'UserThemeHandler',
			'action' => 'display_podcasts',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Displays multiple podcasts',
		));
		$rules[] = new RewriteRule( array(
			'name' => 'atom_feed_podcast_comments',
			'parse_regex' => '%^(?P<slug>[^/]+)/atom/comments(?:/page/(?P<page>\d+))?/?$%i', 
			'build_str' => '{$slug}/atom/comments(/page/{$page})', 
			'handler' => 'AtomHandler', 
			'action' => 'entry_comments', 
			'priority' => 8, 
			'description' => 'Podcast comments',
		));

		return $rules;
	}

	/**
	* Filter the parameters being passed to Posts::get()
	*
	* @param array $filters The parameters to be passed to Posts::get()
	*
	* @return array The updated parameters
	*/
	public function filter_template_where_filters( $filters )
	{
		$vars = Controller::get_handler_vars();
		if( strlen( $vars['entire_match'] ) && strpos( $vars['entire_match'], 'podcast/' ) !== FALSE && isset( $vars['podcast_name'] ) ) {
			$filters['where'] = "{posts}.id in ( select post_id from {postinfo} where name = '{$vars['podcast_name']}' )";
		}

		if( isset( $filters['content_type'] ) ) {
			$types = Utils::single_array( $filters->offsetGet( 'content_type' ) );
			$types[] = Post::type( 'podcast' );
			$filters->offsetSet( 'content_type', $types );
		}
		return $filters;
	}

	/**
	* Filter the parameters passed to Posts::get()  in the Atomhandler.
	* @param $content_type. mixed. content types being passed.
	* @return array. content types with Podcast type added.
	*/
	public function filter_atom_get_collection_content_type( $content_type )
	{
		$content_type = Utils::single_array( $content_type );
		$content_type[] = Post::type( 'podcast' );
		return $content_type;
	}

	/**
	* If their is an enclosure on the post, add it to the feed
	* @param $feed_entry. String. The entry as it will appear in the feed.
	* @param $post. Post. The post that is providing the content for the feed entry.
	*/
	public function action_atom_add_post( $feed_entry, $post )
	{
		$info = $post->info->get_url_args();
		foreach( $info as $key => $value ) {
			if( is_array( $value ) && isset( $value['enclosure'] ) ) {

				// create a url
				$episode = URL::get( 'podcast_media', array( 'podcast_name' => $key, 'post_name' => $post->slug, 'method' => 'feed', 'filename' => rawurlencode( basename( $value['enclosure'] ) ) ) );

				$enclosure = $feed_entry->addChild( 'link' );
				$enclosure->addAttribute( 'rel', 'enclosure' );
				$enclosure->addAttribute( 'href', $episode );
				$enclosure->addAttribute( 'length', $value['size'] );
				$enclosure->addAttribute( 'type', 'audio/mpeg' );
			}
		}
	}

	/**
	* If their is an enclosure on the post, add it to the feed
	* @param $feed_entry. String. The entry as it will appear in the feed.
	* @param $post. Post. The post that is providing the content for the feed entry.
	*/
	public function action_rss_add_post( $feed_entry, $post )
	{
		$info = $post->info->get_url_args();

		foreach( $info as $key => $value ) {
			if( is_array( $value ) && isset( $value['enclosure'] ) ) {

				// create a url
				$episode = URL::get( 'podcast_media', array( 'podcast_name' => $key, 'post_name' => $post->slug, 'method' => 'feed', 'filename' => rawurlencode( basename( $value['enclosure'] ) ) ) );

				$enclosure = $feed_entry->addChild( 'enclosure' );
				$enclosure->addAttribute( 'url', $episode );
				$enclosure->addAttribute( 'length', $value['size'] );
				$enclosure->addAttribute( 'type', 'audio/mpeg' );
			}
		}
	}

	/**
	* Respond to requests for posts for a specific podcast on the site
	*
	* @param array $handled. Boolean whether or not the request was handled
	* @param $theme. Theme. the current theme in use
	* @return boolean. Whether or not the request was handled
	*/
	public function filter_theme_act_display_podcasts( $handled, $theme )
	{
		$paramarray['fallback'] = array(
			'podcast.multiple',
			'entries.multiple',
			'multiple',
			'home',
		);

		$default_filters = array(
			'content_type' => Post::type( 'podcast' ),
		);
		$paramarray['user_filters'] = $default_filters;

		return $theme->act_display( $paramarray );
	}

	/**
	* Generates the settings form for an iTunes feed
	*
	* @param ArrayIterator $it The current feed
	*
	*/
	protected function itunes_options( $it )
	{
		$feed = $it->key();
		$user = User::identify();
		$options = Options::get( self::OPTIONS_PREFIX . "{$feed}_itunes" );

		$ui = new FormUI( 'feed' );
		$label = sprintf( _t( 'Edit %s iTunes Channel Settings', 'podcast' ), $feed );
		$itunes = $ui->append( 'fieldset', 'itunes', $label );

		$author = $itunes->append( 'text', 'author', 'null:null', _t( 'Podcast Author * : ', 'podcast' ) );
		$author->value = $options['author'] ? $options['author'] : $user->displayname;
		$author->add_validator( 'validate_required' );

		$subtitle = $itunes->append( 'text', 'title', 'null:null', _t( 'Podcast Title: ', 'podcast' ) );
		$subtitle->value = isset($options['title']) ? $options['title'] : Options::get( 'title' );

		$subtitle = $itunes->append( 'text', 'subtitle', 'null:null', _t( 'Podcast Subtitle: ', 'podcast' ) );
		$subtitle->value = $options['subtitle'] ? $options['subtitle'] : Options::get( 'tagline' );

		$summary = $itunes->append( 'textarea', 'summary', 'null:null', _t( 'Podcast Summary * : ', 'podcast' ) );
		$summary->value = $options['summary'] ? $options['summary'] : Options::get( 'tagline' );
		$summary->add_validator( 'validate_required' );

		$owner_name = $itunes->append( 'text', 'owner_name', 'null:null', _t( 'Podcast Owner Name * : ', 'podcast' ) );
		$owner_name->value = $options['owner_name'] ? $options['owner_name'] : $user->displayname;
		$owner_name->add_validator( 'validate_required' );

		$owner_email = $itunes->append( 'text', 'owner_email', 'null:null', _t( 'Podcast Owner EMail: ', 'podcast' ) );
		$owner_email->value = $options['owner_email'] ? $options['owner_email'] : $user->email;
		$owner_email->add_validator( 'validate_email' );

		$explicit = $itunes->append( 'select', 'explicit', 'null:null', _t( 'Content Rating: ', 'podcast' ) );
		$explicit->options = $this->itunes_rating;
		$explicit->value = isset( $options['explicit'] ) ? $this->itunes_rating[$options['explicit']] : $this->itunes_rating['No'];

		$image = $itunes->append( 'text', 'image', 'null:null', _t( 'Podcast Artwork URL: ', 'podcast' ) );
		$image->value = $options['image'] ? $options['image'] : '';
//		$image->add_validator( 'validate_required' );

		$main_category = $itunes->append( 'select', 'main_category', 'null:null', _t( 'Podcast Category * : ', 'podcast' ) );
		$main_category->options = $this->itunes_categories;
		$main_category->value = isset( $options['main_category'] ) ? array_search( $options['main_category'], $this->itunes_categories ) : 0;
		$main_category->add_validator( 'validate_required' );

		$category_2 = $itunes->append( 'select', 'category_2', 'null:null', _t( 'Podcast Category: ', 'podcast' ) );
		$category_2->options = $this->itunes_categories;
		$category_2->value = isset( $options['category_2'] ) ? array_search( $options['category_2'], $this->itunes_categories ) : 0;

		$category_3 = $itunes->append( 'select', 'category_3', 'null:null', _t( 'Podcast Category: ', 'podcast' ) );
		$category_3->options = $this->itunes_categories;
		$category_3->value = isset( $options['category_3'] ) ? array_search( $options['category_3'], $this->itunes_categories ) : 0;

		$block = $itunes->append( 'checkbox', 'block', 'null:null', _t( 'Block Podcast: ', 'podcast' ) );
		$block->value = $options['block'] ? $options['block'] : 0;

		$redirect = $itunes->append( 'text', 'redirect', 'null:null', _t( 'New podcast url: ' ) );

		$ui->append( 'submit', 'submit', _t( 'Submit' ) );
		$ui->on_success( array( $this, 'itunes_updated' ), $it );
		$ui->out();
	}

	/**
	* A callback function used by itunes_options
	* to save the settings.
	*
	* @param FormUI $ui The form that is being saved
	* @param ArrayIterator $it The feed for which the form was completed
	*/
	public function itunes_updated( $ui, $it )
	{
		$options = array(
		'author' => $ui->author->value,
		'title' => $ui->title->value,
		'subtitle' => $ui->subtitle->value,
		'summary' => $ui->summary->value,
		'owner_name' => $ui->owner_name->value,
		'owner_email' => $ui->owner_email->value,
		'explicit' => $ui->explicit->value,
		'image' => $ui->image->value,
		'block' => $ui->block->value,
		'main_category' => $this->itunes_categories[$ui->main_category->value],
		'category_2' => $this->itunes_categories[$ui->category_2->value],
		'category_3' => $this->itunes_categories[$ui->category_3->value],
		'redirect' => $ui->redirect->value,
		);

		Options::set( self::OPTIONS_PREFIX . "{$it->key()}_itunes", $options );
		Session::notice( "{$it->key()} iTunes options updated." );
	}

	/**
	* Modify the publish form to include the iTunes settings for 
	* a specific post
	*
	* @param FormUI $form The form being modified
	* @param Post $post The post being edited
	* @param string $feed The name of the feed for which the form is being modified
	*
	*/
	protected function post_itunes_form( $form, $post, $feed )
	{
		$postfields = $form->publish_controls->enclosures;
		if( isset( $post->info->$feed ) ) {
			$options = $post->info->$feed;
		}
		$control_id = md5( $feed );

		$fieldname = "{$control_id}_player_settings";
		$player = $postfields->append( 'fieldset', $fieldname, _t( 'Display Settings' ) );
		$player->class = 'podcast-settings';

		$fieldname = "show_player_{$control_id}";
		$field = $player->append( 'checkbox', $fieldname, 'null:null', _t( 'Show player instead of link', 'podcast' ), 'tabcontrol_checkbox' );
		$field->value = isset( $options['show_player'] ) ? $options['show_player'] : TRUE;

		$fieldname = "show_download_{$control_id}";
		$field = $player->append( 'checkbox', $fieldname, 'null:null', _t( 'Show download link under player', 'podcast' ), 'tabcontrol_checkbox' );
		$field->value = isset( $options['show_download'] ) ? $options['show_download'] : TRUE;

		$fieldname = "{$control_id}_settings";
		$feed_fields = $postfields->append( 'fieldset', $fieldname, _t( 'Settings for ', 'podcast' ) . $feed );
		$feed_fields->class = 'podcast-settings';

		$fieldname = "enclosure_{$control_id}";
		$customfield = $feed_fields->append( 'text', $fieldname, 'null:null', _t( 'Podcast Enclosure * :', 'podcast' ), 'tabcontrol_text' );
		$customfield->value = isset( $options['enclosure'] ) ? $options['enclosure'] : '';
		$customfield->add_validator( 'validate_required' );

		$fieldname = "subtitle_{$control_id}";
		$customfield = $feed_fields->append( 'text', $fieldname, 'null:null', _t( 'Subtitle:', 'podcast' ), 'tabcontrol_text' );
		$customfield->value = isset( $options['subtitle'] ) ? $options['subtitle'] : '';

		$fieldname = "explicit_{$control_id}";
		$customfield = $feed_fields->append( 'select', $fieldname, 'null:null', _t( 'Content Rating:', 'podcast' ) );
		$customfield->template = 'tabcontrol_select';
		$customfield->options = $this->itunes_rating;
		$customfield->value = isset( $options['rating'] ) ? $this->itunes_rating[array_search( $options['rating'], $this->itunes_rating )] : $this->itunes_rating['No'];

		$fieldname = "summary_{$control_id}";
		$customfield = $feed_fields->append( 'textarea', $fieldname, 'null:null', _t( 'Summary:', 'podcast' ), 'tabcontrol_textarea' );
		$customfield->value = isset( $options['summary'] ) ? $options['summary'] : strip_tags(Format::summarize( Format::autop( $post->content ) ) );

		$fieldname = "block_{$control_id}";
		$customfield = $feed_fields->append( 'checkbox', $fieldname, 'null:null', _t( 'Block:', 'podcast' ), 'tabcontrol_checkbox' );
		$customfield->value = isset( $options['block'] ) ? $options['block'] : 0;
	}

	/**
	* Save the settings made in post_itunes_form
	*
	* @param Form $form The form to get the values from
	* @param Post $post The post for which the values are being saved
	* @param string $feed The name of the feed the values will be used in
	*
	*/
	protected function get_post_itunes_settings( $form, $post, $feed )
	{
		$control_id = md5( $feed );
		if( !strlen( $form->{"enclosure_{$control_id}"}->value ) ) {
			return;
		}
		$options = array(
			'enclosure' => $form->{"enclosure_{$control_id}"}->value,
			'rating' => $form->{"explicit_{$control_id}"}->value,
			'subtitle' => $form->{"subtitle_{$control_id}"}->value,
			'summary' => $form->{"summary_{$control_id}"}->value,
			'block' => $form->{"block_{$control_id}"}->value,
			'show_player' => $form->{"show_player_{$control_id}"}->value,
			'show_download' => $form->{"show_download_{$control_id}"}->value,
		);

		$mp3 = new MP3Info( $options['enclosure'] );
		$result = $mp3->open();
		if( $result ) {
			$options['size'] = $mp3->get_size();
			$options['duration'] = $mp3->format_minutes_seconds( $mp3->get_duration() );
		}
		else {
			$options['size'] = 0;
			$options['duration'] = 0;
		}

		$post->info->$feed = $options;

	}
}

?>
