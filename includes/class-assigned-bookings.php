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
        // Enable error logging
        error_log('=== Starting ajax_update_booking_status ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verify nonce for security
        if (!isset($_POST['nonce'])) {
            $error = 'Security check failed: Nonce not provided';
            error_log('Error: ' . $error);
            wp_send_json_error(array('message' => $error));
            wp_die();
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'update_booking_status')) {
            $error = 'Security check failed: Invalid nonce';
            error_log('Error: ' . $error . '. Received: ' . $_POST['nonce']);
            wp_send_json_error(array('message' => $error));
            wp_die();
        }
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            $current_user = wp_get_current_user();
            $error = 'You do not have permission to update booking status';
            error_log('Error: ' . $error . '. User ID: ' . $current_user->ID . ', Roles: ' . implode(', ', $current_user->roles));
            wp_send_json_error(array('message' => $error));
            wp_die();
        }
        
        // Get and validate input
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? strtolower(sanitize_text_field($_POST['status'])) : '';
        
        error_log('Processing update - Booking ID: ' . $booking_id . ', Status: ' . $status);
        
        // Validate booking ID
        if (!$booking_id || $booking_id <= 0) {
            $error = 'Invalid booking ID: ' . $booking_id;
            error_log('Error: ' . $error);
            wp_send_json_error(array(
                'message' => 'Invalid booking. Please refresh the page and try again.',
                'debug' => $error
            ));
            wp_die();
        }
        
        // Validate status
        $valid_statuses = array('pending', 'confirmed', 'completed', 'cancelled');
        if (empty($status) || !in_array($status, $valid_statuses)) {
            $error = 'Invalid status: ' . $status . '. Must be one of: ' . implode(', ', $valid_statuses);
            error_log('Error: ' . $error);
            wp_send_json_error(array(
                'message' => 'Invalid status. Please select a valid status.',
                'debug' => $error
            ));
            wp_die();
        }
        
        // Check if booking exists and is of the correct post type
        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE ID = %d",
            $booking_id
        ));
        
        if (!$booking) {
            $error = 'Booking not found in database. ID: ' . $booking_id;
            error_log('Error: ' . $error);
            wp_send_json_error(array(
                'message' => 'Booking not found. It may have been deleted.',
                'debug' => $error
            ));
            wp_die();
        }
        
        // Verify post type if needed (uncomment and modify if you have a specific post type)
        /*
        if ($booking->post_type !== 'your_booking_post_type') {
            $error = 'Invalid post type for booking. Expected: your_booking_post_type, Got: ' . $booking->post_type;
            error_log('Error: ' . $error);
            wp_send_json_error(array(
                'message' => 'Invalid booking type.',
                'debug' => $error
            ));
            wp_die();
        }
        */
        
        error_log('Found booking - ID: ' . $booking->ID . ', Type: ' . $booking->post_type . ', Status: ' . $booking->post_status);
        
        error_log('Booking found - Post Type: ' . $booking->post_type . ', Status: ' . $booking->post_status);
        
        // Get current meta for debugging
        $meta = get_post_meta($booking_id);
        error_log('Current booking meta: ' . print_r($meta, true));
        
        // Check if the status is actually changing
        $current_status = get_post_meta($booking_id, '_booking_status', true);
        if ($current_status === $status) {
            error_log('Status is already set to: ' . $status);
            wp_send_json_success(array(
                'message' => 'Status is already set to ' . ucfirst($status),
                'booking' => $this->get_single_booking_data($booking_id)
            ));
            wp_die();
        }
        
        // Map status to WordPress post status
        $status_mapping = array(
            'pending' => 'pending',
            'confirmed' => 'publish',  // 'publish' is the default status for confirmed bookings
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        );
        
        if (!array_key_exists($status, $status_mapping)) {
            wp_send_json_error(array('message' => 'Invalid status'));
            wp_die();
        }
        
        $wp_status = $status_mapping[$status];
        
        // Prepare post data for update
        $post_data = array(
            'ID' => $booking_id,
            'post_status' => $wp_status,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        );
        
        // Start transaction if supported
        $wpdb->query('START TRANSACTION');
        
        try {
            // Update post status
            remove_action('save_post_service_booking', array($this, 'save_booking_meta'), 10, 3);
            $post_updated = wp_update_post($post_data, true);
            add_action('save_post_service_booking', array($this, 'save_booking_meta'), 10, 3);
            
            if (is_wp_error($post_updated)) {
                throw new Exception('Failed to update post status: ' . $post_updated->get_error_message());
            }
            
            // Update booking status in meta
            $meta_updated = update_post_meta($booking_id, '_booking_status', $status);
            
            if ($meta_updated === false) {
                throw new Exception('Failed to update booking status in post meta');
            }
            
            // Add status change log
            $status_log = get_post_meta($booking_id, '_status_change_log', true);
            if (!is_array($status_log)) {
                $status_log = array();
            }
            
            $status_log[] = array(
                'status' => $status,
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id()
            );
            
            update_post_meta($booking_id, '_status_change_log', $status_log);
            
            // Commit the transaction
            $wpdb->query('COMMIT');
            
            error_log('Successfully updated booking status to: ' . $status);
            
        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Error updating booking status: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => 'Failed to update booking status. Please try again.',
                'debug' => $e->getMessage()
            ));
            wp_die();
        }
        
        if (is_wp_error($post_updated)) {
            wp_send_json_error(array('message' => $post_updated->get_error_message()));
            wp_die();
        }
        
        // Clear any caches that might be storing this data
        clean_post_cache($booking_id);
        
        // Get updated booking data
        $booking_data = $this->get_single_booking_data($booking_id);
        
        if (!$booking_data) {
            wp_send_json_error(array('message' => 'Failed to retrieve updated booking data'));
            wp_die();
        }
        
        // Log the update for debugging
        error_log('Booking ' . $booking_id . ' status updated to ' . $status . ' (WP Status: ' . $wp_status . ')');
        
        wp_send_json_success(array(
            'message' => 'Booking status updated successfully',
            'booking' => $booking_data
        ));
        wp_die();
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
        
        // Status labels and their categories
        $status_labels = array(
            'publish' => 'Ongoing',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'refunded' => 'Refunded',
            'failed' => 'Failed'
        );
        
        // Determine status category based on status, not payment
        $status_category = 'upcoming'; // Default category
        
        // Map statuses to categories
        $status_mapping = [
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'confirmed' => 'upcoming',
            'pending' => 'upcoming', // Treat pending as upcoming for display purposes
        ];
        
        // Get the category from mapping or use default
        $status_category = $status_mapping[strtolower($status)] ?? 'upcoming';
        
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

        // Get the current user's capabilities for debugging
        $current_user = wp_get_current_user();
        $user_can_edit = current_user_can('edit_posts');
        
        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'assigned-bookings-script',
            'assignedBookingsData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('update_booking_status'),
                'user_can_edit' => $user_can_edit,
                'user_roles' => !empty($current_user->roles) ? $current_user->roles : array(),
                'i18n' => array(
                    'confirm_complete' => __('Are you sure you want to mark this booking as completed?', 'payndle'),
                    'confirm_cancel' => __('Are you sure you want to cancel this booking? This action cannot be undone.', 'payndle'),
                    'error' => __('An error occurred. Please try again.', 'payndle'),
                    'success' => __('Booking updated successfully!', 'payndle')
                )
            )
        );
        
        // Add debug information to the page
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_add_inline_script('assigned-bookings-script', 
                'console.log("Assigned Bookings Debug - User Can Edit: ' . ($user_can_edit ? 'Yes' : 'No') . '");' .
                'console.log("Assigned Bookings Debug - User Roles: ' . (!empty($current_user->roles) ? implode(', ', $current_user->roles) : 'None') . '");'
            );
        }

        // Add inline styles for notices and status badges
        $custom_css = "
            /* Base Typography */
            .assigned-bookings-container {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, sans-serif;
                color: #0C1930;
                line-height: 1.5;
            }
            
            .assigned-bookings-container h2 {
                font-family: 'Inter', sans-serif;
                font-weight: 700;
                color: #0C1930;
                margin-bottom: 20px;
            }
            
            /* Status filter buttons */
            .status-filters {
                margin-bottom: 24px;
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .status-filter-btn {
                padding: 8px 16px;
                border: 1px solid #E5E7EB;
                background: #FFFFFF;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                font-family: 'Inter', sans-serif;
                color: #0C1930;
                transition: all 0.2s ease;
            }
            .status-filter-btn.active {
                background: #0C1930;
                color: #FFFFFF;
                border-color: #0C1930;
            }
            .status-filter-btn:hover:not(.active) {
                background: #F9FAFB;
                border-color: #D1D5DB;
            }
            
            /* Tables */
            .assigned-bookings-table {
                width: 100%;
                border-collapse: collapse;
                background: #FFFFFF;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .assigned-bookings-table th {
                background: #F9FAFB;
                color: #4B5563;
                font-weight: 600;
                text-align: left;
                padding: 12px 16px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                border-bottom: 1px solid #E5E7EB;
            }
            
            .assigned-bookings-table td {
                padding: 16px;
                border-bottom: 1px solid #E5E7EB;
                font-size: 14px;
                vertical-align: middle;
            }
            
            .assigned-bookings-table tr:last-child td {
                border-bottom: none;
            }
            
            .assigned-bookings-table tr:hover {
                background: #F9FAFB;
            }
            
            /* Action Buttons & Dropdowns */
            .action-dropdown {
                position: relative;
                display: inline-block;
            }
            
            .action-dropdown-toggle {
                background: #FFFFFF;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                padding: 0;
            }
            
            .action-dropdown-toggle:hover {
                background: #F9FAFB;
                border-color: #D1D5DB;
            }
            
            .action-dropdown-toggle .dashicons {
                color: #4B5563;
                width: 20px;
                height: 20px;
                font-size: 20px;
            }
            
            .action-dropdown-menu {
                position: absolute;
                right: 0;
                top: calc(100% + 4px);
                background: #FFFFFF;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                min-width: 200px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                z-index: 1000;
                display: none;
                padding: 8px 0;
                overflow: hidden;
                animation: fadeIn 0.15s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .action-item {
                display: flex;
                align-items: center;
                width: 100%;
                text-align: left;
                padding: 10px 16px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 14px;
                color: #0C1930;
                font-family: 'Inter', sans-serif;
                transition: all 0.15s ease;
                margin: 0;
                line-height: 1.5;
            }
            
            .action-item:hover {
                background: #F9FAFB;
                color: #64C493;
            }
            
            .action-item.complete-booking:hover {
                color: #065F46;
            }
            
            .action-item.cancel-booking:hover {
                color: #B91C1C;
            }
            
            .action-item .dashicons {
                margin-right: 10px;
                font-size: 16px;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }
            
            /* Status badges */
            .booking-notice {
                margin: 15px 0;
                position: relative;
                border-radius: 8px;
                overflow: hidden;
            }
            .notice-dismiss {
                text-decoration: none;
            }
            /* Status badges */
            .status-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 4px 12px;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 500;
                text-transform: capitalize;
                white-space: nowrap;
                font-family: 'Inter', sans-serif;
                letter-spacing: 0.02em;
                min-width: 80px;
                text-align: center;
                transition: all 0.2s ease;
            }
            
            /* Status change button */
            .change-status-btn {
                background: #64C493;
                color: white;
                border: none;
                border-radius: 8px;
                padding: 6px 12px;
                font-size: 13px;
                font-weight: 500;
                font-family: 'Inter', sans-serif;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                line-height: 1.5;
            }
            
            .change-status-btn:hover {
                background: #4FB07D;
                transform: translateY(-1px);
            }
            
            .change-status-btn .dashicons {
                margin-right: 6px;
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            
            /* Status category colors */
            .status-pending {
                background-color: #FEF3C7;
                color: #92400E;
                border: 1px solid #FDE68A;
            }
            
            .status-ongoing {
                background-color: #E0F2FE;
                color: #075985;
                border: 1px solid #BAE6FD;
            }
            
            .status-completed {
                background-color: #D1FAE5;
                color: #065F46;
                border: 1px solid #A7F3D0;
            }
            
            .status-cancelled {
                background-color: #FEE2E2;
                color: #B91C1C;
                border: 1px solid #FECACA;
                text-decoration: line-through;
            }
            
            .status-failed,
            .status-refunded {
                background-color: #F3F4F6;
                color: #4B5563;
                border: 1px solid #E5E7EB;
            }
            /* Payment status */
            .payment-status {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 500;
                text-transform: capitalize;
                font-family: 'Inter', sans-serif;
                min-width: 60px;
            }
            
            .payment-paid {
                background-color: #D1FAE5;
                color: #065F46;
                border: 1px solid #A7F3D0;
            }
            
            .payment-pending {
                background-color: #FEF3C7;
                color: #92400E;
                border: 1px solid #FDE68A;
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
                
                // Determine status category based on status
                $status_category = 'upcoming'; // Default category
                if ($status === 'completed') {
                    $status_category = 'completed';
                } elseif ($status === 'pending') {
                    $status_category = 'pending'; // Keep pending as its own category
                } elseif (in_array($status, ['cancelled', 'refunded', 'failed'])) {
                    $status_category = 'cancelled';
                }
                
                // Format the booking data
                $formatted_bookings[] = array(
                    'booking_id' => $post_id,
                    'status' => $status_labels[$status] ?? ucfirst($status),
                    'status_slug' => $status,
                    'status_category' => $status_category,
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

            <?php 
            // Create a fresh nonce for this request
            $nonce = wp_create_nonce('update_booking_status');
            
            if (empty($bookings)) : ?>
                <div class="empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <h3>No Bookings Found</h3>
                    <p>There are currently no bookings assigned to you. New bookings will appear here once assigned.</p>
                    <a href="#" class="button">
                        <span class="dashicons dashicons-update"></span>
                        Refresh Page
                    </a>
                </div>
            <?php else : 
                // Output the nonce as a data attribute on the table
                ?>
                <div id="assigned-bookings-table" data-nonce="<?php echo esc_attr($nonce); ?>">
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
                                <th>Change Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bookings as $booking) : 
                            $status_class = strtolower($booking['status'] ?? 'pending');
                            $status_category = $booking['status_category'] ?? 'pending';
                        ?>
                            <tr class="status-<?php echo esc_attr($status_class); ?>" 
                                data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>"
                                data-status="<?php echo esc_attr($status_class); ?>"
                                data-status-category="<?php echo esc_attr($status_category); ?>">
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
                                <td class="status-actions" data-label="Change Status">
                                    <select class="status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                        <option value="pending" <?php selected($status_class, 'pending'); ?>>Pending</option>
                                        <option value="confirmed" <?php selected($status_class, 'confirmed'); ?>>Confirmed</option>
                                        <option value="completed" <?php selected($status_class, 'completed'); ?>>Completed</option>
                                        <option value="cancelled" <?php selected($status_class, 'cancelled'); ?>>Cancelled</option>
                                    </select>
                                    <span class="status-saving" style="display: none;">Saving...</span>
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
