<?php
/**
 * Main plugin file
 */

/**
 * Main plugin class.
 */
class WP_Funnel_Manager
{

	/**
	 * Instantiate a WP_Funnel_Manager object.
	 *
	 * Don't call the constructor directly, use the `WP_Funnel_Manager::get_instance()`
	 * static method instead.
	 */
	public function __construct()
	{
	}

	/**
	 * Launch the initialization process.
	 *
	 * @since 1.0.3
	 */
	public function run()
	{
		// Load functions
		add_filter( 'quick_edit_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'funnel_interior_permalink' ), 10, 2 );
		add_action( 'init', array( $this, 'post_parent_query_var' ) );

		// Load a funnel
		$funnel = new Funnel();
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
		if ( 'funnel' != $post->post_type )
			return $actions;

		$url = admin_url( 'edit.php?post_type=funnel_int&post_parent=' . $post->ID );
		$actions['edit_interiors'] = '<a href="' . esc_url( $url ) . '">' . __( 'Edit Interiors', 'wpfunnel' ) . '</a>';

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
	 * Load the plugin text domain.
	 *
	 * @since 1.0.3
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain( 'wpfunnel', false, 'wp-funnel-manager/languages/' );
	}
}
