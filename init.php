<?php
/**
 * Initialise the plugin
 */
namespace WP_Funnel_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
	die;
}

if ( ! defined( 'WP_FUNNEL_DIR' ) )
{
	define( 'WP_FUNNEL_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WP_FUNNEL_URL' ) )
{
	define( 'WP_FUNNEL_URL', plugin_dir_url( __FILE__ ) );
}

// Load funnel type classes.
require WP_FUNNEL_DIR . 'src/legacy.php';
require WP_FUNNEL_DIR . 'src/funnel.php';

// Load other classes.
require WP_FUNNEL_DIR . 'src/plugin.php';

// Initialize the plugin.
$GLOBALS['wpfunnel'] = new WP_Funnel_Manager();
$GLOBALS['wpfunnel']->run();
