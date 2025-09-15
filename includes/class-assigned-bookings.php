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

        // Enqueue Inter font from Google Fonts
        wp_enqueue_style(
            'google-font-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            array(),
            null
        );

        // Enqueue Dashicons for action buttons
        wp_enqueue_style('dashicons');

        wp_enqueue_style(
            'assigned-bookings-style',
            $plugin_url . 'assets/css/assigned-bookings.css',
            array('google-font-inter'),
            '1.1'
        );

        wp_enqueue_script(
            'assigned-bookings-script',
            $plugin_url . 'assets/js/assigned-bookings.js',
            array('jquery'),
            '1.1',
            true
        );
    }

    /**
     * Get all bookings from service_booking post type with extended details
     */
    private function get_all_bookings() {
        $args = array(
            'post_type'      => 'service_booking',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_key'       => '_preferred_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC'
        );
        
        $bookings_query = new WP_Query($args);
        $formatted_bookings = array();
        
        if ($bookings_query->have_posts()) {
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $post_id = get_the_ID();
                
                // Get post status and basic info
                $status = get_post_status($post_id);
                $post_title = get_the_title($post_id);
                
                // Get all meta data
                $service_id = get_post_meta($post_id, '_service_id', true);
                $customer_name = get_post_meta($post_id, '_customer_name', true) ?: str_replace(' - Booking', '', $post_title);
                $customer_email = get_post_meta($post_id, '_customer_email', true);
                $customer_phone = get_post_meta($post_id, '_customer_phone', true);
                $booking_date = get_post_meta($post_id, '_preferred_date', true);
                $booking_time = get_post_meta($post_id, '_preferred_time', true);
                $payment_method = get_post_meta($post_id, '_payment_method', true);
                $staff_id = get_post_meta($post_id, '_staff_id', true);
                
                // Get service details if service ID exists
                $service_name = 'N/A';
                $service_duration = 'N/A';
                $service_price = 'N/A';
                
                if ($service_id) {
                    $service_name = get_the_title($service_id);
                    $service_duration = get_post_meta($service_id, '_duration', true);
                    $service_price = get_post_meta($service_id, '_price', true);
                    if ($service_price !== '') {
                        $service_price = number_format((float)$service_price, 2);
                    }
                }
                
                // Get staff name if staff ID exists
                $staff_name = 'Any Available';
                if ($staff_id) {
                    $staff_name = get_the_title($staff_id);
                }
                
                // Status labels
                $status_labels = array(
                    'publish' => 'Confirmed',
                    'pending' => 'Pending',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                    'refunded' => 'Refunded',
                    'failed' => 'Failed'
                );
                
                // Format the booking data
                $formatted_bookings[] = array(
                    'booking_id' => $post_id,
                    'status' => $status_labels[$status] ?? ucfirst($status),
                    'status_slug' => $status,
                    'booking_date' => $booking_date ?: get_the_date('Y-m-d', $post_id),
                    'booking_time' => $booking_time ?: 'N/A',
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email ?: 'N/A',
                    'customer_phone' => $customer_phone ?: 'N/A',
                    'service_name' => $service_name,
                    'service_duration' => $service_duration ? $service_duration . ' mins' : 'N/A',
                    'service_price' => $service_price,
                    'staff_name' => $staff_name,
                    'notes' => get_the_content() ?: 'None',
                    'created_at' => get_the_date('Y-m-d H:i:s', $post_id),
                    'payment_status' => $status === 'completed' ? 'Paid' : 'Pending',
                    'payment_method' => ucfirst($payment_method) ?: 'N/A'
                );
            }
            wp_reset_postdata();
        }
        
        return $formatted_bookings;
    }

    /**
     * Render Assigned Bookings Table with actual data
     */
    public function render_assigned_bookings() {
        $bookings = $this->get_all_bookings();
        
        ob_start(); 
        ?>
        <div class="assigned-bookings-container">
            <h2>Assigned Bookings</h2>

            <?php if (empty($bookings)) : ?>
                <p>No bookings found.</p>
            <?php else : ?>
                <table class="assigned-bookings-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Staff</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) : 
                            $status_class = strtolower($booking['status'] ?? 'pending');
                        ?>
                            <tr class="status-<?php echo esc_attr($status_class); ?>">
                                <td>#<?php echo esc_html($booking['booking_id']); ?></td>
                                <td>
                                    <strong><?php echo esc_html($booking['customer_name']); ?></strong>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div class="email"><?php echo esc_html($booking['customer_email']); ?></div>
                                        <div class="phone"><?php echo esc_html($booking['customer_phone']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($booking['service_name']); ?></td>
                                <td>
                                    <div class="datetime">
                                        <div class="date"><?php echo date_i18n('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                        <div class="time"><?php echo esc_html($booking['booking_time']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($booking['service_duration']); ?></td>
                                <td><?php echo esc_html($booking['staff_name']); ?></td>
                                <td><?php echo esc_html($booking['service_price']); ?></td>
                                <td>
                                    <span class="payment-status payment-<?php echo strtolower($booking['payment_status']); ?>">
                                        <?php echo esc_html($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="button view-details" data-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <?php if ($status === 'pending' || $status === 'confirmed') : ?>
                                        <button class="button action-button complete-booking" data-id="<?php echo esc_attr($booking['booking_id']); ?>" data-action="complete">
                                            <span class="dashicons dashicons-yes"></span>
                                        </button>
                                        <button class="button action-button cancel-booking" data-id="<?php echo esc_attr($booking['booking_id']); ?>" data-action="cancel">
                                            <span class="dashicons dashicons-no"></span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Modal for booking details -->
            <div id="booking-modal" class="booking-modal">
                <div class="booking-modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>Booking Details</h3>
                    <div id="booking-details-content">
                        <!-- Dynamic content will be loaded here via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle view details button click
            $('.view-details').on('click', function() {
                var bookingId = $(this).data('id');
                // You can implement AJAX call here to fetch detailed booking info
                // and update the #booking-details-content
                $('#booking-modal').show();
            });

            // Close modal when clicking the X
            $('.close-modal').on('click', function() {
                $('#booking-modal').hide();
            });

            // Close modal when clicking outside of it
            $(window).on('click', function(e) {
                if ($(e.target).is('#booking-modal')) {
                    $('#booking-modal').hide();
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
