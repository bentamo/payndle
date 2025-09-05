<?php

if (!defined('ABSPATH')) {
    exit;
}

class Assigned_Bookings {

    /**
     * Register hooks
     */
    public function register() {
        add_shortcode('assigned_bookings', array($this, 'render_assigned_bookings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue CSS & JS
     */
    public function enqueue_assets() {
        $plugin_url = plugin_dir_url(__FILE__) . '../';

        wp_enqueue_style(
            'assigned-bookings-style',
            $plugin_url . 'assets/css/assigned-bookings.css',
            array(),
            '1.0'
        );

        wp_enqueue_script(
            'assigned-bookings-script',
            $plugin_url . 'assets/js/assigned-bookings.js',
            array('jquery'),
            '1.0',
            true
        );
    }

    /**
     * Render Assigned Bookings Table (UI only)
     */
    public function render_assigned_bookings() {
        ob_start(); ?>
        
        <div class="assigned-bookings-container">
            <h2>Assigned Bookings</h2>

            <!-- Placeholder: Replace this sample data with DB-driven data -->
            <table class="assigned-bookings-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer Name</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#1001</td>
                        <td>Jane Doe</td>
                        <td>Haircut</td>
                        <td>2025-09-05</td>
                        <td>10:00 AM</td>
                        <td><span class="status-pending">Pending</span></td>
                        <td>
                            <button class="view-details" data-id="1001">View</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#1002</td>
                        <td>John Smith</td>
                        <td>Massage</td>
                        <td>2025-09-06</td>
                        <td>2:00 PM</td>
                        <td><span class="status-confirmed">Confirmed</span></td>
                        <td>
                            <button class="view-details" data-id="1002">View</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Placeholder: Modal for future details integration -->
            <div id="booking-modal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>Booking Details</h3>
                    <p>Placeholder for dynamic booking details (customer info, service, staff, payment).</p>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
}
