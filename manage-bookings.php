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
    
    // Load bookings directly with PHP
    global $wpdb;
    $bookings = $wpdb->get_results("
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
        ORDER BY b.created_at DESC
        LIMIT 50
    ");
        // Load bookings from the 'service_booking' custom post type and map post meta
        $bookings = [];

        $args = [
            'post_type' => 'service_booking',
            'posts_per_page' => 50,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC'
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

                $staff_name = null;
                $staff_position = null;
                if (!empty($staff_id)) {
                    $staff_post = get_post(intval($staff_id));
                    if ($staff_post && $staff_post->post_status !== 'trash') {
                        // Staff stored as CPT (preferred)
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
                                <td>#<?php echo $booking->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($booking->customer_name ?: 'Unknown'); ?></strong>
                                </td>
                                <td>
                                    <span class="service-name">
                                        <?php echo esc_html($booking->service_name ?: 'Service ID: ' . $booking->service_id); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="staff-info">
                                        <?php if ($booking->staff_name): ?>
                                            <strong><?php echo esc_html($booking->staff_name); ?></strong><br>
                                            <small><?php echo esc_html($booking->staff_position); ?></small>
                                        <?php else: ?>
                                            <em>Any available staff</em>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="contact-info">
                                    <?php echo esc_html($booking->customer_email ?: 'No email'); ?><br>
                                    <small><?php echo esc_html($booking->customer_phone ?: 'No phone'); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($booking->preferred_date ?: 'No date'); ?></strong><br>
                                    <small><?php echo esc_html($booking->preferred_time ?: 'No time'); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($booking->booking_status); ?>">
                                        <?php echo esc_html(ucfirst($booking->booking_status)); ?>
                                    </span>
                                </td>
                                <td class="actions">
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
        <div class="manager-booking-container">
            <div class="manager-booking-header">
                <div class="header-content">
                    <h1 class="booking-title">
                        <i class="fas fa-calendar-plus"></i>
                        New Booking
                    </h1>
                    <p class="booking-subtitle">Create a new appointment for the barbershop</p>
                </div>
                <button class="close-overlay" id="close-booking-overlay">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="manager-booking-form-wrapper">
                <div class="manager-stepper">
                    <div class="steps">
                        <div class="step active" data-step="1">
                            <div class="num">1</div>
                            <div class="label">Customer</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="num">2</div>
                            <div class="label">Service</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="num">3</div>
                            <div class="label">Schedule</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="num">4</div>
                            <div class="label">Confirm</div>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-fill" style="width: 25%"></div>
                    </div>
                </div>

                <form id="manager-booking-form" class="manager-booking-form">
                    <?php wp_nonce_field('manager_booking_nonce', 'booking_nonce'); ?>
                    <input type="hidden" id="booking-id" value="">

                    <!-- Step 1: Customer Information -->
                    <div class="form-step active" data-step="1">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Customer Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="customer-name">Customer Name <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-user"></i>
                                    <input type="text" id="customer-name" name="customer_name" placeholder="Full name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer-email">Email Address <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-envelope"></i>
                                    <input type="email" id="customer-email" name="customer_email" placeholder="Email address" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer-phone">Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-phone"></i>
                                    <input type="tel" id="customer-phone" name="customer_phone" placeholder="Phone number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-nav">
                            <button type="button" class="btn-next">Next Step</button>
                        </div>
                    </div>

                    <!-- Step 2: Service Selection -->
                    <div class="form-step" data-step="2">
                        <h3 class="section-title">
                            <i class="fas fa-cut"></i>
                            Select Service & Staff
                        </h3>
                        
                        <div class="form-group">
                            <label for="service-select">Service <span class="required">*</span></label>
                            <div class="service-selector">
                                <i class="input-icon fas fa-cut"></i>
                                <select id="service-select" name="service_id" required>
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
                            <label for="staff-select">Preferred Staff</label>
                            <input type="hidden" id="staff-select" name="staff_id" value="">
                            <div id="staff-grid" class="staff-grid" aria-live="polite">
                                <div class="staff-grid-empty">Select a service to choose staff</div>
                            </div>
                        </div>
                        
                        <div class="step-nav">
                            <button type="button" class="btn-prev">Previous</button>
                            <button type="button" class="btn-next">Next Step</button>
                        </div>
                    </div>

                    <!-- Step 3: Schedule -->
                    <div class="form-step" data-step="3">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Schedule Appointment
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="booking-date">Appointment Date <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-calendar-alt"></i>
                                    <input type="date" id="booking-date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="booking-time">Appointment Time <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="input-icon fas fa-clock"></i>
                                    <input type="time" id="booking-time" name="preferred_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="booking-notes">Additional Notes</label>
                            <div class="textarea-wrapper">
                                <i class="input-icon fas fa-comment-alt"></i>
                                <textarea id="booking-notes" name="booking_notes" placeholder="Any special requests or notes..."></textarea>
                            </div>
                        </div>
                        
                        <div class="step-nav">
                            <button type="button" class="btn-prev">Previous</button>
                            <button type="button" class="btn-next">Next Step</button>
                        </div>
                    </div>

                    <!-- Step 4: Confirmation -->
                    <div class="form-step" data-step="4">
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
                        
                        <div class="step-nav">
                            <button type="button" class="btn-prev">Previous</button>
                            <button type="submit" class="btn-submit">Create Booking</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div id="manager-booking-success" class="manager-booking-success">
        <div class="manager-booking-backdrop"></div>
        <div class="success-container">
            <div class="success-box">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="success-title">Booking Created Successfully</h3>
                <p class="success-message">The appointment has been added to the system.</p>
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
            table-layout: fixed;
            min-width: 800px;
        }

        /* Column widths for 8 columns: ID, Customer, Service, Staff, Contact, Date & Time, Status, Actions */
        .elite-cuts-table th:nth-child(1) { width: 8%; }   /* ID */
        .elite-cuts-table th:nth-child(2) { width: 15%; }  /* Customer */
        .elite-cuts-table th:nth-child(3) { width: 13%; }  /* Service */
        .elite-cuts-table th:nth-child(4) { width: 12%; }  /* Staff */
        .elite-cuts-table th:nth-child(5) { width: 16%; }  /* Contact */
        .elite-cuts-table th:nth-child(6) { width: 14%; }  /* Date & Time */
        .elite-cuts-table th:nth-child(7) { width: 10%; }  /* Status */
        .elite-cuts-table th:nth-child(8) { width: 12%; }  /* Actions */

        .elite-cuts-table th {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            font-weight: 500;
            text-align: left;
            padding: 1rem 1.25rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }

        /* Center the Actions header only */
        .elite-cuts-table th:nth-child(8) {
            text-align: center;
        }

        .elite-cuts-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            height: 113.16px;
        }

        /* Actions column - maintain table cell behavior */
        .elite-cuts-table td.actions {
            text-align: center;
            vertical-align: middle;
            /* Remove line-height, let table determine natural height */
        }

        /* Container for buttons - positioned to center without affecting cell height */
        .elite-cuts-table td.actions {
            display: table-cell; /* Ensure proper table cell behavior */
        }

        .elite-cuts-table td.actions .elite-button {
            display: inline-block;
            margin: 0 0.2rem;
            min-width: auto;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            height: auto;
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
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(12, 25, 48, 0.2);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transform: scale(0.9) translateY(30px);
            transition: all 0.3s ease;
        }

        .manager-booking-overlay.active .manager-booking-container {
            transform: scale(1) translateY(0);
        }

        /* Header */
        .manager-booking-header {
            position: relative;
            padding: 2rem 2.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .manager-booking-header::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 4px;
        }

        .header-content {
            text-align: center;
            width: 100%;
        }

        .booking-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .booking-title i {
            width: 44px;
            height: 44px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .booking-subtitle {
            color: var(--text-secondary);
            margin: 0;
            font-size: 1rem;
        }

        .close-overlay {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.1rem;
        }

        .close-overlay:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Form wrapper */
        .manager-booking-form-wrapper {
            padding: 2rem 2.5rem;
        }

        /* Stepper */
        .manager-stepper {
            margin-bottom: 2rem;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
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
            top: 20px;
            left: calc(50% + 20px);
            right: calc(-50% + 20px);
            height: 2px;
            background: #e6eaef;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background: var(--accent);
        }

        .step .num {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e6eaef;
            color: #667585;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            z-index: 2;
            position: relative;
            transition: all 0.3s ease;
        }

        .step.active .num {
            background: var(--accent);
            color: white;
        }

        .step .label {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            color: #667585;
            text-align: center;
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
        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--accent);
            font-size: 1.1rem;
        }

        /* Form layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .required {
            color: var(--accent);
            font-weight: 600;
        }

        /* Input styling */
        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 1rem;
            z-index: 2;
        }

        .manager-booking-form input,
        .manager-booking-form select,
        .manager-booking-form textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: var(--bg-primary);
            color: var(--text-primary);
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
        }

        .service-selector select,
        .staff-selector select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.75rem;
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
            top: 1rem;
            transform: none;
            width: 24px;
            height: 24px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .textarea-wrapper textarea {
            padding-left: 3.5rem;
            padding-top: 1rem;
            min-height: 100px;
            resize: vertical;
        }

        /* Service info */
        .selected-service-info {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        .selected-service-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-weight: 600;
        }

        .selected-service-info p {
            margin: 0.25rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .service-price {
            font-weight: 600;
            color: var(--accent);
        }

        /* Booking summary */
        .booking-summary {
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .summary-section {
            margin-bottom: 1rem;
        }

        .summary-section:last-child {
            margin-bottom: 0;
        }

        .summary-section h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .summary-section p {
            margin: 0.25rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Step navigation */
        .step-nav {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-prev,
        .btn-next,
        .btn-submit {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
    </style>

    <script>
    jQuery(document).ready(function($) {
        console.log('Manage Bookings initialized with server-side data');
        
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
        
        $('#reset-filters').on('click', function() {
            console.log('Reset filters clicked');
            $('#filter-from').val('');
            $('#filter-to').val('');
            $('#filter-status').val('');
            $('#booking-search').val('');
            
            // Show all bookings
            allBookings.forEach(function(booking) {
                booking.element.show();
            });
            $('#no-filter-results').remove();
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

        // Edit/Delete button handlers
        $('.edit-booking').on('click', function() {
            const bookingId = $(this).data('id');
            console.log('Edit booking:', bookingId);
            alert('Edit booking #' + bookingId + ' - functionality to be implemented');
        });
        
        $('.delete-booking').on('click', function() {
            if (confirm('Are you sure you want to delete this booking?')) {
                const bookingId = $(this).data('id');
                console.log('Delete booking:', bookingId);
                alert('Delete booking #' + bookingId + ' - functionality to be implemented');
            }
        });

        // ========================================
        // Manager Booking Overlay Functionality
        // ========================================

        let currentStep = 1;
        const totalSteps = 4;
        
        // Open overlay
        $('#add-booking-btn').on('click', function() {
            console.log('Opening manager booking overlay');
            resetForm();
            showOverlay();
        });

        // Close overlay
        $('#close-booking-overlay, .manager-booking-backdrop').on('click', function() {
            hideOverlay();
        });

        // Step navigation
        $('.btn-next').on('click', function() {
            if (validateCurrentStep()) {
                nextStep();
            }
        });

        $('.btn-prev').on('click', function() {
            prevStep();
        });

        // Form submission
        $('#manager-booking-form').on('submit', function(e) {
            e.preventDefault();
            if (validateCurrentStep()) {
                submitBooking();
            }
        });

        // Service selection change
        $('#service-select').on('change', function() {
            const serviceId = $(this).val();
            const $selected = $(this).find('option:selected');
            
            if (serviceId) {
                // Show service info
                const price = $selected.data('price');
                const duration = $selected.data('duration');
                const description = $selected.data('description');
                
                $('#selected-service-info .service-description').text(description || 'No description available');
                $('#selected-service-info .service-duration span').text(duration || 'Not specified');
                $('#selected-service-info .service-price span').text(price ? '₱' + parseFloat(price).toFixed(2) : 'Price varies');
                $('#selected-service-info').show();
                
                // Load staff for this service
                loadStaffForService(serviceId);
            } else {
                hideServiceInfo();
                clearStaffGrid();
            }
        });

        // Functions
        function showOverlay() {
            $('#manager-booking-overlay').addClass('active');
            $('body').css('overflow', 'hidden');
            updateSummary();
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
            $('.form-step').removeClass('active');
            $('.step').removeClass('active');
            
            // Show current step
            $(`.form-step[data-step="${currentStep}"]`).addClass('active');
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
            const currentStepEl = $(`.form-step[data-step="${currentStep}"]`);
            
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
            const $staffGrid = $('#staff-grid');
            $staffGrid.html('<div class="staff-grid-empty">Loading staff...</div>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
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
            const $staffGrid = $('#staff-grid');
            $staffGrid.empty();
            
            // Add "Any staff" option
            const anyStaffCard = $(`
                <div class="staff-card" data-staff-id="">
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
                    <div class="staff-card" data-staff-id="${member.id}">
                        <div class="staff-avatar">${avatarHtml}</div>
                        <div class="staff-name">${member.name}</div>
                    </div>
                `);
                $staffGrid.append(staffCard);
            });
            
            // Add click handlers
            $('.staff-card').on('click', function() {
                $('.staff-card').removeClass('selected');
                $(this).addClass('selected');
                $('#staff-select').val($(this).data('staff-id'));
            });
            
            // Select "Any staff" by default
            anyStaffCard.addClass('selected');
            $('#staff-select').val('');
        }

        function clearStaffGrid() {
            $('#staff-grid').html('<div class="staff-grid-empty">Select a service to choose staff</div>');
            $('#staff-select').val('');
        }

        function hideServiceInfo() {
            $('#selected-service-info').hide();
        }

        function updateSummary() {
            // Customer info
            const customerName = $('#customer-name').val();
            const customerEmail = $('#customer-email').val();
            const customerPhone = $('#customer-phone').val();
            
            $('#summary-customer').text(customerName || 'Not specified');
            $('#summary-contact').text(`${customerEmail || 'No email'}${customerPhone ? ' • ' + customerPhone : ''}`);
            
            // Service info
            const serviceName = $('#service-select option:selected').text();
            const selectedStaffCard = $('.staff-card.selected');
            const staffName = selectedStaffCard.length ? selectedStaffCard.find('.staff-name').text() : 'Any available staff';
            
            $('#summary-service').text(serviceName || 'No service selected');
            $('#summary-staff').text(staffName);
            
            // Date/time info
            const date = $('#booking-date').val();
            const time = $('#booking-time').val();
            
            if (date && time) {
                const formattedDate = new Date(date).toLocaleDateString();
                $('#summary-datetime').text(`${formattedDate} at ${time}`);
            } else {
                $('#summary-datetime').text('Date and time not set');
            }
        }

        function submitBooking() {
            console.log('Submitting booking...');
            
            // Show loading state
            $('.btn-submit').text('Creating...').prop('disabled', true);
            
            // Collect form data
            const formData = {
                customer_name: $('#customer-name').val(),
                customer_email: $('#customer-email').val(),
                customer_phone: $('#customer-phone').val(),
                service_id: $('#service-select').val(),
                staff_id: $('#staff-select').val(),
                preferred_date: $('#booking-date').val(),
                preferred_time: $('#booking-time').val(),
                booking_notes: $('#booking-notes').val(),
                booking_status: $('#booking-status').val(),
                nonce: $('input[name="booking_nonce"]').val()
            };
            
            // Mock submission - replace with actual AJAX call
            setTimeout(() => {
                console.log('Booking created:', formData);
                
                // Reset button
                $('.btn-submit').text('Create Booking').prop('disabled', false);
                
                // Hide main overlay and show success
                hideOverlay();
                showSuccessMessage();
                
                // Optionally reload the page or update the bookings table
                // location.reload();
            }, 1500);
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
            // Optionally scroll to the new booking in the table
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
