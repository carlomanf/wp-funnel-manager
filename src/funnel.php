<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel_Type
{
	private $slug;
	private $template;

	public function __construct( $slug, $template )
	{
		$this->slug = $slug;
		$this->template = $template;
	}

	public function register()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'user_has_cap', array( $this, 'assign_admin' ), 10, 4 );
		add_action( 'wp_roles_init', array( $this, 'add_role' ) );
		add_filter( 'quick_edit_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'funnel_interior_permalink' ), 10, 2 );
		add_action( 'init', array( $this, 'post_parent_query_var' ) );
		add_action( 'admin_menu', array( $this, 'remove_interiors' ) );
		add_action( 'pre_post_update', array( $this, 'interior_without_parent' ), 10, 2 );
		add_filter( 'admin_url', array( $this, 'new_interior' ), 10, 2 );
		add_filter( 'wp_insert_post_parent', array( $this, 'set_post_parent' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_exterior_promote_interior' ) );
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
		// Only register as hierarchical in admin
		$hierarchical = is_admin();
			
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
			"capability_type" => array( $this->slug, $this->slug . '_all' ),
			"map_meta_cap" => true,
			"hierarchical" => $hierarchical,
			"rewrite" => array( "slug" => $this->slug . "_int", "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "page-attributes" ),
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
			"capability_type" => array( $this->slug, $this->slug . '_all' ),
			"map_meta_cap" => true,
			"hierarchical" => $hierarchical,
			"rewrite" => array( "slug" => $this->slug, "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author" ),
		);

		register_post_type( $this->slug, $args );
	}

	// Automatically assign the role to the author of the template
	public function assign_admin( $allcaps, $caps, $args, $user )
	{
		if ( $user->ID == $this->template->post_author )
		{
			$allcaps[ 'delete_' . $this->slug . '_all' ] = true;
			$allcaps[ 'delete_others_' . $this->slug . '_all' ] = true;
			$allcaps[ 'delete_private_' . $this->slug . '_all' ] = true;
			$allcaps[ 'delete_published_' . $this->slug . '_all' ] = true;
			$allcaps[ 'edit_' . $this->slug . '_all' ] = true;
			$allcaps[ 'edit_others_' . $this->slug . '_all' ] = true;
			$allcaps[ 'edit_private_' . $this->slug . '_all' ] = true;
			$allcaps[ 'edit_published_' . $this->slug . '_all' ] = true;
			$allcaps[ 'publish_' . $this->slug . '_all' ] = true;
			$allcaps[ 'read_private_' . $this->slug . '_all' ] = true;
		}

		return $allcaps;
	}

	// Register a role for this funnel type
	public function add_role( $roles )
	{
		$slug = $this->slug . '_admin';
		$name = 'Admin for ' . $this->slug;
		$caps = array();

		$caps[ 'read' ] = true;
		$caps[ 'delete_' . $this->slug . '_all' ] = true;
		$caps[ 'delete_others_' . $this->slug . '_all' ] = true;
		$caps[ 'delete_private_' . $this->slug . '_all' ] = true;
		$caps[ 'delete_published_' . $this->slug . '_all' ] = true;
		$caps[ 'edit_' . $this->slug . '_all' ] = true;
		$caps[ 'edit_others_' . $this->slug . '_all' ] = true;
		$caps[ 'edit_private_' . $this->slug . '_all' ] = true;
		$caps[ 'edit_published_' . $this->slug . '_all' ] = true;
		$caps[ 'publish_' . $this->slug . '_all' ] = true;
		$caps[ 'read_private_' . $this->slug . '_all' ] = true;

		$roles->roles[ $slug ] = array( 'name' => $name, 'capabilities' => $caps );
		$roles->role_objects[ $slug ] = new \WP_Role( $slug, $caps );
		$roles->role_names[ $slug ] = $name;
	}

	public function remove_interiors()
	{
		remove_menu_page('edit.php?post_type=' . $this->slug . '_int');
	}

	/**
	 * Correct the post parent selector for funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function funnel_post_parent( $args )
	{
		global $post;
		if ( $this->slug . '_int' !== $post->post_type )
			return $args;

		$args[ 'post_type' ] = $this->slug;
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
		if ( $this->slug . '_int' !== $post->post_type )
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
	 * Allow post parent as query var
	 * This is needed to view funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function post_parent_query_var()
	{
		if ( !is_admin() )
			return;

		$GLOBALS['wp']->add_query_var( 'post_parent' );
	}
	
	/**
	 * Validates funnel relationship between exterior and interior
	 *
	 * @since 1.2.0
	 */
	private function validate_post_parent( $post_parent_id )
	{	
		// Use get variable as post parent if it's valid
		if ( !( $post = get_post( $post_parent_id ) ) )
			return false;

		if ( $post->post_type != $this->slug )
			return false;

		if ( $post->post_status == 'trash' || $post->post_status == 'auto-draft' )
			return false;

		return true;
	}

	/**
	 * Prevent interiors being saved without an exterior
	 * Hooked to pre_post_update action
	 *
	 * @since 1.1.0
	 */
	public function interior_without_parent( $post_id, $data )
	{
		if ( $data['post_type'] != $this->slug . '_int' )
			return;

		if ( empty( $data['post_parent'] ) || !$this->validate_post_parent( $data['post_parent'] ) )
		{
			wp_die( 'Funnel Interiors must be assigned to a Funnel. Please try again.' );
		}
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
	 * Set the post parent ID upon creation of auto-draft
	 * Hooked to wp_insert_post_parent filter
	 *
	 * @since 1.1.0
	 */
	public function set_post_parent( $post_parent_id )
	{
		// Validate post type
		if ( empty( $_GET['post_type'] ) || $this->slug . '_int' != $_GET['post_type'] )
			return $post_parent_id;

		// Validate post parent
		if ( empty( $_GET['post_parent'] ) || !$this->validate_post_parent( $_GET['post_parent'] ) )
			return $post_parent_id;

		return intval( $_GET['post_parent'] );
	}

	public function trash_exterior_promote_interior( $post_id )
	{
		if ( !( $exterior = get_post( $post_id ) ) || $this->slug != $exterior->post_type )
			return;

		$interiors = get_posts( 'numberposts=-1&orderby=menu_order&order=ASC&post_status=any,trash,auto-draft&post_type=' . $this->slug . '_int&post_parent=' . $post_id );
		$promoted_id = 0;

		foreach ( $interiors as $interior )
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

		if ( !empty( $promoted_id ) )
		{
			foreach ( $interiors as $interior )
			{
				if ( $interior->ID === $promoted_id )
					continue;

				wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => $promoted_id ) );
			}
		}
	}
}
