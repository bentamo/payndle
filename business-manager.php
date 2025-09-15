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
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        // Handle delete business action
        add_action( 'admin_post_payndle_delete_business', array( $this, 'handle_delete_business' ) );
        // Handle view dashboard action
        add_action( 'admin_post_payndle_view_dashboard', array( $this, 'handle_view_dashboard' ) );
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
                $dashboard_url = wp_nonce_url(
                    admin_url('admin-post.php?action=payndle_view_dashboard&business_id=' . $business->ID),
                    'payndle_view_dashboard_' . $business->ID
                );
                echo '<a href="' . esc_url($dashboard_url) . '" class="button button-primary" style="margin-right: 5px;">' . esc_html__('View My Dashboard', 'payndle-business-manager') . '</a>';
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
        // Verify user is logged in
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url() );
            exit;
        }

        $current_user = wp_get_current_user();
        $business_id = isset( $_GET['business_id'] ) ? intval( $_GET['business_id'] ) : 0;
        
        // Verify business exists and user has access
        if ( $business_id ) {
            $business = get_post( $business_id );
            $owner_id = get_post_meta( $business_id, '_business_owner_id', true );
            
            if ( $business && ( $owner_id == $current_user->ID || current_user_can( 'manage_options' ) ) ) {
                // Check if dashboard page exists
                $dashboard_page = get_page_by_path( 'manager-dashboard' );
                
                // Create dashboard page if it doesn't exist
                if ( ! $dashboard_page ) {
                    $page_data = array(
                        'post_title'    => 'Manager Dashboard',
                        'post_name'     => 'manager-dashboard',
                        'post_content'  => '[manager_dashboard]',
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_author'   => $current_user->ID,
                    );
                    
                    $page_id = wp_insert_post( $page_data );
                    
                    if ( is_wp_error( $page_id ) ) {
                        wp_die( esc_html__( 'Error creating dashboard page.', 'payndle-business-manager' ) );
                    }
                }
                
                // Redirect to the dashboard with business ID
                $dashboard_url = add_query_arg( 'business_id', $business_id, home_url( '/manager-dashboard/' ) );
                wp_redirect( $dashboard_url );
                exit;
            }
        }
        
        // If we get here, something went wrong
        wp_die( esc_html__( 'You do not have permission to view this dashboard or the business does not exist.', 'payndle-business-manager' ) );
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
