<?php
/**
 * Manager Business Setup Panel
 * Description: A comprehensive business and service management panel accessible via shortcode for frontend use
 * Version: 1.0.0
 * Shortcode: [manager_panel]
 */

if (!defined('ABSPATH')) {
    exit;
}

class ManagerBusinessPanel {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('manager_panel', [$this, 'render_manager_panel']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Register AJAX actions for each specific action
        add_action('wp_ajax_get_business_info', [$this, 'get_business_info_ajax']);
        add_action('wp_ajax_nopriv_get_business_info', [$this, 'get_business_info_ajax']);
        add_action('wp_ajax_save_business_info', [$this, 'save_business_info_ajax']);
        add_action('wp_ajax_nopriv_save_business_info', [$this, 'save_business_info_ajax']);
        add_action('wp_ajax_get_services', [$this, 'get_services_ajax']);
        add_action('wp_ajax_nopriv_get_services', [$this, 'get_services_ajax']);
        add_action('wp_ajax_get_service', [$this, 'get_service_ajax']);
        add_action('wp_ajax_nopriv_get_service', [$this, 'get_service_ajax']);
        add_action('wp_ajax_create_service', [$this, 'create_service_ajax']);
        add_action('wp_ajax_nopriv_create_service', [$this, 'create_service_ajax']);
        add_action('wp_ajax_update_service', [$this, 'update_service_ajax']);
        add_action('wp_ajax_nopriv_update_service', [$this, 'update_service_ajax']);
        add_action('wp_ajax_delete_service', [$this, 'delete_service_ajax']);
        add_action('wp_ajax_nopriv_delete_service', [$this, 'delete_service_ajax']);
        add_action('wp_ajax_toggle_service_status', [$this, 'toggle_service_status_ajax']);
        add_action('wp_ajax_nopriv_toggle_service_status', [$this, 'toggle_service_status_ajax']);
        add_action('wp_ajax_toggle_service_featured', [$this, 'toggle_service_featured_ajax']);
        add_action('wp_ajax_nopriv_toggle_service_featured', [$this, 'toggle_service_featured_ajax']);
        add_action('wp_ajax_upload_service_thumbnail', [$this, 'upload_service_thumbnail_ajax']);
        add_action('wp_ajax_nopriv_upload_service_thumbnail', [$this, 'upload_service_thumbnail_ajax']);
        add_action('wp_ajax_delete_service_thumbnail', [$this, 'delete_service_thumbnail_ajax']);
        add_action('wp_ajax_nopriv_delete_service_thumbnail', [$this, 'delete_service_thumbnail_ajax']);
        
        // Debug endpoint for troubleshooting
        add_action('wp_ajax_debug_manager_panel', [$this, 'debug_manager_panel_ajax']);
        add_action('wp_ajax_nopriv_debug_manager_panel', [$this, 'debug_manager_panel_ajax']);
    }
    
    public function init() {
        // Initialize any necessary setup
        $this->create_manager_tables();
        
        // Sample data generation removed for production
    }
    
    /**
     * Create database tables for manager functionality
     */
    private function create_manager_tables() {
        global $wpdb;
        
        // Business info table
        $business_table = $wpdb->prefix . 'manager_business';
        
        // Services table
        $services_table = $wpdb->prefix . 'manager_services';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Business information table
        $business_sql = "CREATE TABLE IF NOT EXISTS $business_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            business_name varchar(255) NOT NULL,
            business_description text DEFAULT '',
            business_email varchar(255) DEFAULT '',
            business_phone varchar(20) DEFAULT '',
            business_address text DEFAULT '',
            business_city varchar(100) DEFAULT '',
            business_state varchar(100) DEFAULT '',
            business_zip_code varchar(20) DEFAULT '',
            business_hours text DEFAULT '',
            business_timezone varchar(100) DEFAULT '',
            business_website varchar(255) DEFAULT '',
            business_logo varchar(255) DEFAULT '',
            social_facebook varchar(255) DEFAULT '',
            social_twitter varchar(255) DEFAULT '',
            social_instagram varchar(255) DEFAULT '',
            social_linkedin varchar(255) DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Services table
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($business_sql);
        dbDelta($services_sql);
    }
    
    /**
     * Enqueue frontend assets only when shortcode is present
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'manager_panel')) {
            // Cropper.js for image cropping
            wp_enqueue_style('cropper-css', 'https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.css', [], '1.5.13');
            wp_enqueue_style('manager-panel-style', plugins_url('assets/css/manager-panel.css', __FILE__), [], '1.2.2');
            wp_enqueue_script('cropper-js', 'https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.js', [], '1.5.13', true);
            wp_enqueue_script('manager-panel-script', plugins_url('assets/js/manager-panel.js', __FILE__), ['jquery'], '1.1.2', true);
            
            wp_localize_script('manager-panel-script', 'managerPanel', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('manager_panel_nonce')
            ]);
        }
    }
    
    /**
     * Get business information via AJAX
     */
    public function get_business_info_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $business_table = $wpdb->prefix . 'manager_business';
        $business = $wpdb->get_row("SELECT * FROM $business_table WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        
        if ($business) {
            // Map database fields to expected frontend fields
            $business_data = [
                'business_name' => $business->business_name,
                'description' => $business->business_description,
                'email' => $business->business_email,
                'phone' => $business->business_phone,
                'address' => $business->business_address,
                'city' => $business->business_city ?? '',
                'state' => $business->business_state ?? '',
                'zip_code' => $business->business_zip_code ?? '',
                'business_hours' => $business->business_hours,
                'website' => $business->business_website,
                'facebook' => $business->social_facebook,
                'twitter' => $business->social_twitter,
                'instagram' => $business->social_instagram,
                'linkedin' => $business->social_linkedin,
                'timezone' => $business->business_timezone ?? ''
            ];
            wp_send_json_success($business_data);
        } else {
            wp_send_json_success(null);
        }
    }
    
    /**
     * Save business information via AJAX
     */
    public function save_business_info_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $business_table = $wpdb->prefix . 'manager_business';
        
        $data = [
            'business_name' => sanitize_text_field($_POST['business_name']),
            'business_description' => sanitize_textarea_field($_POST['description']),
            'business_email' => sanitize_email($_POST['email']),
            'business_phone' => sanitize_text_field($_POST['phone']),
            'business_address' => sanitize_textarea_field($_POST['address']),
            'business_city' => sanitize_text_field($_POST['city']),
            'business_state' => sanitize_text_field($_POST['state']),
            'business_zip_code' => sanitize_text_field($_POST['zip_code']),
            'business_hours' => sanitize_textarea_field($_POST['business_hours']),
            'business_timezone' => sanitize_text_field($_POST['timezone']),
            'business_website' => esc_url_raw($_POST['website']),
            'social_facebook' => esc_url_raw($_POST['facebook']),
            'social_twitter' => esc_url_raw($_POST['twitter']),
            'social_instagram' => esc_url_raw($_POST['instagram']),
            'social_linkedin' => esc_url_raw($_POST['linkedin'])
        ];
        
        // Check if business exists
        $existing_business = $wpdb->get_row("SELECT id FROM $business_table WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        
        if ($existing_business) {
            // Update existing business
            $result = $wpdb->update($business_table, $data, ['id' => $existing_business->id]);
        } else {
            // Create new business
            $result = $wpdb->insert($business_table, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Business information saved successfully']);
        } else { 
            wp_send_json_error(['message' => 'Failed to save business information']);
        }
    }
    
    /**
     * Get services via AJAX
     */
    public function get_services_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'");
        if (!$table_exists) {
            wp_send_json_error(['message' => 'Services table does not exist. Please refresh the page to create tables.']);
            return;
        }
        
        $services = $wpdb->get_results("SELECT id, service_name as name, service_description as description, service_price as price, service_duration as duration, service_category as category, service_image as thumbnail, is_featured, is_active, CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status FROM $services_table ORDER BY sort_order ASC, service_name ASC");
        
        // Check for database errors
        if ($wpdb->last_error) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            return;
        }
        
        wp_send_json_success($services);
    }
    
    /**
     * Save service via AJAX (legacy method - not used)
     */
    public function save_service_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        
        $data = [
            'service_name' => sanitize_text_field($_POST['service_name']),
            'service_description' => sanitize_textarea_field($_POST['service_description']),
            'service_price' => floatval($_POST['service_price']),
            'service_duration' => sanitize_text_field($_POST['service_duration']),
            'service_category' => sanitize_text_field($_POST['service_category']),
            'is_featured' => intval($_POST['is_featured'] ?? 0),
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        ];
        
        $service_id = intval($_POST['service_id'] ?? 0);
        
        if ($service_id > 0) {
            // Update existing service
            $result = $wpdb->update($services_table, $data, ['id' => $service_id]);
        } else {
            // Create new service
            $result = $wpdb->insert($services_table, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Service saved successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to save service']);
        }
    }
    
    /**
     * Delete service via AJAX
     */
    public function delete_service_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        $service_id = intval($_POST['service_id']);
        
        $result = $wpdb->delete($services_table, ['id' => $service_id], ['%d']);
        
        if ($result) {
            wp_send_json_success(['message' => 'Service deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete service']);
        }
    }
    
    /**
     * Toggle service status via AJAX
     */
    public function toggle_service_status_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        $service_id = intval($_POST['service_id']);
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $services_table WHERE id = %d", $service_id));
        $new_status = $current_status ? 0 : 1;
        
        $result = $wpdb->update($services_table, ['is_active' => $new_status], ['id' => $service_id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Service status updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update service status']);
        }
    }
    
    /**
     * Get single service via AJAX
     */
    public function get_service_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        $service_id = intval($_POST['service_id']);
        
        $service = $wpdb->get_row($wpdb->prepare("SELECT id, service_name as name, service_description as description, service_price as price, service_duration as duration, service_category as category, is_featured FROM $services_table WHERE id = %d", $service_id));
        
        if ($service) {
            wp_send_json_success($service);
        } else {
            wp_send_json_error(['message' => 'Service not found']);
        }
    }
    
    /**
     * Create service via AJAX
     */
    public function create_service_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        
        $data = [
            'service_name' => sanitize_text_field($_POST['name']),
            'service_description' => sanitize_textarea_field($_POST['description']),
            'service_price' => floatval($_POST['price']),
            'service_duration' => sanitize_text_field($_POST['duration']),
            'service_category' => sanitize_text_field($_POST['category']),
            'is_featured' => intval($_POST['is_featured'] ?? 0),
            'is_active' => 1
        ];
        
        // Handle thumbnail upload if provided
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $thumbnail_url = $this->handle_thumbnail_upload($_FILES['thumbnail']);
            if ($thumbnail_url) {
                $data['service_image'] = $thumbnail_url;
            }
        }
        
        $result = $wpdb->insert($services_table, $data);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Service created successfully', 'id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to create service']);
        }
    }
    
    /**
     * Update service via AJAX
     */
    public function update_service_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        $service_id = intval($_POST['service_id']);
        
        $data = [
            'service_name' => sanitize_text_field($_POST['name']),
            'service_description' => sanitize_textarea_field($_POST['description']),
            'service_price' => floatval($_POST['price']),
            'service_duration' => sanitize_text_field($_POST['duration']),
            'service_category' => sanitize_text_field($_POST['category']),
            'is_featured' => intval($_POST['is_featured'] ?? 0)
        ];
        
        // Handle thumbnail upload if provided
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Delete old thumbnail first
            $old_image = $wpdb->get_var($wpdb->prepare(
                "SELECT service_image FROM $services_table WHERE id = %d",
                $service_id
            ));
            
            $thumbnail_url = $this->handle_thumbnail_upload($_FILES['thumbnail']);
            if ($thumbnail_url) {
                $data['service_image'] = $thumbnail_url;
                
                // Clean up old file
                if ($old_image) {
                    $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $old_image);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
        }
        
        $result = $wpdb->update($services_table, $data, ['id' => $service_id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Service updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update service']);
        }
    }
    
    /**
     * Toggle service featured status via AJAX
     */
    public function toggle_service_featured_ajax() {
        check_ajax_referer('manager_panel_nonce', 'nonce');
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'manager_services';
        $service_id = intval($_POST['service_id']);
        
        // Get current featured status
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT is_featured FROM $services_table WHERE id = %d", $service_id));
        $new_status = $current_status ? 0 : 1;
        
        $result = $wpdb->update($services_table, ['is_featured' => $new_status], ['id' => $service_id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Service featured status updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update featured status']);
        }
    }
    
    /**
     * Check if current user has manager access
     * For now, we'll just check if user is logged in
     * Later you can implement proper role checking
     */
    private function has_manager_access() {
        // Temporary: Allow all logged-in users
        // Later: return current_user_can('manage_options') or custom capability
        return is_user_logged_in();
    }
    
    /**
     * Render the manager panel shortcode
     */
    public function render_manager_panel($atts) {
        $atts = shortcode_atts([
            'title' => 'Business Manager Panel',
            'show_business' => 'true',
            'show_services' => 'true'
        ], $atts);
        
        // Check access (temporary - allow all for now)
        // if (!$this->has_manager_access()) {
        //     return '<div class="manager-panel-error">Access denied. Please log in as a manager.</div>';
        // }
        
        ob_start();
        ?>
        <div class="manager-panel-page-bg" role="region" aria-label="Manager Panel">
            <div id="manager-panel-container" class="manager-panel-wrapper" role="main">
                <div class="manager-panel-header">
                    <h2 id="manager-panel-title"><?php echo esc_html($atts['title']); ?></h2>
                    <div class="manager-panel-actions" role="group" aria-label="Panel actions">
                        <button id="refresh-panel" class="btn btn-secondary" type="button" aria-label="Refresh panel">Refresh</button>
                        <button id="debug-panel" class="btn btn-secondary" type="button" aria-label="Debug panel">Debug</button>
                    </div>
                </div>

                <?php if ($atts['show_business'] === 'true'): ?>
                <section class="manager-section business-section" role="region" aria-labelledby="business-section-title">
                    <div class="section-header">
                        <h3 id="business-section-title">Business Information</h3>
                        <button id="edit-business-btn" class="btn btn-primary" type="button" aria-controls="business-modal" aria-label="Edit business information">Edit Business Info</button>
                    </div>

                    <div id="business-info-display" class="info-display" role="status" aria-live="polite" aria-busy="true">
                        <div class="loading">Loading business information...</div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($atts['show_services'] === 'true'): ?>
                <section class="manager-section services-section" role="region" aria-labelledby="services-section-title">
                    <div class="section-header">
                        <h3 id="services-section-title">Services Management</h3>
                        <div class="section-actions" role="group" aria-label="Services actions">
                            <button id="refresh-services" class="btn btn-secondary" type="button" aria-label="Refresh services">Refresh</button>
                            <button id="add-service-btn" class="btn btn-primary" type="button" aria-controls="service-modal" aria-label="Add new service">Add New Service</button>
                        </div>
                    </div>

                    <div class="services-grid" id="services-grid" role="list" aria-live="polite" aria-busy="true">
                        <div class="loading">Loading services...</div>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Business Info Modal -->
        <div id="business-modal" class="modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="business-modal-title" aria-hidden="true" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="business-modal-title">Business Information</h3>
                    <button class="close" type="button" aria-label="Close">&times;</button>
                </div>
                <form id="business-form">
                    <input type="hidden" id="business_id" name="business_id" value="">

                    <div class="form-section">
                        <h4>Basic Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_name">Business Name *</label>
                                <input type="text" id="business_name" name="business_name" required>
                            </div>
                            <div class="form-group">
                                <label for="business_email">Email Address</label>
                                <input type="email" id="business_email" name="business_email">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_phone">Phone Number</label>
                                <input type="tel" id="business_phone" name="business_phone">
                            </div>
                            <div class="form-group">
                                <label for="business_website">Website</label>
                                <input type="url" id="business_website" name="business_website">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="business_description">Description</label>
                            <textarea id="business_description" name="business_description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="business_address">Address</label>
                            <textarea id="business_address" name="business_address" rows="2"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_city">City</label>
                                <input type="text" id="business_city" name="business_city">
                            </div>
                            <div class="form-group">
                                <label for="business_state">State/Province</label>
                                <input type="text" id="business_state" name="business_state">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_zip_code">Zip/Postal Code</label>
                                <input type="text" id="business_zip_code" name="business_zip_code">
                            </div>
                            <div class="form-group">
                                <label for="business_timezone">Timezone</label>
                                <input type="text" id="business_timezone" name="business_timezone" placeholder="e.g., America/New_York">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="business_hours">Business Hours</label>
                            <textarea id="business_hours" name="business_hours" rows="2" placeholder="e.g., Mon-Fri: 9:00 AM - 6:00 PM"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Social Media</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_facebook">Facebook</label>
                                <input type="url" id="social_facebook" name="social_facebook">
                            </div>
                            <div class="form-group">
                                <label for="social_twitter">Twitter</label>
                                <input type="url" id="social_twitter" name="social_twitter">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_instagram">Instagram</label>
                                <input type="url" id="social_instagram" name="social_instagram">
                            </div>
                            <div class="form-group">
                                <label for="social_linkedin">LinkedIn</label>
                                <input type="url" id="social_linkedin" name="social_linkedin">
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-business">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Business Info</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Service Modal -->
        <div id="service-modal" class="modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="service-modal-title" aria-hidden="true" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="service-modal-title">Add New Service</h3>
                    <button class="close" type="button" aria-label="Close">&times;</button>
                </div>
                <form id="service-form" style="padding: 24px;">
                    <input type="hidden" id="service_id" name="service_id" value="">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_name">Service Name *</label>
                            <input type="text" id="service_name" name="service_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_price">Price (₱)</label>
                            <input type="number" id="service_price" name="service_price" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="service_duration">Duration</label>
                            <input type="text" id="service_duration" name="service_duration" placeholder="e.g., 1 hour, 30 minutes">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="service_description">Description</label>
                        <textarea id="service_description" name="service_description" rows="3"></textarea>
                    </div>

                    <div class="form-section">
                        <h4>Service Thumbnail</h4>
                        <div class="thumbnail-upload-container" style="display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 24px; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px;">
                            
                            <!-- Upload Button & Preview Area -->
                            <div id="thumbnail-display-area" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <div class="thumbnail-preview-box" id="thumbnail-preview" style="width: 120px; height: 120px; border: 2px solid #e2e8f0; border-radius: 8px; display: none; overflow: hidden; background: white;">
                                    <!-- Image preview will appear here -->
                                </div>
                                
                                <button type="button" id="thumbnail-upload-btn" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px;">
                                    <span>📸</span>
                                    <span id="upload-btn-text">Choose Image</span>
                                </button>
                            </div>
                            
                            <!-- Hidden File Input -->
                            <input type="file" id="thumbnail-upload" accept="image/*" style="display: none;">
                            
                            <!-- Action Buttons -->
                            <div class="thumbnail-actions" style="display: flex; gap: 8px;">
                                <button type="button" class="btn btn-sm btn-secondary" id="remove-thumbnail" style="display: none;">Remove</button>
                            </div>
                            
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" value="0">
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="is_featured" name="is_featured" value="1">
                                Featured Service
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-service">Cancel</button>
                        <button type="button" class="btn btn-danger" id="delete-service-form" style="display: none;" data-service-id="">Delete Service</button>
                        <button type="submit" class="btn btn-primary">Save Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Image Cropper Modal -->
        <div id="cropper-modal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="cropper-modal-title" aria-hidden="true" tabindex="-1">
            <div class="modal-content" style="max-width: 900px;">
                <div class="modal-header">
                    <h3 id="cropper-modal-title">Crop Image</h3>
                    <button class="close" type="button" aria-label="Close">&times;</button>
                </div>
                <div style="padding: 16px;">
                    <div style="width:100%; max-height:60vh; overflow:auto;">
                        <img id="cropper-image" src="" alt="Image to crop" style="max-width:100%; display:block;">
                    </div>
                    <div class="modal-actions" style="margin-top: 16px;">
                        <button type="button" class="btn btn-secondary" id="cropper-cancel">Cancel</button>
                        <button type="button" class="btn btn-success" id="cropper-skip">Use Original</button>
                        <button type="button" class="btn btn-primary" id="cropper-apply">Crop &amp; Use</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Handle thumbnail upload for services
     */
    public function upload_service_thumbnail_ajax() {
        if (!check_ajax_referer('manager_panel_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!$this->has_manager_access()) {
            wp_send_json_error('Access denied');
            return;
        }

        if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
            return;
        }

        $thumbnail_url = $this->handle_thumbnail_upload($_FILES['thumbnail']);
        
        if (is_wp_error($thumbnail_url)) {
            wp_send_json_error($thumbnail_url->get_error_message());
            return;
        }

        wp_send_json_success(['thumbnail_url' => $thumbnail_url]);
    }

    /**
     * Delete service thumbnail
     */
    public function delete_service_thumbnail_ajax() {
        if (!check_ajax_referer('manager_panel_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!$this->has_manager_access()) {
            wp_send_json_error('Access denied');
            return;
        }

        $service_id = intval($_POST['service_id'] ?? 0);
        if (!$service_id) {
            wp_send_json_error('Invalid service ID');
            return;
        }

        global $wpdb;
    $table_name = $wpdb->prefix . 'manager_services';
        
        // Get current thumbnail URL
        $current_thumbnail = $wpdb->get_var($wpdb->prepare(
            "SELECT service_image FROM $table_name WHERE id = %d",
            $service_id
        ));

        // Delete file if exists
        if ($current_thumbnail && $current_thumbnail !== '') {
            $this->delete_thumbnail_file($current_thumbnail);
        }

        // Update database
        $result = $wpdb->update(
            $table_name,
            ['service_image' => ''],
            ['id' => $service_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update database');
            return;
        }

        wp_send_json_success('Thumbnail deleted successfully');
    }

    /**
     * Handle thumbnail file upload
     */
    private function handle_thumbnail_upload($file) {
        // Check file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Please upload a valid image file (JPEG, PNG, or GIF)');
        }

        // Check file size (2MB max)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'File size must be less than 2MB');
        }

        // Set upload directory
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/service-thumbnails/';
        $target_url_dir = $upload_dir['baseurl'] . '/service-thumbnails/';

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_' . uniqid() . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $target_url_dir . $filename;
        } else {
            return new WP_Error('upload_failed', 'Failed to upload file');
        }
    }

    /**
     * Delete thumbnail file from server
     */
    private function delete_thumbnail_file($thumbnail_url) {
        if (empty($thumbnail_url)) {
            return;
        }

        // Convert URL to file path
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $thumbnail_url);
        
        // Delete file if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    /**
     * Debug endpoint for troubleshooting
     */
    public function debug_manager_panel_ajax() {
        global $wpdb;
        
        $debug_info = [
            'ajax_working' => true,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_active' => true,
            'tables' => []
        ];
        
        // Check if tables exist
        $business_table = $wpdb->prefix . 'manager_business';
        $services_table = $wpdb->prefix . 'manager_services';
        
        $debug_info['tables']['business_exists'] = !empty($wpdb->get_var("SHOW TABLES LIKE '$business_table'"));
        $debug_info['tables']['services_exists'] = !empty($wpdb->get_var("SHOW TABLES LIKE '$services_table'"));
        
        if ($debug_info['tables']['services_exists']) {
            $debug_info['services_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
            $debug_info['active_services_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $services_table WHERE is_active = 1");
        }
        
        $debug_info['nonce_valid'] = wp_verify_nonce($_POST['nonce'] ?? '', 'manager_panel_nonce');
        $debug_info['user_logged_in'] = is_user_logged_in();
        
        wp_send_json_success($debug_info);
    }
}

// Initialize the plugin
new ManagerBusinessPanel();