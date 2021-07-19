<?php

/**
 * Legacy funnel class.
 */
namespace WP_Funnel_Manager;

class Legacy_Funnel_Type
{
	protected $slug;
	protected $interior_args;
	protected $exterior_args;
	
	public function __construct()
	{
		$this->slug = 'funnel';

		$this->exterior_args = array(
			'label' => __( 'Funnels', 'wpfunnel' ),
			'labels' => array(
				'name' => __( 'Funnels', 'wpfunnel' ),
				'singular_name' => __( 'Funnel', 'wpfunnel' )
			),
			'public' => true,
			'hierarchical' => true,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'capability_type' => 'post',
			'map_meta_cap' => true,
			'supports' => array( 'title', 'editor', 'comments', 'revisions', 'author', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields' ),
			'has_archive' => false,
			'query_var' => true
		);

		$this->interior_args = array(
			'label' => __( 'Interiors', 'wpfunnel' ),
			'labels' => array(
				'name' => __( 'Interiors', 'wpfunnel' ),
				'singular_name' => __( 'Interior', 'wpfunnel' )
			),
			'public' => true,
			'hierarchical' => is_admin(),
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => false,
			'show_in_rest' => true,
			'capability_type' => 'post',
			'map_meta_cap' => true,
			'supports' => array( 'title', 'editor', 'comments', 'revisions', 'author', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields' ),
			'has_archive' => false,
			'query_var' => true
		);
	}

	public function register()
	{
		add_filter( 'admin_url', array( $this, 'new_interior' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'post_parent_query_var' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'funnel_interior_permalink' ), 10, 2 );
		add_filter( 'quick_edit_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'setup_interior' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_exterior_promote_interior' ) );
	}
	
	/**
	 * Validates whether the supplied post ID is a valid exterior
	 * for this funnel type, that the current user can edit.
	 * 
	 * Returns the post if valid, or false if no valid post.
	 *
	 * @since 1.2.0
	 */
	private function validate_post_parent( $post_parent_id )
	{	
		if ( !( $post = get_post( $post_parent_id ) ) )
			return false;

		if ( $post->post_type != $this->slug )
			return false;

		if ( $post->post_status == 'trash' || $post->post_status == 'auto-draft' )
			return false;

		if ( !current_user_can( 'edit_post', $post ) )
			return false;

		return $post;
	}

	/**
	 * Pass the post parent GET variable from edit.php to post-new.php
	 * Hooked to admin_url filter
	 *
	 * @since 1.1.0
	 */
	public function new_interior( $url, $path )
	{
		// Validate path
		if ( strpos( $path, 'post-new.php?post_type=' . $this->slug . '_int' ) !== 0 )
			return $url;

		// Validate post type
		if ( $this->slug . '_int' != get_query_var( 'post_type' ) )
			return $url;

		$post_parent = get_query_var( 'post_parent' );

		// Validate post parent
		if ( !$this->validate_post_parent( $post_parent ) )
			return $url;

		return esc_url( $url . '&post_parent=' . $post_parent );
	}

	/**
	 * Allow post parent as query var
	 * This is needed to view funnel interiors
	 *
	 * @since 1.0.3
	 */
	public static function post_parent_query_var()
	{
		if ( !is_admin() )
			return;

		$GLOBALS['wp']->add_query_var( 'post_parent' );
	}

	public function register_taxonomies()
	{
		register_post_type( $this->slug, $this->exterior_args );
		register_post_type( $this->slug . '_int', $this->interior_args );
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

	/**
	 * Add a link to view and edit funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function funnel_interior_edit( $actions, $post ) {
		if ( $this->slug != $post->post_type )
			return $actions;

		$url = admin_url( 'edit.php?post_type=' . $this->slug . '_int&post_parent=' . $post->ID );
		$actions['edit_interiors'] = '<a href="' . esc_url( $url ) . '">' . __( 'Edit Steps', 'wpfunnel' ) . '</a>';

		return $actions;
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

	public function setup_interior( $data )
	{
		if ( $data['post_type'] != $this->slug . '_int' )
		{
			return $data;
		}

		if ( empty( $data['post_parent'] ) && !empty( $_GET['post_parent'] ) )
		{
			$data['post_parent'] = intval( $_GET['post_parent'] );
		}

		if ( !( $exterior = $this->validate_post_parent( $data['post_parent'] ) ) )
		{
			wp_die( 'Funnel Interiors must be assigned to a Funnel. Please try again.' );
		}

		$data = $this->child_setup_interior( $data, $exterior );
		
		return $data;
	}

	protected function child_setup_interior( $data, $exterior )
	{
		return $data;
	}

	public function trash_exterior_promote_interior( $post_id )
	{
		if ( !( $exterior = get_post( $post_id ) ) || $this->slug != $exterior->post_type )
			return;

		$interiors = new \WP_Query( 'posts_per_page=-1&orderby=menu_order&order=ASC&post_status=any,trash,auto-draft&post_type=' . $this->slug . '_int&post_parent=' . $post_id );
		$promoted_id = 0;

		foreach ( $interiors->posts as $interior )
		{
			if ( $interior->post_status != 'trash' && $interior->post_status != 'auto-draft' )
			{
				wp_update_post( array( 'ID' => $interior->ID, 'post_type' => $this->slug ) );
				wp_update_post( array( 'ID' => $interior->ID, 'menu_order' => 0 ) );
				wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => 0 ) );

				$promoted_id = $interior->ID;
				break;
			}
		}

		// If nothing promoted, promote everything
		if ( empty( $promoted_id ) )
		{
			foreach ( $interiors->posts as $interior )
			{
				wp_update_post( array( 'ID' => $interior->ID, 'post_type' => $this->slug ) );
				wp_update_post( array( 'ID' => $interior->ID, 'menu_order' => 0 ) );
				wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => 0 ) );
			}
		}
		else
		{
			foreach ( $interiors->posts as $interior )
			{
				if ( $interior->ID === $promoted_id )
					continue;

				wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => $promoted_id ) );
			}
		}
	}
}
