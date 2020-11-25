<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel_Type
{
	private $slug;
	private $template;
	private $editor_role;
	private $author_role;
	private $contributor_role;

	public function __construct( $slug, $template )
	{
		$this->slug = $slug;
		$this->template = $template;

		$this->editor_role = array(
			'name' => 'Editor for ' . $this->slug,
			'capabilities' => array(
				'read' => true,
				'upload_files' => true,
				'delete_' . $this->slug . '_any' => true,
				'delete_others_' . $this->slug . '_any' => true,
				'delete_private_' . $this->slug . '_any' => true,
				'delete_published_' . $this->slug . '_any' => true,
				'edit_' . $this->slug . '_any' => true,
				'edit_others_' . $this->slug . '_any' => true,
				'edit_private_' . $this->slug . '_any' => true,
				'edit_published_' . $this->slug . '_any' => true,
				'publish_' . $this->slug . '_any' => true,
				'read_private_' . $this->slug . '_any' => true
			)
		);

		$this->author_role = array(
			'name' => 'Author for ' . $this->slug,
			'capabilities' => array(
				'read' => true,
				'upload_files' => true,
				'delete_' . $this->slug . '_any' => true,
				'delete_published_' . $this->slug . '_any' => true,
				'edit_' . $this->slug . '_any' => true,
				'edit_published_' . $this->slug . '_any' => true,
				'publish_' . $this->slug . '_any' => true
			)
		);

		$this->contributor_role = array(
			'name' => 'Contributor for ' . $this->slug,
			'capabilities' => array(
				'read' => true,
				'delete_' . $this->slug . '_any' => true,
				'edit_' . $this->slug . '_any' => true
			)
		);
	}

	public function register()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'map_meta_cap', array( $this, 'assign_admin' ), 10, 4 );
		add_action( 'wp_roles_init', array( $this, 'add_role' ) );
		add_filter( 'editable_roles', array( $this, 'make_role_editable' ) );
		add_filter( 'post_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'post_parent_query_var' ) );
		add_action( 'admin_menu', array( $this, 'remove_interiors' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'setup_interior' ) );
		add_action( 'save_post', array( $this, 'update_post_author' ), 10, 2 );
		add_filter( 'admin_url', array( $this, 'new_interior' ), 10, 2 );
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
			"capability_type" => array( $this->slug, $this->slug . '_any' ),
			"map_meta_cap" => true,
			"hierarchical" => false,
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
			"capability_type" => array( $this->slug, $this->slug . '_any' ),
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => $this->slug, "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author" ),
			"menu_icon" => "dashicons-filter"
		);

		register_post_type( $this->slug, $args );
	}

	/**
	 * Determine whether a user is the owner of this funnel type
	 * They are an owner if they can edit the original template
	 *
	 * @since 1.2.0
	 */
	public function user_is_owner( $user )
	{
		if ( !post_type_exists( $this->template->post_type ) )
		return false;

		return user_can( $user, 'edit_post', $this->template );
	}

	/**
	 * Automatically assign the editor role to the owner(s) of the funnel type
	 * Hooked to map_meta_cap filter
	 *
	 * @since 1.2.0
	 */
	public function assign_admin( $caps, $cap, $user, $args )
	{
		if ( !in_array( $cap, array_keys( $this->editor_role['capabilities'] ) ) )
		return $caps;

		if ( !$this->user_is_owner( $user ) )
		return $caps;

		return array( 'exist' );
	}

	// Register a role for this funnel type
	public function add_role( $roles )
	{
		$slug = $this->slug . '_editor';

		$roles->role_objects[ $slug ] = new \WP_Role( $slug, $this->editor_role['capabilities'] );
		$roles->role_names[ $slug ] = $this->editor_role['name'];

		$slug = $this->slug . '_author';

		$roles->role_objects[ $slug ] = new \WP_Role( $slug, $this->author_role['capabilities'] );
		$roles->role_names[ $slug ] = $this->author_role['name'];

		$slug = $this->slug . '_contributor';

		$roles->role_objects[ $slug ] = new \WP_Role( $slug, $this->contributor_role['capabilities'] );
		$roles->role_names[ $slug ] = $this->contributor_role['name'];
	}

	public function make_role_editable( $roles )
	{
		if ( $this->user_is_owner( wp_get_current_user() ) )
		{
			$roles[ $this->slug . '_editor' ] = $this->editor_role;
			$roles[ $this->slug . '_author' ] = $this->author_role;
			$roles[ $this->slug . '_contributor' ] = $this->contributor_role;
		}

		return $roles;
	}

	public function remove_interiors()
	{
		remove_menu_page('edit.php?post_type=' . $this->slug . '_int');
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
	 * Prevent interiors being saved without a valid exterior
	 * and align the post author with the exterior
	 * Hooked to wp_insert_post_data filter
	 *
	 * @since 1.2.0
	 */
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

		$data['post_author'] = $exterior->post_author;
		
		return $data;
	}

	public function update_post_author( $post_id, $post )
	{
		if ( $this->slug != $post->post_type )
			return;

		$interiors = get_posts( 'numberposts=-1&post_status=any,trash,auto-draft&post_type=' . $this->slug . '_int&post_parent=' . $post_id );
		
		foreach ( $interiors as $interior )
		{
			wp_update_post( array( 'ID' => $interior->ID, 'post_author' => $post->post_author ) );
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

		// If nothing promoted, promote everything
		if ( empty( $promoted_id ) )
		{
			foreach ( $interiors as $interior )
			{
				wp_update_post( array( 'ID' => $interior->ID, 'post_type' => $this->slug ) );
				wp_update_post( array( 'ID' => $interior->ID, 'menu_order' => 0 ) );
				wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => 0 ) );
			}
		}
		else
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
