<?php
/**
 * Plugin Name: WP Funnel Manager
 * Plugin URI:
 * Description: Organises content into multi-step funnels.
 * Version: 1.3.0
 * Requires at least: 5.8
 * Author: Ask Carlo
 * Author URI: https://askcarlo.com
 * Text Domain: wpfunnel
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
    die;
}

if ( false )
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
        $notice = sprintf(
            // Translators: 1: Required PHP version, 2: Current PHP version.
            '<strong>WP Funnel Manager</strong> requires PHP %1$s to run. This site uses %2$s, so the plugin has been <strong>deactivated</strong>.',
            '7.1',
            PHP_VERSION
        );
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

/**
 * Load plugin initialisation file.
 */
require plugin_dir_path( __FILE__ ) . '/init.php';
