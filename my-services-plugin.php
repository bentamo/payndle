<?php
/*
Plugin Name: My Services Plugin (MVP+AJAX)
Description: Simple user and manager service shortcodes with add/delete and AJAX UI.
Version: 1.3
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================
   REGISTER SERVICE POST TYPE
   ====================== */
function mvp_register_service_post_type() {
    register_post_type('service', array(
        'labels' => array(
            'name' => 'Services',
            'singular_name' => 'Service'
        ),
        'public' => true,
        'supports' => array('title', 'editor'),
    ));
}
add_action('init', 'mvp_register_service_post_type');


/* ======================
   ENQUEUE SCRIPTS
   ====================== */
function mvp_enqueue_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_style(
        'mvp-style',
        $plugin_url . 'assets/css/style.css',
        array(),
        '1.0'
    );

    wp_enqueue_script(
        'mvp-script',
        $plugin_url . 'assets/js/script.js',
        array('jquery'),
        '1.0',
        true
    );

    wp_localize_script('mvp-script', 'mvp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mvp_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mvp_enqueue_scripts');


/* ======================
   AJAX: ADD SERVICE
   ====================== */
function mvp_add_service() {
    check_ajax_referer('mvp_nonce', 'nonce');

    $title = sanitize_text_field($_POST['title']);
    $desc  = sanitize_textarea_field($_POST['description']);

    if ($title && $desc) {
        $post_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $desc,
            'post_status'  => 'publish',
            'post_type'    => 'service',
        ));

        if ($post_id) {
            $service = get_post($post_id);
            wp_send_json_success(array(
                'id'    => $service->ID,
                'title' => esc_html($service->post_title),
                'desc'  => esc_html($service->post_content),
            ));
        }
    }

    wp_send_json_error('Failed to add service.');
}
add_action('wp_ajax_mvp_add_service', 'mvp_add_service');


/* ======================
   AJAX: DELETE SERVICE
   ====================== */
function mvp_delete_service() {
    check_ajax_referer('mvp_nonce', 'nonce');

    $id = intval($_POST['id']);
    if (get_post_type($id) === 'service') {
        wp_delete_post($id, true);
        wp_send_json_success(array('id' => $id));
    }

    wp_send_json_error('Failed to delete service.');
}
add_action('wp_ajax_mvp_delete_service', 'mvp_delete_service');


/* ======================
   USER SHORTCODE
   ====================== */
function mvp_user_services_shortcode() {
    $services = get_posts(array(
        'post_type' => 'service',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    if (!$services) {
        return "<p>No services available yet.</p>";
    }

    ob_start(); ?>
    <div class="mvp-services">
        <?php foreach ($services as $service): ?>
            <div class="mvp-card">
                <h4><?php echo esc_html($service->post_title); ?></h4>
                <p><?php echo esc_html($service->post_content); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('user_services', 'mvp_user_services_shortcode');


/* ======================
   MANAGER SHORTCODE
   ====================== */
function mvp_manager_shortcode() {
    if (!is_user_logged_in()) return "<p>Please log in to manage services.</p>";

    $services = get_posts(array(
        'post_type' => 'service',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    ob_start(); ?>
    <div class="mvp-manager">
        <!-- Add Form -->
        <form id="mvp-add-form">
            <input type="text" name="title" placeholder="Service Title" required>
            <textarea name="description" placeholder="Service Description" required></textarea>
            <button type="submit">Add Service</button>
        </form>

        <!-- Services List -->
        <div id="mvp-service-list">
            <?php if ($services): ?>
                <?php foreach ($services as $service): ?>
                    <div class="mvp-card" data-id="<?php echo $service->ID; ?>">
                        <h4><?php echo esc_html($service->post_title); ?></h4>
                        <p><?php echo esc_html($service->post_content); ?></p>
                        <button class="mvp-delete">Delete</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No services yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('manager_add_service', 'mvp_manager_shortcode');
