<?php
/**
 * Public Services Booking View
 * Description: A public-facing service display and booking system accessible via shortcode
 * Version: 1.0.0
 * Shortcode: [services_booking]
 */

if (!defined('ABSPATH')) {
    exit;
}

class PublicServicesBooking {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('services_booking', [$this, 'render_services_booking']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        add_action('wp_ajax_get_public_services', [$this, 'get_public_services_ajax']);
        add_action('wp_ajax_nopriv_get_public_services', [$this, 'get_public_services_ajax']);
        add_action('wp_ajax_get_public_service_details', [$this, 'get_public_service_details_ajax']);
        add_action('wp_ajax_nopriv_get_public_service_details', [$this, 'get_public_service_details_ajax']);
        add_action('wp_ajax_get_business_contact_info', [$this, 'get_business_contact_info_ajax']);
        add_action('wp_ajax_nopriv_get_business_contact_info', [$this, 'get_business_contact_info_ajax']);
    }

    public function init() {
        // Keep legacy table creation as safe-guard; no-op if tables exist
        $this->create_booking_tables();
        $this->ensure_manager_tables_exist();
    }

    private function create_booking_tables() {
        global $wpdb;
        $booking_table = $wpdb->prefix . 'service_bookings';
        $charset_collate = $wpdb->get_charset_collate();

        $booking_sql = "CREATE TABLE IF NOT EXISTS $booking_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(20) DEFAULT '',
            preferred_date date DEFAULT NULL,
            preferred_time time DEFAULT NULL,
            message text DEFAULT '',
            booking_status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($booking_sql);
    }

    private function ensure_manager_tables_exist() {
        global $wpdb;
        $business_table = $wpdb->prefix . 'manager_business';
        $services_table = $wpdb->prefix . 'manager_services';
        $charset_collate = $wpdb->get_charset_collate();

        $business_sql = "CREATE TABLE IF NOT EXISTS $business_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            business_name varchar(255) NOT NULL,
            business_description text DEFAULT '',
            business_email varchar(255) DEFAULT '',
            business_phone varchar(20) DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $services_sql = "CREATE TABLE IF NOT EXISTS $services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_name varchar(255) NOT NULL,
            service_description text DEFAULT '',
            service_price decimal(10,2) DEFAULT 0.00,
            service_duration varchar(50) DEFAULT '',
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($business_sql);
        dbDelta($services_sql);
    }

    public function enqueue_frontend_assets() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'services_booking')) {
            wp_enqueue_style('services-booking-style', plugins_url('assets/css/services-booking.css', __FILE__), [], '1.0.4');
            wp_enqueue_script('services-booking-script', plugins_url('assets/js/services-booking.js', __FILE__), ['jquery'], '1.0.4', true);
            wp_localize_script('services-booking-script', 'servicesBooking', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('services_booking_nonce')
            ]);
        }
    }

    // Prefer service CPT, fallback to legacy manager_services table
    public function get_public_services_ajax() {
        check_ajax_referer('services_booking_nonce', 'nonce');

        $args = [
            'post_type' => 'service',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        $services = get_posts($args);
        if (!empty($services)) {
            $out = [];
            foreach ($services as $s) {
                $sid = $s->ID;
                $out[] = [
                    'id' => $sid,
                    'name' => $s->post_title,
                    'description' => $s->post_content,
                    'price' => get_post_meta($sid, '_service_price', true),
                    'duration' => get_post_meta($sid, '_service_duration', true),
                    'thumbnail' => get_the_post_thumbnail_url($sid, 'medium'),
                    'is_featured' => (bool) get_post_meta($sid, '_is_featured', true)
                ];
            }
            wp_send_json_success($out);
            return;
        }

        global $wpdb;
        $services_table = $wpdb->prefix . 'manager_services';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $services_table));
        if (!$table_exists) {
            wp_send_json_error(['message' => 'No services available.']);
            return;
        }

        $services = $wpdb->get_results("SELECT id, service_name as name, service_description as description, service_price as price, service_duration as duration, service_image as thumbnail, is_featured FROM $services_table WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, service_name ASC");
        wp_send_json_success($services);
    }

    public function get_public_service_details_ajax() {
        check_ajax_referer('services_booking_nonce', 'nonce');

        $service_id = intval($_POST['service_id'] ?? 0);
        if ($service_id <= 0) {
            wp_send_json_error(['message' => 'Invalid service id']);
            return;
        }

        $service = get_post($service_id);
        if ($service && $service->post_type === 'service' && $service->post_status === 'publish') {
            $out = [
                'id' => $service->ID,
                'name' => $service->post_title,
                'description' => $service->post_content,
                'price' => get_post_meta($service->ID, '_service_price', true),
                'duration' => get_post_meta($service->ID, '_service_duration', true),
                'thumbnail' => get_the_post_thumbnail_url($service->ID, 'medium')
            ];
            wp_send_json_success($out);
            return;
        }

        global $wpdb;
        $services_table = $wpdb->prefix . 'manager_services';
        $service = $wpdb->get_row($wpdb->prepare("SELECT id, service_name as name, service_description as description, service_price as price, service_duration as duration, service_image as thumbnail FROM $services_table WHERE id = %d AND is_active = 1", $service_id));

        if ($service) {
            wp_send_json_success($service);
        } else {
            wp_send_json_error(['message' => 'Service not found']);
        }
    }

    public function get_business_contact_info_ajax() {
        check_ajax_referer('services_booking_nonce', 'nonce');
        global $wpdb;

        $business_table = $wpdb->prefix . 'manager_business';
        $business = $wpdb->get_row("SELECT business_name, business_email, business_phone, business_hours FROM $business_table WHERE is_active = 1 ORDER BY id DESC LIMIT 1");

        if ($business) {
            wp_send_json_success($business);
        } else {
            wp_send_json_success(null);
        }
    }

    public function render_services_booking($atts) {
        $atts = shortcode_atts([
            'title' => 'Our Services',
            'show_featured_first' => 'true',
            'columns' => '3'
        ], $atts);

        ob_start();
        ?>
        <div class="services-booking-page" role="region" aria-label="Services Booking">
            <div id="services-booking-container" class="services-booking-wrapper" role="main">
                <div class="services-booking-header">
                    <h2 id="services-booking-title"><?php echo esc_html($atts['title']); ?></h2>
                    <div class="services-booking-actions" role="group" aria-label="Services actions">
                        <button id="refresh-services-public" class="btn btn-secondary" type="button" aria-label="Refresh services">Refresh</button>
                    </div>
                </div>

                <section class="services-section" role="region" aria-labelledby="services-section-title">
                    <div class="services-grid services-grid-<?php echo esc_attr($atts['columns']); ?>" id="public-services-grid" role="list" aria-live="polite" aria-busy="true">
                        <div class="loading">Loading services...</div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Service Details Modal -->
        <div id="service-details-modal" class="modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="service-details-title" aria-hidden="true" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="service-details-title">Service Details</h3>
                    <button class="close" type="button" aria-label="Close">&times;</button>
                </div>
                <div class="service-details-body">
                    <div class="service-details-image">
                        <img id="service-details-thumbnail" src="" alt="" style="display: none;">
                        <div class="service-placeholder" id="service-placeholder" style="display: none;">
                            <span>📋</span>
                        </div>
                    </div>
                    <div class="service-details-info">
                        <h4 id="service-details-name"></h4>
                        <div class="service-details-price" id="service-details-price"></div>
                        <div class="service-details-duration" id="service-details-duration"></div>
                        <div class="service-details-description" id="service-details-description"></div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="close-details">Close</button>
                    <button type="button" class="btn btn-primary" id="book-service-btn">Book This Service</button>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}

new PublicServicesBooking();
