<?php

/**
 * Habari Podcast Plugin
 *
 */

class Podcast extends Plugin
{
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
			'version' => '0.1',
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

	/**
	* Add the admin template for publishing podcasts
	*/
	function action_init()
	{
		$this->add_template('podcast', dirname(__FILE__) . '/podcast.php');
	}

	/**
	* For podcasts, use the podcast publishing template
	*/
	function action_admin_theme_get_publish( $handler, $theme )
	{
		if( $handler->handler_vars['content_type'] == 'podcast' ) {
			$handler->fetch( 'podcast' );
		}
	}

	/**
	* Add podcast information to an Atom feed entry
	*/
	function act_atom_get_collection_entry( $xml, $post, $handler_vars )
	{
		if ( $post->content_type == Post::type('podcast') ) {
			//$xml add some stuff from $post->info->enclosure_*
		}
	}

	/**
	* Add podcast information to a podcast post a from POST'd Atom feed entry
	*/
	function act_atom_post_collection( $xml, $post, $handler_vars )
	{
		if ( $post->content_type == Post::type('podcast') ) {
			//$post->info->enclosure_* = stuff from $xml
		}
	}

	/**
	* Update podcast information of a podcast post from a PUT'd Atom feed entry
	*/
	function act_atom_put_entry( $xml, $post, $handler_vars )
	{
		if ( $post->content_type == Post::type('podcast') ) {
			//$post->info->enclosure_* = stuff from $xml
		}
	}

	/**
	* Add podcast information to an RSS feed entry
	*/
	function act_rss_add_post( $item, $post )
	{
		if ( $post->content_type == Post::type('podcast') ) {
			//$xml add some stuff from $post->info->enclosure_*
		}
	}

}

?>
