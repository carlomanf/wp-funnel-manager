<?php

/**
 * Natural funnel class.
 */
namespace WP_Funnel_Manager;

class Natural_Funnel_Type extends Dynamic_Funnel_Type
{
	private $added_wp_link_pages = array();
	private $query = null;
	private $steps = array();
	const PERM_PATTERN = '%2$d_funnel_%1$d';

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

		// Add content_pagination filter
		add_filter( 'content_pagination', array( $this, 'populate_funnel_steps' ), 10, 2 );

		// Add pre_handle_404 filter
		add_filter( 'pre_handle_404', array( $this, 'handle_404' ), 10, 2 );

		// Use wp_link_pages_link filter to include nonces in pagination links
		add_filter( 'wp_link_pages_link', array( $this, 'add_nonces_to_pagination_links' ), 10, 2 );
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

	public function create_query()
	{
		if ( !isset( $this->query ) )
		{
			$this->query = new \WP_Query(
				array(
					'post_type' => 'wpfunnel_head',
					'post_status' => 'publish',
					'posts_per_page' => -1
				)
			);
		}
	}

	public function add_template( $query_result, $query, $template_type )
	{
		$this->create_query();

		foreach ( $this->query->posts as $funnel )
		{
			if ( $template_type === 'wp_template' && (
				!isset( $query['slug__in'] ) || in_array( 'single-' . $this->slug . '-' . $funnel->post_name, $query['slug__in'], true )
			) && (
				!isset( $query['wp_id'] ) || (int) $query['wp_id'] === $funnel->ID
			) )
			{
				$id = wp_get_theme()->get_stylesheet() . '//single-' . $this->slug . '-' . $funnel->post_name;
				$replace_key = count( $query_result );

				foreach ( array_keys( $query_result ) as $key )
				{
					if ( $query_result[ $key ]->id === $id )
					{
						$replace_key = $key;
						break;
					}
				}

				$this->construct_template( $query_result[ $replace_key ], 'single-' . $this->slug . '-' . $funnel->post_name, $funnel->post_title, $funnel->post_content );
			}
		}

		return $query_result;
	}

	public function replace_template( $block_template, $id, $template_type )
	{
		if ( $template_type === 'wp_template' && strpos( $id, wp_get_theme()->get_stylesheet() . '//single-' . $this->slug . '-' ) === 0 )
		{
			$this->create_query();

			$funnel_slug = substr( $id, strrpos( $id, '-' ) + 1 );
			$funnel = null;

			foreach ( $this->query->posts as $post )
			{
				if ( $post->post_name === $funnel_slug )
				{
					$funnel = $post;
					break;
				}
			}

			isset( $funnel ) and $this->construct_template( $block_template, 'single-' . $this->slug . '-' . $funnel_slug, $funnel->post_title, $funnel->post_content );
		}

		return $block_template;
	}

	private function update_user( $user, $funnel, $step )
	{
		$steps = (array) get_user_option( 'wpfunnel_steps', $user );
		$permissions = (array) get_user_option( 'wpfunnel_permissions', $user );

		$permissions[] = sprintf( self::PERM_PATTERN, $funnel, $step );
		update_user_option( $user, 'wpfunnel_permissions', array_unique( $permissions ) );

		$steps[ $funnel ] = (string) $step;
		update_user_option( $user, 'wpfunnel_steps', $steps );
	}

	// Populate funnel steps using content_pagination filter
	public function populate_funnel_steps( $pages, $post )
	{
		if ( $post->post_type === $this->slug )
		{
			$this->assign_steps( $post->ID );

			$pages = array();
			foreach ( $this->steps[ $post->ID ] as $step )
			{
				$pages[] = $step->post_content;
			}
		}

		return $pages;
	}

	// Custom handler for pre_handle_404 filter
	public function handle_404( $handled, $query )
	{
		if ( $query->is_singular( $this->slug ) )
		{
			$funnel = $query->posts[0]->ID;
			$page = $query->generate_postdata( $funnel )['page'];
			$user = get_current_user_id();
			$step = null;

			$this->assign_steps( $funnel );

			if ( $page <= count( $this->steps[ $funnel ] ) )
			{
				$step = $this->steps[ $funnel ][ $page - 1 ]->ID;
				$steps = (array) get_user_option( 'wpfunnel_steps', $user );
			}

			if ( isset( $step ) && ( 1 === wp_verify_nonce( isset( $_GET['funnel_nonce'] ) ? $_GET['funnel_nonce'] : '', sprintf( self::PERM_PATTERN, $funnel, $step ) ) || !isset( $steps[ $funnel ] ) && $page === 1 || (int) $steps[ $funnel ] === $step ) )
			{
				$this->update_user( $user, $funnel, $step );
				$this->assign_steps( $funnel, true );

				status_header( 200 );
			}
			else
			{
				$query->set_404();
				status_header( 404 );
				nocache_headers();
			}

			$handled = true;
		}

		return $handled;
	}

	// Assign steps if not already assigned
	private function assign_steps( $id, $force = false )
	{
		if ( !isset( $this->steps[ $id ] ) || $force )
		{
			 $query = new \WP_Query( array(
				'post_type' => $this->interior_slug,
				'post_parent' => $id,
				'posts_per_page' => -1,
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'post_status' => 'publish'
			) );

			if ( current_user_can( 'edit_post', $id ) )
			{
				$this->steps[ $id ] = $query->posts;
			}
			else
			{
				$this->steps[ $id ] = array();
				$user = get_current_user_id();

				foreach ( $query->posts as $step )
				{
					$permission = true;
					$permissions = (array) get_user_option( 'wpfunnel_permissions', $user );

					foreach ( $query->posts as $other_step )
					{
						if ( $other_step->menu_order < $step->menu_order )
						{
							$permission = $permission && in_array( sprintf( self::PERM_PATTERN, $id, $other_step->ID ), $permissions, true );
						}
					}

					$permission and $this->steps[ $id ][] = $step;
				}
			}
		}
	}

	// Add nonces to pagination links
	public function add_nonces_to_pagination_links( $link, $i )
	{
		if ( $i > 0 && get_post_type() === $this->slug )
		{
			$funnel = get_the_ID();
			$this->assign_steps( $funnel );

			if ( $i <= count( $this->steps[ $funnel ] ) )
			{
				$step = $this->steps[ $funnel ][ $i - 1 ]->ID;

				$link = preg_replace_callback( '/href=["\']([^"\']+)["\']/', function( $matches ) use ( $funnel, $step ) {
					return 'href="' . esc_url( add_query_arg( 'funnel_nonce', wp_create_nonce( sprintf( self::PERM_PATTERN, $funnel, $step ) ), $matches[1] ) ) . '"';
				}, $link );
			}
		}

		return $link;
	}
}
