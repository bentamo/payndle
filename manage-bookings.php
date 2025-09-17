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

    <!-- Add/Edit Booking Modal -->
    <div id="booking-modal" class="elite-modal" style="display: none;">
        <div class="elite-modal-content">
            <div class="elite-modal-header">
                <h3>Add New Booking</h3>
                <span class="elite-close">&times;</span>
            </div>
            <div class="elite-modal-body">
                <form id="booking-form">
                    <input type="hidden" id="booking-id" value="">
                    
                    <div class="form-group">
                        <label for="customer">Customer</label>
                        <select id="customer" class="elite-select" required>
                            <option value="">Select Customer</option>
                            <!-- Populated via AJAX -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="service">Service</label>
                        <select id="service" class="elite-select" required>
                            <option value="">Select Service</option>
                            <!-- Populated via AJAX -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="barber">Barber</label>
                        <select id="barber" class="elite-select" required>
                            <option value="">Select Barber</option>
                            <!-- Populated via AJAX -->
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="booking-date">Date</label>
                            <input type="date" id="booking-date" class="elite-input" required>
                        </div>
                        <div class="form-group">
                            <label for="booking-time">Time</label>
                            <input type="time" id="booking-time" class="elite-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" class="elite-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="elite-button secondary" id="cancel-booking">Cancel</button>
                        <button type="submit" class="elite-button primary">Save Booking</button>
                    </div>
                </form>
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
            position: relative;
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
            vertical-align: middle;
            height: auto;
            max-height: 32px;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
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
        
        console.log('Manage Bookings ready with ' + allBookings.length + ' bookings loaded');
    });
        // Initialize the date pickers
        const today = new Date();
        $('#date-from').val(today.toISOString().split('T')[0]);
        
        const nextWeek = new Date();
        nextWeek.setDate(today.getDate() + 7);
        $('#date-to').val(nextWeek.toISOString().split('T')[0]);

        // Modal functionality
        const modal = document.getElementById('booking-modal');
        const addBtn = document.getElementById('add-booking-btn');
        const closeBtn = document.getElementsByClassName('elite-close')[0];
        const cancelBtn = document.getElementById('cancel-booking');

        // Open modal for new booking
        addBtn.onclick = function() {
            document.querySelector('#booking-form h3').textContent = 'Add New Booking';
            document.getElementById('booking-form').reset();
            modal.style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            modal.style.display = 'none';
        }

        closeBtn.onclick = closeModal;
        cancelBtn.onclick = closeModal;

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Form submission
        $('#booking-form').on('submit', function(e) {
            e.preventDefault();
            // Add form submission logic here
            alert('Booking saved successfully!');
            closeModal();
        });

        // Load bookings with real database integration
        function loadBookings() {
            console.log('Loading real bookings from database...');
            
            const tbody = $('#bookings-list');
            tbody.html('<tr class="loading-row"><td colspan="7"><div class="loading-spinner"></div><span>Loading bookings...</span></td></tr>');
            
            // Check if AJAX variables are available
            if (typeof eliteManageBookings === 'undefined') {
                console.error('eliteManageBookings is undefined - AJAX variables not loaded');
                tbody.html('<tr><td colspan="7" class="no-bookings">Error: AJAX configuration missing. Please refresh the page.</td></tr>');
                return;
            }
            
            console.log('AJAX Config:', eliteManageBookings);
            
            // Get filter values
            const filters = {
                action: 'get_manage_bookings',
                date_from: $('#filter-from').val(),
                date_to: $('#filter-to').val(),
                status: $('#filter-status').val(),
                search: $('#booking-search').val(),
                security: eliteManageBookings.nonce
            };
            
            console.log('AJAX data:', filters);
            console.log('AJAX URL:', eliteManageBookings.ajaxUrl);
            
            $.ajax({
                url: eliteManageBookings.ajaxUrl,
                type: 'POST',
                data: filters,
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        displayBookings(response.data);
                    } else {
                        console.log('AJAX Error:', response);
                        tbody.html('<tr><td colspan="7" class="no-bookings">Error: ' + (response.data || 'Unknown error') + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Network Error:', {xhr: xhr, status: status, error: error});
                    console.log('Response Text:', xhr.responseText);
                    tbody.html('<tr><td colspan="7" class="no-bookings">Network error: ' + error + ' (Status: ' + status + ')</td></tr>');
                }
            });
        }
        
        function displayBookings(bookings) {
            console.log('Displaying bookings:', bookings);
            
            const tbody = $('#bookings-list');
            tbody.empty();

            if (!bookings || bookings.length === 0) {
                tbody.append('<tr><td colspan="7" class="no-bookings">No bookings found</td></tr>');
                return;
            }

            bookings.forEach(booking => {
                const serviceName = booking.service_name || 'Service ID: ' + booking.service_id || 'Unknown Service';
                const barberName = booking.barber_name || 'Not assigned';
                const customerPhone = booking.customer_phone ? '<br><small>' + booking.customer_phone + '</small>' : '';
                
                const row = `
                    <tr>
                        <td>#${booking.id}</td>
                        <td>
                            ${booking.customer_name || 'Unknown'}
                            <br><small>${booking.customer_email || 'No email'}</small>
                            ${customerPhone}
                        </td>
                        <td>${serviceName}</td>
                        <td>${barberName}</td>
                        <td>${formatDateTime(booking.preferred_date + ' ' + booking.preferred_time)}</td>
                        <td><span class="status-badge status-${booking.booking_status}">${booking.booking_status}</span></td>
                        <td class="actions">
                            <button class="elite-button small edit-booking" data-id="${booking.id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="elite-button small delete-booking" data-id="${booking.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Add event listeners for edit/delete buttons
            $('.edit-booking').on('click', function() {
                const bookingId = $(this).data('id');
                // In a real implementation, load the booking data and populate the form
                document.querySelector('#booking-form h3').textContent = 'Edit Booking';
                // Populate form with booking data
                modal.style.display = 'flex';
            });

            $('.delete-booking').on('click', function() {
                if (confirm('Are you sure you want to delete this booking?')) {
                    const bookingId = $(this).data('id');
                    // In a real implementation, make an AJAX call to delete the booking
                    alert(`Booking #${bookingId} deleted`);
                    // Reload bookings
                    loadBookings();
                }
            });
        }

        // Helper function to format date and time
        function formatDateTime(datetime) {
            const [date, time] = datetime.split(' ');
            const [year, month, day] = date.split('-');
            return `${month}/${day}/${year} ${formatTime(time)}`;
        }

        // Helper function to format time (24h to 12h)
        function formatTime(time) {
            const [hours, minutes] = time.split(':');
            const h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const hh = h % 12 || 12;
            return `${hh}:${minutes} ${ampm}`;
        }

        // Filter event handlers
        $('#apply-filters').on('click', function() {
            console.log('Apply filters clicked');
            loadBookings();
        });
        
        $('#reset-filters').on('click', function() {
            console.log('Reset filters clicked');
            $('#filter-from').val('');
            $('#filter-to').val('');
            $('#filter-status').val('');
            $('#booking-search').val('');
            loadBookings();
        });
        
        // Search on enter key
        $('#booking-search').on('keypress', function(e) {
            if (e.which === 13) {
                console.log('Search enter pressed');
                loadBookings();
            }
        });
        
        // Test AJAX connection button (for debugging)
        if (console && console.log) {
            console.log('Adding test AJAX button for debugging');
            $('<button>Test AJAX</button>').click(function() {
                console.log('Testing AJAX connection...');
                $.ajax({
                    url: eliteManageBookings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_manage_bookings',
                        security: eliteManageBookings.nonce
                    },
                    success: function(response) {
                        console.log('Test AJAX Success:', response);
                        alert('AJAX working! Found ' + (response.data ? response.data.length : 0) + ' bookings');
                    },
                    error: function(xhr, status, error) {
                        console.log('Test AJAX Error:', xhr, status, error);
                        alert('AJAX Error: ' + error);
                    }
                });
            }).appendTo('.elite-cuts-header');
        }

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
