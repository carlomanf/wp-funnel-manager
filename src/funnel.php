<?php

/**
 * Main funnel class.
 */
class Funnel
{
	public function __construct()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_taxonomies()
	{
		register_taxonomy( 'funnel', array( 'wp_block', 'page' ), array(
			'hierarchical' => true,
			'labels'       => array(
				'name'          => __( 'Funnels', 'wpfunnel' ),
				'singular_name' => __( 'Funnel', 'wpfunnel' ),
			),
			'rewrite'        => array(
				'slug'         => 'funnel',
				'hierarchical' => true
			)
		) );

		/**
		 * Post Type: Funnel Interiors.
		 * Temporary until funnel infrastructure is ready
		 */

		$labels = array(
			"name" => __( "Interiors", "wpfunnel" ),
			"singular_name" => __( "Interior", "wpfunnel" ),
		);

		$args = array(
			"label" => __( "Interiors", "wpfunnel" ),
			"labels" => $labels,
			"description" => "",
			"public" => true,
			"publicly_queryable" => true,
			"show_ui" => true,
			"show_in_rest" => true,
			"rest_base" => "",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => true,
			"rewrite" => array( "slug" => "funnel_int", "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
		);

		register_post_type( "funnel_int", $args );

		/**
		 * Post Type: Funnel Exteriors.
		 * Temporary until funnel infrastructure is ready
		 */

		$labels = array(
			"name" => __( "Funnels", "wpfunnel" ),
			"singular_name" => __( "Funnel", "wpfunnel" ),
		);

		$args = array(
			"label" => __( "Funnels", "wpfunnel" ),
			"labels" => $labels,
			"description" => "",
			"public" => true,
			"publicly_queryable" => true,
			"show_ui" => true,
			"show_in_rest" => true,
			"rest_base" => "",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => true,
			"rewrite" => array( "slug" => "funnel", "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
		);

		register_post_type( "funnel", $args );


	}
}
