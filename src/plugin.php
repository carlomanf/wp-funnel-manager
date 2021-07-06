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
	 * Whether this plugin manages the legacy funnel type.
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	private $is_legacy;

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
		if ( get_option( 'wpfunnel_ignore_legacy' ) )
		{
			$this->is_legacy = false;
		}
		else
		{
			if ( empty( get_posts( 'numberposts=-1&post_type=funnel&post_status=any,trash' ) ) )
			{
				update_option( 'wpfunnel_ignore_legacy', '1' );
				$this->is_legacy = false;
			}
			else
			{
				$this->is_legacy = true;
			}
		}

		if ( $this->is_legacy )
		{
			$this->funnel_types[] = new Legacy_Funnel_Type();
		}

		add_theme_support( 'block-templates' );

		foreach ( get_posts( 'numberposts=-1&post_type=wp_template' ) as $post )
		{
			if ( empty( get_post_meta( $post->ID, 'wpfunnel' ) ) )
			continue;

			if ( $post->post_status !== 'publish' )
			continue;

			if ( strpos( $post->post_name, 'single-' ) !== 0 )
			continue;

			else
			$slug = substr( $post->post_name, 7 );

			if ( $this->is_legacy && $slug === 'funnel' )
			continue;

			if ( substr( $slug, -4 ) === '_int' )
			continue;

			$this->funnel_types[] = new Funnel_Type( $slug, $post );
		}
	}

	public function is_legacy()
	{
		return $this->is_legacy;
	}

	public function register_funnel_types()
	{
		foreach ( $this->funnel_types as $type )
		{
			$type->register();
		}
	}

	public function menu()
	{
		$new_type = 'post-new.php?post_type=wp_template&wpfunnel=1';
		$parent = $this->is_legacy ? 'edit.php?post_type=funnel' : $new_type;

		$wp_template = get_post_type_object( 'wp_template' );
		$new_type_capability = $wp_template === null ? 'do_not_allow' : $wp_template->cap->create_posts;
		$parent_capability = $this->is_legacy ? 'edit_posts' : $new_type_capability;

		add_menu_page( 'Funnels', 'Funnels', $parent_capability, $parent, '', 'dashicons-filter', 25 );
		add_submenu_page( $parent, 'Add New Type', 'Add New Type', $new_type_capability, $new_type, '', 20 );
	}

	/**
	 * Launch the initialization process.
	 *
	 * @since 1.0.3
	 */
	public function run()
	{
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );

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
