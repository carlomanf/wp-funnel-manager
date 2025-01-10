<?php

/**
 * Natural funnel class.
 */
namespace WP_Funnel_Manager;

class Natural_Funnel_Type extends Dynamic_Funnel_Type
{
	private $added_wp_link_pages = array();

	public function __construct()
	{
		parent::__construct( 'wpfunnel_head', 0, '', '', 0 );

		$this->interior_slug = 'wpfunnel_step';

		$this->exterior_args['rewrite'] = array( 'slug' => 'funnel' );

		$this->exterior_args['label'] = __( 'Funnels', 'wpfunnel' );
		$this->exterior_args['labels'] = array(
			'name' => __( 'Funnels', 'wpfunnel' ),
			'singular_name' => __( 'Funnel', 'wpfunnel' )
		);

		$this->interior_args['label'] = __( 'Funnel Steps', 'wpfunnel' );
		$this->interior_args['labels'] = array(
			'name' => __( 'Funnel Steps', 'wpfunnel' ),
			'singular_name' => __( 'Funnel Step', 'wpfunnel' )
		);

		$this->exterior_args['capabilities'] = array(
			'create_posts' => 'author_funnels',
			'delete_posts' => 'contribute_funnels',
			'delete_others_posts' => 'edit_funnels',
			'delete_private_posts' => 'edit_funnels',
			'delete_published_posts' => 'author_funnels',
			'edit_posts' => 'contribute_funnels',
			'edit_others_posts' => 'edit_funnels',
			'edit_private_posts' => 'edit_funnels',
			'edit_published_posts' => 'author_funnels',
			'publish_posts' => 'author_funnels',
			'read' => 'author_funnels',
			'read_private_posts' => 'edit_funnels'
		);

		$this->interior_args['publicly_queryable'] = false;
		$this->interior_args['capabilities'] = $this->exterior_args['capabilities'];
	}

	public function register()
	{
		parent::register();

		remove_filter( 'editable_roles', array( $this, 'make_role_editable' ) );
		remove_filter( 'map_meta_cap', array( $this, 'assign_editor_to_owner' ), 10, 4 );
		remove_filter( 'single_template_hierarchy', array( $this, 'apply_template_to_interior' ) );
		remove_action( 'wp_roles_init', array( $this, 'add_role' ) );
		remove_action( 'init', array( $this, 'assign_menu' ), 9 );
		remove_action( 'wp_trash_post', array( $this, 'trash_exterior_promote_interior' ) );

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
