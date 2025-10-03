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
    // Ensure landing page exists on any save as well (immediate availability)
    add_action( 'save_post_payndle_business', array( $this, 'ensure_landing_page' ), 20, 2 );
        
    // No longer create dedicated manager/staff dashboard pages on publish; dashboards are dynamic now
        // Ensure landing page is created immediately when business is published
        add_action( 'publish_payndle_business', array( $this, 'ensure_landing_page' ), 10, 2 );
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
                // Manager/Staff Dashboard URLs route to dynamic pages via handler
                $manager_dashboard_url = admin_url('admin-post.php?action=payndle_view_dashboard&type=manager&business_id=' . $business->ID);
                $staff_dashboard_url   = admin_url('admin-post.php?action=payndle_view_dashboard&type=staff&business_id=' . $business->ID);
                echo '<div style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px;">';
                
                // Landing Page button
                // Ensure the landing page exists and is published
                $this->ensure_landing_page($business->ID, $business);
                $landing_id = get_post_meta($business->ID, '_business_landing_id', true);
                $landing_post = $landing_id ? get_post($landing_id) : null;

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
            
            // Determine which shortcode to target based on requested dashboard type
            $target_shortcode = ($dashboard_type === 'staff') ? '[assigned_bookings]' : '[manager_dashboard]';
            $target_page_id = $this->find_page_with_shortcode( $target_shortcode );

            if ( $target_page_id ) {
                $url = add_query_arg( 'business_id', $business_id, get_permalink( $target_page_id ) );
                wp_redirect( $url );
                exit;
            }

            // Fallback: if no dashboard page exists, redirect to Landing Page (ensured at publish/save) with a helpful query flag
            $landing_id = get_post_meta( $business_id, '_business_landing_id', true );
            if ( $landing_id && get_post( $landing_id ) ) {
                $landing_url = add_query_arg( array( 'business_id' => $business_id, 'notice' => 'no_dashboard_page' ), get_permalink( $landing_id ) );
                wp_redirect( $landing_url );
                exit;
            }

            // As a last resort, show an admin message
            wp_die( esc_html__( 'No dashboard page found. Please create a page containing the appropriate shortcode (e.g., [manager_dashboard]) to use the dynamic dashboard.', 'payndle-business-manager' ) );
        }
        
        // If we get here, something went wrong
        wp_die( esc_html__( 'The business does not exist.', 'payndle-business-manager' ) );
    }

    /**
     * Locate a published page that contains the given shortcode in its content.
     * Returns the page ID or 0 if not found.
     */
    private function find_page_with_shortcode( $shortcode ) {
        $shortcode = trim( (string) $shortcode );
        if ( empty( $shortcode ) ) { return 0; }

        $q = new WP_Query( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'fields'         => 'ids',
        ) );
        if ( ! $q->have_posts() ) { return 0; }
        foreach ( $q->posts as $pid ) {
            $content = get_post_field( 'post_content', $pid );
            if ( $content && strpos( $content, $shortcode ) !== false ) {
                return intval( $pid );
            }
        }
        return 0;
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
    
    // create_business_dashboard removed; dashboards are now handled dynamically via existing pages and business_id context

    /**
     * Ensure the business landing page exists and is published.
     * Can be called on publish and from admin list rendering.
     */
    public function ensure_landing_page( $post_id, $post ) {
        if ( ! $post_id || ! $post || ( isset($post->post_type) && $post->post_type !== 'payndle_business' ) ) {
            return;
        }

        $landing_id = get_post_meta( $post_id, '_business_landing_id', true );
        $landing_post = $landing_id ? get_post( $landing_id ) : null;

        if ( $landing_post && $landing_post->post_status === 'publish' ) {
            return; // already exists
        }

        $landing_title = sprintf( __( '%s - Welcome', 'payndle-business-manager' ), $post->post_title );
        $landing_slug  = sanitize_title( 'business-landing-' . $post->post_name );
        $landing_content = '<!-- wp:uagb/container {"block_id":"pndl-landing","innerContentWidth":"alignfull","backgroundType":"color","backgroundColor":"#f4f4f4","variationSelected":true,"isBlockRootParent":true} -->
<div class="wp-block-uagb-container uagb-block-pndl-landing alignfull uagb-is-root-container"><!-- wp:shortcode -->
[business_landing]
<!-- /wp:shortcode --></div>
<!-- /wp:uagb/container -->';

        $landing_data = array(
            'post_title'   => $landing_title,
            'post_name'    => $landing_slug,
            'post_content' => $landing_content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_post_field( 'post_author', $post_id ),
            'meta_input'   => array( '_business_id' => $post_id ),
        );

        if ( $landing_id ) {
            $landing_data['ID'] = $landing_id;
            $updated_id = wp_update_post( $landing_data );
            if ( is_wp_error( $updated_id ) ) { return; }
            $landing_id = $updated_id;
        } else {
            $landing_id = wp_insert_post( $landing_data );
            if ( is_wp_error( $landing_id ) ) { return; }
            update_post_meta( $post_id, '_business_landing_id', $landing_id );
        }

        // Ensure publish and refresh cache
        wp_publish_post( $landing_id );
        clean_post_cache( $landing_id );
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
