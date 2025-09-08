<?php
/*
Plugin Name: Manager Dashboard
Plugin URI:  https://payndle.com
Description: Clean and simple business management dashboard. Use shortcode [manager_dashboard].
Version:     1.2
Author:      Payndle
License:     GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

function manager_dashboard_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="login-prompt">
            <h2>Welcome to Payndle</h2>
            <p>Please sign in to access your business dashboard.</p>
            <a href="' . wp_login_url( get_permalink() ) . '" class="login-button">Sign In</a>
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
        <header class="dashboard-header">
            <h1>Welcome, <?php echo esc_html($current_user->display_name); ?></h1>
            <p>Manage your business profile and services</p>
        </header>

        <div class="dashboard-content">
            <section class="business-info">
                <h2>Business Information</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3>Business Information</h3>
                        <p><strong>Name:</strong> <?php echo esc_html($business_name); ?></p>
                        <?php if (!empty($business_code)) : ?>
                            <p><strong>Business ID:</strong> <?php echo esc_html($business_code); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($business_description)) : ?>
                            <p><strong>Description:</strong> <?php echo esc_html($business_description); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($business_website)) : ?>
                            <p><strong>Website:</strong> <a href="<?php echo esc_url($business_website); ?>" target="_blank"><?php echo esc_html($business_website); ?></a></p>
                        <?php endif; ?>
                        <a href="/business-setup/?business_id=<?php echo esc_attr($business_id); ?>" class="action-link">Edit Business Details</a>
                    </div>
                    
                    <div class="info-card">
                        <h3>Contact Information</h3>
                        <?php if (!empty($business_email)) : ?>
                            <p><strong>Email:</strong> <?php echo esc_html($business_email); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($business_phone)) : ?>
                            <p><strong>Phone:</strong> <?php echo esc_html($business_phone); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($business_address)) : ?>
                            <p><strong>Address:</strong> 
                                <?php 
                                echo esc_html($business_address);
                                if (!empty($business_city)) echo ', ' . esc_html($business_city);
                                if (!empty($business_state)) echo ', ' . esc_html($business_state);
                                if (!empty($business_zip)) echo ' ' . esc_html($business_zip);
                                if (!empty($business_country)) echo ', ' . esc_html($business_country);
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-card">
                        <h3>Account Status</h3>
                        <p class="status <?php echo esc_attr($business_status); ?>">
                            <?php echo ucfirst(esc_html($business_status)); ?>
                        </p>
                        <p>
                            <?php if ($business_status === 'active') : ?>
                                Your business is live and visible to customers
                            <?php else : ?>
                                Your business profile is not yet active
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($setup_completed)) : ?>
                            <p><small>Setup completed: <?php echo esc_html(date('F j, Y', strtotime($setup_completed))); ?></small></p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="#" class="action-button">Add Service</a>
                    <a href="#" class="action-button">Manage Schedule</a>
                    <a href="#" class="action-button">View Customers</a>
                </div>
            </section>

            <section class="profile-section">
                <div class="profile-header">
                    <h2>Your Profile</h2>
                    <a href="<?php echo esc_url(get_edit_profile_url()); ?>" class="edit-button">Edit Profile</a>
                </div>
                <div class="profile-details">
                    <p><strong>Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                    <p><strong>Member since:</strong> <?php echo esc_html(date('F Y', strtotime($current_user->user_registered))); ?></p>
                </div>
            </section>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'manager_dashboard', 'manager_dashboard_shortcode' );

// Add minimal CSS
add_action('wp_head', function() {
    if (is_page() && has_shortcode(get_post()->post_content, 'manager_dashboard')) {
        ?>
        <style>
            .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .dashboard-header { margin-bottom: 30px; }
            .dashboard-header h1 { font-size: 24px; margin: 0 0 5px; }
            .dashboard-header p { color: #666; margin: 0; }
            
            .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
            .info-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .info-card h3 { margin: 0 0 10px; font-size: 16px; color: #444; }
            .info-card p { margin: 5px 0; }
            .business-code { color: #666; font-size: 14px; }
            
            .action-link { display: inline-block; margin-top: 10px; color: #2563eb; text-decoration: none; }
            .status { 
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: 500;
                margin: 5px 0;
            }
            .status.active { 
                color: #16a34a;
                background-color: #f0fdf4;
                border: 1px solid #86efac;
            }
            .status.inactive,
            .status.pending {
                color: #e11d48;
                background-color: #fff1f2;
                border: 1px solid #fda4af;
            }
            
            .quick-actions { margin: 30px 0; }
            .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
            .action-button {
                padding: 8px 16px;
                background: #f3f4f6;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                color: #111827;
                text-decoration: none;
                font-size: 14px;
            }
            
            .profile-section { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .edit-button {
                padding: 6px 12px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-size: 14px;
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
