<?php
/**
 * Plugin Name: Payndle Landing
 * Description: Provides the [payndle_landing] shortcode to render the Payndle marketing landing page with branding, about, carousel, partner form, and search with filters. No DB dependencies.
 * Version: 0.1.0
 * Author: Payndle
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin paths
if (!defined('PAYNDLE_LANDING_PATH')) {
    define('PAYNDLE_LANDING_PATH', plugin_dir_path(__FILE__));
}
if (!defined('PAYNDLE_LANDING_URL')) {
    define('PAYNDLE_LANDING_URL', plugin_dir_url(__FILE__));
}

add_action('wp_enqueue_scripts', function () {
    // Inter font
    wp_enqueue_style(
        'payndle-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    // Styles
    wp_enqueue_style(
        'payndle-landing',
        PAYNDLE_LANDING_URL . 'assets/css/payndle-landing.css',
        ['payndle-inter'],
        file_exists(PAYNDLE_LANDING_PATH . 'assets/css/payndle-landing.css') ? filemtime(PAYNDLE_LANDING_PATH . 'assets/css/payndle-landing.css') : '0.1.0'
    );

    // Scripts
    wp_enqueue_script(
        'payndle-landing',
        PAYNDLE_LANDING_URL . 'assets/js/payndle-landing.js',
        ['jquery'],
        file_exists(PAYNDLE_LANDING_PATH . 'assets/js/payndle-landing.js') ? filemtime(PAYNDLE_LANDING_PATH . 'assets/js/payndle-landing.js') : '0.1.0',
        true
    );

    // Localize config (if needed later)
    wp_localize_script('payndle-landing', 'payndleLanding', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
});

// Shortcode to render the landing
add_shortcode('payndle_landing', function ($atts = []) {
    ob_start();
    $template = PAYNDLE_LANDING_PATH . 'templates/landing.php';
    if (file_exists($template)) {
        include $template;
    } else {
        echo '<p>Payndle Landing template missing.</p>';
    }
    return ob_get_clean();
});
