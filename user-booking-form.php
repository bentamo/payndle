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
        // Main shortcodes now use v3 wizard interface
        add_shortcode('user_booking_form', [$this, 'render_booking_form_v3']);
        add_shortcode('user-booking-form', [$this, 'render_booking_form_v3']);
        
        // Legacy shortcodes for backward compatibility
        add_shortcode('user_booking_form_legacy', [$this, 'render_booking_form_legacy']);
        add_shortcode('user-booking-form-legacy', [$this, 'render_booking_form_legacy']);
        add_shortcode('user-booking-form-v2', [$this, 'render_booking_form_v2']);
        
        // V3 explicit shortcodes (now same as main)
        add_shortcode('user_booking_form_v3', [$this, 'render_booking_form_v3']);
        add_shortcode('user-booking-form-v3', [$this, 'render_booking_form_v3']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register AJAX actions
        add_action('wp_ajax_submit_user_booking', [$this, 'submit_user_booking_ajax']);
        add_action('wp_ajax_nopriv_submit_user_booking', [$this, 'submit_user_booking_ajax']);
        add_action('wp_ajax_submit_user_booking_v3', [$this, 'submit_user_booking_v3']);
        add_action('wp_ajax_nopriv_submit_user_booking_v3', [$this, 'submit_user_booking_v3']);
        add_action('wp_ajax_get_selected_service_info', [$this, 'get_selected_service_info_ajax']);
        add_action('wp_ajax_nopriv_get_selected_service_info', [$this, 'get_selected_service_info_ajax']);
        add_action('wp_ajax_test_booking_system', [$this, 'test_booking_system']);
        add_action('wp_ajax_nopriv_test_booking_system', [$this, 'test_booking_system']);

        // Staff lookup for selected service (used by booking forms)
        add_action('wp_ajax_get_staff_for_service', [$this, 'get_staff_for_service']);
        add_action('wp_ajax_nopriv_get_staff_for_service', [$this, 'get_staff_for_service']);

    // Availability check for per-service schedule (used by v3 schedule step)
    add_action('wp_ajax_check_schedule_availability_v3', [$this, 'check_schedule_availability_v3']);
    add_action('wp_ajax_nopriv_check_schedule_availability_v3', [$this, 'check_schedule_availability_v3']);

        // timetable shortcode moved out of booking form
    }

    // timetable implementation moved to separate file (staff-timetable.php)
    
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
        $css_ver = @filemtime(plugin_dir_path(__FILE__) . 'assets/css/user_booking_form.css') ?: '1.0.1';
        wp_enqueue_style(
            'user-booking-form-css',
            plugin_dir_url(__FILE__) . 'assets/css/user_booking_form.css',
            [],
            $css_ver
        );

    // Ensure Inter font is available for the booking form
    wp_enqueue_style('payndle-google-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
        
        $js_ver = @filemtime(plugin_dir_path(__FILE__) . 'assets/js/user-booking-form.js') ?: '1.0.1';
        wp_enqueue_script(
            'user-booking-form-js',
            plugin_dir_url(__FILE__) . 'assets/js/user-booking-form.js',
            ['jquery'],
            $js_ver,
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

        // Localize script for v3 AJAX (reusing same data but different object name)
        wp_localize_script('user-booking-form-js', 'userBookingV3', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user_booking_nonce'), // Use same nonce for compatibility
            'messages' => [
                'saved' => __('Booking saved. We will contact you shortly.', 'payndle'),
                'error' => __('There was a problem. Please try again.', 'payndle')
            ]
        ]);
    }

    /**
     * Render booking form variant v2 - enqueues variant stylesheet then calls legacy renderer
     */
    public function render_booking_form_v2($atts) {
        // Enqueue the v2 stylesheet only when this shortcode is used
        wp_enqueue_style(
            'user-booking-form-css',
            plugin_dir_url(__FILE__) . 'assets/css/user_booking_form.css',
            [],
            '1.0.0'
        );

        // Reuse the legacy renderer for layout; renderer outputs the HTML via output buffering
        return $this->render_booking_form_legacy($atts);
    }

    /**
     * Render booking form v3 - lightweight 4-step wizard with modern styling
     */
    public function render_booking_form_v3($atts) {
        $atts = shortcode_atts(['show_service_selector' => 'true'], $atts);

        ob_start();
        ?>
        <div class="ubf-v3-container">
            <div class="ubf-v3-header">
                <h1 class="ubf-v3-title">Book Your Appointment</h1>
                <p class="ubf-v3-sub">Complete each step to book your appointment</p>
            </div>

            <div class="ubf-v3-form-wrapper">
                <div class="ubf-v3-stepper">
                    <div class="ubf-steps">
                        <div class="ubf-step" data-step="1"><div class="num">1</div><div class="label">Select Service</div></div>
                        <div class="ubf-step" data-step="2"><div class="num">2</div><div class="label">Personal</div></div>
                        <div class="ubf-step" data-step="3"><div class="num">3</div><div class="label">Schedule</div></div>
                        <div class="ubf-step" data-step="4"><div class="num">4</div><div class="label">Payment</div></div>
                    </div>
                    <div class="ubf-progress"><div class="ubf-progress-fill" style="width:0%"></div></div>
                </div>

                <form id="user-booking-form-v3" class="ubf-v3-form" novalidate data-tax-rate="0">
                    <?php wp_nonce_field('user_booking_nonce', 'booking_nonce'); ?>

                    <div class="ubf-form-step" data-step="1">
                        <h3 class="section-title">Select Service</h3>
                        <?php if ($atts['show_service_selector'] === 'true'): ?>
                        <div class="ubf-service-blocks">
                                    <?php $min_date = date('Y-m-d'); ?>
                                    <div class="ubf-service-block">
                                            <select id="ubf_service_id" class="ubf-service-select" name="service_id[]" required>
                                    <option value="">Choose a service</option>
                                    <?php echo $this->get_services_options(); ?>
                                </select>

                                <label style="display:block;margin-top:8px;font-weight:600">Preferred Staff (optional)</label>
                                <input type="hidden" class="ubf-staff-input" name="staff_id[]" value="">
                                <div class="ubf-staff-grid" aria-live="polite">
                                    <div class="staff-grid-empty">Select a service to choose staff</div>
                                </div>
                                            <!-- schedule inputs are rendered in the Schedule step (step 3) per service -->
                            </div>
                        </div>

                        <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                            <button type="button" class="ubf-add-service">Add another service</button>
                            <small style="color:#556;">You can add multiple services and choose staff for each.</small>
                        </div>

                        <?php else: ?>
                            <input type="hidden" name="service_id[]" value="">
                        <?php endif; ?>
                        <div class="ubf-step-nav"><button type="button" class="ubf-next">Next</button></div>
                    </div>

                    <div class="ubf-form-step" data-step="2" style="display:none;">
                        <h3 class="section-title">Personal Information</h3>
                        <input type="text" id="ubf_customer_name" name="customer_name" placeholder="Full name" required>
                        <input type="email" id="ubf_customer_email" name="customer_email" placeholder="Email" required>
                        <input type="tel" id="ubf_customer_phone" name="customer_phone" placeholder="Phone">
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="button" class="ubf-next">Next</button>
                        </div>
                    </div>

                    <div class="ubf-form-step" data-step="3" style="display:none;">
                        <h3 class="section-title">Preferred Schedule</h3>
                        <?php $min_date = date('Y-m-d'); ?>
                        <!-- Backwards-compatible global schedule (kept but hidden visually when per-service schedules exist) -->
                        <div class="ubf-global-schedule" style="display:none">
                            <input type="date" id="ubf_preferred_date" name="preferred_date" min="<?php echo esc_attr($min_date); ?>" data-business-start="09:00" data-business-end="19:00">
                            <select id="ubf_preferred_time" name="preferred_time">
                                <option value="">Preferred time (optional)</option>
                                <?php for ($h = 9; $h <= 19; $h++) { $value = sprintf('%02d:00', $h); $display = date('g:i A', strtotime($value)); echo '<option value="' . esc_attr($value) . '">' . esc_html($display) . '</option>'; } ?>
                            </select>
                        </div>

                        <!-- Per-service schedule list: rows will be rendered here by JS based on selected services/assigned staff -->
                        <div id="ubf-per-service-schedule">
                            <div class="ubf-schedule-placeholder">Select and configure services in step 1 to set schedules per service.</div>
                        </div>
                        <div class="ubf-field-error ubf-schedule-error" style="display:none;color:#c43d3d;margin-bottom:8px"></div>
                        <textarea id="ubf_message" name="message" placeholder="Additional message"></textarea>
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="button" class="ubf-next">Next</button>
                        </div>
                    </div>

                    <div class="ubf-form-step" data-step="4" style="display:none;">
                        <div class="ubf-payment-headers">
                            <h3 class="section-title">Choose Payment Method</h3>
                            <div class="ubf-breakdown-heading ubf-breakdown-heading-header">Payment breakdown</div>
                        </div>
                        <div class="ubf-payment-grid">
                            <div class="payment-methods payment-methods-stack">
                            <div class="payment-option">
                                <input type="radio" id="ubf_payment_cash" name="payment_method" value="cash" checked>
                                <label for="ubf_payment_cash" class="payment-label">
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
                                <input type="radio" id="ubf_payment_card" name="payment_method" value="card">
                                <label for="ubf_payment_card" class="payment-label">
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
                                <input type="radio" id="ubf_payment_gcash" name="payment_method" value="gcash">
                                <label for="ubf_payment_gcash" class="payment-label">
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
                                <input type="radio" id="ubf_payment_paymaya" name="payment_method" value="paymaya">
                                <label for="ubf_payment_paymaya" class="payment-label">
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
                                <input type="radio" id="ubf_payment_online" name="payment_method" value="online">
                                <label for="ubf_payment_online" class="payment-label">
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

                            <!-- Right column: detailed breakdown per service and totals -->
                            <div class="ubf-payment-breakdown" aria-live="polite">
                                <div class="breakdown-services" id="ubf-breakdown-services">
                                    <!-- Service lines inserted here by JS -->
                                    <div class="breakdown-service-placeholder">No services selected</div>
                                </div>
                                <div class="breakdown-row"><span class="label">Subtotal</span><span class="value" id="ubf-breakdown-subtotal">₱0.00</span></div>
                                <div class="breakdown-row" id="ubf-breakdown-tax-row" style="display:none"><span class="label">Tax</span><span class="value" id="ubf-breakdown-tax">₱0.00</span></div>
                                <div class="breakdown-row" id="ubf-breakdown-discount-row" style="display:none"><span class="label">Discount</span><span class="value" id="ubf-breakdown-discount">-₱0.00</span></div>
                                <div class="breakdown-total"><span class="label">Total Payment</span><span class="value" id="ubf-breakdown-total">₱0.00</span></div>
                            </div>
                        </div>

                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="submit" class="ubf-submit" id="ubf-v3-submit">Complete Booking</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <div class="ubf-v3-success" id="ubf-v3-success" style="display:none;">
            <div class="ubf-success-box">
                <div class="ubf-success-icon">✓</div>
                <h3 class="ubf-success-title">Booking Request Submitted</h3>
                <p class="ubf-success-message">Thank you — your booking request has been received. We'll contact you shortly to confirm the appointment.</p>
                <div class="ubf-success-actions">
                    <button type="button" class="ubf-btn-primary" id="ubf-v3-success-view">View My Bookings</button>
                    <button type="button" class="ubf-btn-secondary" id="ubf-v3-success-new">Book Another</button>
                </div>
            </div>
        </div>

        <!-- Debug banner removed -->

        <?php
        return ob_get_clean();
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
     * Convert service duration to minutes.
     * Accepts formats like '90' (minutes) or '1:30' / '01:30' (H:MM) or '90 mins'.
     * Returns integer minutes (default 60).
     */
    private function duration_to_minutes($duration) {
        if (empty($duration)) return 60;
        $duration = trim($duration);

        // If numeric string (e.g. '90')
        if (is_numeric($duration)) {
            return intval($duration);
        }

        // If contains colon like H:MM
        if (strpos($duration, ':') !== false) {
            $parts = explode(':', $duration);
            $hours = intval($parts[0]);
            $minutes = intval($parts[1] ?? 0);
            return $hours * 60 + $minutes;
        }

        // If contains word 'hour' or 'min'
        if (preg_match('/(\d+)\s*hour/i', $duration, $m)) {
            return intval($m[1]) * 60;
        }
        if (preg_match('/(\d+)\s*min/i', $duration, $m)) {
            return intval($m[1]);
        }

        // Fallback to 60 minutes
        return 60;
    }

    /**
     * Check if a staff member has an overlapping booking for given date/time and duration.
     * Returns conflicting booking ID if overlap found, or false otherwise.
     */
    private function staff_has_overlap($staff_id, $preferred_date, $preferred_time, $duration_minutes) {
        if (empty($staff_id) || empty($preferred_date) || empty($preferred_time)) return false;

        // Build requested start/end DateTime
        try {
            $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(date_default_timezone_get());
            $start = DateTime::createFromFormat('Y-m-d H:i', $preferred_date . ' ' . $preferred_time, $tz);
            if (!$start) {
                // try alternative parsing
                $start = new DateTime($preferred_date . ' ' . $preferred_time);
            }
        } catch (Exception $e) {
            return false;
        }

        $end = clone $start;
        $end->modify('+' . intval($duration_minutes) . ' minutes');

        // Query bookings for this staff on the same date
        $booking_args = array(
            'post_type' => 'service_booking',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array('key' => '_staff_id', 'value' => $staff_id, 'compare' => '='),
                array('key' => '_preferred_date', 'value' => $preferred_date, 'compare' => '=')
            ),
            'fields' => 'ids'
        );

        $existing = get_posts($booking_args);
        if (empty($existing)) return false;

        foreach ($existing as $bid) {
            $ex_time = get_post_meta($bid, '_preferred_time', true);
            $ex_service_id = get_post_meta($bid, '_service_id', true);

            if (empty($ex_time)) continue; // skip bookings without a set time

            // Determine existing booking duration
            $ex_duration = 60; // default
            if ($ex_service_id) {
                $s_post = get_post($ex_service_id);
                if ($s_post && $s_post->post_type === 'service') {
                    $ex_duration = get_post_meta($ex_service_id, '_service_duration', true) ?: get_post_meta($ex_service_id, 'service_duration', true);
                } else {
                    // try legacy table lookup if needed
                    $legacy = intval(get_post_meta($bid, '_service_duration', true));
                    if ($legacy) $ex_duration = $legacy;
                }
            }

            $ex_minutes = $this->duration_to_minutes($ex_duration);

            try {
                $ex_start = DateTime::createFromFormat('Y-m-d H:i', $preferred_date . ' ' . $ex_time, $tz);
                if (!$ex_start) { $ex_start = new DateTime($preferred_date . ' ' . $ex_time); }
            } catch (Exception $e) {
                continue;
            }

            $ex_end = clone $ex_start;
            $ex_end->modify('+' . intval($ex_minutes) . ' minutes');

            // Overlap if start < ex_end && ex_start < end
            if ($start < $ex_end && $ex_start < $end) {
                return $bid;
            }
        }

        return false;
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
        // Keep initial dropdown minimal; JS will populate based on selected service
        return '<option value="">Any available staff member</option>';
    }

    /**
     * AJAX: Return staff assigned to a given service (by service post ID)
     * Response: { success: true, staff: [ { id, name } ] }
     */
    public function get_staff_for_service() {
        // Verify nonce
        $nonce_ok = check_ajax_referer('user_booking_nonce', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json([ 'success' => false, 'message' => __('Security check failed', 'payndle') ]);
        }

        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            wp_send_json([ 'success' => false, 'message' => __('Invalid service', 'payndle') ]);
        }

        $staff = array();

        // Primary source: assigned_staff on the service post (synced during admin/shortcode save)
        $assigned = get_post_meta($service_id, 'assigned_staff', true);
        if (is_array($assigned) && !empty($assigned)) {
            $assigned_ids = array_map('absint', array_values($assigned));
            $assigned_ids = array_filter($assigned_ids);
            if (!empty($assigned_ids)) {
                $args_assigned = array(
                    'post_type' => 'staff',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'post__in' => $assigned_ids,
                    'fields' => 'ids'
                );
                $q1 = new WP_Query($args_assigned);
                if ($q1->have_posts()) {
                    foreach ($q1->posts as $pid) {
                        $avatar = '';
                        $avatar_id = get_post_meta($pid, 'staff_avatar_id', true);
                        if ($avatar_id) {
                            $img = wp_get_attachment_image_src($avatar_id, 'thumbnail');
                            if ($img) { $avatar = $img[0]; }
                        }
                        if (!$avatar) {
                            $meta_url = get_post_meta($pid, 'staff_avatar', true);
                            if (!empty($meta_url)) { $avatar = esc_url_raw($meta_url); }
                        }
                        if (!$avatar && has_post_thumbnail($pid)) {
                            $img = wp_get_attachment_image_src(get_post_thumbnail_id($pid), 'thumbnail');
                            if ($img) { $avatar = $img[0]; }
                        }
                        $staff[] = array('id' => $pid, 'name' => get_the_title($pid), 'avatar' => $avatar);
                    }
                }
            }
        }

        // Fallback: query by staff_services meta and legacy staff_role mapping
        if (empty($staff)) {
            $meta_query = array();
            // Do NOT strictly require active status—include all to avoid hiding older records
            // If you prefer only active, uncomment below:
            // $meta_query[] = array('key' => 'staff_status', 'value' => 'active', 'compare' => '=');

            $service_or = array(
                'relation' => 'OR',
                array(
                    'key' => 'staff_services',
                    'value' => '"' . $service_id . '"',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'staff_services',
                    'value' => 'i:' . $service_id . ';',
                    'compare' => 'LIKE'
                )
            );
            $s_post = get_post($service_id);
            if ($s_post && !empty($s_post->post_title)) {
                $service_or[] = array(
                    'key' => 'staff_role',
                    'value' => $s_post->post_title,
                    'compare' => '='
                );
            }
            $meta_query[] = $service_or;

            $args = array(
                'post_type' => 'staff',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => $meta_query,
                'fields' => 'ids'
            );
            $q = new WP_Query($args);
            if ($q->have_posts()) {
                foreach ($q->posts as $pid) {
                    $avatar = '';
                    $avatar_id = get_post_meta($pid, 'staff_avatar_id', true);
                    if ($avatar_id) {
                        $img = wp_get_attachment_image_src($avatar_id, 'thumbnail');
                        if ($img) { $avatar = $img[0]; }
                    }
                    if (!$avatar) {
                        $meta_url = get_post_meta($pid, 'staff_avatar', true);
                        if (!empty($meta_url)) { $avatar = esc_url_raw($meta_url); }
                    }
                    if (!$avatar && has_post_thumbnail($pid)) {
                        $img = wp_get_attachment_image_src(get_post_thumbnail_id($pid), 'thumbnail');
                        if ($img) { $avatar = $img[0]; }
                    }
                    $staff[] = array('id' => $pid, 'name' => get_the_title($pid), 'avatar' => $avatar);
                }
            }
        }

        // If preferred date/time provided, filter out staff that already have a booking at that slot
        $pref_date = isset($_POST['preferred_date']) ? sanitize_text_field($_POST['preferred_date']) : '';
        $pref_time = isset($_POST['preferred_time']) ? sanitize_text_field($_POST['preferred_time']) : '';
        if (!empty($pref_date) && !empty($pref_time) && !empty($staff)) {
            global $wpdb;
            $booked_staff_ids = array();
            // Query posts of type service_booking that have matching meta
            $booking_args = array(
                'post_type' => 'service_booking',
                // consider any status so pending/unpublished bookings still block the slot
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array('key' => '_preferred_date', 'value' => $pref_date, 'compare' => '='),
                    array('key' => '_preferred_time', 'value' => $pref_time, 'compare' => '=')
                ),
                'fields' => 'ids'
            );
            $bq = new WP_Query($booking_args);
            if ($bq->have_posts()) {
                foreach ($bq->posts as $bid) {
                    $sid = get_post_meta($bid, '_staff_id', true);
                    if ($sid) $booked_staff_ids[] = absint($sid);
                }
            }

            if (!empty($booked_staff_ids)) {
                // Remove booked staff from $staff list
                $staff = array_values(array_filter($staff, function($s) use ($booked_staff_ids) {
                    return !in_array($s['id'], $booked_staff_ids);
                }));
            }
        }

        wp_send_json(array(
            'success' => true,
            'staff' => $staff
        ));
    }
    
    /**
     * Render the legacy booking form (original single-page version)
     */
    public function render_booking_form_legacy($atts) {
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
                        <input type="hidden" id="staff_id" name="staff_id" value="">
                        <div id="staff-grid" class="staff-grid" aria-live="polite">
                            <div class="staff-grid-empty">Select a service to choose staff</div>
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
                                    <select id="preferred_time" name="preferred_time">
                                        <option value="">Preferred time (optional)</option>
                                        <?php
                                        for ($h = 9; $h <= 19; $h++) {
                                            $value = sprintf('%02d:00', $h);
                                            $display = date('g:i A', strtotime($value));
                                            echo '<option value="' . esc_attr($value) . '">' . esc_html($display) . '</option>';
                                        }
                                        ?>
                                    </select>
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
        // Debug logging removed: temporary booking-debug.log writes cleaned up.
        
        // Test database structure first
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE $booking_table");
        if ($columns) {
            $column_names = array_column($columns, 'Field');
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // Debug logging removed.
            }
        } else {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // Debug logging removed.
            }
        }
        
        // Check nonce - be more lenient for debugging
        $nonce_check = check_ajax_referer('user_booking_nonce', 'nonce', false);
        if (!$nonce_check) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // Debug logging removed for nonce verification failure.
            }
            
            // For debugging, let's try to continue anyway but log it
            // wp_send_json([
            //     'success' => false,
            //     'message' => 'Security check failed. Please refresh the page and try again.'
            // ]);
        }
        
        // Check if required POST data exists
        if (!isset($_POST['service_id']) || !isset($_POST['customer_name']) || !isset($_POST['customer_email'])) {
            // Debug logging removed for missing required POST data.
            wp_send_json([
                'success' => false,
                'message' => 'Missing required form data. Please fill out all required fields.'
            ]);
        }
        
        // Sanitize and validate input
        $service_id = intval($_POST['service_id']);
        $staff_id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
        // Debug logging removed for received staff_id.
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    // Support per-block schedules: preferred_date[] and preferred_time[]
    $preferred_date_input = $_POST['preferred_date'] ?? array();
    $preferred_time_input = $_POST['preferred_time'] ?? array();

    if (!is_array($preferred_date_input)) { $preferred_date_input = array($preferred_date_input); }
    if (!is_array($preferred_time_input)) { $preferred_time_input = array($preferred_time_input); }

    // sanitize arrays
    $preferred_dates = array_map('sanitize_text_field', $preferred_date_input);
    $preferred_times = array_map('sanitize_text_field', $preferred_time_input);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'cash');

        // Normalize preferred_date to Y-m-d where possible
        if (!empty($preferred_date)) {
            $norm = null;
            // numeric timestamp
            if (is_numeric($preferred_date)) {
                $ts = intval($preferred_date);
                if ($ts > 10000000000) { $ts = intval($ts / 1000); }
                try {
                    $dt = new DateTime('@' . $ts);
                    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    $norm = $dt->format('Y-m-d');
                } catch (Exception $e) { $norm = null; }
            }
            // common date formats
            if (!$norm) {
                $formats = array('Y-m-d','d/m/Y','d-m-Y','d.m.Y','m/d/Y');
                foreach ($formats as $f) {
                    $d = DateTime::createFromFormat($f, $preferred_date);
                    if ($d instanceof DateTime) { $norm = $d->format('Y-m-d'); break; }
                }
            }
            // try generic parse
            if (!$norm) {
                try { $d = new DateTime($preferred_date); $norm = $d->format('Y-m-d'); } catch (Exception $e) { $norm = null; }
            }
            if ($norm) $preferred_date = $norm;
        }

        // Normalize preferred_time to HH:MM (24-hour) where possible
        if (!empty($preferred_time)) {
            $t = trim($preferred_time);
            $normt = null;
            // try parse with DateTime
            try {
                $dt = new DateTime($t);
                $normt = $dt->format('H:i');
            } catch (Exception $e) {
                // try strtotime fallback
                $ts = strtotime($t);
                if ($ts !== false) { $normt = date('H:i', $ts); }
            }
            // numeric like 900 or 0900
            if (!$normt && is_numeric($t)) {
                $num = intval($t);
                if ($num >= 100) {
                    $h = intval(floor($num / 100));
                    $m = $num % 100;
                    $normt = sprintf('%02d:%02d', $h, $m);
                } else {
                    $normt = sprintf('%02d:00', $num);
                }
            }
            if ($normt) $preferred_time = $normt;
        }
        
        // Debug logging removed for sanitized data.
        
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
            // Debug logging removed for validation errors.
            wp_send_json([
                'success' => false,
                'errors' => $errors
            ]);
        }
        
        // Check if tables exist
        $services_table = $wpdb->prefix . 'manager_services';
        
        // Verify services table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") != $services_table) {
            // Debug logging removed for services table missing.
            wp_send_json([
                'success' => false,
                'message' => 'Services system not properly configured. Please contact administrator.'
            ]);
        }
        
        // Verify booking table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$booking_table}'") != $booking_table) {
            // Debug logging removed for booking table missing.
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
            // Debug logging removed for found CPT service.
        } else {
            // Fallback to legacy table
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $services_table WHERE id = %d AND is_active = 1",
                $service_id
            ));

            if ($wpdb->last_error) {
                // Debug logging removed for database error when fetching legacy service.
                wp_send_json([
                    'success' => false,
                    'message' => 'Database error occurred. Please try again later.'
                ]);
            }

            if (!$service) {
                // Debug logging removed for service not found.
                wp_send_json([
                    'success' => false,
                    'message' => 'Selected service is not available. Please choose another service.'
                ]);
            }

            // Debug logging removed for found legacy service.
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
        
        // Debug logging removed for attempting to insert data.
        
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

        // Conflict check: ensure the selected staff doesn't have an overlapping booking
        if (!empty($staff_id) && !empty($preferred_date) && !empty($preferred_time)) {
            // Determine duration for the requested service
            $duration_meta = null;
            if (!empty($service->service_duration)) {
                $duration_meta = $service->service_duration;
            } else {
                // Try legacy meta on the service post
                $duration_meta = get_post_meta($service_id, '_service_duration', true) ?: get_post_meta($service_id, 'service_duration', true);
            }
            $duration_minutes = $this->duration_to_minutes($duration_meta);

            $conflict_bid = $this->staff_has_overlap($staff_id, $preferred_date, $preferred_time, $duration_minutes);
            if ($conflict_bid) {
                // Debug logging removed for overlap detection.

                wp_send_json([
                    'success' => false,
                    'message' => 'Selected staff is not available at the chosen date and time (overlaps another booking). Please choose a different time or staff.'
                ]);
            }
        }

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            // Debug logging removed for wp_insert_post error.

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

        // Debug logging removed for successfully created booking post.

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
            // Debug logging removed for email notification failure.
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

    /**
     * Handle v3 AJAX booking submission - lightweight version
     */
    public function submit_user_booking_v3() {
        check_ajax_referer('user_booking_nonce', 'nonce'); // Use same nonce as original for compatibility

        // Accept multiple services (service_id[] and staff_id[])
        $service_input = $_POST['service_id'] ?? array();
        if (!is_array($service_input)) { $service_input = array($service_input); }
        $staff_input = $_POST['staff_id'] ?? array();
        if (!is_array($staff_input)) { $staff_input = array($staff_input); }

        $service_ids = array_map('absint', $service_input);
        $staff_ids = array_map('absint', $staff_input);

    // If schedule-specific ids are submitted (from per-service schedule rows), prefer those for alignment
    $schedule_service_input = $_POST['schedule_service_id'] ?? array();
    if (!is_array($schedule_service_input)) { $schedule_service_input = array($schedule_service_input); }
    $schedule_staff_input = $_POST['schedule_staff_id'] ?? array();
    if (!is_array($schedule_staff_input)) { $schedule_staff_input = array($schedule_staff_input); }

    $schedule_service_ids = array_map('absint', $schedule_service_input);
    $schedule_staff_ids = array_map('absint', $schedule_staff_input);

    // If schedule arrays are present, use them as the per-index mapping; otherwise fall back to service_ids/staff_ids
    $use_schedule_arrays = !empty($schedule_service_ids);

        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');

        // Support per-service preferred_date[] and preferred_time[] arrays. Backwards-compatible with single fields.
        $preferred_dates_raw = $_POST['preferred_date'] ?? array();
        $preferred_times_raw = $_POST['preferred_time'] ?? array();

        if (!is_array($preferred_dates_raw)) {
            $single = sanitize_text_field($preferred_dates_raw);
            $preferred_dates = array_fill(0, max(1, count($service_ids)), $single);
        } else {
            $preferred_dates = array_map('sanitize_text_field', $preferred_dates_raw);
        }

        if (!is_array($preferred_times_raw)) {
            $single_t = sanitize_text_field($preferred_times_raw);
            $preferred_times = array_fill(0, max(1, count($service_ids)), $single_t);
        } else {
            $preferred_times = array_map('sanitize_text_field', $preferred_times_raw);
        }

        // Keep legacy scalar variables for compatibility where needed
        $preferred_date = isset($preferred_dates[0]) ? $preferred_dates[0] : '';
        $preferred_time = isset($preferred_times[0]) ? $preferred_times[0] : '';

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'cash');

        // Basic validation: name, email and at least one valid service
        $has_valid_service = false;
        foreach ($service_ids as $sid) { if ($sid > 0) { $has_valid_service = true; break; } }

        if (empty($customer_name) || empty($customer_email) || !$has_valid_service) {
            wp_send_json(['success' => false, 'message' => 'Missing required fields']);
        }

        // Debug: log the incoming arrays to help trace alignment/values (only when WP_DEBUG)
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // Debug logging removed for submit_user_booking_v3 incoming arrays.
            }

        // Pre-check for overlaps across all requested schedule rows to avoid partial booking creation
        // Also enforce uniqueness across submitted schedule rows (server-side) mirroring frontend checks
        $seen = array();
        $conflict_rows = array();
        $conflict_existing = array(); // map index => existing booking id
        $loopCount = $use_schedule_arrays ? count($schedule_service_ids) : count($service_ids);
        for ($index = 0; $index < $loopCount; $index++) {
            $sid = $use_schedule_arrays ? intval($schedule_service_ids[$index]) : intval($service_ids[$index] ?? 0);
            if ($sid <= 0) continue;

            $staff_for_index = $use_schedule_arrays ? intval($schedule_staff_ids[$index] ?? 0) : intval($staff_ids[$index] ?? 0);
            $date_for_index = $preferred_dates[$index] ?? '';
            $time_for_index = $preferred_times[$index] ?? '';

            // if both date and time provided, validate
            if ($staff_for_index && $date_for_index && $time_for_index) {
                $keyStaff = 'staff|' . $staff_for_index . '|' . $date_for_index . '|' . $time_for_index;
                $keyServiceStaff = 'service|' . $sid . '|' . $staff_for_index . '|' . $date_for_index . '|' . $time_for_index;

                // check duplicates in request
                if (isset($seen[$keyStaff])) {
                    // mark current and previous indices as conflicting
                    $conflict_rows[] = $index;
                    foreach ($seen[$keyStaff] as $prevIndex) { $conflict_rows[] = $prevIndex; }
                }
                if (isset($seen[$keyServiceStaff])) {
                    $conflict_rows[] = $index;
                    foreach ($seen[$keyServiceStaff] as $prevIndex) { $conflict_rows[] = $prevIndex; }
                }

                // record index
                if (!isset($seen[$keyStaff])) $seen[$keyStaff] = array();
                $seen[$keyStaff][] = $index;
                if (!isset($seen[$keyServiceStaff])) $seen[$keyServiceStaff] = array();
                $seen[$keyServiceStaff][] = $index;

                // Determine duration for this service
                $s_duration = get_post_meta($sid, '_service_duration', true) ?: get_post_meta($sid, 'service_duration', true);
                $s_minutes = $this->duration_to_minutes($s_duration);

                $conflict_bid = $this->staff_has_overlap($staff_for_index, $date_for_index, $time_for_index, $s_minutes);
                if ( defined('WP_DEBUG') && WP_DEBUG ) {
                    // Debug logging removed for per-index overlap checks.
                }
                if ($conflict_bid) {
                    $conflict_rows[] = $index;
                    $conflict_existing[$index] = $conflict_bid;
                }
            }
        }

        // If we collected duplicates or overlaps already, respond with indices
        if (!empty($conflict_rows)) {
            // make unique & reindex
            $conflict_rows = array_values(array_unique($conflict_rows));
            wp_send_json([
                'success' => false,
                'message' => 'One or more schedule rows conflict (either duplicate selections or overlap with existing bookings). Please review the highlighted schedule entries.',
                'conflict_rows' => $conflict_rows,
                'conflict_existing' => $conflict_existing
            ]);
        }

        // Final strict de-duplication guard: ensure no two rows in the request are identical
        $strict_seen = array();
        $strict_conflicts = array();
        $loopCount2 = $use_schedule_arrays ? count($schedule_service_ids) : count($service_ids);
        for ($i = 0; $i < $loopCount2; $i++) {
            $sid_i = $use_schedule_arrays ? intval($schedule_service_ids[$i]) : intval($service_ids[$i] ?? 0);
            $staff_i = $use_schedule_arrays ? intval($schedule_staff_ids[$i] ?? 0) : intval($staff_ids[$i] ?? 0);
            $date_i = $preferred_dates[$i] ?? '';
            $time_i = $preferred_times[$i] ?? '';
            if (!$staff_i || !$date_i || !$time_i) continue; // only consider fully-specified schedule rows

            $kStaff = $staff_i . '|' . $date_i . '|' . $time_i;
            $kServiceStaff = $sid_i . '|' . $staff_i . '|' . $date_i . '|' . $time_i;

            if (isset($strict_seen['staff'][$kStaff])) {
                $strict_conflicts[] = $i;
                $strict_conflicts[] = $strict_seen['staff'][$kStaff];
            } else {
                $strict_seen['staff'][$kStaff] = $i;
            }

            if ($sid_i && isset($strict_seen['service_staff'][$kServiceStaff])) {
                $strict_conflicts[] = $i;
                $strict_conflicts[] = $strict_seen['service_staff'][$kServiceStaff];
            } else {
                $strict_seen['service_staff'][$kServiceStaff] = $i;
            }
        }
        if (!empty($strict_conflicts)) {
            $strict_conflicts = array_values(array_unique($strict_conflicts));
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // Debug logging removed for strict duplicate guard.
            }
            wp_send_json([
                'success' => false,
                'message' => 'Duplicate schedule rows detected in your request. Please remove duplicates and try again.',
                'conflict_rows' => $strict_conflicts
            ]);
        }

        // Create a shared group key so each created booking post can be associated together
        $group_key = uniqid('group_', true);
        $created = array();
        $createLoop = $use_schedule_arrays ? $schedule_service_ids : $service_ids;
        $leader_id = 0;

        foreach ($createLoop as $index => $service_id) {
            $service_id = absint($service_id);
            if ($service_id <= 0) continue;

            $staff_id = $use_schedule_arrays ? (isset($schedule_staff_ids[$index]) && $schedule_staff_ids[$index] ? intval($schedule_staff_ids[$index]) : 0) : (isset($staff_ids[$index]) && $staff_ids[$index] ? intval($staff_ids[$index]) : 0);

            // Validate staff id: ensure it references a staff CPT. If not, treat as "Any available staff" and do not persist a wrong post id
            $validated_staff_id = 0;
            $staff_name_to_save = '';
            if ($staff_id) {
                $maybe = get_post(intval($staff_id));
                // Accept the id as staff if it's not a booking/service post and it's not trashed.
                if ($maybe && isset($maybe->post_type) && $maybe->post_status !== 'trash' && !in_array($maybe->post_type, array('service_booking','service'))) {
                    $validated_staff_id = intval($staff_id);
                    $staff_name_to_save = get_the_title($validated_staff_id);
                } else {
                    // Invalid staff reference (could be a booking id or legacy id). Don't save as staff_id.
                    $validated_staff_id = 0;
                    $staff_name_to_save = 'Any available staff';
                }
            } else {
                $validated_staff_id = 0;
                $staff_name_to_save = 'Any available staff';
            }
            $post_id = wp_insert_post([
                'post_type' => 'service_booking',
                'post_title' => $customer_name . ' - Booking',
                'post_status' => 'pending',
                'post_content' => $message
            ]);

            if (is_wp_error($post_id) || !$post_id) {
                // rollback any previously created posts for this group to keep atomicity
                if (!empty($created)) {
                    foreach ($created as $c) { wp_delete_post($c, true); }
                }
                wp_send_json(['success' => false, 'message' => 'Failed to create booking entries. No bookings were saved.']);
            }

            // Save meta for this booking; use per-index schedule if provided
            $date_for_index = $preferred_dates[$index] ?? '';
            $time_for_index = $preferred_times[$index] ?? '';

            update_post_meta($post_id, '_service_id', $service_id);
            update_post_meta($post_id, '_customer_name', $customer_name);
            update_post_meta($post_id, '_customer_email', $customer_email);
            update_post_meta($post_id, '_customer_phone', $customer_phone);
            if ($date_for_index) update_post_meta($post_id, '_preferred_date', $date_for_index);
            if ($time_for_index) update_post_meta($post_id, '_preferred_time', $time_for_index);
            update_post_meta($post_id, '_payment_method', $payment_method);
            // Persist a safe staff id and the staff name snapshot so the admin UI can always display a reliable label
            if ($validated_staff_id) {
                update_post_meta($post_id, '_staff_id', $validated_staff_id);
            } else {
                // ensure meta is cleared for no-selection
                update_post_meta($post_id, '_staff_id', 0);
            }
            // Always save the staff name snapshot (so admin view remains consistent even if staff CPT is later removed)
            update_post_meta($post_id, '_staff_name', sanitize_text_field($staff_name_to_save));

            // attach group metadata
            update_post_meta($post_id, '_group_booking_key', $group_key);
            if (!$leader_id) {
                $leader_id = $post_id;
            }
            update_post_meta($post_id, '_group_parent', $leader_id);

            $created[] = $post_id;
        }

        if (empty($created)) {
            wp_send_json(['success' => false, 'message' => 'Failed to create any bookings']);
        }

        $response = ['success' => true, 'message' => 'Bookings created', 'bookings' => $created, 'group_key' => $group_key];
        if (count($created) === 1) { $response['id'] = $created[0]; }
        wp_send_json($response);
    }

    /**
     * AJAX endpoint: check availability for a proposed schedule without creating bookings.
     * Expects the same schedule arrays as submit_user_booking_v3: schedule_service_id[], schedule_staff_id[], preferred_date[], preferred_time[]
     * Returns { success: false, conflict_rows: [...], conflict_existing: { index: existing_booking_id, ... }, message }
     */
    public function check_schedule_availability_v3() {
        // Basic request guard
        if ( ! isset($_POST) || empty($_POST) ) {
            wp_send_json(['success' => false, 'message' => 'No schedule provided']);
        }

        // Extract arrays similarly to submit_user_booking_v3
        $schedule_service_ids = isset($_POST['schedule_service_id']) && is_array($_POST['schedule_service_id']) ? $_POST['schedule_service_id'] : array();
        $schedule_staff_ids = isset($_POST['schedule_staff_id']) && is_array($_POST['schedule_staff_id']) ? $_POST['schedule_staff_id'] : array();
        $preferred_dates = isset($_POST['preferred_date']) && is_array($_POST['preferred_date']) ? $_POST['preferred_date'] : array();
        $preferred_times = isset($_POST['preferred_time']) && is_array($_POST['preferred_time']) ? $_POST['preferred_time'] : array();

        // Normalize lengths: use service ids as the driver
        $len = count($schedule_service_ids);
        if ($len === 0) {
            wp_send_json(['success' => true, 'message' => 'No services selected']);
        }

    // Basic per-row validation and duplicate detection (intra-request)
    $seen = array();
    $conflicts = array();
    $conflict_items = array();
    $conflict_existing = array();
    $strict_seen = array('service_staff' => array());
    $strict_conflicts = array();

        for ($i = 0; $i < $len; $i++) {
            $svc = absint($schedule_service_ids[$i]);
            $staff = isset($schedule_staff_ids[$i]) ? intval($schedule_staff_ids[$i]) : 0;
            $date = isset($preferred_dates[$i]) ? trim($preferred_dates[$i]) : '';
            $time = isset($preferred_times[$i]) ? trim($preferred_times[$i]) : '';

            // skip incomplete blocks (no staff chosen) — these are not checked here
            if (!$staff || !$date || !$time) continue;

            // intra-request strict duplicate guard: same service+staff+date+time
            $kServiceStaff = sprintf('%d|%d|%s|%s', $svc, $staff, $date, $time);
            if (isset($strict_seen['service_staff'][$kServiceStaff])) {
                $strict_conflicts[] = $i;
                $strict_conflicts[] = $strict_seen['service_staff'][$kServiceStaff];
            } else {
                $strict_seen['service_staff'][$kServiceStaff] = $i;
            }

            // server-side overlap check: reuse staff_has_overlap if available
            if (method_exists($this, 'staff_has_overlap')) {
                // Determine duration: try to read from service meta if possible.
                // Normalize using duration_to_minutes() so formats like '1:30' or '90 mins' work.
                $duration = 60; // sensible default
                if ($svc) {
                    $meta_duration = get_post_meta($svc, 'service_duration', true);
                    if (empty($meta_duration)) {
                        $meta_duration = get_post_meta($svc, '_service_duration', true);
                    }
                    if (!empty($meta_duration)) {
                        // Use helper to convert various formats to minutes
                        $duration = intval($this->duration_to_minutes($meta_duration));
                    }
                }
                $overlap = $this->staff_has_overlap($staff, $date, $time, $duration);
                if ($overlap) {
                    // staff_has_overlap expected to return existing booking id or truthy; map to booking id when available
                    $conflicts[] = $i;
                    $conflict_existing[$i] = is_numeric($overlap) ? intval($overlap) : 0;
                    // also build a descriptive conflict item so client can match rows robustly
                    $conflict_items[] = array(
                        'original_index' => $i,
                        'service_id' => $svc,
                        'staff_id' => $staff,
                        'date' => $date,
                        'time' => $time,
                        'existing' => isset($conflict_existing[$i]) ? intval($conflict_existing[$i]) : 0
                    );
                }
            }
        }

        if (!empty($strict_conflicts)) {
            $strict_conflicts = array_values(array_unique($strict_conflicts));
            // build descriptive items for strict conflicts
            $items = array();
            foreach ($strict_conflicts as $si) {
                $svc = isset($schedule_service_ids[$si]) ? absint($schedule_service_ids[$si]) : 0;
                $staff = isset($schedule_staff_ids[$si]) ? intval($schedule_staff_ids[$si]) : 0;
                $date = isset($preferred_dates[$si]) ? trim($preferred_dates[$si]) : '';
                $time = isset($preferred_times[$si]) ? trim($preferred_times[$si]) : '';
                $items[] = array('original_index' => $si, 'service_id' => $svc, 'staff_id' => $staff, 'date' => $date, 'time' => $time);
            }
            wp_send_json([
                'success' => false,
                'message' => 'Duplicate schedule rows detected in your request. Please remove duplicates and try again.',
                'conflict_rows' => $strict_conflicts,
                'conflict_items' => $items
            ]);
        }

        // If any conflicts (intra-request duplicates or server-detected overlaps)
        // were detected, return an explicit failure response so the client cannot
        // mistakenly treat the result as available.
        if (!empty($strict_conflicts) || !empty($conflicts) || !empty($conflict_items)) {
            $all_conflict_rows = array_values(array_unique(array_merge($strict_conflicts, $conflicts)));
            $response = [
                'success' => false,
                'message' => (!empty($strict_conflicts)) ? 'Duplicate schedule rows detected in your request.' : 'Some selected slots are already taken',
                'conflict_rows' => $all_conflict_rows,
                'conflict_items' => $conflict_items,
                'conflict_existing' => isset($conflict_existing) ? $conflict_existing : array()
            ];
            // debug logging removed
            wp_send_json($response);
        }

        wp_send_json(['success' => true, 'message' => 'Slots available']);
    }
}

// Initialize the plugin
new UserBookingForm();