<?php
/**
 * Plugin Name: Payndle Business Manager
 * Description: A plugin to manage businesses created in the Payndle system.
 * Version: 1.0.0
 * Author: Bentamo
 * License: GPL2
 * Shortcode: []
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Payndle_Business_Manager
 */
class Payndle_Business_Manager {

    /**
     * Constructor to initialize the plugin.
     */
    /**
     * @var string The meta key prefix for business owner details
     */
    private $owner_meta_prefix = '_business_owner_';

    /**
     * Constructor to initialize the plugin.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        // Handle delete business action
        add_action( 'admin_post_payndle_delete_business', array( $this, 'handle_delete_business' ) );
        // Handle view dashboard action
        add_action( 'admin_post_payndle_view_dashboard', array( $this, 'handle_view_dashboard' ) );
        
        // Add meta boxes for business details
        add_action( 'add_meta_boxes', array( $this, 'add_business_meta_boxes' ) );
        add_action( 'save_post_payndle_business', array( $this, 'save_business_meta' ), 10, 2 );
        
        // Create dashboard when business is published
        add_action( 'publish_payndle_business', array( $this, 'create_business_dashboard' ), 10, 2 );
    }

    /**
     * Add admin menu for managing businesses.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Manage Businesses', 'payndle-business-manager' ), // Page title
            __( 'Businesses', 'payndle-business-manager' ),       // Menu title
            'manage_options',                                    // Capability
            'manage-businesses',                                 // Menu slug
            array( $this, 'render_admin_page' ),                 // Callback function
            'dashicons-admin-site',                             // Icon
            25                                                  // Position
        );
    }

    /**
     * Render the admin page content with dynamic data.
     */
    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Manage Businesses', 'payndle-business-manager' ) . '</h1>';
        echo '<p>' . esc_html__( 'Here you can view and manage all created businesses.', 'payndle-business-manager' ) . '</p>';

        // Query published payndle_business posts
        $query_args = [
            'post_type' => 'payndle_business',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        $q = new WP_Query($query_args);
        $businesses = $q->posts;

        if (empty($businesses)) {
            echo '<p>' . esc_html__( 'No businesses found.', 'payndle-business-manager' ) . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . esc_html__( 'Business Code', 'payndle-business-manager' ) . '</th><th>' . esc_html__( 'Business Name', 'payndle-business-manager' ) . '</th><th>' . esc_html__( 'Owner', 'payndle-business-manager' ) . '</th><th>' . esc_html__( 'Created At', 'payndle-business-manager' ) . '</th><th>' . esc_html__('Status','payndle-business-manager') . '</th><th>' . esc_html__('Actions','payndle-business-manager') . '</th></tr></thead>';
            echo '<tbody>';

            foreach ($businesses as $business) {
                $owner_id = get_post_meta($business->ID, '_business_owner_id', true);
                $owner = get_userdata($owner_id);
                $owner_name = $owner ? $owner->display_name : 'Unknown';
                $created_at = get_post_meta($business->ID, '_business_setup_completed', true);

                echo '<tr>';
                $code = get_post_meta($business->ID, '_business_code', true);
                echo '<td>' . esc_html($code ? $code : '') . '</td>';
                echo '<td>' . esc_html($business->post_title) . '</td>';
                echo '<td>' . esc_html($owner_name) . '</td>';
                echo '<td>' . esc_html($created_at) . '</td>';
                echo '<td>' . esc_html($business->post_status) . '</td>';
                // Actions: View Dashboard and Delete (secure form)
                echo '<td class="actions">';
                // Add View Dashboard button with admin-post action
                // Manager Dashboard URL
                $manager_dashboard_url = admin_url('admin-post.php?action=payndle_view_dashboard&type=manager&business_id=' . $business->ID);
                error_log('Generated Manager Dashboard URL: ' . $manager_dashboard_url);
                
                // Staff Dashboard URL
                $staff_dashboard_url = admin_url('admin-post.php?action=payndle_view_dashboard&type=staff&business_id=' . $business->ID);
                error_log('Generated Staff Dashboard URL: ' . $staff_dashboard_url);
                echo '<div style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px;">';
                
                // Landing Page button
                $landing_id = get_post_meta($business->ID, '_business_landing_id', true);
                $landing_post = $landing_id ? get_post($landing_id) : null;
                
                // If landing page doesn't exist or is not published, create it
                if (!$landing_post || $landing_post->post_status !== 'publish') {
                    // Create landing page
                    $landing_title = sprintf( __( '%s - Welcome', 'payndle-business-manager' ), $business->post_title );
                    $landing_slug = sanitize_title( 'business-landing-' . $business->post_name );
                    $landing_content = '<!-- wp:uagb/container {"block_id":"pndl-landing","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-pndl-landing alignfull uagb-is-root-container"><!-- wp:shortcode -->
[business_landing]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/container -->';

                    $landing_data = array(
                        'post_title'    => $landing_title,
                        'post_name'     => $landing_slug,
                        'post_content'  => $landing_content,
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_author'   => get_post_field( 'post_author', $business->ID ),
                        'meta_input'    => array(
                            '_business_id' => $business->ID
                        )
                    );

                    // If we have an existing landing page, update it
                    if ($landing_id) {
                        $landing_data['ID'] = $landing_id;
                        wp_update_post($landing_data);
                    } else {
                        // Create new landing page
                        $landing_id = wp_insert_post($landing_data);
                        if (!is_wp_error($landing_id)) {
                            update_post_meta($business->ID, '_business_landing_id', $landing_id);
                        }
                    }
                    
                    // Force publish status and clear cache
                    wp_publish_post($landing_id);
                    clean_post_cache($landing_id);
                    $landing_post = get_post($landing_id);
                }

                // Show the landing page button if we have a valid page
                if ($landing_post && $landing_post->post_status === 'publish') {
                    $landing_url = get_permalink($landing_id);
                    if ($landing_url) {
                        echo '<a href="' . esc_url($landing_url) . '" class="button button-secondary" style="width: 100%; text-align: center;">' . 
                             '<span class="dashicons dashicons-admin-site" style="margin: 3px 5px 0 -3px;"></span>' . 
                             esc_html__('Landing Page', 'payndle-business-manager') . '</a>';
                    }
                }

                // Manager Dashboard button
                echo '<a href="' . esc_url($manager_dashboard_url) . '" class="button button-primary" style="width: 100%; text-align: center;">' . 
                     '<span class="dashicons dashicons-dashboard" style="margin: 3px 5px 0 -3px;"></span>' . 
                     esc_html__('View My Dashboard', 'payndle-business-manager') . '</a>';
                
                // Staff Dashboard button
                echo '<a href="' . esc_url($staff_dashboard_url) . '" class="button button-secondary" style="width: 100%; text-align: center;">' . 
                     '<span class="dashicons dashicons-groups" style="margin: 3px 5px 0 -3px;"></span>' . 
                     esc_html__('Staff Dashboard', 'payndle-business-manager') . '</a>';
                
                echo '</div>';
                echo '<form method="post" action="' . esc_url( admin_url('admin-post.php') ) . '" onsubmit="return confirm(\'Are you sure you want to delete this business? This action cannot be undone.\');">';
                echo '<input type="hidden" name="action" value="payndle_delete_business" />';
                echo '<input type="hidden" name="business_id" value="' . esc_attr($business->ID) . '" />';
                wp_nonce_field( 'payndle_delete_business_' . $business->ID, 'payndle_delete_nonce' );
                echo '<input type="submit" class="button button-danger" value="' . esc_attr__('Delete','payndle-business-manager') . '" />';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }

        echo '</div>';
    }

    /**
     * Handle view dashboard action.
     */
    public function handle_view_dashboard() {
        $business_id = isset( $_GET['business_id'] ) ? intval( $_GET['business_id'] ) : 0;
        $dashboard_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'manager';
        
        error_log('Attempting to view dashboard:');
        error_log('Business ID: ' . $business_id);
        error_log('Dashboard Type: ' . $dashboard_type);
        
        if ( $business_id ) {
            $business = get_post( $business_id );
            error_log('Business Post: ' . ($business ? 'Found' : 'Not Found'));
            if ($business) {
                error_log('Business Post Type: ' . $business->post_type);
                
                if ($business->post_type !== 'payndle_business') {
                    error_log('Invalid business type: ' . $business->post_type);
                    wp_die( esc_html__( 'Invalid business type.', 'payndle-business-manager' ) );
                    return;
                }
            } else {
                error_log('Business not found with ID: ' . $business_id);
                wp_die( esc_html__( 'Business not found.', 'payndle-business-manager' ) );
                return;
            }
            
            // Determine which dashboard to show based on type
            if ($dashboard_type === 'staff') {
                $dashboard_id = get_post_meta( $business_id, '_business_staff_dashboard_id', true );
            } else {
                $dashboard_id = get_post_meta( $business_id, '_business_dashboard_id', true );
            }
                
                if ( $dashboard_id && get_post( $dashboard_id ) ) {
                    // Redirect to the dedicated dashboard
                    $dashboard_url = get_permalink( $dashboard_id );
                    
                    // Add business_id as a query parameter if not already in the URL
                    $dashboard_url = add_query_arg( 'business_id', $business_id, $dashboard_url );
                    
                    wp_redirect( $dashboard_url );
                    exit;
                } else {
                // Create the appropriate dashboard if it doesn't exist
                if ($dashboard_type === 'staff') {
                    // Create staff dashboard
                    $this->create_business_dashboard( $business_id, $business, 'staff' );
                    $dashboard_id = get_post_meta( $business_id, '_business_staff_dashboard_id', true );
                } else {
                    // Create manager dashboard
                    $this->create_business_dashboard( $business_id, $business, 'manager' );
                    $dashboard_id = get_post_meta( $business_id, '_business_dashboard_id', true );
                }
                    
                    if ( $dashboard_id ) {
                        $dashboard_url = add_query_arg( 'business_id', $business_id, get_permalink( $dashboard_id ) );
                        wp_redirect( $dashboard_url );
                        exit;
                }
            }
        }
        
        // If we get here, something went wrong
        wp_die( esc_html__( 'The business does not exist.', 'payndle-business-manager' ) );
    }

    /**
     * Add meta boxes for business details
     */
    public function add_business_meta_boxes() {
        add_meta_box(
            'business_owner_details',
            __( 'Owner Details', 'payndle-business-manager' ),
            array( $this, 'render_owner_meta_box' ),
            'payndle_business',
            'normal',
            'high'
        );
    }

    /**
     * Render the owner details meta box
     */
    public function render_owner_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'save_business_owner_details', 'business_owner_nonce' );
        
        // Get saved values
        $owner_id = get_post_meta( $post->ID, '_business_owner_id', true );
        $owner = $owner_id ? get_userdata( $owner_id ) : null;
        
        // Get owner details
        $first_name = $owner ? $owner->first_name : '';
        $last_name = $owner ? $owner->last_name : '';
        $email = $owner ? $owner->user_email : '';
        $phone = $owner ? get_user_meta( $owner_id, 'billing_phone', true ) : '';
        
        // Output fields
        ?>
        <div class="business-owner-details">
            <p>
                <label for="owner_first_name"><?php _e( 'First Name', 'payndle-business-manager' ); ?></label><br>
                <input type="text" id="owner_first_name" name="owner_first_name" 
                       value="<?php echo esc_attr( $first_name ); ?>" class="widefat">
            </p>
            <p>
                <label for="owner_last_name"><?php _e( 'Last Name', 'payndle-business-manager' ); ?></label><br>
                <input type="text" id="owner_last_name" name="owner_last_name" 
                       value="<?php echo esc_attr( $last_name ); ?>" class="widefat">
            </p>
            <p>
                <label for="owner_email"><?php _e( 'Email', 'payndle-business-manager' ); ?></label><br>
                <input type="email" id="owner_email" name="owner_email" 
                       value="<?php echo esc_attr( $email ); ?>" class="widefat">
            </p>
            <p>
                <label for="owner_phone"><?php _e( 'Phone', 'payndle-business-manager' ); ?></label><br>
                <input type="tel" id="owner_phone" name="owner_phone" 
                       value="<?php echo esc_attr( $phone ); ?>" class="widefat">
            </p>
        </div>
        <?php
    }

    /**
     * Save business meta data
     */
    public function save_business_meta( $post_id, $post ) {
        // Check nonce
        if ( ! isset( $_POST['business_owner_nonce'] ) || 
             ! wp_verify_nonce( $_POST['business_owner_nonce'], 'save_business_owner_details' ) ) {
            return $post_id;
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        // Save owner details
        if ( isset( $_POST['owner_first_name'] ) || isset( $_POST['owner_last_name'] ) || 
             isset( $_POST['owner_email'] ) || isset( $_POST['owner_phone'] ) ) {
            
            $owner_id = get_post_meta( $post_id, '_business_owner_id', true );
            
            if ( $owner_id ) {
                $user_data = array(
                    'ID' => $owner_id,
                    'first_name' => sanitize_text_field( $_POST['owner_first_name'] ),
                    'last_name'  => sanitize_text_field( $_POST['owner_last_name'] ),
                );
                
                // Update email if changed
                if ( ! empty( $_POST['owner_email'] ) && is_email( $_POST['owner_email'] ) ) {
                    $user_data['user_email'] = sanitize_email( $_POST['owner_email'] );
                }
                
                // Update user
                wp_update_user( $user_data );
                
                // Update phone number
                if ( isset( $_POST['owner_phone'] ) ) {
                    update_user_meta( $owner_id, 'billing_phone', sanitize_text_field( $_POST['owner_phone'] ) );
                }
            }
        }
        
        return $post_id;
    }
    
    /**
     * Create a dedicated dashboard page for a business
     */
    public function create_business_dashboard( $post_id, $post, $type = 'manager' ) {
        // Check if this is a revision
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        // Check if this is a business post type
        if ( 'payndle_business' !== $post->post_type ) {
            return;
        }
        
        // Determine which dashboard to create
        if ($type === 'staff') {
            $meta_key = '_business_staff_dashboard_id';
            $dashboard_title = sprintf( __( 'Staff Dashboard - %s', 'payndle-business-manager' ), $post->post_title );
            $dashboard_slug = sanitize_title( 'staff-dashboard-' . $post->post_name );
            $dashboard_content = '<!-- wp:uagb/container {"block_id":"b9a6c81d","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#64c493","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-b9a6c81d alignfull uagb-is-root-container"></div>
<!-- /wp:uagb/container -->

<!-- wp:uagb/container {"block_id":"19535bd1","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-19535bd1 alignfull uagb-is-root-container"><!-- wp:shortcode -->
[assigned_bookings]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/container -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';

            $dashboard_data = array(
                'post_title'    => $dashboard_title,
                'post_name'     => $dashboard_slug,
                'post_content'  => $dashboard_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => get_post_field( 'post_author', $post_id ),
                'meta_input'    => array(
                    '_business_id' => $post_id
                )
            );
            
            // Insert the dashboard page
            $dashboard_id = wp_insert_post( $dashboard_data );
            
            if ( ! is_wp_error( $dashboard_id ) ) {
                // Save the dashboard ID in business meta using the correct meta key
                update_post_meta( $post_id, $meta_key, $dashboard_id );
                
                // Also save the dashboard URL for easy access
                $dashboard_url = get_permalink( $dashboard_id );
                update_post_meta( $post_id, $meta_key . '_url', $dashboard_url );

                // Create Landing Page if we're creating the manager dashboard
                if ($type === 'manager') {
                    $landing_title = sprintf( __( '%s - Welcome', 'payndle-business-manager' ), $post->post_title );
                    $landing_slug = sanitize_title( 'business-landing-' . $post->post_name );
                    $landing_content = '<!-- wp:uagb/container {"block_id":"pndl-landing","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-pndl-landing alignfull uagb-is-root-container"><!-- wp:shortcode -->
[business_landing]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/container -->';

                    $landing_data = array(
                        'post_title'    => $landing_title,
                        'post_name'     => $landing_slug,
                        'post_content'  => $landing_content,
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_author'   => get_post_field( 'post_author', $post_id ),
                        'meta_input'    => array(
                            '_business_id' => $post_id
                        )
                    );

                    $landing_id = wp_insert_post( $landing_data );
                    if ( ! is_wp_error( $landing_id ) ) {
                        update_post_meta( $post_id, '_business_landing_id', $landing_id );
                    }
                }
            }
        } else {
            $meta_key = '_business_dashboard_id';
            $dashboard_title = sprintf( __( 'Business Dashboard - %s', 'payndle-business-manager' ), $post->post_title );
            $dashboard_slug = sanitize_title( 'dashboard-' . $post->post_name );
            
            // Define the manager dashboard content with all required shortcodes in order
            $dashboard_content = '<!-- wp:uagb/container {"block_id":"c35ac3a5","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#64c493","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-c35ac3a5 alignfull uagb-is-root-container"></div>
<!-- /wp:uagb/container -->

<!-- wp:uagb/container {"block_id":"3dcbb986","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true,"layout":"flex"} -->
<div class="wp-block-uagb-container uagb-layout-flex uagb-block-3dcbb986 alignfull uagb-is-root-container"><!-- wp:shortcode -->
[manager_dashboard]
<!-- /wp:shortcode -->

<!-- wp:uagb/tabs {"block_id":"6e6c5d03","tabHeaders":["Bookings","Staff","Services"],"borderStyle":"","borderWidth":"","borderColor":"","tabBorderTopWidth":1,"tabBorderLeftWidth":1,"tabBorderRightWidth":1,"tabBorderBottomWidth":1,"tabBorderStyle":"solid","tabBorderColor":"#e0e0e0"} -->
<div class="wp-block-uagb-tabs uagb-block-6e6c5d03 uagb-tabs__wrap uagb-tabs__hstyle1-desktop uagb-tabs__vstyle6-tablet uagb-tabs__stack1-mobile" data-tab-active="0"><ul class="uagb-tabs__panel uagb-tabs__align-left" role="tablist"><li class="uagb-tab uagb-tabs__active" role="none"><a href="#uagb-tabs__tab0" class="uagb-tabs-list uagb-tabs__icon-position-left" data-tab="0" role="tab"><div>Bookings</div></a></li><li class="uagb-tab " role="none"><a href="#uagb-tabs__tab1" class="uagb-tabs-list uagb-tabs__icon-position-left" data-tab="1" role="tab"><div>Staff</div></a></li><li class="uagb-tab " role="none"><a href="#uagb-tabs__tab2" class="uagb-tabs-list uagb-tabs__icon-position-left" data-tab="2" role="tab"><div>Services</div></a></li></ul><div class="uagb-tabs__body-wrap"><!-- wp:uagb/tabs-child {"block_id":"6e6c5d03","header":"Bookings","tabActive":0,"tabHeaders":["Bookings","Staff","Services"]} -->
<div class="wp-block-uagb-tabs-child uagb-tabs__body-container uagb-inner-tab-0" aria-labelledby="uagb-tabs__tab0"><!-- wp:shortcode -->
[elite_cuts_manage_bookings]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/tabs-child -->

<!-- wp:uagb/tabs-child {"block_id":"a4393535","id":1,"header":"Staff","tabActive":0,"tabHeaders":["Bookings","Staff","Services"]} -->
<div class="wp-block-uagb-tabs-child uagb-tabs__body-container uagb-inner-tab-1" aria-labelledby="uagb-tabs__tab1"><!-- wp:shortcode -->
[manage_staff]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/tabs-child -->

<!-- wp:uagb/tabs-child {"block_id":"6e6c5d03","id":2,"header":"Services","tabActive":0,"tabHeaders":["Bookings","Staff","Services"]} -->
<div class="wp-block-uagb-tabs-child uagb-tabs__body-container uagb-inner-tab-2" aria-labelledby="uagb-tabs__tab2"><!-- wp:shortcode -->
[manager_add_service]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/tabs-child --></div></div>
<!-- /wp:uagb/tabs --></div>
<!-- /wp:uagb/container -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';
            
            $dashboard_data = array(
                'post_title'    => $dashboard_title,
                'post_name'     => $dashboard_slug,
                'post_content'  => $dashboard_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => get_post_field( 'post_author', $post_id ),
                'meta_input'    => array(
                    '_business_id' => $post_id
                )
            );
            
            // Insert the dashboard page
            $dashboard_id = wp_insert_post( $dashboard_data );
            
            if ( ! is_wp_error( $dashboard_id ) ) {
                // Save the dashboard ID in business meta using the correct meta key
                update_post_meta( $post_id, $meta_key, $dashboard_id );
                
                // Also save the dashboard URL for easy access
                $dashboard_url = get_permalink( $dashboard_id );
                update_post_meta( $post_id, $meta_key . '_url', $dashboard_url );
                
                // Create Staff Dashboard
                $staff_dashboard_title = sprintf( __( 'Staff Dashboard - %s', 'payndle-business-manager' ), $post->post_title );
                $staff_dashboard_slug = sanitize_title( 'staff-dashboard-' . $post->post_name );
                $staff_dashboard_content = '<!-- wp:uagb/container {"block_id":"b9a6c81d","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#64c493","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-b9a6c81d alignfull uagb-is-root-container"></div>
<!-- /wp:uagb/container -->

<!-- wp:uagb/container {"block_id":"19535bd1","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-19535bd1 alignfull uagb-is-root-container"><!-- wp:shortcode -->
[assigned_bookings]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/container -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';

                $staff_dashboard_data = array(
                    'post_title'    => $staff_dashboard_title,
                    'post_name'     => $staff_dashboard_slug,
                    'post_content'  => $staff_dashboard_content,
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_author'   => get_post_field( 'post_author', $post_id ),
                    'meta_input'    => array(
                        '_business_id' => $post_id
                    )
                );

                $staff_dashboard_id = wp_insert_post( $staff_dashboard_data );
                if ( ! is_wp_error( $staff_dashboard_id ) ) {
                    update_post_meta( $post_id, '_business_staff_dashboard_id', $staff_dashboard_id );
                }
            }
        }
    }

    /**
     * Handle the deletion of a business.
     */
    public function handle_delete_business() {
        // Verify nonce and user capabilities
        if ( ! isset( $_POST['payndle_delete_nonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['payndle_delete_nonce'] ) ), 'payndle_delete_business_' . ( isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0 ) ) ) {
            wp_die( esc_html__( 'Security check failed.', 'payndle-business-manager' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'payndle-business-manager' ) );
        }

        $business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;
        
        if ( $business_id ) {
            // Force delete the business post
            wp_delete_post( $business_id, true );
            
            // Redirect back to the businesses list with success message
            wp_safe_redirect( 
                add_query_arg( 
                    'message', 
                    'deleted', 
                    admin_url( 'admin.php?page=manage-businesses' ) 
                ) 
            );
            exit;
        }
    }

}

// Initialize the plugin.
new Payndle_Business_Manager();
