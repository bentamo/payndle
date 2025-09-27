<?php
/**
 * Elite Cuts - Manage Bookings
 * Admin interface for managing barbershop appointments
 * Shortcode: [elite_cuts_manage_bookings]
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function elite_cuts_manage_bookings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get current business ID and code
    $current_business_id = 0;
    $current_user_id = get_current_user_id();
    
    // Try to get business ID from user's owned business
    $user_business = get_posts([
        'post_type' => 'payndle_business',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_business_owner_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($user_business)) {
        $current_business_id = $user_business[0]->ID;
        $business_code = get_post_meta($current_business_id, '_business_code', true);
    }
    
    if (!$current_business_id || !$business_code) {
        echo '<div class="notice notice-error"><p>Error: No valid business found. Please create a business profile first.</p></div>';
        return;
    }

    // Load bookings directly with PHP, filtered by business_id
    global $wpdb;
    $bookings = $wpdb->get_results($wpdb->prepare("
        SELECT 
            b.id,
            b.service_id,
            b.staff_id,
            b.customer_name,
            b.customer_email,
            b.customer_phone,
            b.preferred_date,
            b.preferred_time,
            b.booking_status,
            b.created_at,
            s.service_name,
            st.staff_name,
            st.staff_position
        FROM {$wpdb->prefix}service_bookings b
        LEFT JOIN {$wpdb->prefix}manager_services s ON b.service_id = s.id
        LEFT JOIN {$wpdb->prefix}staff_members st ON b.staff_id = st.id
        WHERE b.business_code = %s
        ORDER BY b.created_at DESC
        LIMIT 50
    ", $business_code));
        // Load bookings from the 'service_booking' custom post type and map post meta
        $bookings = [];

        $args = [
            'post_type' => 'service_booking',
            'posts_per_page' => 50,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_business_code',
                    'value' => $business_code,
                    'compare' => '='
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $id = $post->ID;
                $service_id = get_post_meta($id, '_service_id', true);
                $staff_id = get_post_meta($id, '_staff_id', true);
                $customer_name = get_post_meta($id, '_customer_name', true);
                $customer_email = get_post_meta($id, '_customer_email', true);
                $customer_phone = get_post_meta($id, '_customer_phone', true);
                $preferred_date = get_post_meta($id, '_preferred_date', true);
                $preferred_time = get_post_meta($id, '_preferred_time', true);
                $booking_status = get_post_meta($id, '_booking_status', true) ?: 'pending';
                $created_at = get_post_meta($id, '_created_at', true) ?: get_the_date('Y-m-d H:i:s', $post);

                // Resolve service and staff names prioritizing CPT posts, with legacy table fallback
                $service_name = null;
                if (!empty($service_id)) {
                    $service_post = get_post(intval($service_id));
                    if ($service_post && $service_post->post_status !== 'trash') {
                        $service_name = get_the_title($service_post);
                    } else {
                        // Legacy services table fallback
                        $services_table = $wpdb->prefix . 'manager_services';
                        $svc = $wpdb->get_row($wpdb->prepare("SELECT service_name FROM $services_table WHERE id = %d", intval($service_id)));
                        if ($svc) { $service_name = $svc->service_name; }
                    }
                }

                // Prefer a stored snapshot of the staff name if available (saved at booking creation/update)
                $staff_name = get_post_meta($id, '_staff_name', true);
                $staff_position = null;

                // If snapshot isn't present or is the default text, try resolving from the stored _staff_id
                if ((empty($staff_name) || strcasecmp(trim($staff_name), 'any available staff') === 0) && !empty($staff_id)) {
                    $staff_post = get_post(intval($staff_id));
                    if ($staff_post && $staff_post->post_status !== 'trash') {
                        // Use the post title for the staff label regardless of post_type (covers staff CPT variants)
                        $staff_name = get_the_title($staff_post);
                        $staff_position = get_post_meta($staff_post->ID, 'staff_position', true);
                    } else {
                        // Legacy staff table fallback
                        $staff_table = $wpdb->prefix . 'staff_members';
                        $st = $wpdb->get_row($wpdb->prepare("SELECT staff_name, staff_position FROM $staff_table WHERE id = %d", intval($staff_id)));
                        if ($st) {
                            $staff_name = $st->staff_name;
                            $staff_position = $st->staff_position;
                        }
                    }
                }

                // Final fallback to the default label
                if (empty($staff_name)) { $staff_name = 'Any available staff'; }

                $bookings[] = (object) [
                    'id' => $id,
                    'service_id' => $service_id,
                    'staff_id' => $staff_id,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'customer_phone' => $customer_phone,
                    'preferred_date' => $preferred_date,
                    'preferred_time' => $preferred_time,
                    'booking_status' => $booking_status,
                    'created_at' => $created_at,
                    'service_name' => $service_name,
                    'staff_name' => $staff_name,
                    'staff_position' => $staff_position
                ];
            }
            wp_reset_postdata();
        }
    // Post-process bookings to detect grouped bookings.
    // NOTE: Bookings created together for multiple services are stored as separate
    // service_booking posts. We mark these as 'grouped' when they share the same
    // customer name and created date so the admin UI can indicate grouped bookings.
        // Bookings that were created together for multiple services will often share the same
        // customer name and have very similar created_at timestamps. We'll group by a
        // composite key of customer_name + created_at date (date only) to mark grouped
        // service bookings stored individually but created as part of the same grouped booking.
        $grouped_map = [];
        foreach ($bookings as $b) {
            $date_only = '';
            if (!empty($b->created_at)) {
                $dt = strtotime($b->created_at);
                if ($dt !== false) { $date_only = date('Y-m-d', $dt); }
            }
            $key = trim(strtolower($b->customer_name)) . '|' . $date_only;
            if (!isset($grouped_map[$key])) { $grouped_map[$key] = []; }
            $grouped_map[$key][] = $b->id;
        }
        // Attach a simple flag and group size to each booking object for rendering
        foreach ($bookings as $b) {
            $date_only = '';
            if (!empty($b->created_at)) {
                $dt = strtotime($b->created_at);
                if ($dt !== false) { $date_only = date('Y-m-d', $dt); }
            }
            $key = trim(strtolower($b->customer_name)) . '|' . $date_only;
            $group_ids = $grouped_map[$key] ?? [];
            $b->group_size = count($group_ids);
            // position in group (1-based) to help label like "Grouped booking (1 of 3)"
            $b->group_index = ($b->group_size > 1) ? array_search($b->id, $group_ids) + 1 : 1;
            $b->is_grouped = ($b->group_size > 1);
        }
    
    ?>
    <div class="wrap elite-cuts-admin">
        <div class="elite-cuts-header">
            <div class="shop-info">
                <h1 class="shop-name">Elite Cuts Barbershop</h1>
                <p class="shop-slogan">Precision Cuts & Grooming</p>
            </div>
            <div class="header-actions">
                <h1 class="elite-cuts-title">
                    Manage Bookings
                </h1>
                <button id="add-booking-btn" class="elite-button primary">
                    New Booking
                </button>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="elite-cuts-filters">
            <div class="filter-row">
                <div class="filter-group date-range">
                    <div class="filter-item">
                        <label for="filter-from">From</label>
                        <input type="date" id="filter-from" class="elite-input date-input">
                    </div>
                    <div class="filter-item">
                        <label for="filter-to">To</label>
                        <input type="date" id="filter-to" class="elite-input date-input">
                    </div>
                </div>
                
                <div class="filter-group status-filter">
                    <div class="filter-item">
                        <label for="filter-status">Status</label>
                        <select id="filter-status" class="elite-select">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-group search-filter">
                    <div class="filter-item search-box">
                        <label for="booking-search">Search</label>
                        <div class="search-container">
                            <input type="text" id="booking-search" class="elite-input search-input" placeholder="Search bookings...">
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="button" id="apply-filters" class="elite-button primary">
                        Apply Filters
                    </button>
                    <button type="button" id="reset-filters" class="elite-button secondary">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="table-container">
            <table class="elite-cuts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Contact</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookings-list">
                    <?php if ($bookings && count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr data-booking-id="<?php echo $booking->id; ?>">
                                <td data-label="ID">#<?php echo $booking->id; ?></td>
                                <td data-label="Customer">
                                    <strong><?php echo esc_html($booking->customer_name ?: 'Unknown'); ?></strong>
                                </td>
                                <td data-label="Service">
                                    <span class="service-name">
                                        <?php echo esc_html($booking->service_name ?: 'Service ID: ' . $booking->service_id); ?>
                                    </span>
                                    <?php if (!empty($booking->is_grouped) && $booking->is_grouped): ?>
                                        <div class="grouped-booking-label" title="This booking is part of a grouped booking">
                                            <small><em>Grouped booking (<?php echo intval($booking->group_index); ?> of <?php echo intval($booking->group_size); ?>)</em></small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Staff">
                                    <span class="staff-info">
                                        <?php if (!empty($booking->staff_name)): ?>
                                            <strong><?php echo esc_html($booking->staff_name); ?></strong><br>
                                            <small><?php echo esc_html($booking->staff_position); ?></small>
                                        <?php else: ?>
                                            <em>Any available staff</em>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="contact-info" data-label="Contact">
                                    <?php echo esc_html($booking->customer_email ?: 'No email'); ?><br>
                                    <small><?php echo esc_html($booking->customer_phone ?: 'No phone'); ?></small>
                                </td>
                                <td data-label="Date &amp; Time">
                                    <strong><?php echo esc_html($booking->preferred_date ?: 'No date'); ?></strong><br>
                                    <small><?php echo esc_html($booking->preferred_time ?: 'No time'); ?></small>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?php echo esc_attr($booking->booking_status); ?>">
                                        <?php echo esc_html(ucfirst($booking->booking_status)); ?>
                                    </span>
                                </td>
                                <td class="actions" data-label="Actions">
                                    <button class="elite-button small edit-booking" data-id="<?php echo $booking->id; ?>">
                                        Edit
                                    </button>
                                    <button class="elite-button small delete-booking" data-id="<?php echo $booking->id; ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-bookings">
                                <?php if ($wpdb->last_error): ?>
                                    Database Error: <?php echo esc_html($wpdb->last_error); ?>
                                <?php else: ?>
                                    No bookings found. <br>
                                    <small>Bookings are stored as the `service_booking` custom post type. Legacy table: <?php echo $wpdb->prefix; ?>service_bookings</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Manager Booking Form Overlay -->
    <div id="manager-booking-overlay" class="manager-booking-overlay">
        <div class="manager-booking-backdrop"></div>
        <div class="ubf-v3-container manager-booking-container">
            <div class="ubf-v3-header manager-booking-header">
                <div class="header-content">
                    <h1 class="ubf-v3-title booking-title">New Booking</h1>
                    <p class="ubf-v3-sub booking-subtitle">Create a new appointment for the barbershop</p>
                </div>
                <button class="close-overlay" id="close-booking-overlay">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="ubf-v3-form-wrapper manager-booking-form-wrapper">
                <div class="ubf-v3-stepper manager-stepper">
                    <div class="ubf-steps steps">
                        <div class="ubf-step step active" data-step="1"><div class="num">1</div><div class="label">Customer</div></div>
                        <div class="ubf-step step" data-step="2"><div class="num">2</div><div class="label">Service</div></div>
                        <div class="ubf-step step" data-step="3"><div class="num">3</div><div class="label">Schedule</div></div>
                        <div class="ubf-step step" data-step="4"><div class="num">4</div><div class="label">Confirm</div></div>
                    </div>
                    <div class="ubf-progress progress"><div class="ubf-progress-fill progress-fill" style="width:25%"></div></div>
                </div>

                <form id="manager-booking-form" class="ubf-v3-form manager-booking-form">
                    <?php wp_nonce_field('manager_booking_nonce', 'booking_nonce'); ?>
                    <input type="hidden" id="booking-id" value="">

                    <!-- Step 1: Customer Information -->
                    <div class="ubf-form-step form-step active" data-step="1">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Customer Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="customer-name">Customer Name <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="input-icon fas fa-user"></i>
                                        <input type="text" id="ubf_customer_name" name="customer_name" placeholder="Full name" required>
                                    </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer-email">Email Address <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-envelope"></i>
                                    <input type="email" id="ubf_customer_email" name="customer_email" placeholder="Email address" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer-phone">Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-phone"></i>
                                    <input type="tel" id="ubf_customer_phone" name="customer_phone" placeholder="Phone number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-nav">
                            <button type="button" class="btn-next">Next Step</button>
                        </div>
                    </div>

                    <!-- Step 2: Service Selection -->
                    <div class="ubf-form-step form-step" data-step="2">
                        <h3 class="section-title">
                            <i class="fas fa-cut"></i>
                            Select Service & Staff
                        </h3>
                        
                        <div class="form-group">
                            <label for="service-select">Service <span class="required">*</span></label>
                            <div class="service-selector">
                                <i class="input-icon fas fa-cut"></i>
                                <select id="ubf_service_id" name="service_id" required>
                                    <option value="">Choose a service</option>
                                    <?php echo elite_cuts_get_services_options(); ?>
                                </select>
                                <i class="select-icon fas fa-chevron-down"></i>
                            </div>
                        </div>
                        
                        <div class="selected-service-info" id="selected-service-info" style="display: none;">
                            <h4>Service Details</h4>
                            <p class="service-description"></p>
                            <p class="service-duration">Duration: <span></span></p>
                            <p class="service-price">Price: <span></span></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="ubf_staff_id">Preferred Staff</label>
                            <input type="hidden" id="ubf_staff_id" name="staff_id" value="">
                            <input type="hidden" id="ubf_staff_name_snapshot" name="staff_name_snapshot" value="">
                            <div id="ubf_staff_grid" class="staff-grid" aria-live="polite">
                                <div class="staff-grid-empty">Select a service to choose staff</div>
                            </div>
                        </div>
                        
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="button" class="ubf-next">Next</button>
                        </div>
                    </div>

                    <!-- Step 3: Schedule -->
                    <div class="ubf-form-step form-step" data-step="3">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Schedule Appointment
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="booking-date">Appointment Date <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-calendar-alt"></i>
                                    <input type="date" id="ubf_preferred_date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="booking-time">Appointment Time <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-clock"></i>
                                    <input type="time" id="ubf_preferred_time" name="preferred_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                                <label for="ubf_message">Additional Notes</label>
                            <div class="textarea-wrapper">
                                <i class="input-icon fas fa-comment-alt"></i>
                                <textarea id="ubf_message" name="message" placeholder="Any special requests or notes..."></textarea>
                            </div>
                        </div>
                        
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="button" class="ubf-next">Next</button>
                        </div>
                    </div>

                    <!-- Step 4: Confirmation -->
                    <div class="ubf-form-step form-step" data-step="4">
                        <h3 class="section-title">
                            <i class="fas fa-check-circle"></i>
                            Confirm Booking
                        </h3>
                        
                        <div class="booking-summary">
                            <div class="summary-section">
                                <h4>Customer</h4>
                                <p id="summary-customer"></p>
                                <p id="summary-contact"></p>
                            </div>
                            
                            <div class="summary-section">
                                <h4>Service</h4>
                                <p id="summary-service"></p>
                                <p id="summary-staff"></p>
                            </div>
                            
                            <div class="summary-section">
                                <h4>Appointment</h4>
                                <p id="summary-datetime"></p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-status">Initial Status</label>
                            <div class="service-selector">
                                <i class="input-icon fas fa-flag"></i>
                                <select id="booking-status" name="booking_status">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <i class="select-icon fas fa-chevron-down"></i>
                            </div>
                        </div>
                        
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="submit" class="ubf-btn-primary btn-submit">Create Booking</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        /* Provide UBF v3 AJAX settings in admin so the UBFv3 JS can submit bookings */
        window.userBookingV3 = window.userBookingV3 || {};
        window.userBookingV3.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        window.userBookingV3.nonce = '<?php echo wp_create_nonce('user_booking_nonce'); ?>';
        window.userBookingV3.messages = window.userBookingV3.messages || { error: 'An error occurred' };
    </script>

    <!-- Success Message -->
    <div id="manager-booking-success" class="manager-booking-success">
        <div class="manager-booking-backdrop"></div>
        <div class="success-container">
            <div class="success-box">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="success-title">Booking Complete</h3>
                <p class="success-message">The appointment has been added to Manage Bookings.</p>
                <div class="success-actions">
                    <button class="btn-primary" id="view-booking">View Booking</button>
                    <button class="btn-secondary" id="create-another">Create Another</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Force primary text color for all elements (overrides) */
        .elite-cuts-admin,
        .elite-cuts-admin *,
        .elite-cuts-table td,
        .elite-cuts-table th,
        .service-name,
        .contact-info {
            color: var(--text-primary) !important;
        }
        
        /* Specifically ensure service column text uses primary with stronger weight */
        .service-name,
        .service-name * {
            color: var(--text-primary) !important;
            font-weight: 700;
        }

        /* ===== Admin UBF v3 visual polish =====
           These overrides only affect UBF v3 forms in the admin bookings modal
           to better match the provided design (rounded pale-blue inputs, green
           progress fill, pill Next button, etc.). */
        .ubf-v3-container .ubf-v3-form-wrapper { padding: 2rem; border-radius: 12px; }
        .ubf-v3-form .form-grid { gap: 1.75rem; }

        .ubf-v3-form .form-group input,
        .ubf-v3-form .form-group textarea,
        .ubf-v3-form .form-group select {
            background: #eef6fb; /* pale blue */
            border: 1px solid rgba(12,25,48,0.06);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            height: 48px;
            box-shadow: none;
        }
        .ubf-v3-form .form-group textarea { min-height: 120px; height: auto; padding-top: 1rem; }

        /* Stepper circles and active state */

    /* Highlight newly created booking row briefly */
    #bookings-list tr.new-booking-highlight { background: linear-gradient(90deg, rgba(198,255,226,0.5), rgba(255,255,255,0)); }
    #bookings-list tr.new-booking-highlight td { transition: background 0.3s ease; }
        .ubf-v3-stepper .ubf-step { color: var(--text-secondary); }
        .ubf-v3-stepper .ubf-step .num {
            width: 36px; height: 36px; line-height: 36px; border-radius: 999px;
            background: #fff; color: var(--text-primary); border: 2px solid rgba(12,25,48,0.06);
            box-shadow: none; font-weight: 700;
        }
        .ubf-v3-stepper .ubf-step.active .num {
            background: var(--accent); color: #fff; border-color: var(--accent);
            box-shadow: 0 6px 18px rgba(100,196,147,0.12);
        }

        /* Progress fill thicker and green */
        .ubf-progress { height: 6px; border-radius: 6px; background: rgba(12,25,48,0.06); }
        .ubf-progress-fill { background: var(--accent) !important; height: 6px; border-radius: 6px; }

        /* Next/Previous buttons styling to match the pill button on screenshot */
        .ubf-step-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .ubf-step-nav .ubf-next, .ubf-step-nav .ubf-prev, .ubf-step-nav .ubf-btn-primary {
            border-radius: 10px; padding: 0.6rem 1.1rem; font-weight: 700; border: none; cursor: pointer;
        }
        .ubf-step-nav .ubf-next { background: var(--accent); color: #fff; float: right; }
        .ubf-step-nav .ubf-prev { background: transparent; color: var(--text-secondary); border: 1px solid rgba(12,25,48,0.06); }

        /* Make labels slightly bolder and adjust spacing */
        .ubf-v3-form .form-group label { font-weight: 600; margin-bottom: 0.5rem; }

        /* Fix small screens inside admin modal */
        @media (max-width: 767px) {
            .ubf-v3-form .form-group input, .ubf-v3-form .form-group select { height: 44px; }
            .ubf-v3-form .form-grid { grid-template-columns: 1fr; }
            .ubf-step-nav { margin-top: 1rem; }
        }
        
        /* Theme variables updated to the requested palette (green/navy/white/gray) */
        :root {
            --bg-primary: #FFFFFF; /* Secondary White as the page background */
            --bg-secondary: #FFFFFF;
            --bg-tertiary: #f7fbf7; /* very light tint for subtle separation */
            --text-primary: #0C1930; /* Primary Navy for main text */
            --text-secondary: rgba(12, 25, 48, 0.85);
            --accent: #64C493; /* Primary Green accent */
            --accent-hover: #54b07a;
            --border-color: #BCC3C8; /* Accent Gray for borders */
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --info: #2196f3;
            --radius: 8px; /* Button / card radius */
            --shadow: 0 2px 10px rgba(12, 25, 48, 0.06);
            --card-bg: #FFFFFF;
            --input-bg: #FFFFFF;
        }

        /* Base Styles */
        .elite-cuts-admin {
            background: var(--bg-primary);
            color: var(--text-primary);
            padding: 1.5rem;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .elite-cuts-header {
            background: var(--bg-secondary);
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent);
        }

        .shop-name {
            color: var(--accent);
            margin: 0 0 0.25rem 0;
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            letter-spacing: 0.3px;
        }

        .shop-slogan {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.875rem;
            font-weight: 400;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: var(--accent);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(100, 196, 147, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--accent);
        }

        .stat-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .stat-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 400;
        }

        /* Filters */
        .elite-cuts-filters {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }

        .filter-item {
            width: 100%;
        }

        .filter-item label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .date-range {
            display: flex;
            gap: 1rem;
        }

        .date-range .filter-item {
            flex: 1;
            min-width: 150px;
        }

        .search-filter {
            min-width: 250px;
            max-width: 350px;
        }

        .search-container {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .search-icon {
            position: absolute;
            left: 5px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            pointer-events: none;
            z-index: 2;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 3rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background-color: var(--input-bg);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
            box-sizing: border-box;
            text-indent: 0.5rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(100, 196, 147, 0.18);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            margin-left: auto;
            align-self: flex-end;
        }

        .filter-actions .elite-button {
            min-width: 120px;
            justify-content: center;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }

        .filter-actions .elite-button i {
            margin-right: 0.5rem;
        }

        @media (max-width: 1024px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                min-width: 100%;
            }

            .search-filter {
                max-width: 100%;
            }

            .filter-actions {
                margin-left: 0;
                margin-top: 0.5rem;
                justify-content: flex-end;
            }
        }

        @media (max-width: 480px) {
            .date-range {
                flex-direction: column;
                gap: 0.75rem;
            }

            .filter-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .filter-actions .elite-button {
                width: 100%;
            }
        }

        /* Table */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .elite-cuts-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-primary);
            /* Use auto layout so columns can shrink and wrap inside boxed containers */
            table-layout: auto;
            /* allow the table to shrink below previously forced minimums when the WP container is boxed */
            min-width: 0;
        }

        /* Prefer flexible columns; avoid forcing rigid percentages so content can wrap */
        .elite-cuts-table th:nth-child(1), /* ID */
        .elite-cuts-table th:nth-child(2), /* Customer */
        .elite-cuts-table th:nth-child(3), /* Service */
        .elite-cuts-table th:nth-child(4), /* Staff */
        .elite-cuts-table th:nth-child(5), /* Contact */
        .elite-cuts-table th:nth-child(6), /* Date & Time */
        .elite-cuts-table th:nth-child(7), /* Status */
        .elite-cuts-table th:nth-child(8)  /* Actions */ {
            /* let the browser size columns naturally and allow wrapping of cell content */
            width: auto;
            max-width: none;
        }

        .elite-cuts-table th {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            font-weight: 500;
            text-align: left;
            padding: 0.65rem 0.9rem;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
            white-space: normal;
        }

        /* Center the Actions header only */
        .elite-cuts-table th:nth-child(8) {
            text-align: center;
        }

        .elite-cuts-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            /* Let rows expand naturally to fit wrapped content */
            height: auto;
            white-space: normal; /* allow wrapping */
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        /* Truncate long unbroken strings with ellipsis for specific columns to avoid ugly breaks */
        .elite-cuts-table td:nth-child(1), /* ID */
        .elite-cuts-table td:nth-child(3), /* Service */
        .elite-cuts-table td:nth-child(4), /* Staff */
        .elite-cuts-table td:nth-child(5)  /* Contact */ {
            max-width: 12rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* On very small screens (or extremely narrow boxed containers) allow wrapping instead of ellipsis */
        @media (max-width: 480px) {
            .elite-cuts-table td:nth-child(1),
            .elite-cuts-table td:nth-child(3),
            .elite-cuts-table td:nth-child(4),
            .elite-cuts-table td:nth-child(5) {
                max-width: none;
                white-space: normal;
                overflow-wrap: anywhere;
            }
        }

        /* Actions column - keep centered, but allow buttons to wrap/stack on small widths */
        .elite-cuts-table td.actions {
            text-align: center;
            vertical-align: middle;
            display: table-cell; /* Ensure proper table cell behavior */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        /* Buttons inside action cells should wrap and be compact */
        .elite-cuts-table td.actions .elite-button {
            display: inline-flex;
            margin: 0 0.2rem 0.2rem 0.2rem;
            min-width: 0;
            padding: 0.35rem 0.6rem;
            font-size: 0.75rem;
            height: auto;
        }

        /* Stack action buttons vertically on narrow containers so they don't force horizontal scrolling */
        @media (max-width: 720px) {
            .elite-cuts-table td.actions .elite-button { display: block; width: 100%; margin: 0 0 0.5rem 0; }
        }

        .elite-cuts-table tbody tr:last-child td {
            border-bottom: none;
        }

        .elite-cuts-table tbody tr:hover {
            background: rgba(100, 196, 147, 0.04);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .status-pending {
            background: rgba(0, 0, 0, 0.03);
            color: var(--warning);
            border: 1px solid rgba(255, 193, 7, 0.12);
        }

        .status-confirmed {
            background: rgba(100, 196, 147, 0.08);
            color: var(--accent);
            border: 1px solid rgba(100, 196, 147, 0.16);
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--accent);
            color: var(--text-primary);
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .btn-view {
            color: var(--info);
        }

        .btn-edit {
            color: var(--accent);
        }

        .btn-delete {
            color: var(--danger);
        }

        /* Buttons */
        .elite-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: var(--text-primary);
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }

        .elite-button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(100, 196, 147, 0.18);
        }

        .elite-button.secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .elite-button.secondary:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-color: var(--text-secondary);
        }

        /* Small button variant for actions */
        .elite-button.small {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 500;
            min-width: 60px;
            text-transform: none;
            letter-spacing: 0.2px;
            border-radius: 4px;
        }

        .elite-button.small:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(100, 196, 147, 0.15);
        }

        /* Loading State */
        .loading-row td {
            text-align: center;
            padding: 2.5rem;
            color: var(--text-secondary);
        }

        .loading-spinner {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid rgba(100, 196, 147, 0.08);
            border-radius: 50%;
            border-top-color: var(--accent);
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .elite-cuts-admin {
                padding: 1rem;
            }

            .elite-cuts-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .elite-cuts-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-left {
                flex-direction: column;
                gap: 0.75rem;
            }

            .filter-group {
                width: 100%;
                justify-content: space-between;
            }

            .search-container {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .elite-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* ========================================
           Manager Booking Overlay Styles
           ======================================== */

        /* Overlay backdrop and container */
        .manager-booking-overlay,
        .manager-booking-success {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .manager-booking-overlay.active,
        .manager-booking-success.active {
            opacity: 1;
            visibility: visible;
        }

        .manager-booking-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(12, 25, 48, 0.75);
            backdrop-filter: blur(4px);
        }

        /* Main container */
        .manager-booking-container {
            position: relative;
            background: var(--bg-primary);
            border-radius: 12px;
            width: 90%;
            max-width: 750px;
            max-height: 90vh;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 0 20px 60px rgba(12, 25, 48, 0.2);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transform: scale(0.9) translateY(30px);
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .manager-booking-overlay.active .manager-booking-container {
            transform: scale(1) translateY(0);
        }

        /* Header */
        .manager-booking-header {
            position: relative;
            padding: 1.25rem 1.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .manager-booking-header::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--accent);
            border-radius: 3px;
        }

        .header-content {
            text-align: center;
            width: 100%;
        }

        .booking-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.375rem 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .booking-title i {
            width: 38px;
            height: 38px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .booking-subtitle {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.9rem;
        }

        .close-overlay {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
        }

        .close-overlay:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Form wrapper */
        .manager-booking-form-wrapper {
            padding: 1.25rem 1.75rem 1.75rem;
            box-sizing: border-box;
            width: 100%;
        }

        /* Stepper */
        .manager-stepper {
            margin-bottom: 1.5rem;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 17px;
            left: calc(50% + 17px);
            right: calc(-50% + 17px);
            height: 2px;
            background: #e6eaef;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background: var(--accent);
        }

        .step .num {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e6eaef;
            color: #667585;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 2;
            position: relative;
            transition: all 0.3s ease;
        }

        .step.active .num {
            background: var(--accent);
            color: white;
        }

        .step .label {
            font-size: 0.8rem;
            margin-top: 0.375rem;
            color: #667585;
            text-align: center;
            font-weight: 500;
        }

        .step.active .label {
            color: var(--text-primary);
            font-weight: 600;
        }

        .progress {
            height: 4px;
            background: #e6eaef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #4fb88a);
            transition: width 0.3s ease;
        }

        /* Form steps */
        .form-step,
        .ubf-form-step {
            display: none;
        }

        .form-step.active,
        .ubf-form-step.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        /* Ensure UBF v3 steps inside the manager overlay show when active.
           Use higher specificity and !important to override external CSS that
           may hide steps (some UBF CSS shows only data-step="1"). */
        .manager-booking-container .ubf-form-step { display: none !important; }
        .manager-booking-container .ubf-form-step.active { display: block !important; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 1.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .section-title i {
            color: var(--accent);
            font-size: 1rem;
        }

        /* Form layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.125rem;
            margin-bottom: 1.25rem;
            width: 100%;
            box-sizing: border-box;
        }

        @media (min-width: 600px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
        }

        .form-group {
            position: relative;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
        }

        .required {
            color: var(--accent);
            font-weight: 600;
        }

        /* Input styling */
        .input-wrapper {
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .input-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 0.875rem;
            z-index: 2;
            width: 18px;
            text-align: center;
            pointer-events: none;
        }

        .manager-booking-form input,
        .manager-booking-form select,
        .manager-booking-form textarea {
            width: 100%;
            padding: 0.6875rem 0.75rem 0.6875rem 4.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: var(--bg-primary);
            color: var(--text-primary);
            box-sizing: border-box;
            max-width: 100%;
        }

        .manager-booking-form input:focus,
        .manager-booking-form select:focus,
        .manager-booking-form textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(100, 196, 147, 0.12);
            transform: translateY(-1px);
        }

        /* Select styling */
        .service-selector,
        .staff-selector {
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .service-selector select,
        .staff-selector select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding: 0.6875rem 3rem 0.6875rem 4.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .select-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--text-secondary);
            font-size: 0.75rem;
            width: 18px;
            text-align: center;
        }

        .select-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Textarea styling */
        .textarea-wrapper {
            position: relative;
        }

        .textarea-wrapper .input-icon {
            top: 0.875rem;
            transform: none;
            width: 20px;
            height: 20px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            left: 1.5rem;
        }

        .textarea-wrapper textarea {
            padding-left: 4.5rem;
            padding-top: 0.875rem;
            min-height: 80px;
            resize: vertical;
        }

        /* Service info */
        .selected-service-info {
            margin-top: 0.75rem;
            padding: 0.875rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        .selected-service-info h4 {
            margin: 0 0 0.375rem 0;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .selected-service-info p {
            margin: 0.1875rem 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .service-price {
            font-weight: 600;
            color: var(--accent);
        }

        /* Booking summary */
        .booking-summary {
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            padding: 1.125rem;
            margin-bottom: 1.125rem;
        }

        .summary-section {
            margin-bottom: 0.75rem;
        }

        .summary-section:last-child {
            margin-bottom: 0;
        }

        .summary-section h4 {
            margin: 0 0 0.375rem 0;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .summary-section p {
            margin: 0.1875rem 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        /* Step navigation */
        .step-nav {
            display: flex;
            justify-content: flex-end;
            gap: 0.875rem;
            margin-top: 1.5rem;
            padding-top: 1.125rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-prev,
        .btn-next,
        .btn-submit {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .btn-prev {
            background: var(--text-primary);
            color: white;
        }

        .btn-prev:hover {
            background: #0a1726;
            transform: translateY(-1px);
        }

        .btn-next,
        .btn-submit {
            background: var(--accent);
            color: white;
        }

        .btn-next:hover,
        .btn-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(100, 196, 147, 0.3);
        }

        /* Success overlay */
        .success-container {
            position: relative;
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
            transform: scale(0.9);
            transition: all 0.3s ease;
        }

        .manager-booking-success.active .success-container {
            transform: scale(1);
        }

        .success-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .success-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.75rem 0;
        }

        .success-message {
            color: var(--text-secondary);
            margin: 0 0 2rem 0;
            line-height: 1.5;
        }

        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-primary,
        .btn-secondary {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--text-primary);
            color: white;
        }

        .btn-secondary:hover {
            background: #0a1726;
            transform: translateY(-1px);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .manager-booking-container {
                width: 95%;
                max-height: 95vh;
            }

            .manager-booking-header,
            .manager-booking-form-wrapper {
                padding: 1.5rem;
            }

            .booking-title {
                font-size: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .steps {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .step {
                flex: 0 0 calc(50% - 0.25rem);
            }

            .step:nth-child(odd):not(:last-child)::after {
                display: none;
            }

            .success-actions {
                flex-direction: column;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .manager-booking-header,
            .manager-booking-form-wrapper {
                padding: 1rem;
            }

            .booking-title {
                font-size: 1.25rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .booking-title i {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .step-nav {
                flex-direction: column;
            }

            .btn-prev,
            .btn-next,
            .btn-submit {
                width: 100%;
                justify-content: center;
            }
        }

        /* ========================================
           Staff Grid and Enhanced UI Styles
           ======================================== */

        /* Staff Grid Styles */
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.875rem;
            margin-top: 0.75rem;
            padding: 0.875rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            min-height: 100px;
        }

        .staff-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem 0.5rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-height: 85px;
            position: relative;
        }

        .staff-card:hover {
            border-color: var(--accent);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(100, 196, 147, 0.15);
        }

        .staff-card.selected {
            border-color: var(--accent);
            background: rgba(100, 196, 147, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(100, 196, 147, 0.2);
        }

        .staff-card.selected::after {
            content: '✓';
            position: absolute;
            top: 6px;
            right: 6px;
            width: 16px;
            height: 16px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 0.375rem;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .staff-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.375rem;
        }

        .staff-name {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.2;
            word-wrap: break-word;
            text-align: center;
        }

        .staff-grid-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 1.5rem 0.875rem;
            color: var(--text-secondary);
            font-style: italic;
            background: var(--bg-primary);
            border: 2px dashed var(--border-color);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            font-size: 0.85rem;
        }

        /* Service Selector Improvements */
        .service-selector {
            position: relative;
            margin-bottom: 1rem;
        }

        .service-selector select {
            width: 100%;
            padding: 0.875rem 2.75rem 0.875rem 2.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            appearance: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .service-selector select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(100, 196, 147, 0.12);
        }

        .service-selector .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 1rem;
            z-index: 2;
            pointer-events: none;
        }

        .service-selector .select-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Selected Service Info Improvements */
        .selected-service-info {
            display: none;
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            border-left: 4px solid var(--accent);
        }

        .selected-service-info.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .selected-service-info h4 {
            margin: 0 0 0.75rem 0;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .selected-service-info h4 i {
            color: var(--accent);
        }

        .selected-service-info .service-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
        }

        .selected-service-info .service-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .selected-service-info .service-detail i {
            color: var(--accent);
            width: 16px;
            text-align: center;
        }

        .selected-service-info .service-price {
            font-weight: 600;
            color: var(--accent);
        }

        /* Better input alignment and spacing */
        .manager-booking-form input,
        .manager-booking-form select,
        .manager-booking-form textarea {
            width: 100%;
            padding: 0.6875rem 0.75rem 0.6875rem 4.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: var(--bg-primary);
            color: var(--text-primary);
            box-sizing: border-box;
            max-width: 100%;
        }

        .manager-booking-form input:focus,
        .manager-booking-form select:focus,
        .manager-booking-form textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(100, 196, 147, 0.12);
            transform: translateY(-1px);
        }

        /* Input wrapper improvements */
        .input-wrapper {
            position: relative;
            width: 100%;
            display: block;
            box-sizing: border-box;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 0.875rem;
            z-index: 2;
            pointer-events: none;
            width: 16px;
            text-align: center;
        }

        /* Form group spacing improvements */
        .form-group {
            position: relative;
            margin-bottom: 1rem;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Responsive improvements for staff grid */
        @media (max-width: 768px) {
            .manager-booking-container {
                width: 95%;
                max-width: 480px;
                margin: 0.75rem auto;
            }

            .manager-booking-header {
                padding: 1rem 1.25rem 0.75rem;
            }

            .manager-booking-form-wrapper {
                padding: 1rem 1.25rem 1.25rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.875rem;
            }

            .staff-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.625rem;
                padding: 0.625rem;
            }

            .staff-card {
                padding: 0.625rem 0.375rem;
                min-height: 75px;
            }

            .staff-avatar,
            .staff-initials {
                width: 32px;
                height: 32px;
                font-size: 0.75rem;
                margin-bottom: 0.25rem;
            }

            .staff-name {
                font-size: 0.7rem;
            }

            .booking-title {
                font-size: 1.25rem;
            }

            .booking-title i {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }

            .section-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .manager-booking-container {
                width: 95%;
                max-width: 360px;
                margin: 0.5rem auto;
            }

            .manager-booking-header {
                padding: 0.875rem 1rem 0.625rem;
            }

            .manager-booking-form-wrapper {
                padding: 0.875rem 1rem 1.125rem;
            }

            .staff-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
                padding: 0.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .step-nav {
                flex-direction: column-reverse;
                gap: 0.625rem;
            }

            .btn-prev,
            .btn-next,
            .btn-submit {
                width: 100%;
                justify-content: center;
                padding: 0.625rem 1rem;
                font-size: 0.8rem;
            }

            .booking-title {
                font-size: 1.125rem;
                flex-direction: column;
                gap: 0.375rem;
            }

            .manager-booking-form input,
            .manager-booking-form select,
            .manager-booking-form textarea {
                padding: 0.5625rem 0.625rem 0.5625rem 3.5rem;
                font-size: 0.8rem;
            }

            .input-icon {
                font-size: 0.75rem;
                left: 1rem;
                width: 14px;
            }

            .select-icon {
                font-size: 0.7rem;
                right: 0.625rem;
                width: 14px;
            }

            .textarea-wrapper .input-icon {
                width: 18px;
                height: 18px;
                font-size: 0.65rem;
                left: 1rem;
            }

            .textarea-wrapper textarea {
                padding-left: 3.5rem;
                min-height: 70px;
            }
        }

        /* Override: remove all form icons and reset input padding */
        .manager-booking-form .input-icon,
        .manager-booking-form .select-icon,
        .manager-booking-form .textarea-wrapper .input-icon,
        .manager-booking-form i.input-icon {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .manager-booking-form input,
        .manager-booking-form select,
        .manager-booking-form textarea {
            padding-left: 0.75rem !important;
        }

        /* Mobile override */
        @media (max-width: 767px) {
            .manager-booking-form input,
            .manager-booking-form select,
            .manager-booking-form textarea {
                padding-left: 0.75rem !important;
            }
        }
    </style>

    <style>
    /* UBF v3 visual tokens for manager overlay */
    :root{
        --ubf-font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --ubf-primary-navy: #0C1930;
        --ubf-accent: #64C493;
        --ubf-radius: 12px;
        --ubf-shadow: 0 8px 24px rgba(12,25,48,0.06);
        --ubf-bg: #FFFFFF;
    }

    .manager-booking-container { font-family: var(--ubf-font-family); color: var(--ubf-primary-navy); background: var(--ubf-bg); padding: 20px; border-radius: var(--ubf-radius); max-width: 820px; margin: 0 auto; box-shadow: var(--ubf-shadow); }
    .manager-booking-header { margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid rgba(12,25,48,0.06); }
    .manager-booking-header .booking-title { font-weight:700; font-size:1.6rem; margin:0; color:var(--ubf-primary-navy); }
    .manager-booking-header .booking-subtitle { color:#51647a; margin-top:6px; }

    .manager-booking-form-wrapper { background: var(--ubf-bg); padding: 20px; border-radius: var(--ubf-radius); box-shadow: var(--ubf-shadow); }
    .manager-booking-form .form-section { margin-bottom: 18px; }
    .manager-booking-form .section-title { font-size: 1.05rem; font-weight:600; color:var(--ubf-primary-navy); margin-bottom:8px; }

    /* Inputs like UBF v3 */
    .manager-booking-form input[type="text"],
    .manager-booking-form input[type="email"],
    .manager-booking-form input[type="tel"],
    .manager-booking-form input[type="date"],
    .manager-booking-form input[type="time"],
    .manager-booking-form select,
    .manager-booking-form textarea {
        width:100%;
        padding:12px;
        border:1px solid #e6eaef;
        border-radius:var(--ubf-radius);
        margin-bottom:12px;
        font-size:0.95rem;
        box-sizing:border-box;
        background: var(--ubf-bg);
        color: var(--ubf-primary-navy);
    }

    .manager-booking-form textarea { min-height:100px; }

    .manager-booking-form .form-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:16px; }
    .manager-booking-form .btn { padding: 10px 20px; border-radius: 10px; font-weight:700; }
    .manager-booking-form .btn-primary { background: var(--ubf-accent); color: #fff; border: 1px solid var(--ubf-accent); }
    .manager-booking-form .btn-secondary { background: transparent; color: rgba(12,25,48,0.65); border:1px solid #e6eaef; }

    /* keep inputs compact now that icons are removed */
    .manager-booking-form input, .manager-booking-form select, .manager-booking-form textarea { padding-left: 12px !important; }
    @media (max-width:767px) { .manager-booking-container { padding:16px; } .manager-booking-form .form-actions { flex-direction:column; } }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Native click capture logger (runs even if jQuery isn't bound yet)
        try {
            document.addEventListener('click', function(e){
                var btn = e.target.closest && e.target.closest('button, .elite-button, .edit-booking, .delete-booking, #apply-filters, #reset-filters, #view-booking, #create-another');
                if (btn) {
                    try {
                        console.log('[NativeClick] target:', btn.id || '(no-id)', 'classes:', btn.className || '(no-class)', 'text:', (btn.textContent || '').trim().slice(0,40));
                    } catch(err) { console.log('[NativeClick] logging error', err); }
                }
            }, true);
        } catch(err) { console.log('Native click logger not available', err); }

    console.log('Manage Bookings initialized with server-side data');

    // Resolve AJAX endpoint robustly: prefer localized UBF/admin objects, then global ajaxurl, then PHP admin-ajax URL
    var manageAjaxUrl = (window.userBookingV3 && userBookingV3.ajaxurl) || (window.userBookingV3 && window.userBookingV3.ajaxurl) || (window.eliteManageBookings && eliteManageBookings.ajaxUrl) || (typeof ajaxurl !== 'undefined' && ajaxurl) || '<?php echo admin_url('admin-ajax.php'); ?>';
    console.log('[ManageBookings] AJAX endpoint resolved to:', manageAjaxUrl);
        
        // Store original bookings data for filtering
        let allBookings = [];
        $('#bookings-list tr[data-booking-id]').each(function() {
            const $row = $(this);
            allBookings.push({
                element: $row,
                id: $row.data('booking-id'),
                customer: $row.find('td:eq(1)').text().trim().toLowerCase(),
                service: $row.find('td:eq(2)').text().trim().toLowerCase(),
                contact: $row.find('td:eq(3)').text().trim().toLowerCase(),
                date: $row.find('td:eq(4)').text().trim(),
                status: $row.find('.status-badge').attr('class').replace('status-badge status-', ''),
                originalIndex: $row.index()
            });
        });
        
        console.log('Loaded ' + allBookings.length + ' bookings for filtering');

        // Filter function
        function filterBookings() {
            const dateFrom = $('#filter-from').val();
            const dateTo = $('#filter-to').val();
            const status = $('#filter-status').val();
            const search = $('#booking-search').val().toLowerCase();
            
            console.log('Filtering with:', { dateFrom, dateTo, status, search });
            
            let visibleCount = 0;
            
            allBookings.forEach(function(booking) {
                let show = true;
                
                // Date filtering (simplified - just check if date contains the filter)
                if (dateFrom && booking.date.indexOf(dateFrom) === -1) {
                    show = false;
                }
                if (dateTo && booking.date.indexOf(dateTo) === -1) {
                    show = false;
                }
                
                // Status filtering
                if (status && booking.status !== status) {
                    show = false;
                }
                
                // Search filtering
                if (search && 
                    booking.customer.indexOf(search) === -1 && 
                    booking.service.indexOf(search) === -1 && 
                    booking.contact.indexOf(search) === -1) {
                    show = false;
                }
                
                if (show) {
                    booking.element.show();
                    visibleCount++;
                } else {
                    booking.element.hide();
                }
            });
            
            console.log('Showing ' + visibleCount + ' of ' + allBookings.length + ' bookings');
            
            // Show "no results" message if nothing visible
            if (visibleCount === 0 && allBookings.length > 0) {
                if ($('#no-filter-results').length === 0) {
                    $('#bookings-list').append('<tr id="no-filter-results"><td colspan="7" class="no-bookings">No bookings match your filters</td></tr>');
                }
            } else {
                $('#no-filter-results').remove();
            }
        }

        // Filter event handlers
        $('#apply-filters').on('click', function() {
            console.log('Apply filters clicked');
            filterBookings();
        });

        // Live filtering when the dropdowns or date inputs change
        $('#filter-status').on('change', function() { filterBookings(); });
        $('#filter-from, #filter-to').on('change', function() { filterBookings(); });

        // Reset filters: clear inputs, restore original order, and re-run filtering
        $('#reset-filters').on('click', function() {
            console.log('Reset filters clicked');
            // Clear values (reset selects to first option)
            $('#filter-from').val('');
            $('#filter-to').val('');
            $('#filter-status').prop('selectedIndex', 0);
            $('#booking-search').val('');

            // Restore original order (use originalIndex captured on load)
            allBookings.sort(function(a,b){ return a.originalIndex - b.originalIndex; });
            allBookings.forEach(function(booking){
                $('#bookings-list').append(booking.element);
            });

            // Show all bookings and clear any "no results" message
            allBookings.forEach(function(booking) { booking.element.show(); });
            $('#no-filter-results').remove();

            // Re-run the filtering once (now cleared) to ensure UI consistency
            filterBookings();
        });
        
        // Search on enter key
        $('#booking-search').on('keypress', function(e) {
            if (e.which === 13) {
                console.log('Search enter pressed');
                filterBookings();
            }
        });
        
        // Real-time search
        $('#booking-search').on('input', function() {
            filterBookings();
        });

        // Edit/Delete button handlers (delegated for robustness)
        $(document).on('click', '.edit-booking', function() {
            const bookingId = $(this).data('id');
            console.log('Edit booking (delegated):', bookingId);
            // Fetch booking details and populate the manager booking form for editing
            $.post(manageAjaxUrl, { action: 'elite_cuts_get_booking', id: bookingId, nonce: '<?php echo wp_create_nonce('manage_bookings_nonce'); ?>' })
                .done(function(resp){
                    if (resp && resp.success && resp.data && resp.data.booking) {
                        const b = resp.data.booking;
                        // populate fields (support ubf ids and legacy)
                        $('#booking-id').val(b.id || b.ID || '');
                        if ($('#ubf_customer_name').length) { $('#ubf_customer_name').val(b.customer_name); } else { $('#customer-name').val(b.customer_name); }
                        if ($('#ubf_customer_email').length) { $('#ubf_customer_email').val(b.customer_email); } else { $('#customer-email').val(b.customer_email); }
                        if ($('#ubf_customer_phone').length) { $('#ubf_customer_phone').val(b.customer_phone); } else { $('#customer-phone').val(b.customer_phone); }
                        if ($('#ubf_service_id').length) { $('#ubf_service_id').val(b.service_id); } else { $('#service-select').val(b.service_id); }
                        if ($('#ubf_staff_id').length) { $('#ubf_staff_id').val(b.staff_id); }
                        if ($('#ubf_staff_name_snapshot').length) { $('#ubf_staff_name_snapshot').val(b.staff_name || ''); }
                        if ($('#staff-select').length) { $('#staff-select').val(b.staff_id); }
                        if ($('#ubf_preferred_date').length) { $('#ubf_preferred_date').val(b.preferred_date); } else { $('#booking-date').val(b.preferred_date); }
                        if ($('#ubf_preferred_time').length) { $('#ubf_preferred_time').val(b.preferred_time); } else { $('#booking-time').val(b.preferred_time); }
                        if ($('#ubf_message').length) { $('#ubf_message').val(b.message); } else { $('#booking-notes').val(b.message); }
                        if ($('#booking-status').length) { $('#booking-status').val(b.booking_status); }

                        // Mark form as edit mode
                        $('#manager-booking-form').data('editing', true);
                        $('#manager-booking-form').data('editingId', b.id || b.ID || '');
                        // Ensure service change triggers staff load and UI updates
                        try {
                            // Set current step to 1 (customer) so fields are visible
                            currentStep = 1;
                            updateStepDisplay();
                            updateProgress();

                            // Trigger change to load staff for the selected service; renderStaffGrid will auto-select staff
                            if ($('#ubf_service_id').length) { $('#ubf_service_id').trigger('change'); }
                            if ($('#service-select').length) { $('#service-select').trigger('change'); }
                        } catch(err) { console.warn('Edit overlay preparation failed', err); }

                        showOverlay();
                    } else {
                        alert('Failed to load booking details');
                    }
                })
                .fail(function(){ alert('Failed to fetch booking'); });
        });

        $(document).on('click', '.delete-booking', function() {
            const bookingId = $(this).data('id');
            if (!bookingId) return;
            if (!confirm('Are you sure you want to delete this booking?')) return;
            console.log('Delete booking (delegated):', bookingId);
            $.post(manageAjaxUrl, { action: 'elite_cuts_delete_booking', id: bookingId, nonce: '<?php echo wp_create_nonce('manage_bookings_nonce'); ?>' })
                .done(function(resp){
                    if (resp && resp.success) {
                        // remove row from DOM
                        $('#bookings-list').find('tr[data-booking-id="' + bookingId + '"]').fadeOut(200, function(){
                            $(this).remove();
                            // Also remove from the in-memory allBookings so Reset won't re-insert it
                            try {
                                allBookings = allBookings.filter(function(b){ return String(b.id) !== String(bookingId); });
                                console.log('Removed booking', bookingId, 'from allBookings. Remaining:', allBookings.length);
                            } catch(err) { console.warn('Failed to remove booking from allBookings', err); }
                        });
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : (resp && resp.message) ? resp.message : 'Failed to delete booking');
                    }
                })
                .fail(function(){ alert('Failed to delete booking'); });
        });

        // Diagnostic: log all button clicks inside the admin area to help debug missing handlers
        $(document).on('click', '.elite-cuts-admin button, .elite-cuts-admin .elite-button, .manager-booking-success button', function(e){
            try {
                const $btn = $(this);
                const id = $btn.attr('id') || '';
                const classes = $btn.attr('class') || '';
                const text = $btn.text().trim().slice(0,40);
                console.log('[Debug][ManageBookings] Button clicked:', { id: id, classes: classes, text: text });
            } catch(err) { console.log('[Debug][ManageBookings] Button click logging failed', err); }
        });

        // Success modal actions
        $(document).on('click', '#view-booking', function() {
            console.log('View Booking clicked');
            // If a new booking was just created, try to highlight it; otherwise reload
            const newId = window.newBookingId || $('#booking-id').val();
            if (newId) {
                const row = $('#bookings-list').find('tr[data-booking-id="' + newId + '"]');
                if (row.length) {
                    $('html,body').animate({ scrollTop: row.offset().top - 100 }, 300);
                    row.addClass('new-booking-highlight');
                    setTimeout(() => row.removeClass('new-booking-highlight'), 2500);
                } else {
                    console.log('New booking row not found, reloading to show it');
                    location.reload();
                }
            } else {
                location.reload();
            }
        });

        $(document).on('click', '#create-another', function() {
            console.log('Create another booking clicked');
            hideOverlay();
            setTimeout(function(){ resetForm(); showOverlay(); }, 300);
        });

        // ========================================
        // Manager Booking Overlay Functionality
        // ========================================

        let currentStep = 1;
        const totalSteps = 4;
        
        // Open overlay (delegated)
        $(document).on('click', '#add-booking-btn', function() {
            console.log('Opening manager booking overlay (delegated)');
            resetForm();
            showOverlay();
            
            // EMERGENCY DEBUG: Force overlay visible if CSS is broken
            setTimeout(function() {
                if (!$('#manager-booking-overlay').hasClass('active')) {
                    console.warn('Overlay not active, forcing visibility');
                    $('#manager-booking-overlay').css({
                        'opacity': '1',
                        'visibility': 'visible',
                        'display': 'flex'
                    });
                }
            }, 100);
        });

        // Close overlay (delegated)
        $(document).on('click', '#close-booking-overlay, .manager-booking-backdrop', function() {
            console.log('Closing overlay (delegated)');
            hideOverlay();
        });

        // Step navigation (delegated)
        // Support both legacy (.btn-next/.btn-prev) and UBF v3 (.ubf-next/.ubf-prev) button classes
        $(document).on('click', '.btn-next, .ubf-next', function(event) {
            console.log('Next button clicked (delegated) - currentStep', currentStep, 'target:', event.target);
            if (validateCurrentStep()) {
                nextStep();
            }
        });

        $(document).on('click', '.btn-prev, .ubf-prev', function(event) {
            console.log('Prev button clicked (delegated) - currentStep', currentStep, 'target:', event.target);
            prevStep();
        });

        // Form submission (delegated) - ensures handler fires when form is submitted
        $(document).on('submit', '#manager-booking-form', function(e) {
            console.log('Manager booking form submit intercepted (delegated)');
            e.preventDefault();
            // Prevent double submissions
            if ($(this).data('submitting')) {
                console.log('Form is already submitting - ignoring duplicate submit');
                return;
            }
            $(this).data('submitting', true);

            if (!validateCurrentStep()) {
                console.log('Validation failed on submit - not proceeding');
                $(this).data('submitting', false);
                return;
            }

            // If editing an existing booking, call update endpoint
            const isEditing = $(this).data('editing');
            const editingId = $(this).data('editingId');
            console.log('Submit state:', { isEditing: isEditing, editingId: editingId });
            if (isEditing && editingId) {
                console.log('Submitting update for booking', editingId);
                const payload = {
                    action: 'elite_cuts_update_booking',
                    nonce: '<?php echo wp_create_nonce('manage_bookings_nonce'); ?>',
                    id: editingId,
                    customer_name: ($('#ubf_customer_name').val() || $('#customer-name').val() || ''),
                    customer_email: ($('#ubf_customer_email').val() || $('#customer-email').val() || ''),
                    customer_phone: ($('#ubf_customer_phone').val() || $('#customer-phone').val() || ''),
                    service_id: ($('#ubf_service_id').val() || $('#service-select').val() || ''),
                    staff_id: ($('#ubf_staff_id').val() || $('#staff-select').val() || ''),
                    preferred_date: ($('#ubf_preferred_date').val() || $('#booking-date').val() || ''),
                    preferred_time: ($('#ubf_preferred_time').val() || $('#booking-time').val() || ''),
                    message: ($('#ubf_message').val() || $('#booking-notes').val() || ''),
                    booking_status: ($('#booking-status').val() || '')
                };

                $.post(manageAjaxUrl, payload)
                    .done(function(resp){
                        console.log('Update response:', resp);
                        if (resp && resp.success) {
                            // update row in table if present
                            const row = $('#bookings-list').find('tr[data-booking-id="' + editingId + '"]');
                            if (row.length) {
                                row.find('td:nth-child(2) strong').text(payload.customer_name || 'Unknown');
                                row.find('.service-name').text($('#ubf_service_id option:selected').text() || $('#service-select option:selected').text() || payload.service_id);
                                row.find('.contact-info').html((payload.customer_email || 'No email') + '<br><small>' + (payload.customer_phone || 'No phone') + '</small>');
                                row.find('td:nth-child(6) strong').text(payload.preferred_date || 'No date');
                                row.find('td:nth-child(6) small').text(payload.preferred_time || 'No time');
                                row.find('.status-badge').text((payload.booking_status || 'pending').charAt(0).toUpperCase() + (payload.booking_status || 'pending').slice(1));
                                // Use the server-provided staff name snapshot if available
                                var staffLabel = (resp && resp.data && resp.data.staff_name) ? resp.data.staff_name : (resp && resp.staff_name ? resp.staff_name : ($('#ubf_staff_name_snapshot').val() || 'Any available staff'));
                                staffLabel = staffLabel || 'Any available staff';
                                row.find('.staff-info').html('<strong>' + $('<div/>').text(staffLabel).html() + '</strong><br><small></small>');
                            }

                                // Also update the in-memory booking snapshot so Reset doesn't revert the change
                                try {
                                    for (let i = 0; i < allBookings.length; i++) {
                                        if (String(allBookings[i].id) === String(editingId)) {
                                            allBookings[i].customer = (payload.customer_name || 'Unknown').toLowerCase();
                                            allBookings[i].service = (row.length ? row.find('.service-name').text().trim().toLowerCase() : (payload.service_id || '')).toLowerCase();
                                            allBookings[i].contact = ((payload.customer_email || 'No email') + ' ' + (payload.customer_phone || 'No phone')).trim().toLowerCase();
                                            allBookings[i].date = payload.preferred_date || allBookings[i].date;
                                            allBookings[i].status = payload.booking_status || allBookings[i].status;
                                            break;
                                        }
                                    }
                                } catch(err) { console.warn('Failed to update allBookings snapshot', err); }

                            // clear editing state and hide overlay
                            $('#manager-booking-form').data('editing', false).data('editingId', null);
                            // clear submitting flag
                            $('#manager-booking-form').data('submitting', false);
                            hideOverlay();
                            showSuccessMessage();
                        } else {
                            alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to update booking');
                            $('#manager-booking-form').data('submitting', false);
                        }
                    })
                    .fail(function(){ alert('Failed to update booking'); $('#manager-booking-form').data('submitting', false); });
                
                return;
            }

            // Not editing — proceed with new booking creation
            submitBooking();
        });

        // Fallback: click on the Create Booking button should trigger the form submit so edit mode is respected
        $(document).on('click', '#manager-booking-form .btn-submit, #manager-booking-form .ubf-btn-primary.btn-submit', function(e) {
            console.log('Create Booking button clicked (delegated fallback)');
            e.preventDefault();
            // Trigger native form submit which will be handled by the delegated submit handler (which checks editing state)
            if (validateCurrentStep()) {
                $('#manager-booking-form').trigger('submit');
            } else {
                console.log('Validation failed on create button click - not proceeding');
            }
        });

        // Service selection change - support both UBF v3 and legacy selects
        $('#ubf_service_id, #service-select').on('change', function() {
            const serviceId = $(this).val();
            const $selected = $(this).find('option:selected');

            if (serviceId) {
                // Show service info (use selected option data attributes where available)
                const price = $selected.data('price') || $selected.attr('data-price');
                const duration = $selected.data('duration') || $selected.attr('data-duration');
                const description = $selected.data('description') || $selected.attr('data-description');

                $('#selected-service-info .service-description').text(description || 'No description available');
                $('#selected-service-info .service-duration span').text(duration || 'Not specified');
                $('#selected-service-info .service-price span').text(price ? '₱' + parseFloat(price).toFixed(2) : 'Price varies');
                $('#selected-service-info').show();

                // Load staff for this service (prefer admin loader)
                if (typeof loadStaffForService === 'function') {
                    loadStaffForService(serviceId);
                }
            } else {
                hideServiceInfo();
                clearStaffGrid();
            }

            updateSummary();
        });

        // Live-update summary when fields change (support UBF v3 and legacy IDs)
        $('#ubf_customer_name, #customer-name, #ubf_customer_email, #customer-email, #ubf_customer_phone, #customer-phone, #ubf_preferred_date, #booking-date, #ubf_preferred_time, #booking-time, #ubf_message, #booking-notes, #ubf_service_id, #service-select').on('input change', function() {
            updateSummary();
        });

        // Functions
        function showOverlay() {
            $('#manager-booking-overlay').addClass('active');
            $('body').css('overflow', 'hidden');
            updateSummary();
            
            // EMERGENCY DEBUG: Force overlay visible with inline styles
            $('#manager-booking-overlay').css({
                'opacity': '1 !important',
                'visibility': 'visible !important',
                'display': 'flex !important',
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100%',
                'height': '100%',
                'z-index': '10000'
            });
            console.log('Overlay forced visible with inline styles');
        }

        function hideOverlay() {
            $('#manager-booking-overlay').removeClass('active');
            $('body').css('overflow', '');
            setTimeout(() => {
                resetForm();
            }, 300);
        }

        function resetForm() {
            currentStep = 1;
            $('#manager-booking-form')[0].reset();
            updateStepDisplay();
            updateProgress();
            hideServiceInfo();
            clearStaffGrid();
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
                updateProgress();
                
                if (currentStep === 4) {
                    updateSummary();
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
                updateProgress();
            }
        }

        function updateStepDisplay() {
            // Hide all steps
            $('.form-step, .ubf-form-step').removeClass('active');
            $('.step').removeClass('active');
            
            // Show current step
            $(`.form-step[data-step="${currentStep}"], .ubf-form-step[data-step="${currentStep}"]`).addClass('active');
            $(`.step[data-step="${currentStep}"]`).addClass('active');
            
            // Update completed steps
            for (let i = 1; i < currentStep; i++) {
                $(`.step[data-step="${i}"]`).addClass('active');
            }
        }

        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            $('.progress-fill').css('width', progress + '%');
        }

        function validateCurrentStep() {
            let isValid = true;
            const currentStepEl = $(`.form-step[data-step="${currentStep}"], .ubf-form-step[data-step="${currentStep}"]`);
            
            // Clear previous errors
            currentStepEl.find('.error').removeClass('error');
            currentStepEl.find('.error-message').remove();
            
            // Validate required fields in current step
            currentStepEl.find('input[required], select[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    if ($(this).next('.error-message').length === 0) {
                        $(this).after('<div class="error-message" style="color: #f44336; font-size: 0.85rem; margin-top: 0.25rem;">This field is required</div>');
                    }
                    isValid = false;
                }
            });
            
            // Additional validation for email
            const email = currentStepEl.find('input[type="email"]');
            if (email.length && email.val()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.val())) {
                    email.addClass('error');
                    if (email.next('.error-message').length === 0) {
                        email.after('<div class="error-message" style="color: #f44336; font-size: 0.85rem; margin-top: 0.25rem;">Please enter a valid email address</div>');
                    }
                    isValid = false;
                }
            }
            
            return isValid;
        }

        function loadServiceInfo(serviceId) {
            // Mock service data - replace with actual AJAX call
            const mockServices = {
                '1': {
                    name: 'Classic Haircut',
                    description: 'Professional haircut with styling',
                    duration: '30 minutes',
                    price: '$25'
                },
                '2': {
                    name: 'Beard Trim',
                    description: 'Precision beard trimming and shaping',
                    duration: '20 minutes',
                    price: '$15'
                }
            };
            
            const service = mockServices[serviceId];
            if (service) {
                $('#selected-service-info .service-description').text(service.description);
                $('#selected-service-info .service-duration span').text(service.duration);
                $('#selected-service-info .service-price span').text(service.price);
                $('#selected-service-info').show();
            }
        }

        function hideServiceInfo() {
            $('#selected-service-info').hide();
        }

        function loadStaffForService(serviceId) {
            console.log('Loading staff for service:', serviceId);
            // prefer the UBF v3 grid if present, otherwise fallback to legacy grid
            const $staffGrid = $('#ubf_staff_grid').length ? $('#ubf_staff_grid') : $('#staff-grid');
            $staffGrid.html('<div class="staff-grid-empty">Loading staff...</div>');
            
            $.ajax({
                url: manageAjaxUrl,
                type: 'POST',
                data: {
                    action: 'elite_cuts_get_staff_for_service',
                    service_id: serviceId,
                    nonce: '<?php echo wp_create_nonce('manage_bookings_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Staff AJAX response:', response);
                    
                    if (response.success && response.data.staff && response.data.staff.length > 0) {
                        console.log('Staff data:', response.data.staff);
                        renderStaffGrid(response.data.staff);
                    } else {
                        console.log('No staff found or failed response');
                        console.log('Response data:', response.data);
                        $staffGrid.html('<div class="staff-grid-empty">No staff available for this service</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading staff:', error);
                    console.error('XHR:', xhr);
                    $staffGrid.html('<div class="staff-grid-empty">Error loading staff</div>');
                }
            });
        }

        function renderStaffGrid(staff) {
            // prefer the UBF v3 grid when available
            const $staffGrid = $('#ubf_staff_grid').length ? $('#ubf_staff_grid') : $('#staff-grid');
            $staffGrid.empty();
            
            // Add "Any staff" option
            const anyStaffCard = $(`
                <div class="staff-card" data-staff-id="" data-id="">
                    <div class="staff-avatar">
                        <div class="staff-initials">ANY</div>
                    </div>
                    <div class="staff-name">Any Available Staff</div>
                </div>
            `);
            $staffGrid.append(anyStaffCard);
            
            // Add staff cards
            staff.forEach(function(member) {
                const initials = member.name.split(' ').map(n => n[0]).join('').substr(0, 2).toUpperCase();
                const avatarHtml = member.avatar ? 
                    `<img src="${member.avatar}" alt="${member.name}">` : 
                    `<div class="staff-initials">${initials}</div>`;
                
                const staffCard = $(`
                    <div class="staff-card" data-staff-id="${member.id}" data-id="${member.id}">
                        <div class="staff-avatar">${avatarHtml}</div>
                        <div class="staff-name">${member.name}</div>
                    </div>
                `);
                $staffGrid.append(staffCard);
            });
            
            // Add delegated click handler on the grid - set the UBF v3 hidden staff id so summary and submit use it
            $staffGrid.off('click', '.staff-card').on('click', '.staff-card', function() {
                $staffGrid.find('.staff-card').removeClass('selected');
                $(this).addClass('selected');
                // support both legacy #staff-select and UBF v3 hidden input #ubf_staff_id
                const sid = $(this).data('staff-id') || $(this).data('id');
                console.log('Staff card selected, id:', sid, 'name:', $(this).find('.staff-name').text());
                if ($('#ubf_staff_id').length) { $('#ubf_staff_id').val(sid); }
                if ($('#staff-select').length) { $('#staff-select').val(sid); }
            });
            
            // Auto-select a staff card if the hidden input already has a value (useful when editing)
            try {
                const pref = ($('#ubf_staff_id').length ? $('#ubf_staff_id').val() : '') || ($('#staff-select').length ? $('#staff-select').val() : '');
                if (pref) {
                    const $match = $staffGrid.find('.staff-card').filter(function(){
                        const sid = $(this).data('staff-id') || $(this).data('id');
                        return String(sid) === String(pref);
                    }).first();
                    if ($match.length) {
                        $match.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false');
                        if ($('#ubf_staff_id').length) { $('#ubf_staff_id').val(pref); }
                        if ($('#staff-select').length) { $('#staff-select').val(pref); }
                        console.log('Auto-selected staff card for id', pref);
                    }
                }
            } catch(err) { console.warn('Auto-select staff failed', err); }

            // Select "Any staff" by default
            anyStaffCard.addClass('selected');
            $('#staff-select').val('');
        }

        function clearStaffGrid() {
            $('#staff-grid').html('<div class="staff-grid-empty">Select a service to choose staff</div>');
            if ($('#ubf_staff_id').length) { $('#ubf_staff_id').val(''); }
            if ($('#staff-select').length) { $('#staff-select').val(''); }
        }

        function hideServiceInfo() {
            $('#selected-service-info').hide();
        }

        function updateSummary() {
            // Customer info - support UBF v3 IDs with legacy fallbacks
            const customerName = ($('#ubf_customer_name').val() || $('#customer-name').val() || '').trim();
            const customerEmail = ($('#ubf_customer_email').val() || $('#customer-email').val() || '').trim();
            const customerPhone = ($('#ubf_customer_phone').val() || $('#customer-phone').val() || '').trim();

            $('#summary-customer').text(customerName || 'Not specified');
            $('#summary-contact').text((customerEmail || 'No email') + (customerPhone ? ' • ' + customerPhone : ''));

            // Service info - prefer UBF v3 select
            const serviceName = ($('#ubf_service_id option:selected').text() || $('#service-select option:selected').text() || '').trim();
            // Staff name: prefer stored booking snapshot in hidden inputs (#ubf_staff_id_name may be set), then selected card, then legacy select text
            let staffName = 'Any available staff';
            // hidden snapshot (set when editing a booking via AJAX payload)
            const snapshot = $('#ubf_staff_name_snapshot').length ? $('#ubf_staff_name_snapshot').val() : '';
            if (snapshot) {
                staffName = snapshot.trim();
            } else {
                const ubfSelected = $('#ubf_staff_grid .staff-card.selected');
                if (ubfSelected.length) { staffName = ubfSelected.find('.staff-name').text().trim() || staffName; }
                else if ($('#staff-select option:selected').length) { const t = $('#staff-select option:selected').text().trim(); if (t) staffName = t; }
            }

            $('#summary-service').text(serviceName || 'No service selected');
            $('#summary-staff').text(staffName);

            // Date/time info - support UBF v3 inputs or legacy
            const date = ($('#ubf_preferred_date').val() || $('#booking-date').val() || '');
            const time = ($('#ubf_preferred_time').val() || $('#booking-time').val() || '');

            if (date && time) {
                const formattedDate = new Date(date).toLocaleDateString();
                $('#summary-datetime').text(`${formattedDate} at ${time}`);
            } else {
                $('#summary-datetime').text('Date and time not set');
            }
        }

        function submitBooking() {
            console.log('Submitting booking...');

            // Safety: don't run create flow if the form is currently in edit mode
            try {
                if ($('#manager-booking-form').data('editing')) {
                    console.log('Form is in editing mode - aborting create to avoid duplicate');
                    $('#manager-booking-form').data('submitting', false);
                    return;
                }
            } catch(err) { console.warn('Error checking editing state', err); }

            // Show loading state
            $('.btn-submit').text('Creating...').prop('disabled', true);

            // Prefer UBF v3 inputs, fall back to legacy IDs
            // Robustly determine staff id: prefer hidden ubf field, then select, then selected card's data attributes
            let staffIdRaw = ($('#ubf_staff_id').length ? $('#ubf_staff_id').val() : '') || ($('#staff-select').length ? $('#staff-select').val() : '');
            if (!staffIdRaw) {
                // try selected cards in either UBF or legacy grid
                const s1 = $('#ubf_staff_grid .staff-card.selected').data('staff-id') || $('#ubf_staff_grid .staff-card.selected').data('id');
                const s2 = $('#staff-grid .staff-card.selected').data('staff-id') || $('#staff-grid .staff-card.selected').data('id');
                staffIdRaw = s1 || s2 || '';
            }
            const staffId = staffIdRaw ? parseInt(staffIdRaw, 10) : '';

            const data = {
                action: 'submit_user_booking_v3',
                nonce: (window.userBookingV3 && userBookingV3.nonce) || $('input[name="booking_nonce"]').val() || '',
                service_id: ($('#ubf_service_id').val() || $('#service-select').val() || ''),
                customer_name: ($('#ubf_customer_name').val() || $('#customer-name').val() || ''),
                customer_email: ($('#ubf_customer_email').val() || $('#customer-email').val() || ''),
                customer_phone: ($('#ubf_customer_phone').val() || $('#customer-phone').val() || ''),
                preferred_date: ($('#ubf_preferred_date').val() || $('#booking-date').val() || ''),
                preferred_time: ($('#ubf_preferred_time').val() || $('#booking-time').val() || ''),
                message: ($('#ubf_message').val() || $('#booking-notes').val() || ''),
                staff_id: staffId,
                booking_status: ($('#booking-status').val() || '')
            };

            var ajaxEndpoint = manageAjaxUrl || ((window.userBookingV3 && userBookingV3.ajaxurl) || ajaxurl || admin_url || '');
            console.log('Booking submit AJAX endpoint:', ajaxEndpoint, 'data:', data);

            $.post(ajaxEndpoint, data)
                .done(function(resp){
                    console.log('Server response:', resp);
                    $('.btn-submit').text('Create Booking').prop('disabled', false);
                    // clear submitting flag
                    $('#manager-booking-form').data('submitting', false);
                    if (resp && resp.success) {
                                // store the newly created booking id so the UI can jump to it
                                if (resp.id) {
                                    window.newBookingId = resp.id;
                                    if ($('#booking-id').length) { $('#booking-id').val(resp.id); }
                                }
                                hideOverlay();
                                showSuccessMessage();
                    } else {
                        alert((resp && resp.message) ? resp.message : 'Failed to create booking');
                    }
                })
                .fail(function(xhr, status, err){
                    console.error('Booking submit failed', status, err);
                    $('.btn-submit').text('Create Booking').prop('disabled', false);
                    // clear submitting flag
                    $('#manager-booking-form').data('submitting', false);
                    alert('Failed to submit booking. Please try again.');
                });
        }

        function showSuccessMessage() {
            $('#manager-booking-success').addClass('active');
            $('body').css('overflow', 'hidden');
        }

        function hideSuccessMessage() {
            $('#manager-booking-success').removeClass('active');
            $('body').css('overflow', '');
        }

        // Success overlay handlers
        $('#view-booking').on('click', function() {
            hideSuccessMessage();
            // If we have a newBookingId, try to scroll to it and highlight; otherwise reload to refresh list
            var id = window.newBookingId || $('#booking-id').val();
            if (id) {
                var $row = $('#bookings-list').find('tr[data-booking-id="' + id + '"]');
                if ($row.length) {
                    // ensure table is visible and scroll to row
                    $('html, body').animate({ scrollTop: $row.offset().top - 100 }, 400);
                    // Add highlight class
                    $row.addClass('new-booking-highlight');
                    setTimeout(function() { $row.removeClass('new-booking-highlight'); }, 5000);
                    return;
                }
            }
            // Fallback: reload the page so the booking list is refreshed
            location.reload();
        });

        $('#create-another').on('click', function() {
            hideSuccessMessage();
            setTimeout(() => {
                resetForm();
                showOverlay();
            }, 300);
        });

        // Close success overlay on backdrop click
        $('.manager-booking-success .manager-booking-backdrop').on('click', function() {
            hideSuccessMessage();
        });

        // Escape key to close overlays
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                if ($('#manager-booking-overlay').hasClass('active')) {
                    hideOverlay();
                } else if ($('#manager-booking-success').hasClass('active')) {
                    hideSuccessMessage();
                }
            }
        });
        
        console.log('Manage Bookings ready with ' + allBookings.length + ' bookings loaded');
    });

    </script>
    <?php
}

// Add the menu item
function elite_cuts_add_admin_menu() {
    add_menu_page(
        'Manage Bookings',
        'Bookings',
        'manage_options',
        'elite-cuts-bookings',
        'elite_cuts_manage_bookings_page',
        'dashicons-calendar-alt',
        30
    );
}
add_action('admin_menu', 'elite_cuts_add_admin_menu');

// Enqueue admin styles and scripts
function elite_cuts_admin_enqueue_scripts($hook) {
    if ('toplevel_page_elite-cuts-bookings' !== $hook) {
        return;
    }
    
    // Enqueue jQuery and jQuery UI
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    
    // Enqueue Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    // Localize script for AJAX
    wp_localize_script('jquery', 'eliteManageBookings', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('manage_bookings_nonce')
    ));

    // Enqueue UBF v3 assets so admin forms can mirror the front-end booking design
    $css_ver = @filemtime(plugin_dir_path(__FILE__) . 'assets/css/user_booking_form.css') ?: '1.0.0';
    wp_enqueue_style(
        'user-booking-form-css-admin',
        plugin_dir_url(__FILE__) . 'assets/css/user_booking_form.css',
        [],
        $css_ver
    );

    $js_ver = @filemtime(plugin_dir_path(__FILE__) . 'assets/js/user-booking-form.js') ?: '1.0.0';
    wp_enqueue_script(
        'user-booking-form-js-admin',
        plugin_dir_url(__FILE__) . 'assets/js/user-booking-form.js',
        array('jquery'),
        $js_ver,
        true
    );

    // Localize booking JS objects expected by UBF v3 (frontend uses userBookingAjax and userBookingV3)
    wp_localize_script('user-booking-form-js-admin', 'userBookingV3', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('user_booking_nonce'),
        'messages' => array(
            'saved' => __('Booking saved. We will contact you shortly.', 'payndle'),
            'error' => __('There was a problem. Please try again.', 'payndle')
        ),
        'bookingHistoryUrl' => ''
    ));

    // Also provide the older userBookingAjax object for compatibility with other scripts
    wp_localize_script('user-booking-form-js-admin', 'userBookingAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('user_booking_nonce'),
        'messages' => array(
            'success' => 'Your booking request has been submitted successfully!',
            'error' => 'Something went wrong. Please try again.'
        )
    ));
}
add_action('admin_enqueue_scripts', 'elite_cuts_admin_enqueue_scripts');

// ========================================
// Manager Booking Data Functions
// ========================================

/**
 * Get services options for the booking form
 */
function elite_cuts_get_services_options($selected_id = '') {
    // Get services from the 'service' custom post type
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

        // Try to read meta fields
        $price = get_post_meta($sid, '_service_price', true);
        $duration = get_post_meta($sid, '_service_duration', true);
        $description = get_post_meta($sid, '_service_description', true);

        // Fall back to alternate meta keys
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
 * AJAX handler to get service information
 */
function elite_cuts_get_service_info() {
    check_ajax_referer('manage_bookings_nonce', 'nonce');
    
    $service_id = intval($_POST['service_id']);
    if (!$service_id) {
        wp_send_json_error('Invalid service ID');
    }

    $service = get_post($service_id);
    if (!$service || $service->post_type !== 'service') {
        wp_send_json_error('Service not found');
    }

    $price = get_post_meta($service_id, '_service_price', true);
    $duration = get_post_meta($service_id, '_service_duration', true);
    $description = get_post_meta($service_id, '_service_description', true);

    // Fall back to alternate meta keys
    if ($price === '') $price = get_post_meta($service_id, 'service_price', true);
    if ($duration === '') $duration = get_post_meta($service_id, 'service_duration', true);
    if ($description === '') $description = $service->post_content;

    $price_label = $price ? '₱' . number_format(floatval($price), 2) : 'Price varies';

    wp_send_json_success([
        'name' => $service->post_title,
        'description' => $description,
        'duration' => $duration,
        'price' => $price_label
    ]);
}
add_action('wp_ajax_get_service_info', 'elite_cuts_get_service_info');

/**
 * AJAX handler to get staff for a service
 */
function elite_cuts_get_staff_for_service() {
    check_ajax_referer('manage_bookings_nonce', 'nonce');
    
    // Debug: Log that the function was called
    error_log("AJAX function elite_cuts_get_staff_for_service called");
    error_log("POST data: " . print_r($_POST, true));
    
    $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
    if (!$service_id) {
        error_log("No service ID provided");
        wp_send_json_error('Invalid service');
    }

    $staff = array();
    
    // Debug: Log the service ID
    error_log("Getting staff for service ID: " . $service_id);

    // Primary source: assigned_staff on the service post
    $assigned = get_post_meta($service_id, 'assigned_staff', true);
    error_log("Assigned staff meta: " . print_r($assigned, true));
    
    if (is_array($assigned) && !empty($assigned)) {
        $assigned_ids = array_map('absint', array_values($assigned));
        $assigned_ids = array_filter($assigned_ids);
        error_log("Assigned staff IDs: " . print_r($assigned_ids, true));
        
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
            error_log("Assigned staff query found: " . $q1->found_posts . " posts");
            
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
        error_log("No assigned staff found, trying fallback methods");
        
        $meta_query = array();
        
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
            error_log("Added staff_role query for: " . $s_post->post_title);
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
        
        error_log("Fallback query args: " . print_r($args, true));
        
        $q = new WP_Query($args);
        error_log("Fallback query found: " . $q->found_posts . " posts");
        
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
    
    // Additional fallback: get ALL staff if no specific assignments found
    if (empty($staff)) {
        error_log("No staff found with meta queries, getting all staff");
        
        $all_staff_args = array(
            'post_type' => 'staff',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids'
        );
        
        $all_staff_query = new WP_Query($all_staff_args);
        error_log("All staff query found: " . $all_staff_query->found_posts . " posts");
        
        if ($all_staff_query->have_posts()) {
            foreach ($all_staff_query->posts as $pid) {
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

    error_log("Final staff array: " . print_r($staff, true));

    wp_send_json_success([
        'staff' => $staff,
        'debug' => [
            'service_id' => $service_id,
            'assigned_meta' => $assigned,
            'staff_count' => count($staff)
        ]
    ]);
}
add_action('wp_ajax_elite_cuts_get_staff_for_service', 'elite_cuts_get_staff_for_service');
add_action('wp_ajax_nopriv_elite_cuts_get_staff_for_service', 'elite_cuts_get_staff_for_service');

/**
 * AJAX: Delete a booking (admin)
 */
function elite_cuts_delete_booking_ajax() {
    check_ajax_referer('manage_bookings_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Get current business ID
    $current_user_id = get_current_user_id();
    $current_business_id = 0;
    $user_business = get_posts([
        'post_type' => 'payndle_business',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_business_owner_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($user_business)) {
        $current_business_id = $user_business[0]->ID;
    }
    
    if (!$current_business_id) {
        wp_send_json_error(['message' => 'No business found. Please create a business profile first.']);
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error(['message' => 'Invalid booking id']);
    }

    // Get business code
    $business_code = get_post_meta($current_business_id, '_business_code', true);
    if (!$business_code) {
        wp_send_json_error(['message' => 'Invalid business code. Please contact support.']);
    }

    // Verify booking belongs to current business
    $booking_business_code = '';
    if (get_post_type($id) === 'service_booking') {
        $booking_business_code = get_post_meta($id, '_business_code', true);
    } else {
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        $booking_business_code = $wpdb->get_var($wpdb->prepare(
            "SELECT business_code FROM $booking_table WHERE id = %d",
            $id
        ));
    }

    if ($booking_business_code !== $business_code) {
        wp_send_json_error(['message' => 'Permission denied: Booking does not belong to your business']);
    }

    // Try to delete as CPT first (permanent delete)
    $post = get_post($id);
    if ($post && $post->post_type === 'service_booking') {
        // Remove attached media (featured image and other attachments) to avoid orphaned files
        // Delete featured image if present
        $thumb_id = get_post_thumbnail_id($id);
        if ($thumb_id) {
            wp_delete_attachment($thumb_id, true);
        }

        // Delete any attachments attached to this post
        $attachments = get_attached_media('', $id);
        if (!empty($attachments)) {
            foreach ($attachments as $att) {
                wp_delete_attachment($att->ID, true);
            }
        }

        // Finally delete the post permanently
        $deleted = wp_delete_post($id, true);
        // As a fallback, also remove any legacy table row that might exist with same id
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        $wpdb->delete($booking_table, ['id' => $id]);

        if ($deleted) {
            wp_send_json_success(['message' => 'Booking permanently deleted', 'deleted' => true]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete booking']);
        }
    }

    // Fallback: legacy table deletion
    global $wpdb;
    $booking_table = $wpdb->prefix . 'service_bookings';
    $deleted = $wpdb->delete($booking_table, ['id' => $id]);
    if ($deleted) {
        wp_send_json_success(['message' => 'Booking deleted (legacy)', 'deleted' => true]);
    }

    wp_send_json_error(['message' => 'Booking not found']);
}
add_action('wp_ajax_elite_cuts_delete_booking', 'elite_cuts_delete_booking_ajax');


/**
 * AJAX: Get booking details for editing
 */
function elite_cuts_get_booking_ajax() {
    check_ajax_referer('manage_bookings_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Get current business ID
    $current_user_id = get_current_user_id();
    $current_business_id = 0;
    $user_business = get_posts([
        'post_type' => 'payndle_business',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_business_owner_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($user_business)) {
        $current_business_id = $user_business[0]->ID;
    }
    
    if (!$current_business_id) {
        wp_send_json_error(['message' => 'No business found. Please create a business profile first.']);
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error(['message' => 'Invalid booking id']);
    }

    // Get business code
    $business_code = get_post_meta($current_business_id, '_business_code', true);
    if (!$business_code) {
        wp_send_json_error(['message' => 'Invalid business code. Please contact support.']);
    }

    // Verify booking belongs to current business
    $booking_business_code = '';
    if (get_post_type($id) === 'service_booking') {
        $booking_business_code = get_post_meta($id, '_business_code', true);
    } else {
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        $booking_business_code = $wpdb->get_var($wpdb->prepare(
            "SELECT business_code FROM $booking_table WHERE id = %d",
            $id
        ));
    }

    if ($booking_business_code !== $business_code) {
        wp_send_json_error(['message' => 'Permission denied: Booking does not belong to your business']);
    }

    // Try CPT first
    if (get_post_type($id) === 'service_booking') {
        $data = [];
        $data['id'] = $id;
        $data['customer_name'] = get_post_meta($id, '_customer_name', true);
        $data['customer_email'] = get_post_meta($id, '_customer_email', true);
        $data['customer_phone'] = get_post_meta($id, '_customer_phone', true);
        $data['service_id'] = get_post_meta($id, '_service_id', true);
    $data['staff_id'] = get_post_meta($id, '_staff_id', true);
    // Provide staff name snapshot if available so the admin overlay can display a stable label
    $data['staff_name'] = get_post_meta($id, '_staff_name', true) ?: '';
        $data['preferred_date'] = get_post_meta($id, '_preferred_date', true);
        $data['preferred_time'] = get_post_meta($id, '_preferred_time', true);
        $data['message'] = get_post_field('post_content', $id);
        $data['booking_status'] = get_post_meta($id, '_booking_status', true) ?: 'pending';

    wp_send_json_success(['booking' => $data]);
    }

    // Legacy fallback from table
    global $wpdb;
    $booking_table = $wpdb->prefix . 'service_bookings';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $booking_table WHERE id = %d", $id));
    if ($row) {
        wp_send_json_success(['booking' => $row]);
    }

    wp_send_json_error(['message' => 'Booking not found']);
}
add_action('wp_ajax_elite_cuts_get_booking', 'elite_cuts_get_booking_ajax');


/**
 * AJAX: Update an existing booking (admin)
 */
function elite_cuts_update_booking_ajax() {
    check_ajax_referer('manage_bookings_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Get current business ID
    $current_user_id = get_current_user_id();
    $current_business_id = 0;
    $user_business = get_posts([
        'post_type' => 'payndle_business',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_business_owner_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($user_business)) {
        $current_business_id = $user_business[0]->ID;
    }
    
    if (!$current_business_id) {
        wp_send_json_error(['message' => 'No business found. Please create a business profile first.']);
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error(['message' => 'Invalid booking id']);
    }

    // Get business code
    $business_code = get_post_meta($current_business_id, '_business_code', true);
    if (!$business_code) {
        wp_send_json_error(['message' => 'Invalid business code. Please contact support.']);
    }

    // Verify booking belongs to current business
    $booking_business_code = '';
    if (get_post_type($id) === 'service_booking') {
        $booking_business_code = get_post_meta($id, '_business_code', true);
    } else {
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        $booking_business_code = $wpdb->get_var($wpdb->prepare(
            "SELECT business_code FROM $booking_table WHERE id = %d",
            $id
        ));
    }

    if ($booking_business_code !== $business_code) {
        wp_send_json_error(['message' => 'Permission denied: Booking does not belong to your business']);
    }

    $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
    $customer_email = sanitize_email($_POST['customer_email'] ?? '');
    $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $staff_id = intval($_POST['staff_id'] ?? 0);
    $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
    $preferred_time = sanitize_text_field($_POST['preferred_time'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $booking_status = sanitize_text_field($_POST['booking_status'] ?? 'pending');

    // If CPT
    if (get_post_type($id) === 'service_booking') {
        wp_update_post(['ID' => $id, 'post_title' => $customer_name . ' - Booking', 'post_content' => $message]);
        update_post_meta($id, '_customer_name', $customer_name);
        update_post_meta($id, '_customer_email', $customer_email);
        update_post_meta($id, '_customer_phone', $customer_phone);
        update_post_meta($id, '_service_id', $service_id);
        // Validate staff id and save a staff name snapshot
        $validated_staff_id = 0;
        $staff_name_snapshot = 'Any available staff';
        if ($staff_id) {
            $maybe = get_post($staff_id);
            if ($maybe && isset($maybe->post_type) && $maybe->post_type === 'staff') {
                $validated_staff_id = intval($staff_id);
                $staff_name_snapshot = get_the_title($validated_staff_id);
            }
        }
        update_post_meta($id, '_staff_id', $validated_staff_id);
        update_post_meta($id, '_staff_name', sanitize_text_field($staff_name_snapshot));
        update_post_meta($id, '_preferred_date', $preferred_date);
        update_post_meta($id, '_preferred_time', $preferred_time);
        update_post_meta($id, '_booking_status', $booking_status);

        $resp = ['message' => 'Booking updated', 'id' => $id, 'staff_name' => get_post_meta($id, '_staff_name', true) ?: ''];
        wp_send_json_success($resp);
    }

    // Legacy table update
    global $wpdb;
    $booking_table = $wpdb->prefix . 'service_bookings';
    $updated = $wpdb->update($booking_table, [
        'service_id' => $service_id,
        'staff_id' => $staff_id,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'preferred_date' => $preferred_date,
        'preferred_time' => $preferred_time,
        'booking_status' => $booking_status,
        'notes' => $message
    ], ['id' => $id]);

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Booking updated (legacy)']);
    }

    wp_send_json_error(['message' => 'Failed to update booking']);
}
add_action('wp_ajax_elite_cuts_update_booking', 'elite_cuts_update_booking_ajax');

// Add shortcode for the frontend booking management
function elite_cuts_manage_bookings_shortcode() {
    // Check if user is logged in and has the right permissions
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p>You need to be logged in with the right permissions to view this page.</p>';
    }
    
    // Enqueue required scripts and styles for frontend
    wp_enqueue_script('jquery');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    // Localize script for AJAX
    wp_localize_script('jquery', 'eliteManageBookings', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('manage_bookings_nonce')
    ));
    
    // Start output buffering to capture the HTML
    ob_start();
    
    // Call the booking management function
    elite_cuts_manage_bookings_page();
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('elite_cuts_manage_bookings', 'elite_cuts_manage_bookings_shortcode');
