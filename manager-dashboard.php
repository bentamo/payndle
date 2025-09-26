<?php
/*
Plugin Name: Manager Dashboard
Plugin URI:  https://payndle.com
Description: Clean and simple business management dashboard. Use shortcode [manager_dashboard].
Version:     1.3
Author:      Payndle
License:     GPL2
Shortcode: [manager_dashboard]
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle form submission
function save_business_info() {
    check_ajax_referer('business_info_nonce', 'business_info_nonce');
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : 0;

    // Validate business post exists and is the expected post type
    if ( ! $business_id ) {
        wp_send_json_error('Missing business id');
    }

    $business_post = get_post( $business_id );
    if ( ! $business_post || $business_post->post_type !== 'payndle_business' ) {
        wp_send_json_error('Invalid business');
    }

    // Capability check: user must be able to edit this specific post or be an admin
    $current_user_id = get_current_user_id();
    $owner_id = intval( get_post_meta( $business_id, '_business_owner_id', true ) );
    if ( $owner_id !== $current_user_id && ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_post', $business_id ) ) {
        wp_send_json_error('Unauthorized');
    }
    $fields = [
        'business_name', 'business_description', 'business_email', 'business_phone',
        'business_address', 'business_city', 'business_state', 'business_zip',
        'business_country', 'business_website', 'business_hours'
    ];

    $response = [];


    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            // Use textarea sanitizer for larger text fields
            if ( in_array( $field, array( 'business_description', 'business_hours' ), true ) ) {
                $value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
            } else {
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            }

            if ( $field === 'business_name' ) {
                // Only update the post_title of the validated business post
                wp_update_post( array(
                    'ID'         => $business_id,
                    'post_title' => $value,
                ) );
            } else {
                update_post_meta( $business_id, '_' . $field, $value );
            }

            $response[ $field ] = $value;
        }
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_save_business_info', 'save_business_info');

function manager_dashboard_shortcode() {
    if ( ! is_user_logged_in() ) {
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

        $business_name = $business->post_title;
        $business_code = get_post_meta( $business->ID, '_business_code', true );
        $business_phone = get_post_meta( $business->ID, '_business_phone', true );
        $business_email = get_post_meta( $business->ID, '_business_email', true );
        $business_address = get_post_meta( $business->ID, '_business_address', true );
        $business_city = get_post_meta( $business->ID, '_business_city', true );
        $business_state = get_post_meta( $business->ID, '_business_state', true );
        $business_zip = get_post_meta( $business->ID, '_business_zip', true );
        $business_country = get_post_meta( $business->ID, '_business_country', true );
        $business_website = get_post_meta( $business->ID, '_business_website', true );
        $business_description = get_post_meta( $business->ID, '_business_description', true );
    } else {
        $business_name = 'Your Business';
        $business_code = '';
        $business_phone = $business_email = $business_address = '';
        $business_city = $business_state = $business_zip = $business_country = '';
        $business_website = $business_description = '';
    }

    // Simple placeholders for counts (kept but not rendered)
    $products_count = intval(get_post_meta($business_id, '_products_count', true) ?: 0);
    $users_count    = intval(get_post_meta($business_id, '_users_count', true) ?: 0);
    $orders_count   = intval(get_post_meta($business_id, '_orders_count', true) ?: 0);

    // Ensure frontend script is enqueued and localized for AJAX
    if ( function_exists( 'wp_enqueue_script' ) ) {
        wp_enqueue_script(
            'manager-dashboard-js',
            plugin_dir_url(__FILE__) . 'assets/js/manager-dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('manager-dashboard-js', 'managerDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            // Nonce name: this will be sent as business_info_nonce
            'nonce'    => wp_create_nonce('business_info_nonce'),
        ));
    }

    ob_start();
    ?>
    <div class="dashboard-wrap">
        <div class="dashboard-inner">

            <header class="dashboard-header" role="banner">
                <h1 class="welcome">Welcome</h1>
                <p class="business-name"><?php echo esc_html($business_name); ?></p>
            </header>

            <div class="grid">
                <section class="left-col">
                    <div class="card card-business">
                        <div class="card-head">
                            <div class="head-left">
                                <h2 class="title">Business Information</h2>
                                <p class="subtitle">Manage profile, contact & location</p>
                            </div>
                            <div class="head-actions">
                                <button id="edit-business-info" class="btn btn-outline" aria-label="Edit business">
                                    Edit
                                </button>
                            </div>
                        </div>

                        <div id="business-info-display" class="card-body">
                            <div class="row">
                                <div class="label">Business Name</div>
                                <div class="value"><?php echo esc_html($business_name); ?></div>
                            </div>

                            <?php if ( ! empty($business_code) ) : ?>
                            <div class="row">
                                <div class="label">Business ID</div>
                                <div class="value"><?php echo esc_html($business_code); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( ! empty($business_description) ) : ?>
                            <div class="row">
                                <div class="label">Description</div>
                                <div class="value"><?php echo esc_html($business_description); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="label">Email</div>
                                <div class="value"><?php echo esc_html($business_email ?: '—'); ?></div>
                            </div>

                            <div class="row">
                                <div class="label">Phone</div>
                                <div class="value"><?php echo esc_html($business_phone ?: '—'); ?></div>
                            </div>

                            <div class="row">
                                <div class="label">Website</div>
                                <div class="value">
                                    <?php if ( ! empty($business_website) ) : ?>
                                        <a href="<?php echo esc_url($business_website); ?>" target="_blank"><?php echo esc_html(parse_url($business_website, PHP_URL_HOST) ?: $business_website); ?></a>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ( $business_address || $business_city || $business_state || $business_zip || $business_country ) : ?>
                            <div class="row">
                                <div class="label">Address</div>
                                <div class="value">
                                    <?php if ( $business_address ) : ?><div><?php echo esc_html($business_address); ?></div><?php endif; ?>
                                    <div><?php echo esc_html(implode(', ', array_filter([$business_city, $business_state, $business_zip])) ?: '—'); ?></div>
                                    <?php if ( $business_country ) : ?><div><?php echo esc_html($business_country); ?></div><?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <form id="business-info-form" class="card-body form" style="display:none;">
                            <?php wp_nonce_field('business_info_nonce','business_info_nonce'); ?>
                            <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">

                            <div class="form-row">
                                <label for="business_name">Business Name</label>
                                <input id="business_name" name="business_name" value="<?php echo esc_attr($business_name); ?>" required>
                            </div>

                            <div class="form-row">
                                <label for="business_description">Description</label>
                                <textarea id="business_description" name="business_description"><?php echo esc_textarea($business_description); ?></textarea>
                            </div>

                            <div class="form-grid-3">
                                <div class="form-row">
                                    <label for="business_email">Email</label>
                                    <input id="business_email" name="business_email" value="<?php echo esc_attr($business_email); ?>">
                                </div>
                                <div class="form-row">
                                    <label for="business_phone">Phone</label>
                                    <input id="business_phone" name="business_phone" value="<?php echo esc_attr($business_phone); ?>">
                                </div>
                                <div class="form-row">
                                    <label for="business_website">Website</label>
                                    <input id="business_website" name="business_website" value="<?php echo esc_attr($business_website); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <label for="business_address">Address</label>
                                <textarea id="business_address" name="business_address"><?php echo esc_textarea($business_address); ?></textarea>
                            </div>

                            <div class="form-grid-3">
                                <div class="form-row">
                                    <label for="business_city">City</label>
                                    <input id="business_city" name="business_city" value="<?php echo esc_attr($business_city); ?>">
                                </div>
                                <div class="form-row">
                                    <label for="business_state">State</label>
                                    <input id="business_state" name="business_state" value="<?php echo esc_attr($business_state); ?>">
                                </div>
                                <div class="form-row">
                                    <label for="business_zip">ZIP</label>
                                    <input id="business_zip" name="business_zip" value="<?php echo esc_attr($business_zip); ?>">
                                </div>
                            </div>

                            <div class="form-row actions">
                                <button type="button" id="cancel-edit" class="btn btn-outline">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

        </div>
    </div>

    <style>
    :root{
        --primary-green:#64C493;
        --primary-navy:#0C1930;
        --bg:#64C493; /* solid primary green background */
        --card:#ffffff;
        --muted:#6B7280;
        --accent:#64C493; /* keep accent consistent with solid green */
        --radius:8px;
        --ff: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    }

    /* solid green page background */
    .dashboard-wrap{
        padding:18px;
        background:var(--bg);
        font-family:var(--ff);
        color:var(--primary-navy);
    }
    .dashboard-inner{
        max-width:1240px;
        margin:0 auto;
        padding:28px; /* add breathing room so white cards contrast on green */
    }

    .dashboard-header{
        background:transparent;
        padding:8px 0 18px;
        margin-bottom:8px;
    }
    .dashboard-header .welcome{ margin:0; font-size:20px; font-weight:700; color:var(--primary-navy); }
    .dashboard-header .business-name{ margin:6px 0 0; font-size:16px; color:var(--muted); }

    .grid{ display:grid; grid-template-columns: 1fr; gap:20px; align-items:start; }

    /* Left - full width now */
    .card{ background:var(--card); border-radius:var(--radius); box-shadow:0 6px 24px rgba(12,25,48,0.06); overflow:hidden; }
    .card-business .card-head{ display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #f1f3f5; }
    .title{ margin:0; font-size:20px; font-weight:700; }
    .subtitle{ margin:4px 0 0; color:var(--muted); font-size:13px; }

    .card-body{ padding:22px; }
    .row{ display:flex; gap:18px; padding:14px 0; border-bottom:1px solid #f5f7f9; align-items:flex-start; }
    .row:last-child{ border-bottom:none; }
    .label{ flex:0 0 200px; color:var(--muted); font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.02em; }
    .value{ flex:1; font-size:15px; color:var(--primary-navy); }

    /* Form */
    .form{ padding-top:0; }
    .form-row{ margin-bottom:12px; display:block; }
    .form-row label{ display:block; font-size:13px; color:var(--muted); margin-bottom:6px; font-weight:600; }
    .form-row input, .form-row textarea{ width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e6e9ee; font-size:14px; }
    .form-grid-3{ display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
    .actions{ display:flex; justify-content:flex-end; gap:10px; }

    /* Buttons */
    .btn{ border-radius:var(--radius); padding:8px 12px; border:0; cursor:pointer; font-weight:700; font-family:var(--ff); }
    .btn-primary{ background:linear-gradient(90deg,var(--primary-green),#4CAF7D); color:#fff; }
    .btn-outline{ background:transparent; border:1px solid #e6e9ee; color:var(--primary-navy); }

    /* Responsive */
    @media (max-width: 980px){
        .card-business .card-head{ padding:16px; }
        .card-body{ padding:18px; }
        .label{ flex-basis:160px; }
    }
    @media (max-width: 720px){
        .dashboard-inner{ padding:0 12px; }
        .card-business .card-head{ flex-direction:column; align-items:flex-start; gap:10px; }
        .label{ flex-basis:auto; }
        .row{ flex-direction:column; align-items:flex-start; gap:8px; }
        .form-grid-3{ grid-template-columns: 1fr; }
    }
    </style>

    <!-- manager-dashboard.js handles edit/cancel/submit interactions; script enqueued and localized by PHP -->
    <?php
    return ob_get_clean();
}
add_shortcode('manager_dashboard', 'manager_dashboard_shortcode');