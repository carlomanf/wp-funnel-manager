<?php
/**
 * Plugin Name: WP Funnel Manager
 * Plugin URI:
 * Description: Organises content into multi-step funnels.
 * Version: 1.4.1
 * Requires at least: 6.6
 * Requires PHP: 7.2
 * Author: Ask Carlo
 * Author URI: https://askcarlo.com
 * Text Domain: wpfunnel
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
    die;
}

/**
 * Load plugin initialisation file.
 */
require plugin_dir_path( __FILE__ ) . '/init.php';
