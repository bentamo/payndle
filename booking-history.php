<?php
/**
 * Booking History View
 * Description: A booking history display system accessible via shortcode
 * Version: 1.0.0
 * Shortcode: [booking_history]
 */

if (!defined('ABSPATH')) {
    exit;
}

class BookingHistory {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('booking_history', [$this, 'render_booking_history']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Register AJAX actions
        add_action('wp_ajax_get_booking_history', [$this, 'get_booking_history_ajax']);
        add_action('wp_ajax_nopriv_get_booking_history', [$this, 'get_booking_history_ajax']);
        add_action('wp_ajax_filter_bookings', [$this, 'filter_bookings_ajax']);
        add_action('wp_ajax_nopriv_filter_bookings', [$this, 'filter_bookings_ajax']);
        add_action('wp_ajax_update_booking_status', [$this, 'update_booking_status_ajax']);
        add_action('wp_ajax_nopriv_update_booking_status', [$this, 'update_booking_status_ajax']);
        add_action('wp_ajax_get_booking_services', [$this, 'get_booking_services_ajax']);
        add_action('wp_ajax_nopriv_get_booking_services', [$this, 'get_booking_services_ajax']);
    }
    
    public function init() {
        // Update booking table to include payment method
        $this->update_booking_table();
    }
    
    /**
     * Update database table to include payment method
     */
    private function update_booking_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'service_bookings';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, skip modification
            return;
        }
        
        // Check if payment_method column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'payment_method'");
        
        if (empty($column_exists)) {
            // Add payment_method column
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash' AFTER customer_phone");
            if ($result === false) {
                error_log("Failed to add payment_method column: " . $wpdb->last_error);
            }
        }
        
        // Check if payment_status column exists
        $payment_status_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'payment_status'");
        
        if (empty($payment_status_exists)) {
            // Add payment_status column
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending' AFTER payment_method");
            if ($result === false) {
                error_log("Failed to add payment_status column: " . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_script('jquery');
        
        // Enqueue Font Awesome for icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
        
        // Enqueue Google Fonts
        wp_enqueue_style('google-fonts-booking', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap');
    }
    
    /**
     * Render booking history shortcode
     */
    public function render_booking_history($atts) {
        try {
            $atts = shortcode_atts([
                'service_id' => '',
                'status' => '',
                'limit' => 20,
                'show_filters' => 'yes'
            ], $atts);
            
            ob_start();
            ?>
            
            <div class="booking-history-container">
                <!-- Debug info for troubleshooting -->
                <div id="debug-info" style="display: none; background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                    <strong>Debug Info:</strong>
                    <ul>
                        <li>Plugin loaded successfully</li>
                        <li>Shortcode parameters: <?php echo json_encode($atts); ?></li>
                        <li>Current user: <?php echo is_user_logged_in() ? 'Logged in' : 'Not logged in'; ?></li>
                        <li>WordPress version: <?php echo get_bloginfo('version'); ?></li>
                    </ul>
                    <button onclick="document.getElementById('debug-info').style.display='none'">Hide Debug</button>
                </div>
                
                <style>
                /* Base Styles - Matching Landing Page */
                .booking-history-container {
                    font-family: 'Poppins', sans-serif;
                    background: #ffffff;
                    color: #333333;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 40px 20px;
                }
                
                .booking-history-header {
                    text-align: center;
                    margin-bottom: 50px;
                }
                
                .booking-history-title {
                    font-family: 'Playfair Display', serif;
                    font-size: 3rem;
                    font-weight: 700;
                    color: #1a1a1a;
                    margin-bottom: 20px;
                    position: relative;
                }
                
                .booking-history-title span {
                    color: #c9a74d;
                }
                
                .booking-history-subtitle {
                    font-size: 1.1rem;
                    color: #666;
                    max-width: 600px;
                    margin: 0 auto;
                    line-height: 1.6;
                }
                
                /* Filters Section */
                .booking-filters {
                    background: #f8f9fa;
                    padding: 30px;
                    border-radius: 15px;
                    margin-bottom: 40px;
                    border: 1px solid rgba(201, 167, 77, 0.1);
                }
                
                .filters-row {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 20px;
                }
                
                .filter-group {
                    display: flex;
                    flex-direction: column;
                }
                
                .filter-label {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                    font-size: 0.9rem;
                }
                
                .filter-select,
                .filter-input {
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 1rem;
                    transition: all 0.3s ease;
                    background: white;
                    color: #333;
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
                    background-repeat: no-repeat;
                    background-position: right 12px center;
                    background-size: 16px;
                    padding-right: 40px;
                }
                
                .filter-input {
                    background-image: none;
                    padding-right: 15px;
                }
                
                .filter-select:focus,
                .filter-input:focus {
                    outline: none;
                    border-color: #c9a74d;
                    box-shadow: 0 0 0 3px rgba(201, 167, 77, 0.1);
                }
                
                .filter-select option {
                    color: #333;
                    background: white;
                    padding: 8px 12px;
                }
                
                .filter-select option:hover,
                .filter-select option:focus {
                    background: #f8f9fa;
                    color: #c9a74d;
                }
                
                .filter-actions {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                }
                
                .btn {
                    padding: 12px 30px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 1rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                
                .btn-primary {
                    background: linear-gradient(135deg, #c9a74d, #d4b968);
                    color: white;
                    box-shadow: 0 4px 15px rgba(201, 167, 77, 0.3);
                }
                
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(201, 167, 77, 0.4);
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn-secondary:hover {
                    background: #5a6268;
                    transform: translateY(-2px);
                }
                
                /* Booking Cards */
                .bookings-grid {
                    display: grid;
                    gap: 25px;
                    margin-bottom: 40px;
                }
                
                .booking-card {
                    background: white;
                    border-radius: 15px;
                    padding: 30px;
                    border: 1px solid #e9ecef;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }
                
                .booking-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 5px;
                    height: 100%;
                    background: linear-gradient(135deg, #c9a74d, #d4b968);
                }
                
                .booking-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                }
                
                .booking-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 20px;
                }
                
                .booking-id {
                    font-weight: 700;
                    color: #c9a74d;
                    font-size: 1.1rem;
                }
                
                .booking-status {
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .status-pending {
                    background: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeaa7;
                }
                
                .status-confirmed {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                
                .status-cancelled {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                
                .status-completed {
                    background: #d1ecf1;
                    color: #0c5460;
                    border: 1px solid #bee5eb;
                }
                
                .booking-details {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                }
                
                .detail-group {
                    display: flex;
                    flex-direction: column;
                }
                
                .detail-label {
                    font-size: 0.85rem;
                    color: #666;
                    margin-bottom: 5px;
                    font-weight: 500;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .detail-value {
                    font-size: 1rem;
                    color: #333;
                    font-weight: 600;
                }
                
                .service-name {
                    color: #c9a74d;
                    font-weight: 700;
                }
                
                .customer-info {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .payment-method {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 15px;
                    background: #f8f9fa;
                    border-radius: 20px;
                    font-size: 0.9rem;
                    font-weight: 600;
                    color: #495057;
                }
                
                .booking-actions {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                }
                
                .btn-sm {
                    padding: 8px 20px;
                    font-size: 0.9rem;
                }
                
                /* Loading and Empty States */
                .loading-state {
                    text-align: center;
                    padding: 60px 20px;
                    color: #666;
                }
                
                .loading-spinner {
                    display: inline-block;
                    width: 40px;
                    height: 40px;
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #c9a74d;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 20px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .empty-state {
                    text-align: center;
                    padding: 60px 20px;
                    color: #666;
                }
                
                .empty-icon {
                    font-size: 4rem;
                    color: #c9a74d;
                    margin-bottom: 20px;
                }
                
                /* Responsive Design */
                @media (max-width: 768px) {
                    .booking-history-container {
                        padding: 20px 15px;
                    }
                    
                    .booking-history-title {
                        font-size: 2rem;
                    }
                    
                    .booking-filters {
                        padding: 20px;
                    }
                    
                    .filters-row {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                    
                    .filter-actions {
                        flex-direction: column;
                    }
                    
                    .booking-card {
                        padding: 20px;
                    }
                    
                    .booking-header {
                        flex-direction: column;
                        gap: 15px;
                    }
                    
                    .booking-details {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                    
                    .booking-actions {
                        flex-direction: column;
                    }
                    
                    .filter-select,
                    .filter-input {
                        font-size: 16px; /* Prevents zoom on iOS */
                    }
                }
                
                /* Browser compatibility for dropdowns */
                .filter-select {
                    min-height: 48px;
                    cursor: pointer;
                }
                
                .filter-select::-ms-expand {
                    display: none; /* Hide arrow in IE */
                }
                
                /* Ensure text is visible in all browsers */
                .filter-select option {
                    color: #333 !important;
                    background: white !important;
                }
                
                /* Firefox specific fixes */
                @-moz-document url-prefix() {
                    .filter-select {
                        background-image: none;
                        -moz-appearance: menulist;
                    }
                }
                
                /* Print Styles */
                @media print {
                    .booking-filters,
                    .booking-actions {
                        display: none;
                    }
                    
                    .booking-card {
                        break-inside: avoid;
                        box-shadow: none;
                        border: 1px solid #ddd;
                    }
                }
            </style>
            
            <!-- Header -->
            <div class="booking-history-header">
                <h1 class="booking-history-title">
                    Booking <span>History</span>
                </h1>
                <p class="booking-history-subtitle">
                    View and manage all service bookings with detailed information about customers, appointments, and payment status.
                </p>
                <div style="margin-top: 15px;">
                    <button onclick="document.getElementById('debug-info').style.display='block'" 
                            style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        Show Debug Info
                    </button>
                </div>
            </div>
            
            <?php if ($atts['show_filters'] === 'yes'): ?>
            <!-- Filters -->
            <div class="booking-filters">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Service</label>
                        <select id="filter-service" class="filter-select">
                            <option value="">All Services</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select id="filter-status" class="filter-select">
                            <option value="">All Statuses</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="confirmed">✅ Confirmed</option>
                            <option value="completed">🎉 Completed</option>
                            <option value="cancelled">❌ Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Payment Method</label>
                        <select id="filter-payment" class="filter-select">
                            <option value="">All Payment Methods</option>
                            <option value="cash">💵 Cash Payment</option>
                            <option value="card">💳 Credit/Debit Card</option>
                            <option value="gcash">📱 GCash</option>
                            <option value="paymaya">📱 PayMaya</option>
                            <option value="online">🌐 Online Payment</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date From</label>
                        <input type="date" id="filter-date-from" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date To</label>
                        <input type="date" id="filter-date-to" class="filter-input">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" id="apply-filters">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary" id="reset-filters">
                        <i class="fas fa-refresh"></i> Reset
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bookings Display -->
            <div id="bookings-container">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading booking history...</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let currentFilters = {};
            
            // Load initial data
            loadBookings();
            loadServices();
            
            // Filter event handlers
            $('#apply-filters').on('click', function() {
                applyFilters();
            });
            
            $('#reset-filters').on('click', function() {
                resetFilters();
            });
            
            // Status update handlers
            $(document).on('click', '.status-update-btn', function() {
                const bookingId = $(this).data('booking-id');
                const newStatus = $(this).data('new-status');
                updateBookingStatus(bookingId, newStatus);
            });
            
            function loadBookings(filters = {}) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_booking_history',
                        filters: filters,
                        nonce: '<?php echo wp_create_nonce('booking_history_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayBookings(response.data.bookings);
                        } else {
                            showError(response.data.message || 'Failed to load bookings');
                        }
                    },
                    error: function() {
                        showError('Network error occurred');
                    }
                });
            }
            
            function loadServices() {
                console.log('Loading services...');
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_booking_services',
                        nonce: '<?php echo wp_create_nonce('booking_history_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('Services response:', response);
                        if (response.success && response.data.services) {
                            const serviceSelect = $('#filter-service');
                            serviceSelect.empty().append('<option value="">All Services</option>');
                            response.data.services.forEach(function(service) {
                                console.log('Adding service:', service.service_name);
                                serviceSelect.append(`<option value="${service.id}">${service.service_name}</option>`);
                            });
                            console.log('Services loaded successfully');
                        } else {
                            console.log('No services data or failed response');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Failed to load services:', error);
                        console.log('XHR:', xhr);
                    }
                });
            }
            
            function displayBookings(bookings) {
                const container = $('#bookings-container');
                
                if (!bookings || bookings.length === 0) {
                    container.html(`
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No bookings found</h3>
                            <p>No bookings match your current filter criteria.</p>
                        </div>
                    `);
                    return;
                }
                
                let html = '<div class="bookings-grid">';
                
                bookings.forEach(function(booking) {
                    const statusClass = `status-${booking.booking_status}`;
                    const paymentIcon = getPaymentIcon(booking.payment_method);
                    
                    html += `
                        <div class="booking-card">
                            <div class="booking-header">
                                <div class="booking-id">#${booking.id}</div>
                                <div class="booking-status ${statusClass}">${booking.booking_status}</div>
                            </div>
                            <div class="booking-details">
                                <div class="detail-group">
                                    <div class="detail-label">Service</div>
                                    <div class="detail-value service-name">${booking.service_name || 'Unknown Service'}</div>
                                </div>
                                <div class="detail-group">
                                    <div class="detail-label">Customer</div>
                                    <div class="detail-value">
                                        <div class="customer-info">
                                            <i class="fas fa-user"></i>
                                            ${booking.customer_name}
                                        </div>
                                        <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                                            <i class="fas fa-envelope"></i> ${booking.customer_email}
                                        </div>
                                        ${booking.customer_phone ? `<div style="font-size: 0.9rem; color: #666; margin-top: 2px;">
                                            <i class="fas fa-phone"></i> ${booking.customer_phone}
                                        </div>` : ''}
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <div class="detail-label">Appointment</div>
                                    <div class="detail-value">
                                        <div><i class="fas fa-calendar"></i> ${formatDate(booking.preferred_date)}</div>
                                        <div style="margin-top: 5px;"><i class="fas fa-clock"></i> ${formatTime(booking.preferred_time)}</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <div class="detail-label">Payment</div>
                                    <div class="detail-value">
                                        <div class="payment-method">
                                            ${paymentIcon} ${booking.payment_method || 'Cash'}
                                        </div>
                                        <div style="font-size: 0.9rem; color: #666; margin-top: 8px;">
                                            Status: ${booking.payment_status || 'Pending'}
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <div class="detail-label">Booked On</div>
                                    <div class="detail-value">${formatDateTime(booking.created_at)}</div>
                                </div>
                            </div>
                            ${booking.message ? `<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <div class="detail-label">Message</div>
                                <div style="color: #555; font-style: italic;">"${booking.message}"</div>
                            </div>` : ''}
                            <div class="booking-actions">
                                ${getStatusActions(booking)}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                container.html(html);
            }
            
            function getPaymentIcon(method) {
                const icons = {
                    'cash': '<i class="fas fa-money-bill-wave"></i>',
                    'card': '<i class="fas fa-credit-card"></i>',
                    'online': '<i class="fas fa-globe"></i>',
                    'gcash': '<i class="fas fa-mobile-alt"></i>',
                    'paymaya': '<i class="fas fa-mobile-alt"></i>'
                };
                return icons[method] || '<i class="fas fa-money-bill-wave"></i>';
            }
            
            function getStatusActions(booking) {
                let actions = '';
                
                if (booking.booking_status === 'pending') {
                    actions += `<button class="btn btn-primary btn-sm status-update-btn" data-booking-id="${booking.id}" data-new-status="confirmed">
                        <i class="fas fa-check"></i> Confirm
                    </button>`;
                    actions += `<button class="btn btn-secondary btn-sm status-update-btn" data-booking-id="${booking.id}" data-new-status="cancelled">
                        <i class="fas fa-times"></i> Cancel
                    </button>`;
                } else if (booking.booking_status === 'confirmed') {
                    actions += `<button class="btn btn-primary btn-sm status-update-btn" data-booking-id="${booking.id}" data-new-status="completed">
                        <i class="fas fa-check-circle"></i> Complete
                    </button>`;
                }
                
                return actions;
            }
            
            function applyFilters() {
                currentFilters = {
                    service_id: $('#filter-service').val(),
                    status: $('#filter-status').val(),
                    payment_method: $('#filter-payment').val(),
                    date_from: $('#filter-date-from').val(),
                    date_to: $('#filter-date-to').val()
                };
                
                loadBookings(currentFilters);
            }
            
            function resetFilters() {
                $('#filter-service, #filter-status, #filter-payment').val('');
                $('#filter-date-from, #filter-date-to').val('');
                currentFilters = {};
                loadBookings();
            }
            
            function updateBookingStatus(bookingId, newStatus) {
                if (!confirm(`Are you sure you want to ${newStatus} this booking?`)) {
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_booking_status',
                        booking_id: bookingId,
                        status: newStatus,
                        nonce: '<?php echo wp_create_nonce('booking_history_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            loadBookings(currentFilters);
                        } else {
                            alert(response.data.message || 'Failed to update booking status');
                        }
                    },
                    error: function() {
                        alert('Network error occurred');
                    }
                });
            }
            
            function formatDate(dateStr) {
                if (!dateStr) return 'Not specified';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
            }
            
            function formatTime(timeStr) {
                if (!timeStr) return 'Not specified';
                return new Date('2000-01-01 ' + timeStr).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            function formatDateTime(dateTimeStr) {
                if (!dateTimeStr) return 'Unknown';
                const date = new Date(dateTimeStr);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            
            function showError(message) {
                $('#bookings-container').html(`
                    <div class="empty-state">
                        <div class="empty-icon" style="color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Error</h3>
                        <p>${message}</p>
                    </div>
                `);
            }
        });
        </script>
        
        <?php
        return ob_get_clean();
        
        } catch (Exception $e) {
            error_log('BookingHistory render error: ' . $e->getMessage());
            return '<div style="background: #ffebee; border: 1px solid #f44336; color: #c62828; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3>🚨 Booking History Error</h3>
                <p>There was an error loading the booking history. Please check the error logs or contact the administrator.</p>
                <details style="margin-top: 10px;">
                    <summary>Technical Details</summary>
                    <code>' . esc_html($e->getMessage()) . '</code>
                </details>
            </div>';
        }
    }
    
    /**
     * AJAX handler for getting booking history
     */
    public function get_booking_history_ajax() {
        try {
            check_ajax_referer('booking_history_nonce', 'nonce');
            
            global $wpdb;
            
            $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
            
            $booking_table = $wpdb->prefix . 'service_bookings';
            $services_table = $wpdb->prefix . 'manager_services';
            
            // Check if tables exist
            $booking_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$booking_table}'") == $booking_table;
            $services_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") == $services_table;
            
            if (!$booking_table_exists) {
                wp_send_json_success(array('bookings' => array()));
                return;
            }
            
            // Build query
            $where_conditions = array('1=1');
            $where_values = array();
            
            if (!empty($filters['service_id'])) {
                $where_conditions[] = "b.service_id = %d";
                $where_values[] = intval($filters['service_id']);
            }
            
            if (!empty($filters['status'])) {
                $where_conditions[] = "b.booking_status = %s";
                $where_values[] = sanitize_text_field($filters['status']);
            }
            
            if (!empty($filters['payment_method'])) {
                $where_conditions[] = "b.payment_method = %s";
                $where_values[] = sanitize_text_field($filters['payment_method']);
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(b.preferred_date) >= %s";
                $where_values[] = sanitize_text_field($filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(b.preferred_date) <= %s";
                $where_values[] = sanitize_text_field($filters['date_to']);
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            if ($services_table_exists) {
                $query = "
                    SELECT b.*, s.service_name
                    FROM {$booking_table} b
                    LEFT JOIN {$services_table} s ON b.service_id = s.id
                    WHERE {$where_clause}
                    ORDER BY b.created_at DESC
                    LIMIT 100
                ";
            } else {
                $query = "
                    SELECT b.*, '' as service_name
                    FROM {$booking_table} b
                    WHERE {$where_clause}
                    ORDER BY b.created_at DESC
                    LIMIT 100
                ";
            }
            
            if (!empty($where_values)) {
                $query = $wpdb->prepare($query, $where_values);
            }
            
            $bookings = $wpdb->get_results($query, ARRAY_A);
            
            if ($wpdb->last_error) {
                wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
                return;
            }
            
            wp_send_json_success(array('bookings' => $bookings ? $bookings : array()));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'An error occurred: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for updating booking status
     */
    public function update_booking_status_ajax() {
        try {
            check_ajax_referer('booking_history_nonce', 'nonce');
            
            $booking_id = intval($_POST['booking_id']);
            $status = sanitize_text_field($_POST['status']);
            
            if (!$booking_id || !$status) {
                wp_send_json_error(array('message' => 'Invalid parameters'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'service_bookings';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
            
            if (!$table_exists) {
                wp_send_json_error(array('message' => 'Booking table not found'));
                return;
            }
            
            $result = $wpdb->update(
                $table_name,
                array(
                    'booking_status' => $status,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $booking_id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Booking status updated successfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to update booking status'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'An error occurred: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for getting services for dropdown
     */
    public function get_booking_services_ajax() {
        try {
            check_ajax_referer('booking_history_nonce', 'nonce');
            
            global $wpdb;
            $services_table = $wpdb->prefix . 'manager_services';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") == $services_table;
            
            if (!$table_exists) {
                wp_send_json_success(array('services' => array()));
                return;
            }
            
            $services = $wpdb->get_results(
                "SELECT id, service_name FROM {$services_table} WHERE is_active = 1 ORDER BY service_name ASC",
                ARRAY_A
            );
            
            if ($wpdb->last_error) {
                wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
                return;
            }
            
            wp_send_json_success(array('services' => $services ? $services : array()));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'An error occurred: ' . $e->getMessage()));
        }
    }
}

// Initialize the plugin only if not already initialized
if (!class_exists('BookingHistory_Instance_Check')) {
    class BookingHistory_Instance_Check {
        private static $instance = null;
        
        public static function init() {
            if (self::$instance === null) {
                try {
                    self::$instance = new BookingHistory();
                } catch (Exception $e) {
                    error_log('BookingHistory initialization error: ' . $e->getMessage());
                }
            }
            return self::$instance;
        }
    }
    
    // Initialize on plugins_loaded to ensure WordPress is fully loaded
    add_action('plugins_loaded', array('BookingHistory_Instance_Check', 'init'));
}
