<?php
/**
 * Plugin Name: Payndle Business Manager
 * Description: A plugin to manage businesses created in the Payndle system.
 * Version: 1.0.0
 * Author: Bentamo
 * License: GPL2
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
                // Actions: Delete (secure form)
                echo '<td>';
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
     * Handle the deletion of a business.
     */
    public function handle_delete_business() {
        // Check if the current user has the capability to delete businesses
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'payndle-business-manager' ) );
        }

        // Check the nonce for security
        if ( ! isset( $_POST['payndle_delete_nonce'] ) || ! wp_verify_nonce( $_POST['payndle_delete_nonce'], 'payndle_delete_business_' . $_POST['business_id'] ) ) {
            wp_die( __( 'Nonce verification failed.', 'payndle-business-manager' ) );
        }

        // Get the business ID from the form
        $business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;

        if ( $business_id > 0 ) {
            // Delete the business post
            wp_delete_post( $business_id, true );
        }

        // Redirect back to the admin page with a success message
        wp_redirect( admin_url( 'admin.php?page=manage-businesses&deleted=1' ) );
        exit;
    }
}

// Initialize the plugin.
new Payndle_Business_Manager();
