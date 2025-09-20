<?php
/**
 * Plugin Name:       Assigned Bookings
 * Plugin URI:        https://example.com/plugins/assigned-bookings
 * Description:       Displays an assigned bookings page for staff with shortcode [assigned_bookings]. Includes functionality to manage booking statuses.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * Text Domain:       assigned-bookings
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ASSIGNED_BOOKINGS_VERSION', '1.0.0' );
define( 'ASSIGNED_BOOKINGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASSIGNED_BOOKINGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once ASSIGNED_BOOKINGS_PLUGIN_DIR . 'includes/class-assigned-bookings.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_assigned_bookings() {
	$plugin = new Assigned_Bookings();
	$plugin->register();
}
add_action( 'plugins_loaded', 'run_assigned_bookings' );

/**
 * Activation and deactivation hooks.
 */
register_activation_hook( __FILE__, array( 'Assigned_Bookings', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Assigned_Bookings', 'deactivate' ) );
