<?php
/**
 * Plugin Name: WordPress Plugin Template
 * Version: 1.0.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wordpress-plugin-template
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-wordpress-plugin-template.php';
require_once 'includes/class-wordpress-plugin-template-settings.php';
require_once 'includes/class-custom-login.php';
require_once 'includes/class-assigned-bookings.php';

// Load plugin libraries.
require_once 'includes/lib/class-wordpress-plugin-template-admin-api.php';
require_once 'includes/lib/class-wordpress-plugin-template-post-type.php';
require_once 'includes/lib/class-wordpress-plugin-template-taxonomy.php';
require_once 'public-services-booking.php';
require_once 'landing_page.php';
require_once 'booking-history.php';
require_once 'custom-login.php';
require_once 'plan-page.php';
require_once 'assigned-bookings.php';
<<<<<<< HEAD
require_once 'user-booking-form.php';
=======
require_once 'manage-bookings.php';
>>>>>>> dd8a6d7123d4aea1b52b7592643b12f1b5fe8dbb

/*/ Load booking history with error handling
if (file_exists(__DIR__ . '/booking-history.php')) {
    try {
        require_once 'booking-history.php';
    } catch (Exception $e) {
        error_log('Error loading booking-history.php: ' . $e->getMessage());
    }
}

*/

/**
 * Returns the main instance of WordPress_Plugin_Template to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WordPress_Plugin_Template
 */
function wordpress_plugin_template() {
	$instance = WordPress_Plugin_Template::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WordPress_Plugin_Template_Settings::instance( $instance );
	}

	return $instance;
}

wordpress_plugin_template();