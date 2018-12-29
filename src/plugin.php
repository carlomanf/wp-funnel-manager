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
	 * Load the plugin text domain.
	 *
	 * @since 1.0.3
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain( 'wpfunnel', false, 'wp-funnel-manager/languages/' );
	}
}
