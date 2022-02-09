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
	private $is_legacy = true;

	/**
	 * Whether this plugin manages the templated funnel types.
	 *
	 * @since 1.3.1
	 * @var bool
	 */
	private $is_templated = false;

	/**
	 * Funnel types managed by this plugin.
	 *
	 * @since 1.2.0
	 * @var array
	 */
	private $funnel_types = array();

	public function is_legacy()
	{
		return $this->is_legacy;
	}

	public function register_funnel_types()
	{
		if ( get_option( 'wpfunnel_ignore_legacy' ) )
		{
			$this->is_legacy = false;
		}

		$this->is_templated = current_theme_supports( 'block-templates' );

		if ( $this->is_templated )
		{
			if ( $this->is_legacy && empty( (
				new \WP_Query(
					array(
						'post_type' => 'funnel',
						'post_status' => array( 'any', 'trash' ),
						'posts_per_page' => -1
					)
				)
			)->posts ) )
			{
				update_option( 'wpfunnel_ignore_legacy', '1' );
				$this->is_legacy = false;
			}

			foreach ( (
				new \WP_Query(
					array(
						'post_type' => 'wp_template',
						'post_status' => 'publish',
						'meta_key' => 'wpfunnel',
						'meta_value' => '1',
						'posts_per_page' => -1,
						'orderby' => 'title',
						'order' => 'ASC'
					)
				)
			)->posts as $post )
			{
				if ( strpos( $post->post_name, 'single-' ) === 0 )
				{
					$slug = substr( $post->post_name, 7 );

					if ( ( !$this->is_legacy || $slug !== 'funnel' ) && substr( $slug, -4 ) !== '_int' )
					{
						$this->funnel_types[] = new Funnel_Type( $slug, $post );
					}
				}
			}
		}

		if ( $this->is_legacy )
		{
			$this->funnel_types[] = new Legacy_Funnel_Type();
		}

		foreach ( $this->funnel_types as $type )
		{
			$type->register();
		}
	}

	public function menu()
	{
		if ( $this->is_templated || $this->is_legacy )
		{
			$new_type = 'wpfunnel';
			$parent = $this->is_legacy ? 'edit.php?post_type=funnel' : $new_type;

			$wp_template = get_post_type_object( 'wp_template' );
			$new_type_capability = $wp_template === null ? 'do_not_allow' : $wp_template->cap->create_posts;
			$parent_capability = $this->is_legacy ? 'edit_posts' : $new_type_capability;

			add_menu_page( 'Funnels', 'Funnels', $parent_capability, $parent, '', 'dashicons-filter', 25 );

			if ( $this->is_templated )
			{
				$hook_suffix = add_submenu_page( $parent, 'New Funnel Type', 'New Funnel Type', $new_type_capability, $new_type, array( $this, 'new_funnel_type' ), 20 );
				add_action( 'load-' . $hook_suffix, array( $this, 'new_funnel_type' ) );
			}
		}
	}

	public function new_funnel_type()
	{
		$num = 0;
		$args = array(
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'meta_input' => array(
				'wpfunnel' => '1'
			)
		);

		do {
			$num++;
			$args = array_replace(
				$args,
				array(
					'title' => 'New Funnel Type ' . $num
				)
			);
		}
		while ( ( new \WP_Query( $args ) )->have_posts() );

		$type = uniqid();

		wp_insert_post(
			array_diff_assoc(
				array_replace(
					$args,
					array(
						'post_name' => 'single-' . $type,
						'post_content' => '<!-- wp:post-content /-->',
						'post_title' => $args['title']
					)
				),
				array(
					'title' => $args['title']
				)
			)
		);

		wp_redirect( admin_url( 'post-new.php?post_type=' . $type ) );
		exit;
	}

	/**
	 * Launch the initialization process.
	 *
	 * @since 1.0.3
	 */
	public function run()
	{
		add_action( 'admin_footer', array( $this, 'no_funnels_notice' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'init', array( $this, 'database_upgrade_130' ), 20 );
		add_action( 'init', array( $this, 'database_upgrade_131' ), 20 );
		add_action( 'after_setup_theme', array( $this, 'register_funnel_types' ) );
	}

	public function get_db_version()
	{
		return (int) get_option( 'wpfunnel_db_version', 0 );
	}

	public function database_upgrade_130()
	{
		if ( $this->get_db_version() >= 130 )
		return;

		$funnel_types = new \WP_Query(
			array(
				'post_type' => 'wp_template',
				'post_status' => array( 'any', 'trash', 'auto-draft' ),
				'meta_key' => 'wpfunnel',
				'meta_value' => '1',
				'posts_per_page' => -1
			)
		);

		foreach ( $funnel_types->posts as $post )
		{
			wp_set_post_terms( $post->ID, array(), 'wp_theme' );
		}

		// If upgrading to 130 on version 1.3.1, skip the upgrade to 131
		update_option( 'wpfunnel_db_version', '131' );
	}

	/**
	 * Fixes a now-removed error with the 130 upgrade routine.
	 *
	 * @since 1.3.1
	 */
	public function database_upgrade_131()
	{
		if ( $this->get_db_version() >= 131 || !current_theme_supports( 'block-templates' ) )
		return;

		$not_funnel_types = new \WP_Query(
			array(
				'post_type' => 'wp_template',
				'post_status' => array( 'any', 'trash', 'auto-draft' ),
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'wpfunnel',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'wpfunnel',
						'value' => '1',
						'compare' => '!='
					)
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'wp_theme',
						'operator' => 'NOT EXISTS'
					)
				),
				'posts_per_page' => -1
			)
		);

		foreach ( $not_funnel_types->posts as $post )
		{
			wp_set_post_terms( $post->ID, wp_get_theme()->get_stylesheet(), 'wp_theme', true );
		}

		update_option( 'wpfunnel_db_version', '131' );
	}

	public function no_funnels_notice()
	{
		if ( !$this->is_templated && !$this->is_legacy )
		{
			echo '<div class="notice notice-warning"><p>Thank you for using WP Funnel Manager! To continue building funnels, ensure your active theme <a href="https://make.wordpress.org/core/2021/06/16/introducing-the-template-editor-in-wordpress-5-8/">supports block templates.</a></p></div>';
		}
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
