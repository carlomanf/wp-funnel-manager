<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel
{
	public function __construct()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_taxonomies()
	{
		if ( defined( 'WP_ADMIN' ) && WP_ADMIN )
			$hierarchical = true;
		else
			$hierarchical = false;

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
			"hierarchical" => $hierarchical,
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

		foreach( get_posts( 'post_type=wp_template' ) as $post )
		{
			if ( strpos( $post->post_name, 'single-' ) === 0 )
			{
				$slug = str_replace( 'single-', '', $post->post_name );
				$args = array(
					"label" => $slug,
					"labels" => array(
						"name" => $slug,
						"singular_name" => $slug,
					),
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
					"capability_type" => $slug,
					"map_meta_cap" => true,
					"hierarchical" => false,
					"rewrite" => array( "slug" => $slug, "with_front" => false ),
					"query_var" => true,
					"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
				);
		
				register_post_type( $slug, $args );
			}

		}


	}
}
