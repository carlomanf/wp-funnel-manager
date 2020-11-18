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

	public function register()
	{
		parent::register();

		add_filter( 'post_type_link', array( $this, 'funnel_interior_permalink' ), 10, 2 );
		add_filter( 'quick_edit_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		
		remove_filter( 'post_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
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

	/**
	 * Correct the funnel interior permalink
	 *
	 * @since 1.0.4
	 */
	public function funnel_interior_permalink( $permalink, $post )
	{
		if ( 'funnel_int' !== $post->post_type )
			return $permalink;

		$replace = '';
		foreach ( $post->ancestors as $ancestor )
		{
			$replace = '/' . get_post( $ancestor )->post_name . $replace;
		}
		$replace .= '/';

		$permalink = str_replace( $replace, '/', $permalink );
		return $permalink;
	}

	/**
	 * Correct the post parent selector for funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function funnel_post_parent( $args )
	{
		global $post;
		if ( 'funnel_int' !== $post->post_type )
			return $args;

		$args[ 'post_type' ] = 'funnel';
		return $args;
	}

	public function setup_interior( $data )
	{
		$new_data = parent::setup_interior( $data );

		// Don't replace the post author
		$new_data['post_author'] = $data['post_author'];

		return $new_data;
	}

	public function update_post_author( $post_id, $post )
	{
		return;
	}
}
