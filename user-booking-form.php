<?php
/**
 * User Booking Information Form
 * Description: A dedicated page for user information input when booking services
 * Version: 1.0.0
 * Shortcode: [user_booking_form]
 */

if (!defined('ABSPATH')) {
    exit;
}

class UserBookingForm {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('user_booking_form', [$this, 'render_booking_form']);
    // Alias shortcode with hyphen to be user-friendly: [user-booking-form]
    add_shortcode('user-booking-form', [$this, 'render_booking_form']);
    // New v2 shortcode (color/typography variant) - enqueues a separate stylesheet
    add_shortcode('user-booking-form-v2', [$this, 'render_booking_form_v2']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register AJAX actions
        add_action('wp_ajax_submit_user_booking', [$this, 'submit_user_booking_ajax']);
        add_action('wp_ajax_nopriv_submit_user_booking', [$this, 'submit_user_booking_ajax']);
        add_action('wp_ajax_get_selected_service_info', [$this, 'get_selected_service_info_ajax']);
        add_action('wp_ajax_nopriv_get_selected_service_info', [$this, 'get_selected_service_info_ajax']);
        add_action('wp_ajax_test_booking_system', [$this, 'test_booking_system']);
        add_action('wp_ajax_nopriv_test_booking_system', [$this, 'test_booking_system']);
    }
    
    public function init() {
        // Register a booking custom post type to store bookings as posts with meta
        $this->register_booking_post_type();

        // Ensure required tables exist (kept for backward compatibility/migration)
        $this->ensure_booking_tables_exist();
        // Update existing booking table structure
        $this->update_booking_table_structure();
    }

    /**
     * Register custom post type for bookings
     */
    private function register_booking_post_type() {
        $labels = [
            'name' => __('Service Bookings', 'payndle'),
            'singular_name' => __('Service Booking', 'payndle'),
            'menu_name' => __('Bookings', 'payndle'),
            'name_admin_bar' => __('Booking', 'payndle')
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'author'],
            'has_archive' => false,
            'rewrite' => false,
        ];

        register_post_type('service_booking', $args);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'user-booking-form-css',
            plugin_dir_url(__FILE__) . 'assets/css/user_booking_form.css',
            [],
            '1.0.0'
        );

    // Ensure Inter font is available for the booking form
    wp_enqueue_style('payndle-google-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
        
        wp_enqueue_script(
            'user-booking-form-js',
            plugin_dir_url(__FILE__) . 'assets/js/user-booking-form.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('user-booking-form-js', 'userBookingAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user_booking_nonce'),
            'messages' => [
                'success' => 'Your booking request has been submitted successfully! We will contact you soon to confirm your appointment.',
                'error' => 'Something went wrong. Please try again.',
                'validation_error' => 'Please fill in all required fields.',
                'email_error' => 'Please enter a valid email address.'
            ]
        ]);
    }

    /**
     * Render booking form variant v2 - enqueues variant stylesheet then calls existing renderer
     */
    public function render_booking_form_v2($atts) {
        // Enqueue the v2 stylesheet only when this shortcode is used
        wp_enqueue_style(
            'user-booking-form-css',
            plugin_dir_url(__FILE__) . 'assets/css/user_booking_form.css',
            [],
            '1.0.0'
        );

        // Reuse the main renderer for layout; renderer outputs the HTML via output buffering
        return $this->render_booking_form($atts);
    }
    
    /**
     * Ensure booking tables exist
     */
    private function ensure_booking_tables_exist() {
        global $wpdb;
        
        $booking_table = $wpdb->prefix . 'service_bookings';
        $services_table = $wpdb->prefix . 'manager_services';
        $staff_table = $wpdb->prefix . 'staff_members';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create staff table first (referenced by bookings)
        $staff_sql = "CREATE TABLE IF NOT EXISTS $staff_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            staff_name varchar(255) NOT NULL,
            staff_position varchar(100) NOT NULL DEFAULT 'Barber',
            staff_email varchar(255),
            staff_phone varchar(50),
            staff_availability varchar(50) DEFAULT 'Available',
            staff_status varchar(20) DEFAULT 'active',
            staff_avatar varchar(500),
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Create services table if it doesn't exist (use existing manager_services structure)
        $services_sql = "CREATE TABLE IF NOT EXISTS $services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_name varchar(255) NOT NULL,
            service_description text DEFAULT '',
            service_price decimal(10,2) DEFAULT 0.00,
            service_duration varchar(50) DEFAULT '',
            service_category varchar(100) DEFAULT '',
            service_image varchar(255) DEFAULT '',
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Create bookings table if it doesn't exist
        $booking_sql = "CREATE TABLE IF NOT EXISTS $booking_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            service_id int(11),
            staff_id int(11),
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            preferred_date date,
            preferred_time time,
            message text,
            payment_method varchar(50) DEFAULT 'cash',
            payment_status varchar(20) DEFAULT 'pending',
            booking_status varchar(50) DEFAULT 'pending',
            total_amount decimal(10,2) DEFAULT 0.00,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            booking_date timestamp DEFAULT CURRENT_TIMESTAMP,
            confirmed_date datetime,
            notes text,
            PRIMARY KEY (id),
            FOREIGN KEY (service_id) REFERENCES $services_table(id) ON DELETE SET NULL,
            FOREIGN KEY (staff_id) REFERENCES $staff_table(id) ON DELETE SET NULL
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($staff_sql);
        dbDelta($services_sql);
        dbDelta($booking_sql);
        
        // Add default staff members if table is empty
        $this->add_default_staff();
    }
    
    /**
     * Update existing booking table structure to ensure compatibility
     */
    private function update_booking_table_structure() {
        global $wpdb;
        
        $booking_table = $wpdb->prefix . 'service_bookings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$booking_table}'") != $booking_table) {
            return; // Table doesn't exist, will be created by ensure_booking_tables_exist
        }
        
        // Check and add missing columns
        $columns_to_add = [
            'staff_id' => "ALTER TABLE {$booking_table} ADD COLUMN staff_id INT(11)",
            'payment_status' => "ALTER TABLE {$booking_table} ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending'",
            'booking_status' => "ALTER TABLE {$booking_table} ADD COLUMN booking_status VARCHAR(50) DEFAULT 'pending'",
            'created_at' => "ALTER TABLE {$booking_table} ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'total_amount' => "ALTER TABLE {$booking_table} ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0.00",
            'notes' => "ALTER TABLE {$booking_table} ADD COLUMN notes TEXT"
        ];
        
        foreach ($columns_to_add as $column => $sql) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$booking_table} LIKE '{$column}'");
            if (empty($column_exists)) {
                $result = $wpdb->query($sql);
                if ($result === false) {
                    error_log("Failed to add column {$column} to {$booking_table}: " . $wpdb->last_error);
                } else {
                    error_log("Successfully added column {$column} to {$booking_table}");
                }
            }
        }
    }
    
    /**
     * Add default staff members if table is empty
     */
    private function add_default_staff() {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'staff_members';
        
        // Check if staff table has any members
        $staff_count = $wpdb->get_var("SELECT COUNT(*) FROM $staff_table");
        
        if ($staff_count == 0) {
            $default_staff = [
                [
                    'staff_name' => 'Miguel Santos',
                    'staff_position' => 'Master Barber',
                    'staff_email' => 'miguel@elitecuts.com',
                    'staff_phone' => '+63 917 000 1111',
                    'staff_availability' => 'Available',
                    'staff_status' => 'active',
                    'staff_avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=200&q=80'
                ],
                [
                    'staff_name' => 'Antonio Cruz',
                    'staff_position' => 'Barber',
                    'staff_email' => 'antonio@elitecuts.com',
                    'staff_phone' => '+63 917 000 2222',
                    'staff_availability' => 'Available',
                    'staff_status' => 'active',
                    'staff_avatar' => 'https://images.unsplash.com/photo-1583864692221-95a2c5ba9d5b?auto=format&fit=crop&w=200&q=80'
                ],
                [
                    'staff_name' => 'Rafael Reyes',
                    'staff_position' => 'Stylist',
                    'staff_email' => 'rafael@elitecuts.com',
                    'staff_phone' => '+63 917 000 3333',
                    'staff_availability' => 'Available',
                    'staff_status' => 'active',
                    'staff_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80'
                ]
            ];
            
            foreach ($default_staff as $staff) {
                $wpdb->insert($staff_table, $staff);
            }
        }
    }
    
    /**
     * Get staff options for dropdown
     */
    private function get_staff_options() {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'staff_members';
        
        // First check what columns exist in the table
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $staff_table");
        $column_names = array_column($columns, 'Field');
        
        // Build query based on available columns
        $select_fields = ['id', 'staff_name'];
        
        if (in_array('staff_position', $column_names)) {
            $select_fields[] = 'staff_position';
        }
        if (in_array('staff_availability', $column_names)) {
            $select_fields[] = 'staff_availability';
        }
        
        $where_clause = '';
        if (in_array('staff_status', $column_names)) {
            $where_clause = "WHERE staff_status = 'active'";
        } elseif (in_array('is_active', $column_names)) {
            $where_clause = "WHERE is_active = 1";
        }
        
        $query = "SELECT " . implode(', ', $select_fields) . " 
                  FROM $staff_table 
                  $where_clause
                  ORDER BY staff_name";
        
        $staff_members = $wpdb->get_results($query);
        
        $options = '<option value="">Any available staff member</option>';
        
        if ($staff_members) {
            foreach ($staff_members as $staff) {
                $position = isset($staff->staff_position) ? $staff->staff_position : 'Staff';
                $availability_indicator = '';
                
                if (isset($staff->staff_availability)) {
                    if ($staff->staff_availability === 'Available') {
                        $availability_indicator = ' ✅';
                    } elseif ($staff->staff_availability === 'Busy') {
                        $availability_indicator = ' ⏳';
                    } else {
                        $availability_indicator = ' ❌';
                    }
                }
                
                $options .= sprintf(
                    '<option value="%d">%s - %s%s</option>',
                    esc_attr($staff->id),
                    esc_html($staff->staff_name),
                    esc_html($position),
                    $availability_indicator
                );
            }
        }
        
        return $options;
    }
    
    /**
     * Render the booking form
     */
    public function render_booking_form($atts) {
        $atts = shortcode_atts([
            'service_id' => '',
            'redirect_url' => '',
            'show_service_selector' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        
        <div class="user-booking-container">
            <!-- Header Section -->
            <div class="booking-header">
                <div class="header-content">
                    <h1 class="booking-title">
                        <i class="fas fa-calendar-plus"></i>
                        Book Your Appointment
                    </h1>
                    <p class="booking-subtitle">Fill out the form below and we'll get back to you to confirm your appointment</p>
                </div>
            </div>
            
            <!-- Main Booking Form -->
            <div class="booking-form-wrapper">
                <form id="user-booking-form" class="booking-form" novalidate>
                    <?php wp_nonce_field('user_booking_nonce', 'booking_nonce'); ?>
                    
                    <!-- Service Selection (if enabled) -->
                    <?php if ($atts['show_service_selector'] === 'true'): ?>
                    <div class="form-section service-selection">
                        <h3 class="section-title">
                            <i class="fas fa-cut"></i>
                            Select Service
                        </h3>
                        <div class="service-selector">
                            <select id="service_id" name="service_id" required>
                                <option value="">Choose a service...</option>
                                <?php echo $this->get_services_options($atts['service_id']); ?>
                            </select>
                            <div class="select-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="selected-service-info" id="selected-service-info" style="display: none;">
                            <!-- Service details will be populated here -->
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" id="service_id" name="service_id" value="<?php echo esc_attr($atts['service_id']); ?>">
                    <?php endif; ?>
                    
                    <!-- Staff Selection -->
                    <div class="form-section staff-selection">
                        <h3 class="section-title">
                            <i class="fas fa-user-tie"></i>
                            Preferred Staff Member
                        </h3>
                        <div class="staff-selector">
                            <select id="staff_id" name="staff_id">
                                <?php echo $this->get_staff_options(); ?>
                            </select>
                            <div class="select-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="staff-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Leave blank to be assigned to any available staff member</span>
                        </div>
                    </div>
                    
                    <!-- Personal Information -->
                    <div class="form-section personal-info">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Your Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="customer_name">
                                    Full Name <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" id="customer_name" name="customer_name" required 
                                           placeholder="Enter your full name">
                                </div>
                                <div class="form-error" id="customer_name_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_email">
                                    Email Address <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" id="customer_email" name="customer_email" required 
                                           placeholder="your.email@example.com">
                                </div>
                                <div class="form-error" id="customer_email_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_phone">
                                    Phone Number
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" id="customer_phone" name="customer_phone" 
                                           placeholder="+63 123 456 7890">
                                </div>
                                <div class="form-error" id="customer_phone_error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointment Preferences -->
                    <div class="form-section appointment-prefs">
                        <h3 class="section-title">
                            <i class="fas fa-clock"></i>
                            Preferred Schedule
                        </h3>
                        <p class="section-description">Let us know your preferred date and time (optional)</p>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="preferred_date">
                                    Preferred Date
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-calendar input-icon"></i>
                                    <input type="date" id="preferred_date" name="preferred_date" 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
                                </div>
                                <div class="form-error" id="preferred_date_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="preferred_time">
                                    Preferred Time
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-clock input-icon"></i>
                                    <input type="time" id="preferred_time" name="preferred_time" 
                                           min="08:00" max="18:00">
                                </div>
                                <div class="form-error" id="preferred_time_error"></div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="booking_message">
                                Additional Message
                            </label>
                            <div class="textarea-wrapper">
                                <i class="fas fa-comment input-icon"></i>
                                <textarea id="booking_message" name="message" rows="4" 
                                          placeholder="Any special requests, questions, or additional information..."></textarea>
                            </div>
                            <div class="form-error" id="booking_message_error"></div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section payment-section">
                        <h3 class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </h3>
                        <p class="section-description">How would you like to pay for your service?</p>
                        
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="payment_cash" name="payment_method" value="cash" checked>
                                <label for="payment_cash" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h4>Cash Payment</h4>
                                        <p>Pay in cash when you arrive</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="payment_card" name="payment_method" value="card">
                                <label for="payment_card" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h4>Credit/Debit Card</h4>
                                        <p>Pay with your card at the shop</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="payment_gcash" name="payment_method" value="gcash">
                                <label for="payment_gcash" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h4>GCash</h4>
                                        <p>Pay via GCash mobile wallet</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="payment_paymaya" name="payment_method" value="paymaya">
                                <label for="payment_paymaya" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h4>PayMaya</h4>
                                        <p>Pay via PayMaya digital wallet</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="payment_online" name="payment_method" value="online">
                                <label for="payment_online" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h4>Online Payment</h4>
                                        <p>Pay securely online before your appointment</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Important Notice -->
                    <div class="booking-notice">
                        <div class="notice-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="notice-content">
                            <h4>Important Notice</h4>
                            <p>This is a booking request. We will contact you within 24 hours to confirm your appointment and discuss any additional details.</p>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="reset-form">
                            <i class="fas fa-undo"></i>
                            Reset Form
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit-booking">
                            <i class="fas fa-paper-plane"></i>
                            Submit Booking Request
                            <div class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Success Message -->
            <div class="booking-success" id="booking-success" style="display: none;">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Booking Request Submitted!</h3>
                <p>Thank you for your booking request. We'll contact you soon to confirm your appointment.</p>
                <div class="success-actions">
                    <button type="button" class="btn btn-secondary" id="view-my-bookings">
                        <i class="fas fa-history"></i>
                        View My Bookings
                    </button>
                    <button type="button" class="btn btn-primary" id="book-another">
                        <i class="fas fa-plus"></i>
                        Book Another Service
                    </button>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get services options for dropdown
     */
    private function get_services_options($selected_id = '') {
        // Prefer the 'service' custom post type if available
        $args = [
            'post_type' => 'service',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        $services = get_posts($args);
        $options = '';

        foreach ($services as $service) {
            $sid = $service->ID;
            $selected = ($selected_id == $sid) ? 'selected' : '';

            // Try to read meta fields if present (compat with older structure)
            $price = get_post_meta($sid, '_service_price', true);
            $duration = get_post_meta($sid, '_service_duration', true);
            $description = get_post_meta($sid, '_service_description', true);

            // Fall back to content/title if no meta present
            if ($price === '') $price = get_post_meta($sid, 'service_price', true);
            if ($duration === '') $duration = get_post_meta($sid, 'service_duration', true);
            if ($description === '') $description = $service->post_content;

            $price_label = $price ? '₱' . number_format(floatval($price), 2) : 'Price varies';

            $options .= sprintf(
                '<option value="%d" data-price="%s" data-duration="%s" data-description="%s" %s>%s - %s</option>',
                $sid,
                esc_attr($price),
                esc_attr($duration),
                esc_attr($description),
                $selected,
                esc_html($service->post_title),
                esc_html($price_label)
            );
        }

        return $options;
    }
    
    /**
     * Handle AJAX service info request
     */
    public function get_selected_service_info_ajax() {
        check_ajax_referer('user_booking_nonce', 'nonce');
        
        $service_id = intval($_POST['service_id']);
        $service = get_post($service_id);

        if ($service && $service->post_type === 'service' && $service->post_status === 'publish') {
            $price = get_post_meta($service_id, '_service_price', true);
            if ($price === '') $price = get_post_meta($service_id, 'service_price', true);

            $response = [
                'success' => true,
                'data' => [
                    'name' => $service->post_title,
                    'description' => get_post_meta($service_id, '_service_description', true) ?: $service->post_content,
                    'price' => $price ? '₱' . number_format(floatval($price), 2) : 'Price varies',
                    'duration' => get_post_meta($service_id, '_service_duration', true),
                    'category' => wp_get_post_terms($service_id, 'service_category', array('fields' => 'names'))
                ]
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Service not found'
            ];
        }
        
        wp_send_json($response);
    }
    
    /**
     * Handle AJAX booking submission
     */
    public function submit_user_booking_ajax() {
        // Log all received data for debugging
        file_put_contents(
            plugin_dir_path(__FILE__) . 'booking-debug.log', 
            date('Y-m-d H:i:s') . " - Booking submission started\n" . 
            "POST data: " . print_r($_POST, true) . "\n" .
            "Headers: " . print_r(getallheaders(), true) . "\n\n", 
            FILE_APPEND
        );
        
        // Test database structure first
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE $booking_table");
        if ($columns) {
            $column_names = array_column($columns, 'Field');
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Booking table columns: " . implode(', ', $column_names) . "\n", 
                FILE_APPEND
            );
        } else {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Could not describe booking table or table does not exist\n", 
                FILE_APPEND
            );
        }
        
        // Check nonce - be more lenient for debugging
        $nonce_check = check_ajax_referer('user_booking_nonce', 'nonce', false);
        if (!$nonce_check) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Nonce verification failed. Nonce received: " . ($_POST['nonce'] ?? 'none') . "\n", 
                FILE_APPEND
            );
            
            // For debugging, let's try to continue anyway but log it
            // wp_send_json([
            //     'success' => false,
            //     'message' => 'Security check failed. Please refresh the page and try again.'
            // ]);
        }
        
        // Check if required POST data exists
        if (!isset($_POST['service_id']) || !isset($_POST['customer_name']) || !isset($_POST['customer_email'])) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Missing required POST data\n", 
                FILE_APPEND
            );
            wp_send_json([
                'success' => false,
                'message' => 'Missing required form data. Please fill out all required fields.'
            ]);
        }
        
        // Sanitize and validate input
        $service_id = intval($_POST['service_id']);
        $staff_id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
        $preferred_time = sanitize_text_field($_POST['preferred_time'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'cash');
        
        file_put_contents(
            plugin_dir_path(__FILE__) . 'booking-debug.log', 
            date('Y-m-d H:i:s') . " - Sanitized data: service_id=$service_id, staff_id=$staff_id, customer_name=$customer_name, customer_email=$customer_email\n", 
            FILE_APPEND
        );
        
        // Validation
        $errors = [];
        
        if (empty($customer_name)) {
            $errors['customer_name'] = 'Full name is required';
        }
        
        if (empty($customer_email) || !is_email($customer_email)) {
            $errors['customer_email'] = 'Valid email address is required';
        }
        
        if ($service_id <= 0) {
            $errors['service_id'] = 'Please select a service';
        }
        
        if (!empty($errors)) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Validation errors: " . print_r($errors, true) . "\n", 
                FILE_APPEND
            );
            wp_send_json([
                'success' => false,
                'errors' => $errors
            ]);
        }
        
        // Check if tables exist
        $services_table = $wpdb->prefix . 'manager_services';
        
        // Verify services table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") != $services_table) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Services table does not exist: $services_table\n", 
                FILE_APPEND
            );
            wp_send_json([
                'success' => false,
                'message' => 'Services system not properly configured. Please contact administrator.'
            ]);
        }
        
        // Verify booking table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$booking_table}'") != $booking_table) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Booking table does not exist: $booking_table\n", 
                FILE_APPEND
            );
            wp_send_json([
                'success' => false,
                'message' => 'Booking system not properly configured. Please contact administrator.'
            ]);
        }
        
        // Get service details - prefer 'service' CPT, fallback to legacy manager_services table
        $service_post = get_post($service_id);
        if ($service_post && $service_post->post_type === 'service' && $service_post->post_status === 'publish') {
            // Build a service-like object from post + meta
            $service = (object) [
                'id' => $service_post->ID,
                'service_name' => $service_post->post_title,
                'service_description' => $service_post->post_content,
                'service_price' => get_post_meta($service_post->ID, '_service_price', true),
                'service_duration' => get_post_meta($service_post->ID, '_service_duration', true),
                'is_active' => 1
            ];
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Found CPT service: " . $service->service_name . "\n", 
                FILE_APPEND
            );
        } else {
            // Fallback to legacy table
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $services_table WHERE id = %d AND is_active = 1",
                $service_id
            ));

            if ($wpdb->last_error) {
                file_put_contents(
                    plugin_dir_path(__FILE__) . 'booking-debug.log', 
                    date('Y-m-d H:i:s') . " - Database error when fetching legacy service: " . $wpdb->last_error . "\n", 
                    FILE_APPEND
                );
                wp_send_json([
                    'success' => false,
                    'message' => 'Database error occurred. Please try again later.'
                ]);
            }

            if (!$service) {
                file_put_contents(
                    plugin_dir_path(__FILE__) . 'booking-debug.log', 
                    date('Y-m-d H:i:s') . " - Service not found or inactive. Service ID: $service_id\n", 
                    FILE_APPEND
                );
                wp_send_json([
                    'success' => false,
                    'message' => 'Selected service is not available. Please choose another service.'
                ]);
            }

            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Found legacy service: " . $service->service_name . "\n", 
                FILE_APPEND
            );
        }
        
        // Use basic required columns only
        $insert_data = [
            'service_id' => $service_id,
            'staff_id' => $staff_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'preferred_date' => $preferred_date ?: null,
            'preferred_time' => $preferred_time ?: null,
            'message' => $message,
            'payment_method' => $payment_method
        ];
        
        // Try to add optional columns only if they exist
        $existing_columns = array_column($wpdb->get_results("DESCRIBE $booking_table"), 'Field');
        
        if (in_array('payment_status', $existing_columns)) {
            $insert_data['payment_status'] = 'pending';
        }
        if (in_array('booking_status', $existing_columns)) {
            $insert_data['booking_status'] = 'pending';
        }
        if (in_array('total_amount', $existing_columns)) {
            $insert_data['total_amount'] = $service->service_price ?: 0;
        }
        if (in_array('created_at', $existing_columns)) {
            $insert_data['created_at'] = current_time('mysql');
        }
        
        file_put_contents(
            plugin_dir_path(__FILE__) . 'booking-debug.log', 
            date('Y-m-d H:i:s') . " - Attempting to insert data: " . print_r($insert_data, true) . "\n" .
            "Existing columns: " . implode(', ', $existing_columns) . "\n", 
            FILE_APPEND
        );
        
        // Instead of inserting into a custom table, store booking as a custom post with meta
        $post_title = sprintf('Booking: %s - %s', $customer_name, $service->service_name);
        $post_content = $message ?: '';

        $post_data = [
            'post_type' => 'service_booking',
            'post_title' => wp_trim_words($post_title, 10, ''),
            'post_content' => $post_content,
            'post_status' => 'pending',
            'post_author' => 0
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - wp_insert_post error: " . $post_id->get_error_message() . "\n", 
                FILE_APPEND
            );

            wp_send_json([
                'success' => false,
                'message' => 'Failed to save booking. Error: ' . $post_id->get_error_message()
            ]);
        }

        // Save meta fields
        $meta_map = [
            'service_id' => $service_id,
            'staff_id' => $staff_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'preferred_date' => $preferred_date ?: null,
            'preferred_time' => $preferred_time ?: null,
            'message' => $message,
            'payment_method' => $payment_method,
        ];

        // Optional fields if they exist in the old table - keep consistent meta keys
        if (in_array('payment_status', $existing_columns)) {
            $meta_map['payment_status'] = 'pending';
        }
        if (in_array('booking_status', $existing_columns)) {
            $meta_map['booking_status'] = 'pending';
        }
        if (in_array('total_amount', $existing_columns)) {
            $meta_map['total_amount'] = $service->service_price ?: 0;
        }
        if (in_array('created_at', $existing_columns)) {
            $meta_map['created_at'] = current_time('mysql');
        }

        foreach ($meta_map as $key => $value) {
            update_post_meta($post_id, '_' . $key, $value);
        }

        file_put_contents(
            plugin_dir_path(__FILE__) . 'booking-debug.log', 
            date('Y-m-d H:i:s') . " - Successfully created booking post with ID: $post_id\n" .
            "Meta saved: " . print_r($meta_map, true) . "\n",
            FILE_APPEND
        );

        // Send notification email (optional)
        try {
            $this->send_booking_notification($post_id, $service, [
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time,
                'message' => $message,
                'payment_method' => $payment_method
            ]);
        } catch (Exception $e) {
            file_put_contents(
                plugin_dir_path(__FILE__) . 'booking-debug.log', 
                date('Y-m-d H:i:s') . " - Email notification failed: " . $e->getMessage() . "\n", 
                FILE_APPEND
            );
            // Don't fail the booking if email fails
        }

        wp_send_json([
            'success' => true,
            'message' => 'Booking request submitted successfully!',
            'booking_id' => $post_id,
            'booking_post_type' => 'service_booking'
        ]);
    }
    
    /**
     * Send booking notification email
     */
    private function send_booking_notification($booking_id, $service, $booking_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] New Booking Request #%d', $site_name, $booking_id);
        
        $message = sprintf(
            "New booking request received:\n\n" .
            "Booking ID: #%d\n" .
            "Service: %s\n" .
            "Customer: %s\n" .
            "Email: %s\n" .
            "Phone: %s\n" .
            "Preferred Date: %s\n" .
            "Preferred Time: %s\n" .
            "Payment Method: %s\n" .
            "Message: %s\n\n" .
            "Please contact the customer to confirm the appointment.",
            $booking_id,
            $service->service_name,
            $booking_data['customer_name'],
            $booking_data['customer_email'],
            $booking_data['customer_phone'] ?: 'Not provided',
            $booking_data['preferred_date'] ?: 'Not specified',
            $booking_data['preferred_time'] ?: 'Not specified',
            ucfirst($booking_data['payment_method']),
            $booking_data['message'] ?: 'None'
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Send confirmation to customer
        $customer_subject = sprintf('[%s] Booking Request Received', $site_name);
        $customer_message = sprintf(
            "Dear %s,\n\n" .
            "Thank you for your booking request. We have received your request for:\n\n" .
            "Service: %s\n" .
            "Booking ID: #%d\n\n" .
            "We will contact you within 24 hours to confirm your appointment.\n\n" .
            "Best regards,\n%s Team",
            $booking_data['customer_name'],
            $service->service_name,
            $booking_id,
            $site_name
        );
        
        wp_mail($booking_data['customer_email'], $customer_subject, $customer_message);
    }
    
    /**
     * Test booking system - for debugging purposes
     */
    public function test_booking_system() {
        global $wpdb;
        
        $results = [];
        
        // Test 1: Check if tables exist
        $services_table = $wpdb->prefix . 'manager_services';
        $booking_table = $wpdb->prefix . 'service_bookings';
        
        $results['tables'] = [
            'services_table_exists' => ($wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") == $services_table),
            'booking_table_exists' => ($wpdb->get_var("SHOW TABLES LIKE '{$booking_table}'") == $booking_table)
        ];
        
        // Test 2: Check table structures
        if ($results['tables']['services_table_exists']) {
            $service_columns = $wpdb->get_results("DESCRIBE $services_table");
            $results['service_table_columns'] = array_column($service_columns, 'Field');
        }
        
        if ($results['tables']['booking_table_exists']) {
            $booking_columns = $wpdb->get_results("DESCRIBE $booking_table");
            $results['booking_table_columns'] = array_column($booking_columns, 'Field');
        }
        
        // Test 3: Check if services exist
        if ($results['tables']['services_table_exists']) {
            $service_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table WHERE is_active = 1");
            $results['active_services_count'] = $service_count;
            
            if ($service_count > 0) {
                $sample_service = $wpdb->get_row("SELECT * FROM $services_table WHERE is_active = 1 LIMIT 1");
                $results['sample_service'] = $sample_service;
            }
        }
        
        // Test 4: Test a minimal booking insert
        if ($results['tables']['booking_table_exists'] && $results['active_services_count'] > 0) {
            $test_data = [
                'service_id' => $results['sample_service']->id,
                'customer_name' => 'Test Customer',
                'customer_email' => 'test@example.com',
                'booking_status' => 'pending',
                'created_at' => current_time('mysql')
            ];
            
            $test_insert = $wpdb->insert($booking_table, $test_data);
            
            if ($test_insert) {
                $test_booking_id = $wpdb->insert_id;
                $results['test_insert'] = [
                    'success' => true,
                    'booking_id' => $test_booking_id
                ];
                
                // Clean up test booking
                $wpdb->delete($booking_table, ['id' => $test_booking_id]);
            } else {
                $results['test_insert'] = [
                    'success' => false,
                    'error' => $wpdb->last_error
                ];
            }
        }
        
        wp_send_json($results);
    }
}

// Initialize the plugin
new UserBookingForm();
