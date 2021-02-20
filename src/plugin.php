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
	private $funnel_types = array();

	/**
	 * Instantiate a WP_Funnel_Manager object.
	 *
	 * Don't call the constructor directly, use the `WP_Funnel_Manager::get_instance()`
	 * static method instead.
	 */
	public function __construct()
	{
		if ( ( $legacy = $this->is_legacy() ) )
		{
			$this->funnel_types[] = new Legacy_Funnel_Type();
		}

		if ( function_exists( 'gutenberg_is_fse_theme' ) && gutenberg_is_fse_theme() )
		{
			foreach ( get_posts( 'numberposts=-1&post_type=wp_template' ) as $post )
			{
				if ( $post->post_status !== 'publish' )
				continue;

				if ( strpos( $post->post_name, 'single-' ) !== 0 )
				continue;

				$slug = substr( $post->post_name, 7 );
				
				if ( strpos( $slug, '-' ) !== false )
				continue;

				if ( $legacy && $slug === 'funnel' )
				continue;

				if ( substr( $slug, -4 ) === '_int' )
				continue;

				$this->funnel_types[] = new Funnel_Type( $slug, $post );
			}
		}
	}

	public function is_legacy()
	{
		if ( get_option( 'wpfunnel_ignore_legacy' ) )
		return false;

		if ( empty( get_posts( 'numberposts=-1&post_type=funnel&post_status=any,trash' ) ) )
		{
			update_option( 'wpfunnel_ignore_legacy', '1' );
			return false;
		}
		else
		{
			return true;
		}
	}

	public function register_funnel_types()
	{
		foreach ( $this->funnel_types as $type )
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
		if ( empty( $this->funnel_types ) )
		{
			add_action( 'admin_footer', array( $this, 'no_funnels_notice' ) );
		}
		else
		{
			$this->register_funnel_types();
		}
	}

	public function no_funnels_notice()
	{
		echo '<div class="notice notice-warning"><p>Thank you for activating WP Funnel Manager! To start building funnels, ensure your active theme supports Full Site Editing.</p></div>';
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
