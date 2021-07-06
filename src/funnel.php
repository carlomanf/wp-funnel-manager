<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel_Type
{
	private $slug;
	private $template;
	protected $editor_role;
	protected $author_role;
	protected $contributor_role;
	protected $interior_args;
	protected $exterior_args;

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
		$this->exterior_args = array(
			'label' => $this->template->post_title,
			'public' => true,
			'hierarchical' => false,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => $GLOBALS['wpfunnel']->is_legacy() ? 'edit.php?post_type=funnel' : 'post-new.php?post_type=wp_template&wpfunnel=1',
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'capability_type' => array( $this->slug, $this->slug . '_any' ),
			'map_meta_cap' => true,
			'supports' => array( 'title', 'editor', 'comments', 'revisions', 'author', 'excerpt', 'thumbnail', 'custom-fields' ),
			'has_archive' => false,
			'query_var' => true
		);

		$this->interior_args = array(
			'label' => $this->template->post_title . ' ' . __( 'Interiors', 'wpfunnel' ),
			'labels' => array(
				'name' => $this->template->post_title . ' ' . __( 'Interiors', 'wpfunnel' ),
				'singular_name' => $this->template->post_title . ' ' . __( 'Interior', 'wpfunnel' )
			),
			'public' => true,
			'hierarchical' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest' => true,
			'capability_type' => array( $this->slug, $this->slug . '_any' ),
			'map_meta_cap' => true,
			'supports' => array( 'title', 'editor', 'comments', 'revisions', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields' ),
			'has_archive' => false,
			'query_var' => true
		);

		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'map_meta_cap', array( $this, 'assign_editor_to_owner' ), 10, 4 );
		add_action( 'wp_roles_init', array( $this, 'add_role' ) );
		add_filter( 'editable_roles', array( $this, 'make_role_editable' ) );
		add_filter( 'post_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'post_parent_query_var' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'setup_interior' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'validate_template_slug' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_post_author' ), 10, 2 );
		add_filter( 'save_post_wp_template', array( __CLASS__, 'declare_template' ) );
		add_filter( 'admin_url', array( $this, 'new_interior' ), 10, 2 );
		add_action( 'wp_trash_post', array( $this, 'trash_exterior_promote_interior' ) );
		add_filter( 'single_template_hierarchy', array( $this, 'apply_template_to_interior' ) );
		add_filter( 'pre_get_posts', array( __CLASS__, 'enable_universal_template' ) );
		add_filter( 'get_the_terms', array( __CLASS__, 'localise_universal_template' ), 10, 3 );
	}

	public static function declare_template( $post_id )
	{
		if ( isset( $_GET['wpfunnel'] ) )
		{
			update_post_meta( $post_id, 'wpfunnel', '1' );
		}
	}

	public static function validate_template_slug( $data, $postarr )
	{
		if ( 'wp_template' === $data['post_type'] )
		{
			if ( isset( $_GET['wpfunnel'] ) || !empty( get_post_meta( $postarr['ID'], 'wpfunnel' ) ) )
			{
				if ( empty( $data['post_name'] ) )
				{
					$data['post_name'] = uniqid();
				}

				if ( strpos( $data['post_name'], 'single-' ) !== 0 )
				{
					$data['post_name'] = 'single-' . $data['post_name'];
				}
			}
		}

		return $data;
	}

	public function get_all_funnels()
	{
		$exteriors = new \WP_Query( 'post_type=' . $this->slug );
		$funnels = array();

		foreach ( $exteriors->posts as $exterior )
		{
			$steps = array( $exterior );
			$interiors = new \WP_Query( 'post_type=' . $this->slug . '_int&post_parent=' . $exterior->ID );

			foreach ( $interiors->posts as $interior )
			{
				$steps[] = $interior;
			}

			$funnels[] = new Funnel( $exterior->ID, $steps );
		}

		return $funnels;
	}

	public function register_taxonomies()
	{
		register_post_type( $this->slug, $this->exterior_args );
		register_post_type( $this->slug . '_int', $this->interior_args );
	}

	public function apply_template_to_interior( $templates )
	{
		if ( in_array( 'single-' . $this->slug . '_int.php', $templates ) )
		array_splice( $templates, -1, 0, 'single-' . $this->slug . '.php' );
	
		return $templates;
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
	public function assign_editor_to_owner( $caps, $cap, $user, $args )
	{
		foreach ( $caps as &$capability )
		{
			if ( !in_array( $capability, array_keys( $this->editor_role['capabilities'] ) ) )
			continue;

			if ( !$this->user_is_owner( $user ) )
			continue;

			$capability = 'exist';
		}

		return $caps;
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
		if ( $this->slug === $post->post_type )
		{
			$interiors = new \WP_Query( 'posts_per_page=-1&post_status=any,trash,auto-draft&post_type=' . $this->slug . '_int&post_parent=' . $post_id );
		
			foreach ( $interiors->posts as $interior )
			{
				wp_update_post( array( 'ID' => $interior->ID, 'post_author' => $post->post_author ) );
			}
		}
	}

	public static function localise_universal_template( $terms, $id, $taxonomy )
	{
		if ( empty( $terms ) && 'wp_theme' == $taxonomy )
		{
			$term = new \stdClass();
			$term->name = wp_get_theme()->get_stylesheet();
			$term->slug = wp_get_theme()->get_stylesheet();
			$term->taxonomy = 'wp_theme';
			$terms[] = new \WP_Term( $term );
		}

		return $terms;
	}

	public static function enable_universal_template( $query )
	{
		if ( $query->get( 'post_type' ) === 'wp_template' )
		{
			$tax_query = $query->get( 'tax_query' );
			if ( !empty( $tax_query ) && 'wp_theme' === $tax_query[0]['taxonomy'] )
			{
				$tax_query['relation'] = 'OR';
				$tax_query[] = array(
					'taxonomy' => 'wp_theme',
					'operator' => 'NOT EXISTS'
				);

				$query->set( 'tax_query', $tax_query );
			}
		}

		return $query;
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
