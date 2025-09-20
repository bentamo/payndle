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

/**
 * Helper: centralised plugin debug logger.
 * Writes to error_log only when WP_DEBUG is enabled.
 */
if (!function_exists('payndle_log')) {
    function payndle_log($message) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            if ( is_array($message) || is_object($message) ) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }
}

// Expose a JS flag for debug mode to front-end scripts (useful if needed)
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('jquery', sprintf("window.payndleDebug = %s;", (defined('WP_DEBUG') && WP_DEBUG) ? 'true' : 'false'));
});

// If debug is off, provide a small safeguard to silence console calls in front-end
add_action('wp_head', function() {
    if ( defined('WP_DEBUG') && WP_DEBUG ) return;
    echo "<script>(function(){if(!window.payndleDebug){var i=['log','info','warn','error','debug'];i.forEach(function(k){if(!console[k]) return;console[k]=function(){};});}})();</script>";
});

// Load plugin class files.
require_once 'includes/class-wordpress-plugin-template.php';
require_once 'includes/class-wordpress-plugin-template-settings.php';
require_once 'includes/class-assigned-bookings.php';


// Load plugin libraries.
require_once 'includes/lib/class-wordpress-plugin-template-admin-api.php';
require_once 'includes/lib/class-wordpress-plugin-template-post-type.php';
require_once 'includes/lib/class-wordpress-plugin-template-taxonomy.php';
require_once 'public-services-booking.php';
require_once 'landing_page.php';
require_once 'booking-history.php';
require_once 'assigned-bookings.php';
require_once 'user-booking-form.php';
require_once 'manage-bookings.php';
require_once 'manage-staff.php';
require_once 'business-setup.php';
require_once 'my-services-plugin.php';
require_once 'manager-dashboard.php';
require_once 'business-landing-shortcode.php';
require_once 'user-booking-form.php';
require_once 'manage-staff-shortcode.php';
<<<<<<< HEAD
require_once 'business-header-shortcode.php';
require_once 'contact-us-shortcode.php';
=======
require_once 'staff-timetable.php';
>>>>>>> 91a164f61c80b891396f90f2ae2716beb31175e8


/* Load booking history with error handling
if (file_exists(__DIR__ . '/booking-history.php')) {
    try {		
        require_once 'booking-history.php';
    } catch (Exception $e) {
        error_log('Error loading booking-history.php: ' . $e->getMessage());
    }
}

// Load complete booking history
if (file_exists(__DIR__ . '/complete-booking-history.php')) {
    try {
        require_once 'complete-booking-history.php';
    } catch (Exception $e) {
        error_log('Error loading complete-booking-history.php: ' . $e->getMessage());
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