<?php
/**
 * Simple Booking History Log
 * A clean log view for managers to see completed services
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Booking_Log {
    
    public function __construct() {
        add_shortcode('booking_log', array($this, 'display_booking_log'));
        add_shortcode('booking_history', array($this, 'display_booking_log')); // Backward compatibility
        add_action('wp_ajax_get_booking_log', array($this, 'get_booking_log_ajax'));
        add_action('wp_ajax_nopriv_get_booking_log', array($this, 'get_booking_log_ajax'));
        add_action('wp_ajax_get_services_list', array($this, 'get_services_list_ajax'));
        add_action('wp_ajax_nopriv_get_services_list', array($this, 'get_services_list_ajax'));
    }
    
    public function display_booking_log($atts = array()) {
        $atts = shortcode_atts(array(
            'limit' => 50,
            'status' => 'completed'
        ), $atts);
        
        wp_enqueue_script('jquery');
        
        ob_start();
        ?>
        <div class="booking-log-container">
            <style>
                /* Simple Booking Log Styles */
                .booking-log-container {
                    font-family: 'Poppins', sans-serif;
                    background: #f8f9fa;
                    color: #333333;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 40px 20px;
                    border-radius: 12px;
                }
                
                .log-header {
                    text-align: center;
                    margin-bottom: 40px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #c9a74d;
                }
                
                .log-title {
                    font-family: 'Playfair Display', serif;
                    font-size: 2.5rem;
                    color: #333;
                    margin-bottom: 12px;
                    font-weight: 700;
                }
                
                .log-title span {
                    color: #c9a74d;
                }
                
                .log-subtitle {
                    font-size: 1.1rem;
                    color: #666;
                    margin-bottom: 0;
                    line-height: 1.6;
                }
                
                .log-filters {
                    background: white;
                    padding: 24px;
                    border-radius: 12px;
                    margin-bottom: 30px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                    border: 1px solid #e9ecef;
                }
                
                .filters-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr 1fr auto auto;
                    gap: 20px;
                    align-items: end;
                }
                
                .filter-group {
                    display: flex;
                    flex-direction: column;
                }
                
                .filter-label {
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                }
                
                .filter-input, .filter-select {
                    padding: 12px 16px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 1rem;
                    transition: border-color 0.2s ease;
                    background: white;
                }
                
                .filter-input:focus, .filter-select:focus {
                    outline: none;
                    border-color: #c9a74d;
                    box-shadow: 0 0 0 3px rgba(201, 167, 77, 0.1);
                }
                
                .search-btn {
                    padding: 12px 24px;
                    background: linear-gradient(135deg, #c9a74d, #d4b866);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    height: 48px;
                }
                
                .search-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(201, 167, 77, 0.3);
                }
                
                .log-entries {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
                    overflow: hidden;
                }
                
                .log-table {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                }
                
                .log-table th:nth-child(1) { width: 8%; }   /* ID */
                .log-table th:nth-child(2) { width: 15%; }  /* Service */
                .log-table th:nth-child(3) { width: 15%; }  /* Staff */
                .log-table th:nth-child(4) { width: 20%; }  /* Customer */
                .log-table th:nth-child(5) { width: 15%; }  /* Date Created */
                .log-table th:nth-child(6) { width: 15%; }  /* Appointment Date */
                .log-table th:nth-child(7) { width: 12%; }  /* Status */
                
                .log-table th {
                    background: linear-gradient(135deg, #c9a74d, #d4b866);
                    color: white;
                    padding: 16px;
                    text-align: left;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-size: 0.875rem;
                }
                
                .log-table td {
                    padding: 16px;
                    border-bottom: 1px solid #f1f3f4;
                    vertical-align: top;
                }
                
                .log-table tr:hover {
                    background: #f8f9fa;
                }
                
                .log-table tr:last-child td {
                    border-bottom: none;
                }
                
                .booking-id {
                    font-weight: bold;
                    color: #666;
                    font-size: 0.9em;
                    text-align: center;
                    padding: 4px 8px;
                    background: #f8f9fa;
                    border-radius: 12px;
                    display: inline-block;
                }
                
                .service-name {
                    font-weight: 600;
                    color: #000000;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .staff-name {
                    font-weight: 600;
                    color: #2c5aa0;
                    font-size: 0.95rem;
                }
                
                .staff-position {
                    font-size: 0.8rem;
                    color: #6c757d;
                    font-style: italic;
                    margin-top: 2px;
                }
                
                .staff-unassigned {
                    color: #6c757d;
                    font-style: italic;
                    font-size: 0.9rem;
                }
                
                .customer-name {
                    font-weight: 600;
                    color: #333;
                }
                
                .customer-contact {
                    font-size: 0.875rem;
                    color: #666;
                    margin-top: 4px;
                }
                
                .date-time {
                    font-size: 0.9rem;
                    color: #555;
                }
                
                .completed-badge {
                    display: inline-block;
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .status-pending {
                    background: #fff3cd;
                    color: #856404;
                }
                
                .status-confirmed {
                    background: #d4edda;
                    color: #155724;
                }
                
                .status-completed {
                    background: #d4edda;
                    color: #155724;
                }
                
                .status-cancelled {
                    background: #f8d7da;
                    color: #721c24;
                }
                
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
                
                .empty-log {
                    text-align: center;
                    padding: 60px 20px;
                    color: #666;
                }
                
                .empty-icon {
                    font-size: 3rem;
                    color: #c9a74d;
                    margin-bottom: 20px;
                }
                
                /* Responsive Design */
                @media (max-width: 768px) {
                    .booking-log-container {
                        padding: 20px 16px;
                    }
                    
                    .log-title {
                        font-size: 2rem;
                    }
                    
                    .filters-row {
                        grid-template-columns: 1fr;
                        gap: 16px;
                    }
                    
                    .log-table {
                        font-size: 0.875rem;
                    }
                    
                    .log-table th,
                    .log-table td {
                        padding: 12px 8px;
                    }
                }
                
                @media (max-width: 600px) {
                    .log-table th:nth-child(5),
                    .log-table td:nth-child(5),
                    .log-table th:nth-child(6),
                    .log-table td:nth-child(6) {
                        display: none;
                    }
                }
            </style>
            
            <!-- Header -->
            <div class="log-header">
                <h1 class="log-title">
                    Service <span>Log</span>
                </h1>
                <p class="log-subtitle">
                    View all service bookings and customer information
                </p>
            </div>
            
            <!-- Filters -->
            <div class="log-filters">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label" for="search-customer">Search Customer</label>
                        <input type="text" id="search-customer" class="filter-input" placeholder="Customer name or email...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="service-filter">Filter by Service</label>
                        <select id="service-filter" class="filter-select">
                            <option value="">All Services</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="date-filter">Date Range</label>
                        <input type="date" id="date-filter" class="filter-input">
                    </div>
                    <button id="search-log" class="search-btn">
                        🔍 Search
                    </button>
                    <button id="refresh-log" class="search-btn" style="background: linear-gradient(135deg, #28a745, #34ce57);">
                        🔄 Refresh
                    </button>
                </div>
            </div>
            
            <!-- Log Entries -->
            <div class="log-entries" id="log-container">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading service log...</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load initial data
            loadBookingLog();
            
            // Load services from database
            loadServices();
            
            // Search functionality
            $('#search-log').on('click', function() {
                loadBookingLog();
            });
            
            // Refresh functionality
            $('#refresh-log').on('click', function() {
                // Clear filters and reload
                $('#search-customer').val('');
                $('#service-filter').val('');
                $('#date-filter').val('');
                loadBookingLog();
            });
            
            // Enter key search
            $('#search-customer, #date-filter').on('keypress', function(e) {
                if (e.which === 13) {
                    loadBookingLog();
                }
            });
            
            // Service filter change
            $('#service-filter').on('change', function() {
                loadBookingLog();
            });
            
            function loadServices() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_services_list'
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            const serviceSelect = $('#service-filter');
                            serviceSelect.find('option:not(:first)').remove();
                            response.data.forEach(function(service) {
                                serviceSelect.append(`<option value="${service.id}">${service.service_name}</option>`);
                            });
                        }
                    },
                    error: function() {
                        console.log('Failed to load services');
                    }
                });
            }
            
            function loadBookingLog() {
                const customerSearch = $('#search-customer').val();
                const serviceFilter = $('#service-filter').val();
                const dateFilter = $('#date-filter').val();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_booking_log'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayBookingLog(response.data.bookings);
                        } else {
                            showError('Failed to load booking log');
                        }
                    },
                    error: function() {
                        showError('Network error occurred');
                    }
                });
            }
            
            function displayBookingLog(bookings) {
                const container = $('#log-container');
                
                if (!bookings || bookings.length === 0) {
                    container.html(`
                        <div class="empty-log">
                            <div class="empty-icon">📋</div>
                            <h3>No bookings found</h3>
                            <p>No bookings match your search criteria.</p>
                        </div>
                    `);
                    return;
                }
                
                let html = `
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Service</th>
                                <th>Staff</th>
                                <th>Customer</th>
                                <th>Date Created</th>
                                <th>Appointment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                bookings.forEach(function(booking) {
                    const serviceName = booking.service_name || 'No Service';
                    const staffInfo = booking.staff_name 
                        ? `<div class="staff-name">${booking.staff_name}</div><div class="staff-position">${booking.staff_position || ''}</div>`
                        : '<div class="staff-unassigned">Any Staff</div>';
                    
                    html += `
                        <tr>
                            <td>
                                <div class="booking-id">#${booking.id}</div>
                            </td>
                            <td>
                                <div class="service-name">${serviceName}</div>
                            </td>
                            <td>
                                ${staffInfo}
                            </td>
                            <td>
                                <div class="customer-name">${booking.customer_name}</div>
                                <div class="customer-contact">${booking.customer_email}</div>
                                ${booking.customer_phone ? `<div class="customer-contact">${booking.customer_phone}</div>` : ''}
                            </td>
                            <td>
                                <div class="date-time">${formatDateTime(booking.updated_at || booking.created_at)}</div>
                            </td>
                            <td>
                                <div class="date-time">${formatDate(booking.preferred_date)}</div>
                                <div class="date-time">${formatTime(booking.preferred_time)}</div>
                            </td>
                            <td>
                                <span class="completed-badge status-${booking.booking_status}">${getStatusText(booking.booking_status)}</span>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
                
                container.html(html);
            }
            
            function formatDateTime(dateTime) {
                if (!dateTime) return 'N/A';
                const date = new Date(dateTime);
                const options = {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                };
                return date.toLocaleDateString('en-US', options);
            }
            
            function formatDate(date) {
                if (!date) return 'N/A';
                const d = new Date(date);
                const options = {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                };
                return d.toLocaleDateString('en-US', options);
            }
            
            function formatTime(time) {
                if (!time) return 'N/A';
                // Convert 24-hour time to 12-hour format
                const [hours, minutes] = time.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const hour12 = hour % 12 || 12;
                return `${hour12}:${minutes} ${ampm}`;
            }
            
            function getStatusText(status) {
                const statusMap = {
                    'pending': 'Pending',
                    'confirmed': 'Confirmed',
                    'completed': 'Completed',
                    'cancelled': 'Cancelled'
                };
                return statusMap[status] || 'Unknown';
            }
            
            function showError(message) {
                $('#log-container').html(`
                    <div class="empty-log">
                        <div class="empty-icon">⚠️</div>
                        <h3>Error</h3>
                        <p>${message}</p>
                    </div>
                `);
            }
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    public function get_booking_log_ajax() {
        global $wpdb;
        
        
            // Read from service_booking posts
            $args = [
                'post_type' => 'service_booking',
                'posts_per_page' => 100,
                'post_status' => 'any',
                'orderby' => 'date',
                'order' => 'DESC'
            ];

            $query = new WP_Query($args);
            $bookings = [];

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

                    // Resolve service and staff names if possible
                    $service_name = null;
                    if (!empty($service_id)) {
                        $services_table = $wpdb->prefix . 'manager_services';
                        $svc = $wpdb->get_row($wpdb->prepare("SELECT service_name FROM $services_table WHERE id = %d", intval($service_id)));
                        if ($svc) $service_name = $svc->service_name;
                    }

                    $staff_name = null;
                    $staff_position = null;
                    if (!empty($staff_id)) {
                        $staff_table = $wpdb->prefix . 'staff_members';
                        $st = $wpdb->get_row($wpdb->prepare("SELECT staff_name, staff_position FROM $staff_table WHERE id = %d", intval($staff_id)));
                        if ($st) {
                            $staff_name = $st->staff_name;
                            $staff_position = $st->staff_position;
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
                        'updated_at' => get_post_meta($id, '_updated_at', true),
                        'service_name' => $service_name,
                        'staff_name' => $staff_name,
                        'staff_position' => $staff_position
                    ];
                }
                wp_reset_postdata();
            }

            wp_send_json_success(array(
                'bookings' => $bookings
            ));
        wp_send_json_success(array(
            'bookings' => $bookings ? $bookings : array()
        ));
    }
    
    public function get_services_list_ajax() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        
        // Get actual services from database
        $services = $wpdb->get_results(
            "SELECT id, service_name FROM $services_table WHERE is_active = 1 ORDER BY service_name ASC"
        );
        
        wp_send_json_success($services);
    }
}

// Initialize the class
new Simple_Booking_Log();
?>
