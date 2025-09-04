<?php
/*
Plugin Name: Custom Login Page
Description: Provides a custom login page with shortcode [custom_login]. Built with OOP for future extensibility (Google Auth placeholder included).
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Autoload class
require_once plugin_dir_path(__FILE__) . 'includes/class-custom-login.php';

// Initialize plugin
function custom_login_init() {
    $custom_login = new Custom_Login();
    $custom_login->register();
}
add_action('plugins_loaded', 'custom_login_init');
