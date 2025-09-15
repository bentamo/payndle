<?php
/*
Plugin Name: Manager Dashboard
Plugin URI:  https://payndle.com
Description: Clean and simple business management dashboard. Use shortcode [manager_dashboard].
Version:     1.2
Author:      Payndle
License:     GPL2
Shortcode: [manager_dashboard]
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle form submission
function save_business_info() {
    check_ajax_referer('business_info_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : 0;
    $fields = [
        'business_name', 'business_description', 'business_email', 'business_phone',
        'business_address', 'business_city', 'business_state', 'business_zip',
        'business_country', 'business_website', 'business_hours'
    ];
    
    $response = [];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            if ($field === 'business_name') {
                wp_update_post([
                    'ID' => $business_id,
                    'post_title' => $value
                ]);
            } else {
                update_post_meta($business_id, '_' . $field, $value);
            }
            $response[$field] = $value;
        }
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_save_business_info', 'save_business_info');

function manager_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="login-prompt">
            <h2>Welcome to Payndle</h2>
            <p>Please sign in to access your business dashboard.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="login-button">Sign In</a>
        </div>';
    }

    $current_user = wp_get_current_user();
    $business_id = isset( $_GET['business_id'] ) ? intval( $_GET['business_id'] ) : 0;
    $business = $business_id ? get_post( $business_id ) : null;
    
    if ( $business ) {
        $owner_id = get_post_meta( $business->ID, '_business_owner_id', true );
        if ( $owner_id != $current_user->ID && ! current_user_can( 'manage_options' ) ) {
            return '<p>Access denied. Please contact support if you believe this is an error.</p>';
        }
        
        // Get business information
        $business_name = $business->post_title;
        $business_code = get_post_meta( $business->ID, '_business_code', true );
        $business_status = get_post_meta( $business->ID, '_business_status', true ) ?: 'active';
        $business_phone = get_post_meta( $business->ID, '_business_phone', true );
        $business_email = get_post_meta( $business->ID, '_business_email', true );
        $business_address = get_post_meta( $business->ID, '_business_address', true );
        $business_city = get_post_meta( $business->ID, '_business_city', true );
        $business_state = get_post_meta( $business->ID, '_business_state', true );
        $business_zip = get_post_meta( $business->ID, '_business_zip', true );
        $business_country = get_post_meta( $business->ID, '_business_country', true );
        $business_website = get_post_meta( $business->ID, '_business_website', true );
        $business_description = get_post_meta( $business->ID, '_business_description', true );
        $business_hours = get_post_meta( $business->ID, '_business_hours', true );
        $business_logo = get_post_meta( $business->ID, '_business_logo', true );
        $setup_completed = get_post_meta( $business->ID, '_business_setup_completed', true );
    } else {
        // Default values if no business found
        $business_name = 'Your Business';
        $business_code = '';
        $business_status = 'inactive';
        $business_phone = $business_email = $business_address = '';
        $business_city = $business_state = $business_zip = $business_country = '';
        $business_website = $business_description = $business_hours = $business_logo = '';
        $setup_completed = '';
    }

    ob_start();
    ?>
    <div class="dashboard-container">
        <!-- Simple Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1><?php echo esc_html($business_name); ?> Dashboard</h1>
                <p class="welcome-message">Welcome back, <?php echo esc_html($current_user->display_name); ?></p>
            </div>
        </header>

        <main class="main-content">

            <!-- Business Info Section -->
            <section class="business-info-section">
                <div class="section-header">
                    <h2 class="section-title">Business Information</h2>
                    <button id="edit-business-info" class="btn btn-edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit
                    </button>
                </div>
                
                <form id="business-info-form" class="info-grid" style="display: none;">
                    <?php wp_nonce_field('business_info_nonce', 'business_info_nonce'); ?>
                    <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
                    
                    <div class="form-section">
                        <h3>Business Details</h3>
                        <div class="form-group">
                            <label for="business_name">Business Name</label>
                            <input type="text" id="business_name" name="business_name" value="<?php echo esc_attr($business_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="business_description">Description</label>
                            <textarea id="business_description" name="business_description" rows="3"><?php echo esc_textarea($business_description); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="business_website">Website</label>
                            <input type="url" id="business_website" name="business_website" value="<?php echo esc_url($business_website); ?>">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Contact Information</h3>
                        <div class="form-group">
                            <label for="business_email">Email</label>
                            <input type="email" id="business_email" name="business_email" value="<?php echo esc_attr($business_email); ?>">
                        </div>
                        <div class="form-group">
                            <label for="business_phone">Phone</label>
                            <input type="tel" id="business_phone" name="business_phone" value="<?php echo esc_attr($business_phone); ?>">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Location</h3>
                        <div class="form-group">
                            <label for="business_address">Address</label>
                            <textarea id="business_address" name="business_address" rows="2"><?php echo esc_textarea($business_address); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_city">City</label>
                                <input type="text" id="business_city" name="business_city" value="<?php echo esc_attr($business_city); ?>">
                            </div>
                            <div class="form-group">
                                <label for="business_state">State</label>
                                <input type="text" id="business_state" name="business_state" value="<?php echo esc_attr($business_state); ?>">
                            </div>
                            <div class="form-group">
                                <label for="business_zip">ZIP</label>
                                <input type="text" id="business_zip" name="business_zip" value="<?php echo esc_attr($business_zip); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="business_country">Country</label>
                            <input type="text" id="business_country" name="business_country" value="<?php echo esc_attr($business_country); ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                
                <div id="business-info-display" class="info-grid">
                    <div class="info-card">
                        <h3>Business Details</h3>
                        <div class="info-content">
                            <div class="info-row">
                                <span class="info-label">Name</span>
                                <span class="info-value"><?php echo esc_html($business_name); ?></span>
                            </div>
                            <?php if (!empty($business_code)) : ?>
                            <div class="info-row">
                                <span class="info-label">Business ID</span>
                                <span class="info-value"><?php echo esc_html($business_code); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($business_description)) : ?>
                            <div class="info-row">
                                <span class="info-label">Description</span>
                                <span class="info-value"><?php echo esc_html($business_description); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($business_website)) : ?>
                            <div class="info-row">
                                <span class="info-label">Website</span>
                                <a href="<?php echo esc_url($business_website); ?>" target="_blank" class="info-value">
                                    <?php echo esc_html(parse_url($business_website, PHP_URL_HOST) ?: $business_website); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>Contact Information</h3>
                        <div class="info-content">
                            <?php if (!empty($business_email)) : ?>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo esc_html($business_email); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($business_phone)) : ?>
                            <div class="info-row">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo esc_html($business_phone); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($business_address)) : ?>
                            <div class="info-row">
                                <span class="info-label">Address</span>
                                <span class="info-value">
                                    <?php 
                                    echo esc_html($business_address);
                                    if (!empty($business_city)) echo ', ' . esc_html($business_city);
                                    if (!empty($business_state)) echo ', ' . esc_html($business_state);
                                    if (!empty($business_zip)) echo ' ' . esc_html($business_zip);
                                    if (!empty($business_country)) echo '<br>' . esc_html($business_country);
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>Account Status</h3>
                        <div class="info-content">
                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span class="status-badge <?php echo $business_status === 'active' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst(esc_html($business_status)); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-value">
                                    <?php if ($business_status === 'active') : ?>
                                        Your business is live and visible to customers.
                                    <?php else : ?>
                                        Your business profile is not yet active.
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (!empty($setup_completed)) : ?>
                            <div class="info-row">
                                <span class="info-label">Setup Date</span>
                                <span class="info-value"><?php echo esc_html(date('F j, Y', strtotime($setup_completed))); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'manager_dashboard', 'manager_dashboard_shortcode' );

// Add modern CSS
// Enqueue scripts and styles
function manager_dashboard_scripts() {
    if (is_page() && has_shortcode(get_post()->post_content, 'manager_dashboard')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('manager-dashboard', plugins_url('assets/js/manager-dashboard.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('manager-dashboard', 'managerDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('business_info_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'manager_dashboard_scripts');

add_action('wp_head', function() {
    if (is_page() && has_shortcode(get_post()->post_content, 'manager_dashboard')) {
        ?>
        <style>
            :root {
                /* Brand Colors */
                --primary-green: #64C493;
                --primary-navy: #0C1930;
                --secondary-white: #FFFFFF;
                --accent-gray: #F5F7FA;
                --border-color: #E5E8ED;
                
                /* Semantic Colors */
                --primary-color: var(--primary-green);
                --primary-light: rgba(100, 196, 147, 0.1);
                --secondary-color: var(--primary-navy);
                --text-primary: var(--primary-navy);
                --text-secondary: #6B7280;
                --bg-color: var(--secondary-white);
                --card-bg: var(--secondary-white);
                --success-color: #10B981;
                --warning-color: #F59E0B;
                --danger-color: #EF4444;
                --sidebar-width: 260px;
                
                /* Typography */
                --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                --font-regular: 400;
                --font-medium: 500;
                --font-semibold: 600;
                --font-bold: 700;
                --border-radius: 8px;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: var(--font-family);
            }
            
            body {
                background-color: var(--bg-color);
                color: var(--text-primary);
                line-height: 1.5;
            }
            
            .dashboard-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1.5rem 2rem;
            }
            
            /* Sidebar Styles */
            .sidebar {
                width: var(--sidebar-width);
                background: var(--primary-navy);
                color: var(--secondary-white);
                padding: 2rem 1rem;
                position: fixed;
                height: 100vh;
                overflow-y: auto;
                border-right: 1px solid var(--border-color);
            }
            
            .sidebar-header {
                padding: 0 1rem 2rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                margin-bottom: 2rem;
            }
            
            .sidebar-logo {
                font-size: 1.5rem;
                font-weight: var(--font-bold);
                color: var(--secondary-white);
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .nav-menu {
                list-style: none;
            }
            
            .nav-item {
                margin-bottom: 0.5rem;
            }
            
            .nav-link {
                display: flex;
                align-items: center;
                padding: 0.75rem 1rem;
                color: rgba(255,255,255,0.8);
                text-decoration: none;
                border-radius: 0.5rem;
                transition: all 0.2s;
            }
            
            .nav-link:hover, .nav-link.active {
                background: rgba(255,255,255,0.1);
                color: white;
            }
            
            .nav-link i {
                margin-right: 0.75rem;
                width: 1.25rem;
                text-align: center;
            }
            
            /* Main Content */
            .main-content {
                width: 100%;
                padding: 0;
                background: #fff;
                border-radius: 12px;
                border: 1px solid var(--border-color);
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            
            .dashboard-header {
                background: linear-gradient(135deg, #0C1930 0%, #1a365d 100%);
                color: white;
                padding: 1.5rem;
                margin: 0 -1.5rem 2rem;
                border-radius: 0 0 12px 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            
            .dashboard-header h1 {
                font-size: 1.75rem;
                font-weight: 700;
                color: white;
                margin: 0 0 0.5rem 0;
                letter-spacing: -0.5px;
            }
            
            .welcome-message {
                color: rgba(255, 255, 255, 0.9);
                margin: 0;
                font-size: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .page-title h1 {
                font-size: 1.875rem;
                font-weight: var(--font-bold);
                color: var(--text-primary);
                margin-bottom: 0.5rem;
                font-family: var(--font-family);
            }
            
            .page-title p {
                color: var(--text-secondary);
                margin: 0;
            }
            
            /* Stats Grid */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .stat-card {
                background: var(--card-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                border: 1px solid var(--border-color);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            }
            
            .stat-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }
            
            .stat-icon {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }
            
            .stat-icon.primary {
                background: var(--primary-green);
            }
            
            .stat-icon.success {
                background: var(--success-color);
            }
            
            .stat-icon.warning {
                background: var(--warning-color);
            }
            
            .stat-value {
                font-size: 1.5rem;
                font-weight: var(--font-bold);
                margin-bottom: 0.25rem;
                color: var(--primary-navy);
            }
            
            .stat-label {
                color: var(--text-secondary);
                font-size: 0.875rem;
            }
            
            .stat-change {
                display: flex;
                align-items: center;
                font-size: 0.75rem;
                margin-top: 0.5rem;
            }
            
            .stat-change.positive {
                color: var(--success-color);
            }
            
            .stat-change.negative {
                color: var(--danger-color);
            }
            
            /* Recent Bookings */
            .recent-bookings {
                background: var(--card-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                border: 1px solid var(--border-color);
                margin-bottom: 2rem;
            }
            
            .section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #f1f5f9;
            }
            
            .section-title {
                font-size: 1.25rem;
                font-weight: var(--font-semibold);
                color: var(--primary-navy);
                font-family: var(--font-family);
            }
            
            .view-all {
                color: var(--primary-green);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: var(--font-medium);
                font-family: var(--font-family);
                transition: color 0.2s;
            }
            
            .view-all:hover {
                color: var(--primary-navy);
            }
            
            .booking-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .booking-table th {
                text-align: left;
                padding: 0.75rem 1rem;
                color: var(--text-secondary);
                font-weight: 500;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                border-bottom: 1px solid var(--border-color);
            }
            
            .booking-table td {
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
                vertical-align: middle;
            }
            
            .booking-table tr:last-child td {
                border-bottom: none;
            }
            
            .booking-customer {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .customer-avatar {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 50%;
                background: var(--primary-light);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--primary-green);
                font-weight: var(--font-semibold);
                font-family: var(--font-family);
            }
            
            .customer-info h4 {
                font-size: 0.875rem;
                font-weight: 500;
                margin-bottom: 0.25rem;
            }
            
            .customer-info p {
                font-size: 0.75rem;
                color: var(--text-secondary);
                margin: 0;
            }
            
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .status-badge.completed {
                background: rgba(100, 196, 147, 0.1);
                color: var(--primary-green);
            }
            
            .status-badge.pending {
                background: #fef3c7;
                color: #d97706;
            }
            
            .status-badge.cancelled {
                background: #fee2e2;
                color: #dc2626;
            }
            
            .booking-amount {
                font-weight: 600;
                color: var(--text-primary);
            }
            
            .action-button {
                background: none;
                border: none;
                color: var(--text-secondary);
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 0.5rem;
                transition: all 0.2s;
            }
            
            .btn-primary {
                background: var(--primary-green);
                color: var(--secondary-white);
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: var(--border-radius);
                font-weight: var(--font-medium);
                cursor: pointer;
                transition: all 0.2s;
                font-family: var(--font-family);
            }
            
            .btn-primary:hover {
                background: var(--primary-navy);
                transform: translateY(-1px);
            }
            
            .action-button:hover {
                background: var(--accent-gray);
                color: var(--primary-green);
            }
            
            /* Overview Grid */
            .overview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .overview-card {
                background: var(--card-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                border: 1px solid var(--border-color);
                text-align: center;
            }

            .overview-value {
                font-size: 2rem;
                font-weight: var(--font-bold);
                color: var(--primary-navy);
                margin-bottom: 0.5rem;
            }

            .overview-label {
                color: var(--text-secondary);
                font-size: 0.9rem;
            }

            /* Recent Activity */
            .recent-activity {
                background: var(--card-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                border: 1px solid var(--border-color);
                margin-bottom: 2rem;
            }

            .activity-list {
                margin-top: 1rem;
            }

            .activity-item {
                display: flex;
                align-items: center;
                padding: 1rem 0;
                border-bottom: 1px solid var(--border-color);
            }

            .activity-item:last-child {
                border-bottom: none;
            }

            .activity-icon {
                font-size: 1.25rem;
                margin-right: 1rem;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 50%;
                background: var(--accent-gray);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .activity-details {
                flex: 1;
            }

            .activity-text {
                color: var(--text-primary);
                margin-bottom: 0.25rem;
            }

            .activity-time {
                font-size: 0.8rem;
                color: var(--text-secondary);
            }

            .activity-amount {
                font-weight: var(--font-semibold);
                color: var(--primary-navy);
            }

            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .overview-grid {
                    grid-template-columns: 1fr;
                }
                
                .dashboard-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 1rem;
                }
            }
            .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            /* Form and Info Card Styles */
            .form-section, .info-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 1.75rem 2rem;
                margin-bottom: 1.5rem;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
                transition: all 0.2s ease;
            }
            
            .info-card:hover {
                box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
                transform: translateY(-2px);
            }
            
            .form-section h3, .info-card h3 {
                font-size: 1rem;
                font-weight: 600;
                margin: 0 0 1.25rem 0;
                color: #111827;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid #f3f4f6;
            }
            
            .form-group {
                margin-bottom: 1.5rem;
                position: relative;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #374151;
            }
            
            .form-group input[type="text"],
            .form-group input[type="email"],
            .form-group input[type="tel"],
            .form-group input[type="url"],
            .form-group textarea {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 0.9375rem;
                transition: all 0.2s ease;
                background-color: #f8fafc;
            }
            
            .form-group input[type="text"]:focus,
            .form-group input[type="email"]:focus,
            .form-group input[type="tel"]:focus,
            .form-group input[type="url"]:focus,
            .form-group textarea:focus {
                outline: none;
                border-color: #64C493;
                box-shadow: 0 0 0 3px rgba(100, 196, 147, 0.15);
                background-color: #fff;
            }
            
            .form-group textarea {
                min-height: 100px;
                resize: vertical;
            }
            
            .form-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .form-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                margin-top: 1.5rem;
                padding-top: 1rem;
                border-top: 1px solid #f3f4f6;
            }
            
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.6875rem 1.5rem;
                border-radius: 8px;
                font-size: 0.9375rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px solid transparent;
                gap: 0.5rem;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #64C493 0%, #4CAF7D 100%);
                color: white;
                border: none;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            
            .btn-secondary {
                background-color: #fff;
                color: #4b5563;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }
            
            .btn-secondary:hover {
                background-color: #f8fafc;
                border-color: #cbd5e1;
            }
            
            .btn-edit {
                background: #fff;
                border: 1px solid #e2e8f0;
                color: #4b5563;
                font-size: 0.875rem;
                font-weight: 500;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }
            
            .btn-edit:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
                transform: translateY(-1px);
            }
            
            .btn:hover {
                opacity: 0.9;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.5rem;
                margin-top: 1.5rem;
            }
            
            .info-content {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .info-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                line-height: 1.5;
            }
            
            .info-label {
                font-weight: 500;
                color: #374151;
                min-width: 100px;
                font-size: 0.875rem;
            }
            
            .info-value {
                color: #4b5563;
                flex: 1;
                font-size: 0.875rem;
            }
            
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .status-badge.active {
                background: #ecfdf5;
                color: #059669;
            }
            
            .status-badge.inactive {
                background: #fef3c7;
                color: #d97706;
            }
            
            /* Utility Classes */
            .hidden {
                display: none !important;
            }
            .profile-details p { margin: 5px 0; }
            
            /* Login prompt */
            .login-prompt { max-width: 500px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 8px; text-align: center; }
            .login-prompt h2 { margin-top: 0; }
            .login-button {
                display: inline-block;
                padding: 10px 20px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                margin-top: 15px;
            }
        </style>
        <?php
    }
});
