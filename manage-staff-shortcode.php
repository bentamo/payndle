<?php
/**
 * Staff Management Shortcode (frontend)
 * Reuses the modern admin UI and provides a public AJAX endpoint scoped to the current business.
 */

if (!defined('ABSPATH')) { exit; }

// Helper: get current business id (from session or user meta)
if (!function_exists('payndle_get_current_business_id')) {
    function payndle_get_current_business_id() {
        if (isset($_SESSION['current_business_id'])) {
            return absint($_SESSION['current_business_id']);
        }
        if (!is_user_logged_in()) { return 0; }
        $user_id = get_current_user_id();
        return absint(get_user_meta($user_id, 'business_id', true));
    }
}

// Shared staff modal/form used by both admin and frontend
if (!function_exists('payndle_render_staff_form')) {
    function payndle_render_staff_form() {
        $business_id = function_exists('payndle_get_current_business_id') ? absint(payndle_get_current_business_id()) : 0;
        $services = get_posts(array(
            'post_type' => 'service',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(
                array('key' => '_business_id', 'value' => $business_id, 'compare' => '=')
            )
        ));
        ob_start();
        ?>
        <div id="staff-modal" class="elite-modal" style="display:none;">
            <div class="elite-modal-content">
                <div class="elite-modal-header">
                    <h3 id="staff-modal-title"><?php _e('Add New Staff', 'payndle'); ?></h3>
                    <span class="elite-close staff-close">&times;</span>
                </div>
                <div class="elite-modal-body ubf-v3-container">
                    <form id="staff-form" class="ubf-v3-form" novalidate>
                        <input type="hidden" id="staff-id" value="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-name"><?php _e('Full Name', 'payndle'); ?></label>
                                <input type="text" id="staff-name" class="elite-input" required>
                            </div>
                            <div class="form-group">
                                <label for="staff-service-dropdown"><?php _e('Services', 'payndle'); ?></label>
                                <div class="service-selection-wrapper" style="display:flex; gap:0.5rem; align-items:center;">
                                    <select id="staff-service-dropdown" class="elite-select" style="flex:1;">
                                        <option value=""><?php _e('Select a service...', 'payndle'); ?></option>
                                        <?php foreach ($services as $service):
                                            $price = get_post_meta($service->ID, '_service_price', true);
                                            $price_display = ($price !== '' && $price !== null) ? ' - ₱' . number_format((float)$price, 2) : '';
                                        ?>
                                            <option value="<?php echo esc_attr($service->ID); ?>"><?php echo esc_html($service->post_title . $price_display); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" id="staff-service-add" class="button button-secondary"><?php _e('Add', 'payndle'); ?></button>
                                </div>
                                <div id="staff-service-tags" class="selected-services" style="margin-top:0.5rem;"></div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-email"><?php _e('Email', 'payndle'); ?></label>
                                <input type="email" id="staff-email" class="elite-input" required>
                            </div>
                            <div class="form-group">
                                <label for="staff-phone"><?php _e('Phone', 'payndle'); ?></label>
                                <input type="text" id="staff-phone" class="elite-input">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-status"><?php _e('Status', 'payndle'); ?></label>
                                <select id="staff-status" class="elite-select">
                                    <option value="active"><?php _e('Active', 'payndle'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'payndle'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="staff-avatar"><?php _e('Profile Photo', 'payndle'); ?></label>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <div id="staff-avatar-preview-wrap" style="display:flex; align-items:center; gap:0.6rem;">
                                        <img id="staff-avatar-preview" src="" alt="" class="avatar-preview" style="display:none; width:54px; height:54px; border-radius:50%; object-fit:cover;" />
                                        <div id="staff-avatar-placeholder" class="avatar-initial" style="width:54px; height:54px; display:flex; align-items:center; justify-content:center;">?</div>
                                    </div>
                                    <input type="hidden" id="staff-avatar-id" value="">
                                    <input type="hidden" id="staff-avatar" value="">
                                    <input type="file" id="staff-avatar-file" accept="image/*" style="display:none;" />
                                    <div style="display:flex; flex-direction:column; gap:0.4rem;">
                                        <button type="button" id="staff-avatar-upload" class="button button-secondary"><?php _e('Upload / Select', 'payndle'); ?></button>
                                        <div class="help-text"><?php _e('Choose an image file to upload as profile photo.', 'payndle'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="elite-button secondary staff-cancel"><?php _e('Cancel', 'payndle'); ?></button>
                            <button type="submit" class="elite-button primary"><?php _e('Save Staff', 'payndle'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }
}

// Frontend AJAX endpoint (public)
add_action('wp_ajax_manage_staff_public', 'payndle_handle_staff_ajax_front');
add_action('wp_ajax_nopriv_manage_staff_public', 'payndle_handle_staff_ajax_front');
function payndle_handle_staff_ajax_front() {
    check_ajax_referer('staff_management_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

    $business_id = function_exists('payndle_get_current_business_id') ? absint(payndle_get_current_business_id()) : 0;
    if ($business_id <= 0) wp_send_json_error('Invalid business context');

    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $data = isset($_POST['data']) ? json_decode(wp_unslash($_POST['data']), true) : array();

    // Preload business service ids for scoping
    $business_service_ids = get_posts(array(
        'post_type' => 'service',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => array(array('key' => '_business_id', 'value' => $business_id, 'compare' => '='))
    ));
    $business_service_ids = array_map('absint', (array)$business_service_ids);

    // Helper to filter provided service ids to only those belonging to this business
    $filter_services_to_business = function($ids) use ($business_service_ids) {
        $ids = array_map('absint', (array)$ids);
        return array_values(array_intersect($ids, $business_service_ids));
    };

    switch ($action_type) {
        case 'get_staff':
            $args = array(
                'post_type' => 'staff',
                'post_status' => 'publish',
                'posts_per_page' => isset($data['per_page']) ? absint($data['per_page']) : 10,
                'paged' => isset($data['paged']) ? absint($data['paged']) : 1,
            );
            if (!empty($data['search'])) { $args['s'] = sanitize_text_field($data['search']); }

            // Optional status filter
            $meta_query = array('relation' => 'AND');
            if (!empty($data['status'])) {
                $meta_query[] = array('key' => 'staff_status', 'value' => sanitize_text_field($data['status']), 'compare' => '=');
            }
            // Optional service filter (only if service belongs to business)
            if (!empty($data['service_id'])) {
                $sid = absint($data['service_id']);
                if (in_array($sid, $business_service_ids, true)) {
                    $meta_query[] = array('relation' => 'OR',
                        array('key' => 'staff_services', 'value' => '"' . $sid . '"', 'compare' => 'LIKE'),
                        array('key' => 'staff_services', 'value' => 'i:' . $sid . ';', 'compare' => 'LIKE'),
                    );
                } else {
                    wp_send_json_success(array('staff' => array(), 'pagination' => array('total' => 0, 'current_page' => 1, 'total_pages' => 1)));
                }
            }
            if (count($meta_query) > 1) { $args['meta_query'] = $meta_query; }

            $q = new WP_Query($args);
            $staff = array();
            while ($q->have_posts()) { $q->the_post();
                $pid = get_the_ID();
                $service_ids = array_map('absint', (array) get_post_meta($pid, 'staff_services', true));
                // Keep only services that belong to this business
                $service_ids = array_values(array_intersect($service_ids, $business_service_ids));
                if (empty($service_ids)) { continue; }
                $services = array();
                foreach ($service_ids as $sid) { $p = get_post($sid); if ($p) $services[] = array('id' => (int)$sid, 'title' => $p->post_title); }
                $avatar = get_post_meta($pid, 'staff_avatar', true);
                $avatar_id = get_post_meta($pid, 'staff_avatar_id', true);
                if (empty($avatar) && $avatar_id) { $avatar = wp_get_attachment_image_url($avatar_id, 'thumbnail') ?: wp_get_attachment_url($avatar_id); }
                $staff[] = array(
                    'id' => $pid,
                    'name' => get_the_title(),
                    'email' => get_post_meta($pid, 'email', true) ?: get_post_meta($pid, 'staff_email', true),
                    'phone' => get_post_meta($pid, 'phone', true) ?: get_post_meta($pid, 'staff_phone', true),
                    'status' => get_post_meta($pid, 'staff_status', true) ?: 'active',
                    'role' => get_post_meta($pid, 'staff_role', true),
                    'avatar' => $avatar,
                    'avatar_id' => $avatar_id,
                    'services' => $services,
                );
            }
            wp_reset_postdata();
            wp_send_json_success(array('staff' => $staff, 'pagination' => array('total' => (int)$q->found_posts, 'current_page' => (int)($args['paged']), 'total_pages' => (int)$q->max_num_pages)));
            break;

        case 'add_staff':
        case 'update_staff':
            $is_update = ($action_type === 'update_staff');
            $post_id = $is_update ? absint($data['id'] ?? 0) : 0;
            $name = trim(sanitize_text_field($data['name'] ?? ''));
            $services = $filter_services_to_business($data['services'] ?? array());
            if ($is_update && $post_id <= 0) wp_send_json_error('Invalid staff ID');
            if ($name === '') wp_send_json_error('Name required');
            if (empty($services)) wp_send_json_error('Assign at least one service');

            $payload = array('post_title' => $name, 'post_type' => 'staff', 'post_status' => 'publish');
            if ($is_update) { $payload['ID'] = $post_id; $post_id = wp_update_post($payload); }
            else { $post_id = wp_insert_post($payload); }
            if (is_wp_error($post_id) || !$post_id) wp_send_json_error('Save failed');

            update_post_meta($post_id, 'email', sanitize_email($data['email'] ?? ''));
            update_post_meta($post_id, 'phone', sanitize_text_field($data['phone'] ?? ''));
            update_post_meta($post_id, 'staff_status', sanitize_text_field($data['status'] ?? 'active'));
            update_post_meta($post_id, 'staff_services', $services);
            if (!empty($data['avatar'])) update_post_meta($post_id, 'staff_avatar', esc_url_raw($data['avatar']));
            if (!empty($data['avatar_id'])) { update_post_meta($post_id, 'staff_avatar_id', absint($data['avatar_id'])); if (function_exists('set_post_thumbnail')) @set_post_thumbnail($post_id, absint($data['avatar_id'])); }

            // Sync assigned_staff only for services of this business
            foreach ($business_service_ids as $sid) {
                $assigned = array_map('absint', (array)get_post_meta($sid, 'assigned_staff', true));
                $has = in_array($post_id, $assigned, true);
                $should = in_array($sid, $services, true);
                if ($should && !$has) { $assigned[] = $post_id; update_post_meta($sid, 'assigned_staff', array_values(array_unique($assigned))); }
                if (!$should && $has) { $assigned = array_values(array_filter($assigned, function($v) use ($post_id){ return (int)$v !== (int)$post_id; })); update_post_meta($sid, 'assigned_staff', $assigned); }
            }

            wp_send_json_success(array('message' => $is_update ? 'Staff updated' : 'Staff added', 'id' => $post_id));
            break;

        case 'delete_staff':
            $post_id = absint($data['id'] ?? 0);
            if ($post_id <= 0) wp_send_json_error('Invalid staff ID');
            foreach ($business_service_ids as $sid) {
                $assigned = array_map('absint', (array)get_post_meta($sid, 'assigned_staff', true));
                $assigned = array_values(array_filter($assigned, function($v) use ($post_id){ return (int)$v !== (int)$post_id; }));
                update_post_meta($sid, 'assigned_staff', $assigned);
            }
            if (wp_delete_post($post_id, true)) wp_send_json_success(array('message' => 'Staff deleted'));
            wp_send_json_error('Delete failed');
            break;

        default:
            wp_send_json_error('Invalid action');
    }
}

// Frontend shortcode: reuse the modern admin UI markup
function manage_staff_shortcode($atts) {
    wp_enqueue_style('staff-management-css', plugin_dir_url(__FILE__) . 'assets/css/staff-management.css', array(), '1.0.3');
    wp_enqueue_script('staff-management-js', plugin_dir_url(__FILE__) . 'assets/js/staff-management.js', array('jquery'), '1.0.3', true);
    if (function_exists('wp_enqueue_media')) wp_enqueue_media();

    $business_id = function_exists('payndle_get_current_business_id') ? absint(payndle_get_current_business_id()) : 0;
    wp_localize_script('staff-management-js', 'staffManager', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_action' => 'manage_staff_public',
        'nonce' => wp_create_nonce('staff_management_nonce'),
        'rest_url' => rest_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'business_id' => $business_id,
        'context' => 'frontend',
        'confirm_delete' => __('Are you sure you want to delete this staff member?', 'payndle'),
    ));

    if (!is_user_logged_in()) {
        return '<div class="error-message">' . esc_html__('You must be logged in to access this page.', 'payndle') . '</div>';
    }

    ob_start();
    if (!function_exists('elite_cuts_render_staff_ui')) {
        include_once plugin_dir_path(__FILE__) . 'manage-staff.php';
    }
    if (function_exists('elite_cuts_render_staff_ui')) {
        elite_cuts_render_staff_ui();
    } else {
        echo '<div class="error-message">' . esc_html__('Staff UI renderer missing.', 'payndle') . '</div>';
    }
    return ob_get_clean();
}
add_shortcode('manage_staff', 'manage_staff_shortcode');
