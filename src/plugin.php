<?php
/**
 * Main plugin file
 */

/**
 * Main plugin class.
 */
namespace WP_Funnel_Manager;

class WP_Funnel_Manager
{
	/**
	 * Static instance of the plugin.
	 */
	protected static $instance;

	/**
	 * Funnel types managed by this plugin.
	 *
	 * @since 1.2.0
	 * @var array
	 */
	private $funnel_types;

	/**
	 * Instantiate a WP_Funnel_Manager object.
	 *
	 * Don't call the constructor directly, use the `WP_Funnel_Manager::get_instance()`
	 * static method instead.
	 */
	public function __construct()
	{
		foreach( get_posts( 'post_type=wp_template' ) as $post )
		{
			$slug = str_replace( 'single-', '', $post->post_name );

			if ( strpos( $post->post_name, 'single-' ) === 0 && strpos( $slug, '-' ) === false )
			{
				$this->funnel_types[] = new Funnel_Type( $slug, $post );
			}

		}
	}

	public function register_funnel_types()
	{
		foreach( $this->funnel_types as $type )
		{
			$type->register();
		}
	}

	/**
	 * Launch the initialization process.
	 *
	 * @since 1.0.3
	 */
	public function run()
	{
		// Load a funnel
		$this->register_funnel_types();
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
