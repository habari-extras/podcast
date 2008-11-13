<?php
/**
	* PodcastHandler class
	* Produces podcast specific RSS2 and Atom feeds
	*
	* @package Habari
	*
	* @version $Id$
	* @copyright 2008
 */

class PodcastHandler extends ActionHandler
{

	private $current_url = '';

	/**
	* Respond to requests for podcast feeds
	*
	*/
	public function act_podcast()
	{
		// Expecting: entire_match, name, feed_type in handler_vars
		$this->current_url = Site::get_url( 'habari' ) . '/' . $this->handler_vars['entire_match'];

		switch( $this->handler_vars['feed_type'] ) {
			case 'rss':
				$this->produce_rss( $this->handler_vars['name'] );
				break;
			case 'atom':
				$this->produce_atom( $this->handler_vars['name'] );
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
		$params = array();
		$params['status'] = Post::status( 'published' );
		$params['content_type'] = Post::type( 'podcast' );
		$params['limit'] = Options::get( 'atom_entries' );
		$params['where'] = "{posts}.id IN (SELECT post_id FROM {postinfo} WHERE name = '{$feed_name}')";
		$posts= Posts::get( $params );
		$xml = $this->add_posts( $xml, $posts, $feed_name );
		Plugins::act( 'podcast_rss_collection', $xml, $posts, $feed_name );
		ob_clean();
		header( 'Content-Type: application/xml' );
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
		$title = $channel->addChild( 'title', Options::get( 'title' ) );
		$link = $channel->addChild( 'link', Site::get_url( 'habari' ) );
		$atom_link = $channel->addChild( 'xmlns:atom:link' );
		$atom_link->addAttribute( 'href', $this->current_url );
		$atom_link->addAttribute( 'rel', 'self' );
		$atom_link->addAttribute( 'type', 'application/rss+xml' );
		$lang = $channel->addChild( 'language', strlen( Options::get( 'locale' ) ) ? Options::get( 'locale' ) : 'en-us' );
		if ( $tagline= Options::get( 'tagline' ) ) {
			$description= $channel->addChild( 'description', $tagline );
		}
		$max_time = DB::get_value( "SELECT MAX(pubdate) FROM {posts} WHERE status = ? AND content_type = ? AND id in ( SELECT post_id from {postinfo} where name = ? )", array( Post::status( 'published' ), Post::type( 'podcast' ), $feed_name ) );
		$pubDate= $channel->addChild( 'lastBuildDate', HabariDateTime::date_create( $max_time )->get( 'r' ) );
		$generator= $channel->addChild( 'generator', 'Habari ' . Version::get_habariversion() . ' http://habariproject.org/' );

		$itunes = Options::get( "podcast__{$feed_name}_itunes" );

		$itunes_author = $channel->addChild( 'xmlns:itunes:author', $itunes['author'] );
		$itunes_subtitle = $channel->addChild( 'xmlns:itunes:subtitle', $itunes['subtitle'] );
		$itunes_summary = $channel->addChild( 'xmlns:itunes:summary', $itunes['summary'] );
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
	 *
	 * @TODO replace post podcast markers with a url
	 */
	public function add_posts( $xml, $posts, $feed_name )
	{
		$items = $xml->channel;
		foreach ( $posts as $post ) {
			if ( $post instanceof Post ) {
				// remove Podpress detritus
				$content = preg_replace( '%\[display_podcast\]%', '', $post->content );
				$item = $items->addChild( 'item' );
				$title = $item->addChild( 'title', $post->title );
				$link = $item->addChild( 'link', $post->permalink );
				$description = $item->addChild( 'description', Format::autop( $content ) );
				$pubdate = $item->addChild ( 'pubDate', $post->pubdate->format( 'r' ) );
				$guid = $item->addChild( 'guid', $post->guid );
				$guid->addAttribute( 'isPermaLink', 'false' );

				extract( (array)$post->info->$feed_name );
				$itunes_enclosure = $item->addChild( 'enclosure' );
				$itunes_enclosure->addAttribute( 'url', $enclosure );
				$itunes_enclosure->addAttribute( 'length', $size );
				$itunes_enclosure->addAttribute( 'type', 'audio/mpeg' );

				$itunes_author = $item->addChild( 'xmlns:itunes:author', $post->author->displayname );
				$itunes_subtitle = $item->addChild( 'xmlns:itunes:subtitle', $subtitle );
				$itunes_summary = $item->addChild( 'xmlns:itunes:summary', $summary );
				$itunes_duration = $item->addChild( 'xmlns:itunes:duration', $duration );
				$itunes_explicit = $item->addChild( 'xmlns:itunes:explicit', $rating );
				if( count( $post->tags ) ) {
					$itunes_keywords = $item->addChild( 'xmlns:itunes:keywords', implode( ', ', $post->tags ) );
				}
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
	public function produce_atom( $feed_name )
	{
		echo <<< ATOM
Atom goes here.
ATOM;
	}

}
?>
