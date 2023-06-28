<?php

/**
 * Natural funnel class.
 */
namespace WP_Funnel_Manager;

class Natural_Funnel_Type extends Dynamic_Funnel_Type
{
	private $added_wp_link_pages = array();

	public function __construct( $slug, $wp_id, $title, $blocks, $author )
	{
		parent::__construct( $slug, $wp_id, $title, $blocks, $author );
	}

	public function register()
	{
		parent::register();

		// Workaround for https://github.com/WordPress/gutenberg/issues/29484
		add_filter( 'the_content', array( $this, 'add_wp_link_pages' ), 0 );
		add_filter( 'wp_link_pages_args', array( $this, 'added_wp_link_pages' ) );
	}

	public function added_wp_link_pages( $parsed_args )
	{
		$id = get_the_ID();
		( get_post_type() !== $this->slug || isset( $this->added_wp_link_pages[ $id ] ) ) or $this->added_wp_link_pages[ $id ] = true;
		return $parsed_args;
	}

	public function add_wp_link_pages( $content )
	{
		$id = get_the_ID();

		if ( get_post_type() === $this->slug && $GLOBALS['multipage'] && !isset( $this->added_wp_link_pages[ $id ] ) )
		{
			$this->added_wp_link_pages[ $id ] = false;
			return $content . wp_link_pages( array( 'echo' => 0 ) );
		}

		return $content;
	}
}
