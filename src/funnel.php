<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel_Type
{
	private $slug;

	public function __construct( $slug )
	{
		$this->slug = $slug;
	}

	public function register()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function get_all_funnels()
	{
		$exteriors = get_posts( 'post_type=' . $this->slug );
		$funnels = array();

		foreach ( $exteriors as $exterior )
		{
			$steps = array( $exterior );
			$interiors = get_posts( 'post_type=' . $this->slug . '_int&post_parent=' . $exterior->ID );

			foreach ( $interiors as $interior )
			{
				$steps[] = $interior;
			}

			$funnels[] = new Funnel( $exterior->ID, $steps );
		}

		return $funnels;
	}

	public function register_taxonomies()
	{
		if ( defined( 'WP_ADMIN' ) && WP_ADMIN )
			$hierarchical = true;
		else
			$hierarchical = false;
			
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
			"capability_type" => $this->slug,
			"map_meta_cap" => true,
			"hierarchical" => $hierarchical,
			"rewrite" => array( "slug" => $this->slug . "_int", "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
		);

		register_post_type( $this->slug . "_int", $args );

		$args = array(
			"label" => $this->slug,
			"labels" => array(
				"name" => $this->slug,
				"singular_name" => $this->slug,
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
			"capability_type" => $this->slug,
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => $this->slug, "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
		);

		register_post_type( $this->slug, $args );
	}
}
