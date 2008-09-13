<?php

/**
 * Habari Podcast Plugin
 *
 * @version $Id$
 * @copyright 2008
 */

require_once( 'mp3info.php' );

class Podcast extends Plugin
{
	private $current_url = '';
	const PODCAST_ITUNES = 0;


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

	private $itunes_explicit = array(
		0 => 'Clean',
		1 => 'No',
		2 => 'Yes',
	);

	/**
	*
	* Return information about this plugin
	* @return array Plugin info array
	*/
	function info()
	{
		return array (
			'name' => 'Podcast',
			'url' => 'http://habariproject.org/',
			'author' => 'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'version' => '1.0',
			'description' => 'This plugin provides podcasting functionality and iTunes compatibility.',
			'license' => 'Apache License 2.0',
		);
	}

	/**
	* Set up the podcast content type on activation
	* @param string $plugin_file The filename of the plugin being activated, compare to this class' filename
	*/
	function action_plugin_activation( $plugin_file )
	{
		if( Plugins::id_from_file(__FILE__) == Plugins::id_from_file($plugin_file) ) {
			Post::add_new_type('podcast');
		}
	}

	function action_init()
	{
		$this->load_text_domain( 'podcast' );
		$this->add_template( 'podcast.multiple', dirname(__FILE__) . '/templates/plugin.multiple.php' );
		$this->add_template( 'podcast.single', dirname(__FILE__) . '/templates/plugin.single.php' );
	}

	/**
	* This function is incomplete
	*/
	function action_admin_header( $theme )
	{
		if( $theme->page == 'plugins' ) {
			Stack::add('admin_stylesheet', array($this->get_url() . '/podcast.css', 'screen'));
		}
		if( $theme->page == 'publish' ) {
			Stack::add('admin_stylesheet', array($this->get_url() . '/podcast.css', 'screen'));

			$feeds = Options::get('podcast__feeds');
			if( isset( $feeds ) ) {
				$output = '';
				foreach($feeds as $feed => $feedtype) {
					$feedmd5 = md5($feed);
					$output .= <<< MEDIAJS
$.extend(habari.media.output.audio_mpeg3, {
	add_to_{$feed}: function(fileindex, fileobj) {
		$('#enclosure_{$feedmd5}').val(fileobj.url);
		habari.editor.insertSelection('<!-- file:' + fileobj.url+' -->'+'<a href="'+fileobj.url+' rel="enclosure">'+fileobj.title+'</a>');
	}
});
MEDIAJS;
				}
				echo "<script type=\"text/javascript\">{$output}</script>";
			}
		}
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui($plugin_id, $action)
	{
		if ($plugin_id == $this->plugin_id()){
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
			switch ($action){
				case 'managefeeds' :
					$ui = new FormUI('manage-podcasts');

					$addfeed = $ui->append('fieldset', 'addfeed', 'Add Feed');
					$addfeed->append('text', 'feedname', 'null:null', _t( 'New Feed Name:', 'podcast' ) );
					$addfeed->append('select', 'feedtype', 'null:null', _t( 'New Feed Type:', 'podcast' ) );
					$addfeed->feedtype->options = array('itunes');

					$feeds = Options::get( 'podcast__feeds' );
					$feeddata = array();
					if( isset( $feeds ) ) {
						$feeddata = array_keys(  $feeds );
					}
					if(count($feeddata) > 0) {
						$editfeed = $ui->append('fieldset', 'editfeed', _t( 'Manage Feeds', 'podcast' ) );
						$editfeed->append('static', 'managelabel', '<p>' . _t('Uncheck the feeds that you wish to delete.', 'podcast' ) . '</p>');
						$feeds = $editfeed->append('checkboxes', 'feeds', 'null:null', _t( 'Feeds', 'podcast' ) );
						$feeds->options = array_combine($feeddata, $feeddata);
						$feeds->value = $feeddata; 
					}

					$ui->append('submit', 'submit', 'Submit');

					$ui->on_success(array($this, 'manage_feeds'));
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
	public function filter_plugin_config($actions, $plugin_id)
	{
		if ($plugin_id == $this->plugin_id()){
			$actions['managefeeds'] = _t('Manage Feeds');
			$feeds = Options::get('podcast__feeds');
			if( isset( $feeds ) ) {
				foreach($feeds as $feedname => $feedtype) {
					$actions['feed_' . md5($feedname)] = sprintf(_t('Edit "%s" feed', 'podcast' ), $feedname);
				}
			}
		}
		return $actions;
	}
	
	/**
	 * Process the manage feeds form submission
	 * 
	 * @param FormUI $form The form with the feed information
	 */
	public function manage_feeds($form)
	{
		$feeds = Options::get('podcast__feeds');
		$feedsout = array();
		if(count($feeds) > 0 ) {
			foreach($feeds as $feedname => $feedtype) {
				if(in_array((string)$feedname, $form->feeds->value)) {
					$feedsout[$feedname] = $feedtype;
				}
			}
		}
		if($form->feedname->value != '') {
			$feedsout[$form->feedname->value] = $form->feedtype->value;
		}
		Options::set('podcast__feeds', $feedsout);

		Utils::redirect();
	}

	/**
	* Add fields to the publish page for podcasts
	*
	* @param FormUI $form The publish form
	* @param Post $post 
	* @return array 
	*/
	public function action_form_publish($form, $post)
	{
		if( $form->content_type->value == Post::type('podcast') ) {
			$feeds = Options::get('podcast__feeds');
			$postfields = $form->publish_controls->append('fieldset', 'enclosures', _t( 'Enclosures', 'podcast'  ) );
			foreach($feeds as $feed => $feedtype) {
				switch( $feedtype ) {
				case self::PODCAST_ITUNES:
					$this->post_itunes_form( $form, $post, $feed );
					break;
				}
			}
		}
	}

	function filter_post_content_out( $content )
	{
		preg_match_all( '/<!-- file:(.*) -->/i', $content, $matches, PREG_PATTERN_ORDER );
		$matches_obj = new ArrayObject( $matches[1] );

		for( $it = $matches_obj->getIterator(); $it->valid(); $it->next() ){
			$content = str_ireplace( '<!-- file:' . $it->current() . ' -->', $this->embed_player( $it->current() ), $content );
		}

		return $content;
	}


	function embed_player( $file )
	{
		$player = '<p><object width="300" height="20">';
		$player .= '<param name="movie" value="' . $this->get_url() . '/players/xspf_player_slim.swf?song_url=' . $file . '&song_title=' . basename( $file, '.mp3' ) . '&player_title=' . htmlspecialchars( Options::get( 'title' ), ENT_COMPAT, 'UTF-8' ) . '" />';
		$player .= '<param name="wmode" value="transparent" />';
		$player .= '<embed src="' . $this->get_url() . '/players/xspf_player_slim.swf?song_url=' . $file . '&song_title=' . basename( $file, '.mp3' ). '&player_title=' . htmlspecialchars( Options::get( 'title' ), ENT_COMPAT, 'UTF-8' ) . '" type="application/x-shockwave-flash" wmode="transparent" width="300" height="20"></embed>';
		$player .= '</object></p>';

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
	public function action_publish_post($post, $form)
	{
		if( $post->content_type == Post::type( 'podcast' ) ) {
			$feeds = Options::get('podcast__feeds');
			foreach($feeds as $feed => $feedtype) {
				switch( $feedtype ) {
					case self::PODCAST_ITUNES:
						$this->get_post_itunes_settings( $form, $post, $feed );
					break;
				}
			}

		}
	}

	// Use the templates in the plugin's template directory if they don't exist in the theme
	public function filter_include_template_file( $template_path, $template_name, $class )
	{

		if ( $template_name == 'podcast.single' ) {
			if ( ! file_exists( $template_path ) ) {
				switch ( strtolower($class) ) {
					case 'rawphpengine':
						$template_path= dirname( $this->get_file() ) . '/templates/podcast.single.php';
						break;
				}
			}
		}
		else if ( $template_name == 'podcast.multiple' ) {
			if( ! file_exists( $template_path ) ) {
				switch( strtolower( $class ) ) {
					case 'rawphpengine':
						$template_path = dirname( $this->get_file() ) . '/templates/podcast.multiple.php';
						break;
				}
			}
		}

/*
		if ( in_array( $template_name, array( 'podcast.single', 'podcast.multiple' ) ) ) {
			if ( ! file_exists( $template_path ) ) {
				switch ( strtolower($class) ) {
					case 'rawphpengine':
						if( $template_name == 'podcast.single' ) {
							$template_path= dirname( $this->get_file() ) . '/templates/podcast.single.php';
						}
						else if( $template_name == 'podcast.multiple' ) {
							$template_path = dirname( $this->get_file() ) . '/templates/podcast.multiple.php';
						}
						break;
				}
			}
		}
*/
		return $template_path;
	}

	/**
	* Add rewrite rules to map podcast feeds to this plugin
	*
	* @param array $rules An array of RewriteRules
	* @return array The array of new and old rules
	*/
	public function filter_rewrite_rules( $rules ) {
		$feeds = Options::get('podcast__feeds');
		if( !isset( $feeds ) ) {
			return $rules;
		}
		$feed_regex = implode('|', array_keys( $feeds ) );
		$rules[] = new RewriteRule(array(
			'name' => 'podcast',
			'parse_regex' => '%podcast/(?P<name>' . $feed_regex . ')/(?P<feed_type>rss|atom)/?$%i',
			'build_str' => 'podcast/{$name}/{$feed_type}',
			'handler' => 'UserThemeHandler',
			'action' => 'podcast',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Displays the podcast feed',
		));

		$rules[] = new RewriteRule(array(
			'name' => 'display_podcast',
			'parse_regex' => '%^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$%i',
			'build_str' => '{$slug}(/page/{$page})',
			'handler' => 'UserThemeHandler',
			'action' => 'display_podcast',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Displays a single podcast',
		));

		$rules[] = new RewriteRule(array(
			'name' => 'display_podcasts',
			'parse_regex' => '%^podcast/(?P<name>' . $feed_regex . ')(?:/page/(?P<page>\d+))?/?$%i',
			'build_str' => 'podcast/{$name}(/page/{$page})',
			'handler' => 'UserThemeHandler',
			'action' => 'display_podcasts',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Displays multiple podcasts',
		));

		return $rules;
	}

	public function filter_template_user_filters( $where )
	{
		if( isset( $where['content_type'] ) ) {
			if( is_array( $where['content_type'] ) ) {
				$where['content_type'] = array_merge( $where['content_type'], array( Post::type( 'podcast' ) ) );
			}
			else {
				$where['content_type'] = array( $where['content_type'], Post::type('podcast') );
			}
		}
		return $where;
	}

	public function filter_theme_act_display_podcast( $handled, $theme )
	{
		$default_filters = array( 
			'content_type' => Post::type( 'podcast' )
		);
		$paramarray['user_filters'] = $default_filters;

		$theme->act_display_post( $paramarray );
		return true;
	}
	
	public function filter_theme_act_display_podcasts( $handled, $theme )
	{
		$paramarray['fallback'] = array(
			'podcast.multiple',
			'multiple',
			'home',
		);
		
		$default_filters = array(
			'content_type' => Post::type( 'podcast' ),
		);

		$paramarray['user_filters'] = $default_filters;

		$theme->act_display( $paramarray );
		return true;
	}

	/**
	* Respond to requests for podcasts
	*
	* @param array $handler_vars The variables gathered from the rewrite rules.
	*/
	public function action_handler_podcast($handler_vars) {
		extract($handler_vars); // Expecting: $entire_match $name $feed_type
		$this->current_url = Site::get_url('habari') . DIRECTORY_SEPARATOR . $entire_match;

		switch($feed_type) {
			case 'rss':
				$this->produce_rss($name);
				break;
			case 'atom':
				$this->produce_atom($name);
				break;
		}

		exit;
	}

	/**
	* Produce RSS output for the named feed.
	*
	* @param string $feed_name The name of the feed to output
	*/
	public function produce_rss( $feed_name )
	{
		$xml = $this->create_rss_wrapper( $feed_name );
		$posts= Posts::get( array( 'status' => Post::status( 'published' ), 'content_type' => Post::type( 'podcast' ), array( 'info' => $feed_name ) ) );
		$xml = $this->add_posts( $xml, $posts, $feed_name );
		Plugins::act( 'podcast_rss_collection', $xml, $posts, $feed_name );
		ob_clean();
		header( 'Content-Type: application/xml' );
		file_put_contents( 'podcast.rss', $xml->asXML() );
		echo $xml->asXML();
		exit;
	}

	/**
	 * Creates a basic RSS-format XML structure with channel and items elements
	 * @return SimpleXMLElement The requested RSS document
	 */
	public function create_rss_wrapper( $feed_name )
	{
		$xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8" ?><rss></rss>' );
		$xml->addAttribute( 'xmlns:xmlns:atom', 'http://www.w3.org/2005/Atom' );
		$xml->addAttribute( 'xmlns:xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd' );
		$xml->addAttribute(  'version', '2.0' );
		$channel = $xml->addChild( 'channel' );
		$title = $channel->addChild( 'title', Options::get('title') );
		$link = $channel->addChild( 'link', Site::get_url('habari') );
		$atom_link = $channel->addChild( 'xmlns:atom:link' );
		$atom_link->addAttribute( 'href', $this->current_url );
		$atom_link->addAttribute( 'rel', 'self' );
		$atom_link->addAttribute( 'type', 'application/rss+xml' );
		$lang = $channel->addChild( 'language', strlen( Options::get( 'locale' ) ) ? Options::get( 'locale' ) : 'en-us' );
		if ( $tagline= Options::get( 'tagline' ) ) {
			$description= $channel->addChild( 'description', $tagline );
		}
		$pubDate= $channel->addChild( 'lastBuildDate', date( 'r', strtotime( Post::get()->pubdate ) ) );
		$generator= $channel->addChild( 'generator', 'Habari ' . Version::get_habariversion() . ' http://habariproject.org/' );

		$itunes = Options::get( "podcast__{$feed_name}_itunes" );

		$itunes_author = $channel->addChild( 'xmlns:itunes:author', $itunes['author'] );
		$itunes_subtitle = $channel->addChild( 'xmlns:itunes:subtitle', $itunes['subtitle'] );
		$itunes_summary = $channel->addChild( 'xmlns:itunes:summary', $itunes['subtitle'] );
		$itunes_owner = $channel->addChild( 'xmlns:itunes:owner' );
		$itunes_owner_name = $itunes_owner->addChild( 'xmlns:itunes:name', $itunes['owner_name'] );
		$itunes_owner_email = $itunes_owner->addChild( 'xmlns:itunes:email', $itunes['owner_email'] );
		$itunes_explicit = $channel->addChild( 'xmlns:itunes:explicit', $itunes['explicit'] );
		$itunes_image = $channel->addChild( 'xmlns:itunes:image' );
		$itunes_image->addAttribute( 'href', $itunes['image'] );
		if( strlen( $itunes['main_category'] ) ) {
			$itunes_category = $channel->addChild( 'xmlns:itunes:category' );
			$categories = explode( ':', $itunes['main_category'] );
			$itunes_category->addAttribute( 'text', $categories[0] );
			if( isset( $categories[1] ) ) {
				$child = $itunes_category->addChild( 'xmlns:itunes:category' );
				$child->addAttribute( 'text', $categories[1] );
			}
		}
		if( isset( $itunes['category_2'] ) ) {
			$itunes_category = $channel->addChild( 'xmlns:itunes:category' );
			$categories = explode( ':', $itunes['category_2'] );
			$itunes_category->addAttribute( 'text', $categories[0] );
			if( isset( $categories[1] ) ) {
				$child = $itunes_category->addChild( 'xmlns:itunes:category' );
				$child->addAttribute( 'text', $categories[1] );
			}
		}
		if( strlen( $itunes['category_3'] ) ) {
			$itunes_category = $channel->addChild( 'xmlns:itunes:category' );
			$categories = explode( ':', $itunes['category_3'] );
			$itunes_category->addAttribute( 'text', $categories[0] );
			if( isset( $categories[1] ) ) {
				$child = $itunes_category->addChild( 'xmlns:itunes:category' );
				$child->addAttribute( 'text', $categories[1] );
			}
		}
		$itunes_block = $channel->addChild( 'xmlns:itunes:block', $itunes['block'] ? 'Yes' : 'No' );
		if ( strlen( $itunes['redirect'] ) ) {
			$itunes_redirect = $channel->addChild( 'xmlns:itunes:new-feed-url', $itunes['redirect'] );
		}

		Plugins::act( 'podcast_create_wrapper', $xml );
		return $xml;
	}

	/**
	 * Add posts as items in the provided xml structure
	 * @param SimpleXMLElement $xml The document to add to
	 * @param array $posts An array of Posts to add to the XML
	 * @return SimpleXMLElement The resultant XML with added posts
	 */
	public function add_posts($xml, $posts, $feed_name )
	{
		$items = $xml->channel;
		foreach ( $posts as $post ) {
			if ($post instanceof Post) {
				$item= $items->addChild( 'item' );
				$title= $item->addChild( 'title', $post->title );
				$link= $item->addChild( 'link', $post->permalink );
				$description= $item->addChild( 'description', $post->content );
				$pubdate= $item->addChild ( 'pubDate', date( 'r', strtotime( $post->pubdate ) ) );
				$guid= $item->addChild( 'guid', $post->guid );
				$guid->addAttribute( 'isPermaLink', 'false' );

				list($url, $size, $duration, $explicit, $subtitle, $keywords, $summary, $block ) = $post->info->$feed_name;
				$enclosure = $item->addChild( 'enclosure' );
				$enclosure->addAttribute( 'url', $url );
				$enclosure->addAttribute( 'length', $size );
				$enclosure->addAttribute( 'type', 'audio/mpeg' );

				$itunes_author = $item->addChild( 'xmlns:itunes:author', $post->author->displayname );
				$itunes_subtitle = $item->addChild( 'xmlns:itunes:subtitle', $subtitle );
				$itunes_summary = $item->addChild( 'xmlns:itunes:summary', $summary );
				$itunes_duration = $item->addChild( 'xmlns:itunes:duration', $duration );
				$itunes_explicit = $item->addChild( 'xmlns:itunes:explicit', $explicit );
				$itunes_keywords = $item->addChild( 'xmlns:itunes:keywords', $keywords );
				$itunes_block = $item->addChild( 'xmlns:itunes:block', $block ? 'Yes' : 'No' );

				Plugins::act( 'podcast_add_post', $item, $post );
			}
		}
		return $xml;
	}

	/**
	* Produce Atom output for the named feed.
	*
	* @param string $feed_name The name of the feed to output
	*/
	public function produce_atom($feed_name)
	{
		echo <<< ATOM
Atom goes here.
ATOM;
	}

	protected function itunes_options( $it )
	{
		$feed = $it->key();
		$user = User::identify();
		$options = Options::get( "podcast__{$feed}_itunes" );

		$ui = new FormUI( 'feed' );
		$label = sprintf( _t( 'Edit %s iTunes Channel Settings', 'podcast' ), $feed );
		$itunes = $ui->append( 'fieldset', 'itunes', $label );

		$author = $itunes->append( 'text', 'author', 'null:null', _t( 'Podcast Author: ', 'podcast' ) );
		$author->value = $options['author'] ? $options['author'] : $user->displayname;

		$subtitle = $itunes->append( 'text', 'subtitle', 'null:null', _t( 'Podcast Subtitle: ', 'podcast' ) );
		$subtitle->value = $options['subtitle'] ? $options['subtitle'] : Options::get( 'tagline' );

		$summary = $itunes->append( 'textarea', 'summary', 'null:null', _t( 'Podcast Summary: ', 'podcast' ) );
		$summary->value = $options['summary'] ? $options['summary'] : Options::get( 'tagline' );

		$owner_name = $itunes->append( 'text', 'owner_name', 'null:null', _t( 'Podcast Owner Name: ', 'podcast' ) );
		$owner_name->value = $options['owner_name'] ? $options['owner_name'] : $user->displayname;

		$owner_email = $itunes->append( 'text', 'owner_email', 'null:null', _t( 'Podcast Owner EMail: ', 'podcast' ) );
		$owner_email->value = $options['owner_email'] ? $options['owner_email'] : $user->email;
		$owner_email->add_validator( 'validate_email' );

		$explicit = $itunes->append( 'select', 'explicit', 'null:null', _t( 'Explicit Content: ', 'podcast' ) );
		$explicit->options = $this->itunes_explicit;
		$explicit->value = isset( $options['explicit'] ) ? array_search( $options['explicit'], $this->itunes_explicit ) : 0;

		$image = $itunes->append( 'text', 'image', 'null:null', _t( 'Podcast Artwork URL: ', 'podcast' ) );
		$image->value = $options['image'] ? $options['image'] : '';

		$block = $itunes->append( 'checkbox', 'block', 'null:null', _t( 'Block Podcast: ', 'podcast' ) );
		$block->value = $options['block'] ? $options['block'] : 0;

		$main_category = $itunes->append( 'select', 'main_category', 'null:null', _t( 'Podcast Category: ', 'podcast' ) );
		$main_category->options = $this->itunes_categories;
		$main_category->value = isset( $options['main_category'] ) ? array_search( $options['main_category'], $this->itunes_categories ) : 0;

		$category_2 = $itunes->append( 'select', 'category_2', 'null:null', _t( 'Podcast Category: ', 'podcast' ) );
		$category_2->options = $this->itunes_categories;
		$category_2->value = isset( $options['category_2'] ) ? array_search( $options['category_2'], $this->itunes_categories ) : 0;

		$category_3 = $itunes->append( 'select', 'category_3', 'null:null', _t( 'Podcast Category: ', 'podcast' ) );
		$category_3->options = $this->itunes_categories;
		$category_3->value = isset( $options['category_3'] ) ? array_search( $options['category_3'], $this->itunes_categories ) : 0;

		$redirect = $itunes->append( 'text', 'redirect', 'null:null', _t( 'New podcast url: ' ) );

		$ui->append( 'submit', 'submit', _t( 'Submit' ) );
		$ui->on_success( array( $this, 'itunes_updated' ), $it );
		$ui->out();
	}

	public function itunes_updated( $ui, $it )
	{
		$options = array(
		'author' => $ui->author->value,
		'subtitle' => $ui->subtitle->value,
		'summary' => $ui->summary->value,
		'owner_name' => $ui->owner_name->value,
		'owner_email' => $ui->owner_email->value,
		'explicit' => $this->itunes_explicit[$ui->explicit->value],
		'image' => $ui->image->value,
		'block' => $ui->block->value,
		'main_category' => $this->itunes_categories[$ui->main_category->value],
		'category_2' => $this->itunes_categories[$ui->category_2->value],
		'category_3' => $this->itunes_categories[$ui->category_3->value],
		'redirect' => $ui->redirect->value,
		);

		Options::set( "podcast__{$it->key()}_itunes", $options );
		Session::notice( "{$it->key()} iTunes options updated." );
	}

	protected function post_itunes_form( $form, $post, $feed )
	{
		$postfields = $form->publish_controls->enclosures;
		if( isset( $post->info->$feed ) ) {
			list($url, $size, $duration, $explicit, $subtitle, $keywords, $summary, $block ) = $post->info->$feed;
		}
		$control_id = md5($feed);
		$fieldname = "{$control_id}_settings";
		$feed_fields = $postfields->append( 'fieldset', $fieldname, _t( 'Settings for ', 'podcast' ) . $feed );
		$feed_fields->class = 'podcast-settings';

		$fieldname = "enclosure_{$control_id}";
		$customfield = $feed_fields->append('text', $fieldname, 'null:null', _t( 'Podcast Enclosure:', 'podcast' ), 'tabcontrol_text' );
		$customfield->value = isset( $url ) ? $url : '';

		$fieldname = "subtitle_{$control_id}";
		$customfield = $feed_fields->append( 'text', $fieldname, 'null:null', _t( 'Subtitle:', 'podcast' ), 'tabcontrol_text' );
		$customfield->value = isset( $subtitle ) ? $subtitle : '';

		$fieldname = "explicit_{$control_id}";
		$customfield = $feed_fields->append( 'select', $fieldname, 'null:null', _t( 'Explicit:', 'podcast' ) );
		$customfield->template = 'tabcontrol_select';
		$customfield->options = $this->itunes_explicit;
		$customfield->value = isset( $explicit ) ? array_search( $explicit, $this->itunes_explicit )  : 0;

		$fieldname = "keywords_{$control_id}";
		$customfield = $feed_fields->append( 'text', $fieldname, 'null:null', _t( 'Keywords:', 'podcast' ), 'tabcontrol_text' );
		$customfield->value = isset( $keywords ) ? $keywords : isset( $post->tags ) ?$post->tags : '' ;

		$fieldname = "summary_{$control_id}";
		$customfield = $feed_fields->append( 'textarea', $fieldname, 'null:null', _t( 'Summary:', 'podcast' ), 'tabcontrol_textarea' );
		$customfield->value = isset( $summary) ? $summary : strip_tags(Format::summarize( Format::autop( $post->content ) ) );

		$fieldname = "block_{$control_id}";
		$customfield = $feed_fields->append( 'checkbox', $fieldname, 'null:null', _t( 'Block:', 'podcast' ), 'tabcontrol_checkbox' );
		$customfield->value = isset( $block ) ? $block : 0;
	}

	protected function get_post_itunes_settings( $form, $post, $feed )
	{
		$control_id = md5($feed);

		$fieldname = "enclosure_{$control_id}";
		$url = $form->$fieldname->value;

		$fieldname = "explicit_{$control_id}";
		$explicit = $this->itunes_explicit[$form->$fieldname->value];

		$fieldname = "subtitle_{$control_id}";
		$subtitle = $form->$fieldname->value;

		$fieldname = "keywords_{$control_id}";
		$keywords = $form->$fieldname->value;

		$fieldname = "summary_{$control_id}";
		$summary = $form->$fieldname->value;

		$size = 0;
		$duration = '';
		$mp3 = new MP3Info( $url, TRUE );
		$size = $mp3->get_size();
		$duration = $mp3->format_minutes_seconds( $mp3->get_duration() );

		$fieldname = "block_{$control_id}";
		$block = $form->$fieldname->value;

		$post->info->$feed = array( $url, $size, $duration, $explicit, $subtitle, $keywords, $summary, $block );
	}

}
?>