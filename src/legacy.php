<?php

/**
 * Legacy funnel class.
 */
namespace WP_Funnel_Manager;

class Legacy_Funnel_Type extends Funnel_Type
{
	public function __construct()
	{
		parent::__construct( 'funnel', (object) array( 'post_title' => '' ) );

		$this->editor_role = null;
		$this->author_role = null;
		$this->contributor_role = null;
	}

	public function register()
	{
		parent::register();

		$this->exterior_args['label'] = __( 'Funnels', 'wpfunnel' );

		$this->exterior_args['labels'] = array(
			'name' => __( 'Funnels', 'wpfunnel' ),
			'singular_name' => __( 'Funnel', 'wpfunnel' )
		);

		$this->exterior_args['hierarchical'] = true;
		$this->exterior_args['show_in_menu'] = false;
		$this->exterior_args['capability_type'] = 'post';
		$this->exterior_args['supports'][] = 'page-attributes';

		$this->interior_args['label'] = __( 'Interiors', 'wpfunnel' );

		$this->interior_args['labels'] = array(
			'name' => __( 'Interiors', 'wpfunnel' ),
			'singular_name' => __( 'Interior', 'wpfunnel' )
		);

		$this->interior_args['hierarchical'] = is_admin();
		$this->interior_args['show_in_nav_menus'] = true;
		$this->interior_args['capability_type'] = 'post';
		$this->interior_args['supports'][] = 'author';

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

	// Legacy funnel type has no editors
	public function assign_editor_to_owner( $caps, $cap, $user, $args )
	{
		return $caps;
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

	public function update_theme()
	{
		return;
	}
}
