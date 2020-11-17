<?php

/**
 * Legacy funnel class.
 */
namespace WP_Funnel_Manager;

class Legacy_Funnel_Type extends Funnel_Type
{
	public function __construct()
	{
		parent::__construct( 'funnel', null );
	}

	public function register_taxonomies()
	{
		// Only register as hierarchical in admin
		$hierarchical = is_admin();
			
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
	}

	// Legacy funnel type doesn't have an owner
	public function user_is_owner( $user )
	{
		return false;
	}

	// Legacy funnel type can't register roles because it borrows post capabilities
	public function add_role( $roles )
	{
		return;
	}
}
