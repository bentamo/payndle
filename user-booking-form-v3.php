<?php
/**
 * User Booking Form v3
 * Shortcode: [user_booking_form_v3]
 * Lightweight 4-step wizard with modern styling and AJAX save to service_booking post type.
 */

if (!defined('ABSPATH')) {
    exit;
}

class UserBookingFormV3 {

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('user_booking_form_v3', [$this, 'render_booking_form_v3']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('wp_ajax_submit_user_booking_v3', [$this, 'submit_user_booking_v3']);
        add_action('wp_ajax_nopriv_submit_user_booking_v3', [$this, 'submit_user_booking_v3']);
    }

    public function init() {
        // Ensure post type exists (created elsewhere in the plugin)
        if (!post_type_exists('service_booking')) {
            register_post_type('service_booking', [
                'labels' => [
                    'name' => __('Service Bookings', 'payndle'),
                    'singular_name' => __('Service Booking', 'payndle')
                ],
                'public' => false,
                'show_ui' => true,
                'supports' => ['title','editor']
            ]);
        }
    }

    public function enqueue_assets() {
        // CSS
        wp_enqueue_style('user-booking-form-v3-css', plugin_dir_url(__FILE__) . 'assets/css/user-booking-form-v3.css', [], '1.0.0');
        // Inter font
        wp_enqueue_style('payndle-google-inter-v3', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);

        // JS
        wp_enqueue_script('user-booking-form-v3-js', plugin_dir_url(__FILE__) . 'assets/js/user-booking-form-v3.js', ['jquery'], '1.0.0', true);

        wp_localize_script('user-booking-form-v3-js', 'userBookingV3', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user_booking_v3_nonce'),
            'messages' => [
                'saved' => __('Booking saved. We will contact you shortly.', 'payndle'),
                'error' => __('There was a problem. Please try again.', 'payndle')
            ]
        ]);
    }

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

                <form id="user-booking-form-v3" class="ubf-v3-form" novalidate>
                    <?php wp_nonce_field('user_booking_v3_nonce', 'booking_v3_nonce'); ?>

                    <div class="ubf-form-step" data-step="1">
                        <h3 class="section-title">Select Service</h3>
                        <?php if ($atts['show_service_selector'] === 'true'): ?>
                        <select id="ubf_service_id" name="service_id" required>
                            <option value="">Choose a service</option>
                            <?php echo $this->get_services_options(); ?>
                        </select>

                        <label for="ubf_staff_id" style="display:block;margin-top:8px;font-weight:600">Preferred Staff (optional)</label>
                        <select id="ubf_staff_id" name="staff_id">
                            <option value="">Any available staff</option>
                            <?php echo $this->get_staff_options(); ?>
                        </select>

                        <?php else: ?>
                            <input type="hidden" name="service_id" value="">
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
                        <input type="date" id="ubf_preferred_date" name="preferred_date" min="<?php echo esc_attr($min_date); ?>" data-business-start="08:00" data-business-end="18:00">
                        <input type="time" id="ubf_preferred_time" name="preferred_time" min="08:00" max="18:00">
                        <div class="ubf-field-error ubf-schedule-error" style="display:none;color:#c43d3d;margin-bottom:8px"></div>
                        <textarea id="ubf_message" name="message" placeholder="Additional message"></textarea>
                        <div class="ubf-step-nav">
                            <button type="button" class="ubf-prev">Previous</button>
                            <button type="button" class="ubf-next">Next</button>
                        </div>
                    </div>

                    <div class="ubf-form-step" data-step="4" style="display:none;">
                        <h3 class="section-title">Choose Payment Method</h3>
                        <div class="payment-methods">
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

        <?php
        return ob_get_clean();
    }

    private function get_services_options() {
        // lightweight list from 'service' CPT if available
        $options = '';
        $posts = get_posts(['post_type' => 'service', 'posts_per_page' => -1, 'post_status' => 'publish']);
        foreach ($posts as $p) {
            $options .= sprintf('<option value="%d">%s</option>', esc_attr($p->ID), esc_html($p->post_title));
        }
        return $options;
    }

    private function get_staff_options() {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_members';
        $options = '';

        // If table exists, query it; otherwise attempt to get 'staff' CPT
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists) {
            $rows = $wpdb->get_results("SELECT id, staff_name FROM $table WHERE staff_status = 'active' OR 1=1 ORDER BY staff_name ASC");
            foreach ($rows as $r) {
                $options .= sprintf('<option value="%d">%s</option>', esc_attr($r->id), esc_html($r->staff_name));
            }
        } else {
            $posts = get_posts(['post_type' => 'staff', 'posts_per_page' => -1, 'post_status' => 'publish']);
            foreach ($posts as $p) {
                $options .= sprintf('<option value="%d">%s</option>', esc_attr($p->ID), esc_html($p->post_title));
            }
        }

        return $options;
    }

    public function submit_user_booking_v3() {
        check_ajax_referer('user_booking_v3_nonce', 'nonce');

        $service_id = intval($_POST['service_id'] ?? 0);
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
        $preferred_time = sanitize_text_field($_POST['preferred_time'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'cash');
    $staff_id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;

        if (empty($customer_name) || empty($customer_email) || $service_id <= 0) {
            wp_send_json(['success' => false, 'message' => 'Missing required fields']);
        }

        $post_id = wp_insert_post([
            'post_type' => 'service_booking',
            'post_title' => $customer_name . ' - Booking',
            'post_status' => 'pending',
            'post_content' => $message
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json(['success' => false, 'message' => 'Failed to create booking']);
        }

        // Save meta
        update_post_meta($post_id, '_service_id', $service_id);
        update_post_meta($post_id, '_customer_name', $customer_name);
        update_post_meta($post_id, '_customer_email', $customer_email);
        update_post_meta($post_id, '_customer_phone', $customer_phone);
        update_post_meta($post_id, '_preferred_date', $preferred_date);
        update_post_meta($post_id, '_preferred_time', $preferred_time);
    update_post_meta($post_id, '_payment_method', $payment_method);
    if ($staff_id) update_post_meta($post_id, '_staff_id', $staff_id);

        wp_send_json(['success' => true, 'message' => 'Booking created', 'id' => $post_id]);
    }
}

new UserBookingFormV3();
