<?php

/**
 * Main funnel class.
 */
namespace WP_Funnel_Manager;

class Funnel_Type extends Legacy_Funnel_Type
{
	private $template;
	private $editor_role;
	private $author_role;
	private $contributor_role;

	public function __construct( $slug, $template )
	{
		parent::__construct();

		$this->slug = $slug;
		$this->template = $template;

		$this->exterior_args['label'] = $this->template->post_title;

		unset( $this->exterior_args['labels'] );

		$this->exterior_args['hierarchical'] = false;
		$this->exterior_args['show_in_menu'] = $GLOBALS['wpfunnel']->is_legacy() ? 'edit.php?post_type=funnel' : 'wpfunnel';
		$this->exterior_args['capability_type'] = array( $this->slug, $this->slug . '_any' );
		$this->exterior_args['supports'] = array_diff( $this->exterior_args['supports'], array( 'page-attributes' ) );

		$this->interior_args['label'] = $this->template->post_title . ' ' . __( 'Interiors', 'wpfunnel' );

		$this->interior_args['labels'] = array(
			'name' => $this->template->post_title . ' ' . __( 'Interiors', 'wpfunnel' ),
			'singular_name' => $this->template->post_title . ' ' . __( 'Interior', 'wpfunnel' )
		);

		$this->interior_args['hierarchical'] = false;
		$this->interior_args['show_in_nav_menus'] = false;
		$this->interior_args['capability_type'] = array( $this->slug, $this->slug . '_any' );
		$this->interior_args['supports'] = array_diff( $this->interior_args['supports'], array( 'author' ) );

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
		parent::register();

		add_filter( 'editable_roles', array( $this, 'make_role_editable' ) );
		add_filter( 'get_the_terms', array( __CLASS__, 'localise_universal_template' ), 10, 3 );
		add_filter( 'map_meta_cap', array( $this, 'assign_editor_to_owner' ), 10, 4 );
		add_filter( 'pre_get_posts', array( __CLASS__, 'enable_universal_template' ) );
		add_action( 'save_post', array( $this, 'update_post_author' ), 10, 2 );
		add_filter( 'single_template_hierarchy', array( $this, 'apply_template_to_interior' ) );
		add_action( 'wp_roles_init', array( $this, 'add_role' ) );
		add_filter( 'after_setup_theme', array( __CLASS__, 'regenerate_roles' ), 11 );

		remove_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
	}

	public static function regenerate_roles()
	{
		$roles = wp_roles();
		$roles->for_site( $roles->get_site_id() );
	}

	public function funnel_interior_permalink( $permalink, $post )
	{
		return $permalink;
	}
	
	public function funnel_post_parent( $args )
	{
		return $args;
	}

	/**
	 * Prevent interiors being saved without a valid exterior
	 * and align the post author with the exterior
	 * Hooked to wp_insert_post_data filter
	 *
	 * @since 1.2.0
	 */
	protected function child_setup_interior( $data, $exterior )
	{
		$data['post_author'] = $exterior->post_author;
		
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

	public function apply_template_to_interior( $templates )
	{
		if ( in_array( 'single-' . $this->slug . '_int.php', $templates ) )
		array_splice( $templates, -1, 0, 'single-' . $this->slug . '.php' );
	
		return $templates;
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
}
