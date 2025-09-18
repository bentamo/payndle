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
        
        // Add AJAX handlers
        add_action('wp_ajax_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_nopriv_update_booking_status', array($this, 'ajax_update_booking_status'));
    }
    
    /**
     * Handle AJAX request to update booking status
     */
    public function ajax_update_booking_status() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_booking_status')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get and validate input
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$booking_id || !$status) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Validate status
        $valid_statuses = array('completed', 'cancelled');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        // Update the post status
        $result = wp_update_post(array(
            'ID' => $booking_id,
            'post_status' => $status
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        // Get updated booking data
        $booking = $this->get_single_booking_data($booking_id);
        
        if (!$booking) {
            wp_send_json_error('Failed to retrieve updated booking data');
            return;
        }
        
        // Send success response
        wp_send_json_success(array(
            'message' => 'Booking status updated successfully',
            'booking' => $booking
        ));
    }
    
    /**
     * Get single booking data
     */
    private function get_single_booking_data($booking_id) {
        $post = get_post($booking_id);
        
        if (!$post || $post->post_type !== 'service_booking') {
            return false;
        }
        
        // Get all meta data (same as in get_all_bookings)
        $status = $post->post_status;
        $service_id = get_post_meta($booking_id, '_service_id', true);
        $customer_name = get_post_meta($booking_id, '_customer_name', true) ?: str_replace(' - Booking', '', $post->post_title);
        $customer_email = get_post_meta($booking_id, '_customer_email', true);
        $customer_phone = get_post_meta($booking_id, '_customer_phone', true);
        $booking_date = get_post_meta($booking_id, '_preferred_date', true);
        $booking_time = get_post_meta($booking_id, '_preferred_time', true);
        $payment_method = get_post_meta($booking_id, '_payment_method', true);
        $staff_id = get_post_meta($booking_id, '_staff_id', true);
        
        // Get service details
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
        
        // Get staff name
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
        
        return array(
            'booking_id' => $booking_id,
            'status' => $status_labels[$status] ?? ucfirst($status),
            'status_slug' => $status,
            'booking_date' => $booking_date ?: get_the_date('Y-m-d', $booking_id),
            'booking_time' => $booking_time ?: 'N/A',
            'customer_name' => $customer_name,
            'customer_email' => $customer_email ?: 'N/A',
            'customer_phone' => $customer_phone ?: 'N/A',
            'service_name' => $service_name,
            'service_duration' => $service_duration ? $service_duration . ' mins' : 'N/A',
            'service_price' => $service_price,
            'staff_name' => $staff_name,
            'notes' => $post->post_content ?: 'None',
            'created_at' => get_the_date('Y-m-d H:i:s', $booking_id),
            'payment_status' => $status === 'completed' ? 'Paid' : 'Pending',
            'payment_method' => ucfirst($payment_method) ?: 'N/A'
        );
    }

    /**
     * Enqueue CSS & JS
     */
    public function enqueue_assets() {
        $plugin_url = plugin_dir_url(__FILE__) . '../';

        // Only load on pages with the shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'assigned_bookings')) {
            return;
        }

        // Enqueue Inter font from Google Fonts
        wp_enqueue_style(
            'google-font-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            array(),
            null
        );

        // Enqueue WordPress dashicons
        wp_enqueue_style('dashicons');

        // Enqueue styles
        wp_enqueue_style(
            'assigned-bookings-style',
            $plugin_url . 'assets/css/assigned-bookings.css',
            array('google-font-inter'),
            '1.2'
        );

        // Enqueue scripts
        wp_enqueue_script(
            'assigned-bookings-script',
            $plugin_url . 'assets/js/assigned-bookings.js',
            array('jquery'),
            '1.2',
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'assigned-bookings-script',
            'assignedBookingsData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('update_booking_status'),
                'i18n' => array(
                    'confirm_complete' => __('Are you sure you want to mark this booking as completed?', 'payndle'),
                    'confirm_cancel' => __('Are you sure you want to cancel this booking? This action cannot be undone.', 'payndle'),
                    'error' => __('An error occurred. Please try again.', 'payndle'),
                    'success' => __('Booking updated successfully!', 'payndle')
                )
            )
        );

        // Add inline styles for notices
        $custom_css = "
            .booking-notice {
                margin: 15px 0;
                position: relative;
            }
            .notice-dismiss {
                text-decoration: none;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
                text-transform: capitalize;
            }
            .status-pending {
                background-color: #f0ad4e;
                color: #fff;
            }
            .status-completed {
                background-color: #5cb85c;
                color: #fff;
            }
            .status-cancelled {
                background-color: #d9534f;
                color: #fff;
            }
            .status-confirmed {
                background-color: #5bc0de;
                color: #fff;
            }
            .payment-status {
                display: inline-block;
                padding: 3px 6px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 500;
                text-transform: capitalize;
            }
            .payment-paid {
                background-color: #5cb85c;
                color: #fff;
            }
            .payment-pending {
                background-color: #f0ad4e;
                color: #fff;
            }
        ";
        
        wp_add_inline_style('assigned-bookings-style', $custom_css);
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
                <div class="empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <h3>No Bookings Found</h3>
                    <p>There are currently no bookings assigned to you. New bookings will appear here once assigned.</p>
                    <a href="#" class="button">
                        <span class="dashicons dashicons-update"></span>
                        Refresh Page
                    </a>
                </div>
            <?php else : ?>
                <div class="table-responsive">
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
                                <td data-label="ID">#<?php echo esc_html($booking['booking_id']); ?></td>
                                <td data-label="Customer">
                                    <strong><?php echo esc_html($booking['customer_name']); ?></strong>
                                </td>
                                <td data-label="Contact">
                                    <div class="contact-info">
                                        <div class="email"><?php echo esc_html($booking['customer_email']); ?></div>
                                        <div class="phone"><?php echo esc_html($booking['customer_phone']); ?></div>
                                    </div>
                                </td>
                                <td data-label="Service"><?php echo esc_html($booking['service_name']); ?></td>
                                <td data-label="Date & Time">
                                    <div class="datetime">
                                        <div class="date"><?php echo date_i18n('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                        <div class="time"><?php echo esc_html($booking['booking_time']); ?></div>
                                    </div>
                                </td>
                                <td data-label="Duration"><?php echo esc_html($booking['service_duration']); ?></td>
                                <td data-label="Staff"><?php echo esc_html($booking['staff_name']); ?></td>
                                <td data-label="Amount"><?php echo esc_html($booking['service_price']); ?></td>
                                <td data-label="Payment">
                                    <span class="payment-status payment-<?php echo strtolower($booking['payment_status']); ?>">
                                        <?php echo esc_html($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <div class="action-dropdown">
                                        <button class="action-dropdown-toggle" aria-expanded="false" aria-haspopup="true" aria-label="Actions">
                                            <span class="dashicons dashicons-ellipsis"></span>
                                        </button>
                                        <div class="action-dropdown-menu" role="menu">
                                            <button class="action-item view-details" data-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <span>View Details</span>
                                            </button>
                                            <?php if ($status === 'pending' || $status === 'confirmed') : ?>
                                                <button class="action-item complete-booking" data-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <span>Mark Complete</span>
                                                </button>
                                                <button class="action-item cancel-booking" data-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                                    <span class="dashicons dashicons-no-alt"></span>
                                                    <span>Cancel Booking</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
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
