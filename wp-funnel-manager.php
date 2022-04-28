<?php
/**
 * Plugin Name: WP Funnel Manager
 * Plugin URI:
 * Description: Organises content into multi-step funnels.
 * Version: 1.3.2
 * Requires at least: 5.8
 * Requires PHP: 5.6
 * Author: Ask Carlo
 * Author URI: https://askcarlo.com
 * Text Domain: wpfunnel
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
    die;
}

if ( function_exists( 'wp_get_current_user' ) )
{
    add_action( 'plugins_loaded', 'wpfunnel_init_deactivation' );

    /**
     * Initialise deactivation functions.
     */
    function wpfunnel_init_deactivation()
    {
        if ( current_user_can( 'activate_plugins' ) )
        {
            add_action( 'admin_init', 'wpfunnel_deactivate' );
            add_action( 'admin_notices', 'wpfunnel_deactivation_notice' );
        }
    }

    /**
     * Deactivate the plugin.
     */
    function wpfunnel_deactivate()
    {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    /**
     * Show deactivation admin notice.
     */
    function wpfunnel_deactivation_notice()
    {
        $notice = '<strong>WP Funnel Manager</strong> has been <strong>deactivated</strong> due to the detection of an unresolvable conflict with another of your plugins. Please try again after deactivating your other plugins.';
        ?>
        <div class="updated"><p><?php echo wp_kses_post( $notice ); ?></p></div>
        <?php
        if ( isset( $_GET['activate'] ) ) // WPCS: input var okay, CSRF okay.
        {
            unset( $_GET['activate'] ); // WPCS: input var okay.
        }
    }

    return false;
}
else
{
	function wp_get_current_user()
	{
		return did_action( 'after_setup_theme' ) && ! doing_action( 'after_setup_theme' ) ? _wp_get_current_user() : new WP_User( 0, '' );
	}
}

/**
 * Load plugin initialisation file.
 */
require plugin_dir_path( __FILE__ ) . '/init.php';
