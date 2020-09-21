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
			"capability_type" => array( $this->slug, $this->slug . '_all' ),
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
			"capability_type" => array( $this->slug, $this->slug . '_all' ),
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => $this->slug, "with_front" => false ),
			"query_var" => true,
			"supports" => array( "title", "editor", "thumbnail", "comments", "revisions", "author", "page-attributes" ),
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
}
