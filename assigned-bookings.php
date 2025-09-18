<?php
/*
Plugin Name: Assigned Bookings
Description: Displays an assigned bookings page for staff with shortcode [assigned_bookings]. Includes functionality to manage booking statuses.
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load class
require_once plugin_dir_path(__FILE__) . 'includes/class-assigned-bookings.php';

// Initialize plugin
function assigned_bookings_init() {
    $assigned_bookings = new Assigned_Bookings();
    $assigned_bookings->register();
}
add_action('plugins_loaded', 'assigned_bookings_init');

// AJAX handler is now in the Assigned_Bookings class
