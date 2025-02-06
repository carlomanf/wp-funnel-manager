<?php

/**
 * Dynamic funnel class.
 */
namespace WP_Funnel_Manager;

class Dynamic_Funnel_Type extends Legacy_Funnel_Type
{
	private $wp_id;
	private $title;
	private $blocks;
	private $author;
	private $editor_role;
	private $author_role;
	private $contributor_role;
	private $is_owner = array();

	/**
	 * If a workaround is needed for core ticket #52043, one funnel type is stored here to be assigned as the parent menu.
	 *
	 * @since 1.4.0
	 */
	private static $type_for_parent_menu = null;

	/**
	 * Returns the type that is assigned to the parent menu, if set.
	 *
	 * @since 1.4.0
	 */
	public static function get_type_for_parent_menu()
	{
		return self::$type_for_parent_menu;
	}

	/**
	 * Getter.
	 *
	 * @since 1.4.0
	 */
	public function __get( $key )
	{
		if ( $key === 'slug' ) return $this->slug;
		if ( $key === 'title' ) return $this->title;
	}

	public function __construct( $slug, $wp_id, $title, $blocks, $author )
	{
		parent::__construct();

		$this->slug = $slug;
		$this->interior_slug = $slug . '_int';
		$this->wp_id = $wp_id;
		$this->title = $title;
		$this->blocks = $blocks;
		$this->author = $author;

		$this->exterior_args['label'] = $this->title;

		unset( $this->exterior_args['labels'] );

		$this->exterior_args['hierarchical'] = false;
		$this->exterior_args['capability_type'] = array( $this->slug, $this->slug . '_any' );
		$this->exterior_args['supports'] = array_diff( $this->exterior_args['supports'], array( 'page-attributes' ) );

		$this->interior_args['label'] = $this->title . ' ' . __( 'Interiors', 'wpfunnel' );

		$this->interior_args['labels'] = array(
			'name' => $this->title . ' ' . __( 'Interiors', 'wpfunnel' ),
			'singular_name' => $this->title . ' ' . __( 'Interior', 'wpfunnel' )
		);

		$this->interior_args['hierarchical'] = false;
		$this->interior_args['show_in_nav_menus'] = false;
		$this->interior_args['capability_type'] = array( $this->slug, $this->slug . '_any' );
		$this->interior_args['supports'] = array_diff( $this->interior_args['supports'], array( 'author' ) );

		$this->editor_role = array(
			'name' => 'Editor for ' . $this->title,
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
			'name' => 'Author for ' . $this->title,
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
			'name' => 'Contributor for ' . $this->title,
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
		add_filter( 'map_meta_cap', array( $this, 'assign_editor_to_owner' ), 10, 4 );
		add_filter( 'get_block_templates', array( $this, 'add_template' ), 10, 3 );
		add_filter( 'pre_get_block_template', array( $this, 'replace_template' ), 10, 3 );
		add_action( 'save_post', array( $this, 'update_post_author' ), 10, 2 );
		add_filter( 'single_template_hierarchy', array( $this, 'apply_template_to_interior' ) );
		add_action( 'wp_roles_init', array( $this, 'add_role' ) );
		add_filter( 'after_setup_theme', array( __CLASS__, 'regenerate_roles' ), 11 );
		add_action( 'init', array( $this, 'assign_menu' ), 9 );

		remove_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
	}

	/**
	 * Assign the admin menu for this funnel type.
	 *
	 * This must be called after the `wpfunnel_type` post type is registered at priority 8, and before this type is registered at priority 10.
	 *
	 * @since 1.4.0
	 */
	public function assign_menu()
	{
		if ( isset( self::$type_for_parent_menu ) )
		{
			$this->exterior_args['show_in_menu'] = 'edit.php?post_type=' . self::$type_for_parent_menu->slug;
		}
		else
		{
			$is_legacy = $GLOBALS['wpfunnel']->is_legacy();
			$capability = $is_legacy ? 'edit_posts' : 'contribute_funnels';

			if ( current_user_can( $capability ) )
			{
				$this->exterior_args['show_in_menu'] = $is_legacy ? 'edit.php?post_type=funnel' : 'edit.php?post_type=wpfunnel_head';
			}
			else
			{
				if ( current_user_can( 'edit_' . $this->slug . '_any' ) )
				{
					self::$type_for_parent_menu = $this;
				}

				$this->exterior_args['show_in_menu'] = false;
			}
		}
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
	 * Determine whether a user is the owner of this funnel type.
	 * They are an owner if they can edit the original template.
	 *
	 * Note: Since this function checks the `edit_post` meta capability, it will never return true if called before post types are registered at `init`.
	 *
	 * @since 1.2.0
	 */
	public function user_is_owner( $user )
	{
		// Avoid caching the result if post type is not registered yet.
		if ( !post_type_exists( 'wpfunnel_type' ) )
		{
			return false;
		}

		if ( isset( $this->is_owner[ $user ] ) && is_bool( $this->is_owner[ $user ] ) )
		{
			return $this->is_owner[ $user ];
		}
		else
		{
			$this->is_owner[ $user ] = user_can( $user, 'edit_post', $this->wp_id );
			return $this->is_owner[ $user ];
		}
	}

	public function make_role_editable( $roles )
	{
		if ( $this->user_is_owner( wp_get_current_user()->ID ) )
		{
			$roles[ $this->slug . '_editor' ] = $this->editor_role;
			$roles[ $this->slug . '_author' ] = $this->author_role;
			$roles[ $this->slug . '_contributor' ] = $this->contributor_role;
		}

		return $roles;
	}

	protected function construct_template( &$template, $slug, $title, $blocks )
	{
		$template                 = new \WP_Block_Template();
		$template->wp_id          = $this->wp_id;
		$template->id             = wp_get_theme()->get_stylesheet() . '//' . $slug;
		$template->theme          = wp_get_theme()->get_stylesheet();
		$template->content        = $blocks;
		$template->slug           = $slug;
		$template->source         = 'custom';
		$template->type           = 'wp_template';
		$template->description    = $title . ' is a WP Funnel Manager funnel type.';
		$template->title          = $title;
		$template->status         = 'publish';
		$template->has_theme_file = false;
		$template->is_custom      = !array_key_exists( $template->slug, get_default_block_template_types() );
		$template->author         = $this->author;
	}

	public function add_template( $query_result, $query, $template_type )
	{
		if ( $template_type === 'wp_template' && (
			!isset( $query['slug__in'] ) || in_array( 'single-' . $this->slug, $query['slug__in'], true )
		) && (
			!isset( $query['wp_id'] ) || (int) $query['wp_id'] === $this->wp_id
		) )
		{
			$id = wp_get_theme()->get_stylesheet() . '//single-' . $this->slug;
			$replace_key = count( $query_result );

			foreach ( array_keys( $query_result ) as $key )
			{
				if ( $query_result[ $key ]->id === $id )
				{
					$replace_key = $key;
					break;
				}
			}

			$this->construct_template( $query_result[ $replace_key ], 'single-' . $this->slug, $this->title, $this->blocks );
		}

		return $query_result;
	}

	public function replace_template( $block_template, $id, $template_type )
	{
		if ( $template_type === 'wp_template' && $id === wp_get_theme()->get_stylesheet() . '//single-' . $this->slug )
		{
			$this->construct_template( $block_template, 'single-' . $this->slug, $this->title, $this->blocks );
		}

		return $block_template;
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
			$interiors = new \WP_Query( 'posts_per_page=-1&post_status=any,trash,auto-draft&post_type=' . $this->interior_slug . '&post_parent=' . $post_id );

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
