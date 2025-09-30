<?php
/*
Plugin Name: Service Manager Pro
Description: Modern service management system with categories and AJAX interface.
Version: 2.0
Author: Your Name
Shortcode: [manager_add_service]
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper function to get the current business ID from various contexts
 * 
 * @return int The current business ID or 0 if not found
 */
function mvp_get_current_business_id() {
    // First check URL parameter (highest priority)
    if (isset($_GET['business_id'])) {
        return intval($_GET['business_id']);
    }
    
    // Then check POST data
    if (isset($_POST['business_id'])) {
        return intval($_POST['business_id']);
    }
    
    // Check for session stored business ID
    if (isset($_SESSION['current_business_id'])) {
        return intval($_SESSION['current_business_id']);
    }
    
    // If user is logged in, try to get their primary business
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        
        // Query for businesses where this user is the owner
        $args = array(
            'post_type' => 'business',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_business_owner_id',
                    'value' => $current_user->ID,
                    'compare' => '='
                )
            )
        );
        
        $business_query = new WP_Query($args);
        if ($business_query->have_posts()) {
            $business_query->the_post();
            $business_id = get_the_ID();
            wp_reset_postdata();
            return $business_id;
        }
    }
    
    // Default to 0 (no business found)
    return 0;
}

/* ======================
   REGISTER SERVICE POST TYPE & TAXONOMY
   ====================== */
function mvp_register_service_post_type() {
    // Register Service Post Type
    register_post_type('service', array(
        'labels' => array(
            'name' => 'Services',
            'singular_name' => 'Service',
            'add_new_item' => 'Add New Service',
            'edit_item' => 'Edit Service',
            'view_item' => 'View Service'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-admin-tools',
        'show_ui' => true,
        'register_meta_box_cb' => 'mvp_add_service_meta_boxes',
    ));

    // Register Service Category Taxonomy
    register_taxonomy('service_category', 'service', array(
        'labels' => array(
            'name' => 'Service Categories',
            'singular_name' => 'Category',
            'add_new_item' => 'Add New Category',
            'edit_item' => 'Edit Category',
            'update_item' => 'Update Category',
            'search_items' => 'Search Categories',
            'all_items' => 'All Categories',
            'choose_from_most_used' => 'Choose from most used categories',
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'mvp_register_service_post_type');

/**
 * Add meta boxes to service post type
 */
function mvp_add_service_meta_boxes() {
    add_meta_box(
        'mvp_service_business_id',
        'Business Information',
        'mvp_service_business_id_meta_box',
        'service',
        'side',
        'high'
    );
}

/**
 * Render business ID meta box
 */
function mvp_service_business_id_meta_box($post) {
    // Get current business ID if set
    $business_id = get_post_meta($post->ID, '_business_id', true);
    
    // If no business ID set and user is creating new service, try to get their business
    if (!$business_id && isset($_GET['post']) === false) {
        $current_user_id = get_current_user_id();
        $user_business = get_posts([
            'post_type' => 'payndle_business',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_business_owner_id',
                    'value' => $current_user_id,
                    'compare' => '='
                ]
            ]
        ]);
        if (!empty($user_business)) {
            $business_id = $user_business[0]->ID;
        }
    }
    
    // Add nonce for security
    wp_nonce_field('mvp_service_business_id', 'mvp_service_business_id_nonce');
    
    // If no business found, show error
    if (!$business_id) {
        echo '<p class="error">Error: No business profile found. Please create a business profile first.</p>';
        return;
    }
    
    // Get business name
    $business_name = get_the_title($business_id);
    echo '<p><strong>Business:</strong> ' . esc_html($business_name) . '</p>';
    echo '<input type="hidden" name="mvp_business_id" value="' . esc_attr($business_id) . '">';
}

/**
 * Save business ID when service is saved
 */
function mvp_save_service_business_id($post_id) {
    // Security checks
    if (!isset($_POST['mvp_service_business_id_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['mvp_service_business_id_nonce'], 'mvp_service_business_id')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save business ID
    if (isset($_POST['mvp_business_id'])) {
        $business_id = intval($_POST['mvp_business_id']);
        if ($business_id > 0) {
            update_post_meta($post_id, '_business_id', $business_id);
        }
    }
}
add_action('save_post_service', 'mvp_save_service_business_id');


/* ======================
   ENQUEUE SCRIPTS & STYLES
   ====================== */
function mvp_enqueue_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue Select2 for better dropdowns
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);

    // Enqueue custom styles with modern design
    wp_enqueue_style(
        'mvp-style',
        $plugin_url . 'assets/css/style.css',
        array(),
        '2.0'
    );

    // Add inline styles for quick styling without extra files
    $custom_css = ":root {
            /* Brand Color Palette */
            --primary-green: #64C493;   /* Growth, innovation, payments, progress */
            --primary-navy: #0C1930;    /* Trust, reliability, professionalism */
            --white: #FFFFFF;           /* Backgrounds, text contrast, clean minimalism */
            --accent-gray: #F8FAFC;     /* Backgrounds, subtle dividers, dashboard UI */
            --border-color: #E2E8F0;    /* Border color for UI elements */
            --text-body: #0C1930;       /* Main text color - Using primary navy */
            --text-muted: #64748B;      /* Muted text color */
            
            /* Typography */
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, sans-serif;
            --font-regular: 400;    /* Body text, small text */
            --font-medium: 500;     /* Heading 4 */
            --font-semibold: 600;   /* Heading 2, Heading 3 */
            --font-bold: 700;       /* Heading 1 */
            --border-radius: 8px;   /* Button and card radius */
            
            /* Semantic Colors */
            --primary: var(--primary-green);
            --secondary: var(--primary-navy);
            --accent: var(--primary-green);
            --light: var(--white);
            
            /* Background & Text */
            --bg-light: var(--accent-gray);
            --bg-white: var(--white);
            --text-dark: var(--primary-navy);
            --text-body: #1E293B;
            --text-muted: #64748B;
            
            /* UI Colors */
            --success: var(--primary-green);
            --warning: #F59E0B;         /* Amber for warnings */
            --danger: #EF4444;          /* Red for errors */
            --border-color: #E2E8F0;
            
            /* Effects */
            --box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s ease;
            --border-radius: 8px;
            
            /* Backward compatibility */
            --primary-color: var(--primary);
            --secondary-color: var(--secondary);
            --accent-color: var(--accent);
            --light-color: var(--light);
            --dark-color: var(--text-dark);
            --light-gray: var(--bg-light);
            --success-color: var(--success);
            --danger-color: var(--danger);
            --text-color: var(--text-body);
        }
        
        /* Customer-facing Services Grid */
        .mvp-services {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.25rem 1rem;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            width: 100%;
            box-sizing: border-box;
        }
        
        .mvp-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }
        
        @media (max-width: 1024px) {
            .mvp-services-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.25rem;
            }
        }
        
        @media (max-width: 768px) {
            .mvp-services {
                padding: 1rem 0.75rem;
            }
            
            .mvp-services-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .mvp-services-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        
        .mvp-category-filter {
            margin-bottom: 2rem;
            max-width: 300px;
        }
        
        .mvp-category-filter select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            color: var(--primary-navy);
            background-color: var(--white);
            font-family: var(--font-family);
            font-weight: var(--font-regular);
            transition: all 0.2s ease;
        }
        
        .mvp-category-filter select:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 2px rgba(100, 196, 147, 0.2);
        }
        
        .mvp-category-filter select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .mvp-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(12, 25, 48, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            word-wrap: break-word;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
            transform: translateY(0);
            border: 1px solid rgba(100, 196, 147, 0.2);
        }
        
        .mvp-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -6px rgba(100, 196, 147, 0.16), 0 8px 16px -4px rgba(12, 25, 48, 0.1);
            border-color: var(--primary-green);
        }
        
        .mvp-card-inner {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
        }
        
        .mvp-service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #d1d5db;
        }
        
        .mvp-service-card-header {
            padding: 1rem 1.25rem 0.75rem;
            background: var(--white);
            position: relative;
            border-bottom: 1px solid var(--border-color);
        }
        
        .mvp-service-title {
            margin: 0 0 1rem;
            color: var(--primary-navy);
            font-size: 1.25rem;
            font-weight: var(--font-semibold);
            line-height: 1.4;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(100, 196, 147, 0.15);
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            position: relative;
        }
        
        .mvp-service-title:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--primary-green);
        }
        
        .mvp-service-card-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin: 0.5rem 0 0.25rem;
        }
        
        .mvp-category-badge {
            display: inline-block;
            background: var(--accent-gray);
            color: var(--primary-navy);
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: var(--font-medium);
            line-height: 1.1;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
        }
        
        .mvp-category-badge:hover {
            background: var(--primary-green);
            color: var(--white);
            border-color: var(--primary-green);
        }
        
        .mvp-service-content {
            color: var(--text-body);
            margin: 0.5rem 0 1.5rem;
            line-height: 1.7;
            font-size: 0.9375rem;
            font-weight: var(--font-regular);
            flex-grow: 1;
            opacity: 0.9;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            hyphens: auto;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            padding: 0.5rem 0;
        }
        
        .mvp-service-price {
            font-weight: var(--font-bold);
            color: var(--primary-green);
            font-size: 1.125rem;
            letter-spacing: -0.01em;
            margin: 0.5rem 0 0;
            display: inline-flex;
            align-items: center;
            background: rgba(100, 196, 147, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }
        
        .mvp-service-duration {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: var(--font-regular);
            margin-top: 0.25rem;
        }
        
        .mvp-service-duration svg {
            width: 14px;
            height: 14px;
            fill: var(--primary-green);
        }
        
        /* Buttons */
        .mvp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: var(--font-medium);
            line-height: 1.5;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            min-width: 100px;
            text-align: center;
        }

        .mvp-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Primary Button */
        .mvp-btn-primary {
            background-color: var(--primary-green);
            color: white;
            border: 1px solid var(--primary-green);
            transition: all 0.2s ease;
        }
        
        .mvp-btn-primary:hover {
            background-color: #4a9f78;
            border-color: #4a9f78;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Secondary Button */
        .mvp-btn-secondary {
            background-color: var(--primary-navy);
            color: white;
            border: 1px solid var(--primary-navy);
            transition: all 0.2s ease;
        }
        
        .mvp-btn-secondary:hover {
            background-color: #0a1426;
            border-color: #0a1426;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Outline Button */
        .mvp-btn-outline {
            background-color: transparent;
            color: var(--primary-navy);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .mvp-btn-outline:hover {
            background-color: rgba(12, 25, 48, 0.05);
            border-color: var(--primary-green);
            color: var(--primary-green);
            transform: translateY(-1px);
        }

        /* Danger Button */
        .mvp-btn-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            transition: all 0.2s ease;
        }
        
        .mvp-btn-danger:hover {
            background-color: #fecaca;
            border-color: #fca5a5;
            color: #b91c1c;
            transform: translateY(-1px);
        }
        
        /* Text Button */
        .mvp-btn-text {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            transition: all 0.2s ease;
            border-radius: 4px;
        }
        
        .mvp-btn-text:hover {
            color: var(--primary-green);
            background: rgba(100, 196, 147, 0.1);
            transform: translateY(-1px);
        }
        
        /* Button Sizes */
        .mvp-btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8125rem;
        }
        
        /* Button with icon */
        .mvp-btn i {
            margin-right: 0.5rem;
            font-size: 0.9em;
            line-height: 1;
        }

        /* Form Actions */
        .mvp-form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
            justify-content: flex-end;
        }

        .mvp-no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
            background: var(--accent-gray);
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        /* Animation for service items */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .service-item {
            animation: fadeInUp 0.3s ease-out forwards;
            opacity: 0;
        }
        
        /* Add staggered animation delay */
        .service-item:nth-child(1) { animation-delay: 0.1s; }
        .service-item:nth-child(2) { animation-delay: 0.15s; }
        .service-item:nth-child(3) { animation-delay: 0.2s; }
        .service-item:nth-child(4) { animation-delay: 0.25s; }
        .service-item:nth-child(5) { animation-delay: 0.3s; }
        .service-item:nth-child(6) { animation-delay: 0.35s; }
            line-height: 1.6;
            font-size: 0.9375rem;
        }
        
        /* Category badges */
        .mvp-category-badge {
            display: inline-block;
            background: var(--accent-gray);
            color: var(--primary-navy);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Card Styles */
        .mvp-service-manager-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(12, 25, 48, 0.08);
            overflow: hidden;
            margin: 0 0 2rem;
            border: 1px solid rgba(100, 196, 147, 0.15);
        }
        
        .mvp-card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: var(--accent-gray);
        }
        
        .mvp-card-title {
            margin: 0;
            color: var(--primary-navy);
            font-size: 1.25rem;
            font-weight: var(--font-semibold);
        }
        
        .mvp-service-list {
            padding: 1.5rem;
        }
        
        .mvp-service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
        }
        
        .service-item {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
            transition: all 0.2s ease;
        }
        
        .service-item:hover {
            border-color: var(--primary-green);
            box-shadow: 0 4px 12px rgba(100, 196, 147, 0.1);
        }
        
        .service-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .service-item-title {
            font-size: 1rem;
            font-weight: var(--font-semibold);
            color: var(--primary-navy);
            margin: 0;
            line-height: 1.4;
        }
        
        .service-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .service-item-content {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .service-item-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }
        
        .service-item-price {
            color: var(--primary-green);
            font-weight: var(--font-semibold);
        }
        
        /* Form styles */
        .mvp-form-container {
            background: var(--white);
            padding: 0;
            border-bottom: 1px solid var(--border-color);
            background: var(--accent-gray);
        }
        
        .mvp-category-list li {
            padding: 10px;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Loading state */
        .mvp-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: mvp-spin 1s ease-in-out infinite;
        }
        
        @keyframes mvp-spin {
            to { transform: rotate(360deg); }
        }
        
        .mvp-section {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .mvp-section-title {
            font-size: 1.1rem;
            margin: 0 0 1.25rem;
            color: var(--primary-navy);
            font-weight: var(--font-semibold);
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .mvp-add-category {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            max-width: 400px;
            align-items: center;
        }
        
        .mvp-add-category .mvp-form-control {
            flex: 1;
            min-width: 0;
            margin: 0;
            height: 40px;
            padding: 0.5rem 0.875rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease;
        }
        
        .mvp-add-category .mvp-form-control:focus {
            border-color: var(--primary-green);
            outline: none;
            box-shadow: 0 0 0 2px rgba(100, 196, 147, 0.2);
        }
        
        #mvp-add-category {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        #mvp-add-category .dashicons {
            margin: 0;
            width: 20px;
            height: 20px;
            font-size: 20px;
        }
        
        .mvp-category-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .mvp-category-list li {
            background: var(--white);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            padding: 0.25rem 0.75rem 0.25rem 1rem;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
        }
        
        .mvp-category-list li:hover {
            border-color: var(--primary-green);
            background: rgba(100, 196, 147, 0.05);
        }
        
        .mvp-category-actions {
            display: flex;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }
        
        .mvp-category-actions .mvp-btn {
            width: 24px;
            height: 24px;
            min-width: auto;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .mvp-category-actions .mvp-btn i {
            font-size: 14px;
            line-height: 1;
        }
    ";
    wp_add_inline_style('mvp-style', $custom_css);

    // Enqueue custom scripts
    wp_enqueue_script(
        'mvp-script',
        $plugin_url . 'assets/js/script.js',
        array('jquery', 'select2'),
        '2.0',
        true
    );

    // Get current business ID
    $business_id = mvp_get_current_business_id();
    
    // Localize script with AJAX URL, nonce, and business ID
    wp_localize_script('mvp-script', 'mvp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mvp_nonce'),
        'business_id' => $business_id,
        'are_you_sure' => __('Are you sure you want to delete this service?', 'service-manager')
    ));
}
add_action('wp_enqueue_scripts', 'mvp_enqueue_scripts');


/* ======================
   AJAX: ADD/UPDATE SERVICE
   ====================== */
function mvp_add_service() {
    check_ajax_referer('mvp_nonce', 'nonce');
    
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : mvp_get_current_business_id();
    
    if ($business_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid business context'));
        return;
    }

    $title = sanitize_text_field($_POST['title']);
    $desc  = wp_kses_post($_POST['description']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $categories = isset($_POST['categories']) ? array_map('intval', (array)$_POST['categories']) : array();

    // If editing, verify service belongs to current business
    if ($service_id > 0) {
        $service_business_id = intval(get_post_meta($service_id, '_business_id', true));
        if ($service_business_id !== $current_business_id) {
            wp_send_json_error(['message' => 'Permission denied: This service does not belong to your business.']);
        }
    }

    if (empty($title) || empty($desc)) {
        wp_send_json_error(array('message' => 'Title and description are required.'));
    }

    $post_data = array(
        'post_title' => $title,
        'post_content' => $desc,
        'post_status' => 'publish',
        'post_type' => 'service'
    );

    if ($service_id > 0) {
        $post_data['ID'] = $service_id;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    // Save business ID with consistent meta key
    update_post_meta($post_id, '_business_id', $business_id);
    
    // Persist additional meta fields if provided
    if (isset($_POST['price'])) {
        update_post_meta($post_id, '_service_price', sanitize_text_field($_POST['price']));
    }
    if (isset($_POST['duration'])) {
        update_post_meta($post_id, '_service_duration', sanitize_text_field($_POST['duration']));
    }
    // Featured flag: make sure it's saved as 1 or 0
    $is_featured = isset($_POST['is_featured']) && intval($_POST['is_featured']) === 1 ? 1 : 0;
    update_post_meta($post_id, '_is_featured', $is_featured);

    // Set categories if any
    if (!empty($categories)) {
        $term_ids = array();
        foreach ($categories as $category_id) {
            // Verify category exists and belongs to this business
            if (term_exists($category_id, 'service_category')) {
                $term_business_id = intval(get_term_meta($category_id, '_business_id', true));
                if ($term_business_id === $current_business_id) {
                    $term_ids[] = $category_id;
                }
            }
        }
        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, 'service_category');
        }
    }

    // Get the updated service data
    $service = get_post($post_id);
    $service_cats = get_the_terms($post_id, 'service_category');
    $categories_html = '';
    
    if (!empty($service_cats) && !is_wp_error($service_cats)) {
        foreach ($service_cats as $cat) {
            $categories_html .= sprintf('<span class="mvp-category-badge">%s</span>', esc_html($cat->name));
        }
    }

    wp_send_json_success(array(
        'id'         => $post_id,
        'title'      => esc_html($service->post_title),
        'desc'       => wp_kses_post($service->post_content),
        'categories' => $categories_html,
        'is_new'     => ($service_id === 0)
    ));
}
add_action('wp_ajax_mvp_add_service', 'mvp_add_service');


/* ======================
   AJAX: DELETE SERVICE
   ====================== */
function mvp_delete_service() {
    check_ajax_referer('mvp_nonce', 'nonce');
    
    // Get business ID from request or current context
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : mvp_get_current_business_id();
    
    // Validate business ID
    if ($business_id <= 0) {
        wp_send_json_error('Invalid business context. Please select a business first.');
    }
    
    // Verify user has access to this business
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $owner_id = get_post_meta($business_id, '_business_owner_id', true);
        
        if ($owner_id != $current_user->ID && !current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to manage services for this business.');
        }
    } else {
        wp_send_json_error('You must be logged in to perform this action.');
    }

    $id = intval($_POST['id']);
    if (get_post_type($id) === 'service') {
        // Verify the service belongs to the current business
        $service_business_id = get_post_meta($id, '_business_id', true);
        if ($service_business_id != $business_id) {
            wp_send_json_error('You do not have permission to delete this service.');
        }
        
        wp_delete_post($id, true);
        wp_send_json_success(array('id' => $id));
    }

    wp_send_json_error('Failed to delete service.');
}
add_action('wp_ajax_mvp_delete_service', 'mvp_delete_service');


/* ======================
   USER SHORTCODE
   ====================== */
function mvp_user_services_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'category' => '', // Comma-separated category slugs
        'limit' => -1,    // Number of services to show (-1 for all)
        'columns' => 3,   // Number of columns in grid
        'business_id' => 0, // Specific business ID (optional)
    ), $atts, 'user_services');
    
    // Get business ID from attribute or current context
    $business_id = !empty($atts['business_id']) ? intval($atts['business_id']) : mvp_get_current_business_id();
    
    // If no business ID is found, show an error
    if ($business_id <= 0) {
        return '<div class="error-message">' . __('No business context found. Please specify a business ID or select a business first.', 'service-manager') . '</div>';
    }

    // Build query args
    $args = array(
        'post_type' => 'service',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_business_id',
                'value' => $business_id,
                'compare' => '='
            )
        )
    );

    // Filter by category if specified
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'service_category',
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['category'])),
            ),
        );
    }

    // Get services
    $services = get_posts($args);
    
    // Get categories for filter — if a business is resolved, only show that business's categories
    $term_query = array(
        'taxonomy' => 'service_category',
        'hide_empty' => true,
    );
    if ($resolved_business_id) {
        $term_query['meta_query'] = array(
            array(
                'key' => '_business_id',
                'value' => $resolved_business_id,
                'compare' => '='
            )
        );
    }
    $categories = get_terms($term_query);

    ob_start(); 
    ?>
    <div class="mvp-services" data-business-id="<?php echo esc_attr($business_id); ?>">
        <?php if (!empty($categories) && !is_wp_error($categories)): ?>
        <div class="mvp-category-filter">
            <select id="mvp-category-filter" class="mvp-form-control">
                <option value=""><?php esc_html_e('All Categories', 'service-manager'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="mvp-services-grid" style="--columns: <?php echo absint($atts['columns']); ?>">
            <?php if ($services): ?>
                <?php foreach ($services as $service): 
                    $service_cats = get_the_terms($service->ID, 'service_category');
                    $category_classes = '';
                    $category_data = '';
                    
                    if (!empty($service_cats) && !is_wp_error($service_cats)) {
                        $category_slugs = array();
                        foreach ($service_cats as $cat) {
                            $category_slugs[] = $cat->slug;
                        }
                        $category_classes = implode(' ', array_map(function($slug) { 
                            return 'category-' . sanitize_html_class($slug); 
                        }, $category_slugs));
                        $category_data = 'data-categories="' . esc_attr(implode(',', $category_slugs)) . '"';
                    }

                    // Fetch price and duration meta so we can display them on the public shortcode
                    $price = get_post_meta($service->ID, '_service_price', true);
                    $duration = get_post_meta($service->ID, '_service_duration', true);
                ?>
                    <div class="mvp-card service-item <?php echo esc_attr($category_classes); ?>" 
                         <?php echo $category_data; ?>>
                        <div class="mvp-card-inner">
                            <h3 class="mvp-service-title"><?php echo esc_html($service->post_title); ?></h3>
                            <div class="mvp-service-content">
                                <?php echo wpautop(wp_kses_post($service->post_content)); ?>
                            </div>

                            <?php if (!empty($price)): ?>
                                <div class="mvp-service-price"><?php echo '₱' . esc_html($price); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($duration)): ?>
                                <div class="mvp-service-duration"><?php echo esc_html($duration); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($service_cats) && !is_wp_error($service_cats)): ?>
                                <div class="mvp-service-categories">
                                    <?php foreach ($service_cats as $cat): ?>
                                        <span class="mvp-category-badge"><?php echo esc_html($cat->name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mvp-no-results">
                    <p><?php esc_html_e('No services found. Please check back later.', 'service-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mvp-category-filter').on('change', function() {
            var selectedCategory = $(this).val();
            
            $('.service-item').each(function() {
                var $item = $(this);
                var itemCategories = $item.data('categories') ? $item.data('categories').split(',') : [];
                
                if (!selectedCategory || itemCategories.includes(selectedCategory)) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('user_services', 'mvp_user_services_shortcode');


/* ======================
   AJAX: ADD/UPDATE CATEGORY
   ====================== */
function mvp_handle_category() {
    check_ajax_referer('mvp_nonce', 'nonce');
    
    // Get business ID from request or current context
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : mvp_get_current_business_id();
    
    // Validate business ID
    if ($business_id <= 0) {
        wp_send_json_error('Invalid business context. Please select a business first.');
    }
    
    // Verify user has access to this business
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $owner_id = get_post_meta($business_id, '_business_owner_id', true);
        
        if ($owner_id != $current_user->ID && !current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to manage categories for this business.');
        }
    } else {
        wp_send_json_error('You must be logged in to perform this action.');
    }
    
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $response = array('success' => false);
    
    if ($action === 'add') {
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error('Category name is required.');
        }
        

        // Create the term
        // Check if a category with the same name exists for this business
        $existing_terms = get_terms(array(
            'taxonomy' => 'service_category',
            'hide_empty' => false,
            'name' => $name,
        ));

        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            foreach ($existing_terms as $et) {
                $et_business = intval(get_term_meta($et->term_id, '_business_id', true));
                if ($et_business === $current_business_id) {
                    wp_send_json_error('A category with this name already exists in your business.');
                }
            }
        }

        $term = wp_insert_term($name, 'service_category');
        
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }
        
        // Add business_id as term meta
        update_term_meta($term['term_id'], '_business_id', $current_business_id);
        
        $term_data = get_term($term['term_id'], 'service_category');
        $response = array(
            'success' => true,
            'term_id' => $term['term_id'],
            'name' => $term_data->name,
            'slug' => $term_data->slug,
            'count' => 0,
            'business_id' => $current_business_id
        );
        
    } elseif ($action === 'delete') {
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        
        if (!$term_id) {
            wp_send_json_error('Invalid category ID.');
        }
        
        // Verify this category belongs to the current business
        $term_business_id = intval(get_term_meta($term_id, '_business_id', true));
        if ($term_business_id !== $current_business_id) {
            wp_send_json_error('Permission denied: This category does not belong to your business.');
        }
        
        $result = wp_delete_term($term_id, 'service_category');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $response = array('success' => true);
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_mvp_handle_category', 'mvp_handle_category');

/* ======================
   MANAGER SHORTCODE
   ====================== */
function mvp_manager_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="mvp-login-required">' . 
               '<p>' . __('Please log in to manage services.', 'service-manager') . '</p>' .
               wp_login_form(array('echo' => false)) .
               '</div>';
    }
    
    // Get the current business ID
    $business_id = mvp_get_current_business_id();
    
    // If no business ID is found, show an error
    if ($business_id <= 0) {
        return '<div class="error-message">' . __('No business context found. Please select a business first.', 'service-manager') . '</div>';
    }

    // Get services for the current business
    $services = get_posts(array(
        'post_type' => 'service',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_business_id',
                'value' => $business_id,
                'compare' => '='
            )
        )
    ));

    // Double-check service ownership and filter out any that don't belong
    $services = array_filter($services, function($service) use ($current_business_id) {
        $service_business_id = intval(get_post_meta($service->ID, '_business_id', true));
        return $service_business_id === $current_business_id;
    });

    // Get categories for this business (term meta _business_id)
    $categories = get_terms(array(
        'taxonomy' => 'service_category',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => '_business_id',
                'value' => $current_business_id,
                'compare' => '='
            )
        )
    ));

    // Filter out categories from other businesses (double-check)
    $categories = array_filter($categories, function($term) use ($current_business_id) {
        $term_business_id = intval(get_term_meta($term->term_id, '_business_id', true));
        return $term_business_id === $current_business_id;
    });

    ob_start(); ?>
    <div class="mvp-service-manager-card" data-business-id="<?php echo esc_attr($business_id); ?>">
        <div class="mvp-card-header">
            <h2 class="mvp-card-title"><?php _e('Service Manager', 'service-manager'); ?></h2>
            <button type="button" class="mvp-btn mvp-btn-primary" id="mvp-toggle-form">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add New Service', 'service-manager'); ?>
            </button>
        </div>
        
        <div class="mvp-card-body">
            <!-- Add/Edit Form -->
            <div class="mvp-form-container ubf-v3-container" id="mvp-form-container" style="display: none;">
                <form id="mvp-service-form" class="ubf-v3-form">
                    <input type="hidden" name="action" value="mvp_add_service">
                    <?php wp_nonce_field('mvp_nonce', 'mvp_nonce'); ?>
                    <input type="hidden" name="service_id" id="mvp-service-id" value="">
                    
                    <div class="mvp-form-group">
                        <label for="mvp-service-title"><?php _e('Service Title', 'service-manager'); ?> *</label>
                        <input type="text" id="mvp-service-title" name="title" class="mvp-form-control" required>
                    </div>
                    
                    <div class="mvp-form-group">
                        <label for="mvp-service-description"><?php _e('Description', 'service-manager'); ?> *</label>
                        <textarea id="mvp-service-description" name="description" class="mvp-form-control" rows="5" required></textarea>
                    </div>
                    
                    <div class="mvp-form-group">
                        <label for="mvp-service-price"><?php _e('Price', 'service-manager'); ?></label>
                        <input type="text" id="mvp-service-price" name="price" class="mvp-form-control" placeholder="e.g. 1200.00">
                    </div>

                    <div class="mvp-form-group">
                        <label for="mvp-service-duration"><?php _e('Duration', 'service-manager'); ?></label>
                        <input type="text" id="mvp-service-duration" name="duration" class="mvp-form-control" placeholder="e.g. 30 mins">
                    </div>

                    <div class="mvp-form-group">
                        <label for="mvp-service-featured">
                            <input type="checkbox" id="mvp-service-featured" name="is_featured" value="1"> <?php _e('Mark as featured', 'service-manager'); ?>
                        </label>
                    </div>
                    
                    <div class="mvp-form-group">
                        <label for="mvp-service-categories"><?php _e('Categories', 'service-manager'); ?></label>
                        <select id="mvp-service-categories" name="categories[]" class="mvp-form-control" multiple="multiple">
                            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mvp-form-actions">
                    <button type="submit" class="mvp-btn mvp-btn-primary" id="mvp-submit-btn">
                        <?php _e('Save Service', 'service-manager'); ?>
                    </button>
                    <button type="button" class="mvp-btn mvp-btn-secondary" id="mvp-cancel-edit">
                        <?php _e('Cancel', 'service-manager'); ?>
                    </button>
                </div>
                </form>
            </div>

            <style>
                /* Tiny UBF v3 tweaks for service manager form */
                #mvp-form-container.ubf-v3-container { padding: 16px; }
                #mvp-form-container .ubf-v3-form .mvp-form-control { padding: 10px; border:1px solid #e6eaef; border-radius:10px; }
                
                /* Service category badges */
                .service-item-categories {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 4px;
                    margin: 8px 16px 0;
                }
                
                .service-category-badge {
                    display: inline-block;
                    background-color: #e9f5f0;
                    color: #2e7d5f;
                    font-size: 11px;
                    font-weight: 500;
                    padding: 2px 8px;
                    border-radius: 10px;
                    white-space: nowrap;
                    border: 1px solid #d1e7dd;
                }
                
                .service-item {
                    padding-bottom: 16px;
                }
                
                .service-item-content {
                    margin-top: 12px;
                }
            </style>

        <!-- Category Management -->
        <div class="mvp-category-management">
            <h3><?php _e('Manage Categories', 'service-manager'); ?></h3>
            <div class="mvp-section">
                <h3 class="mvp-section-title"><?php _e('Categories', 'service-manager'); ?></h3>
                
                <div class="mvp-add-category">
                    <input type="text" id="mvp-new-category" class="mvp-form-control" 
                           placeholder="<?php esc_attr_e('Add new category', 'service-manager'); ?>">
                    <button id="mvp-add-category" class="mvp-btn mvp-btn-primary">
                        <i class="dashicons dashicons-plus"></i>
                    </button>
                </div>
                
                <ul class="mvp-category-list">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <span><?php echo esc_html($category->name); ?></span>
                            <div class="mvp-category-actions">
                                <button class="mvp-delete-category mvp-btn mvp-btn-sm mvp-btn-text" 
                                        data-id="<?php echo $category->term_id; ?>"
                                        title="<?php esc_attr_e('Delete', 'service-manager'); ?>">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Services List -->
        <div class="mvp-services-list">
            <h3><?php _e('Your Services', 'service-manager'); ?></h3>
            
            <?php if ($services): ?>
                <div class="mvp-service-filters">
                    <select id="mvp-filter-category" class="mvp-form-control" style="width: 250px; display: inline-block;">
                        <option value=""><?php _e('All Categories', 'service-manager'); ?></option>
                        <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="text" id="mvp-search-services" class="mvp-form-control" 
                           style="width: 250px; display: inline-block; margin-left: 10px;" 
                           placeholder="<?php esc_attr_e('Search services...', 'service-manager'); ?>">
                </div>

                <div id="mvp-service-list" class="mvp-service-grid">
                    <?php foreach ($services as $service): 
                        $service_cats = get_the_terms($service->ID, 'service_category');
                        $category_ids = array();
                        $category_names = array();
                        
                        if (!empty($service_cats) && !is_wp_error($service_cats)) {
                            foreach ($service_cats as $cat) {
                                $category_ids[] = $cat->term_id;
                                $category_names[] = $cat->name;
                            }
                        }
                        
                        $price = get_post_meta($service->ID, '_service_price', true);
                        $duration = get_post_meta($service->ID, '_service_duration', true);
                    ?>
                        <div class="service-item" 
                             data-id="<?php echo $service->ID; ?>"
                             data-categories='<?php echo json_encode($category_ids); ?>'>
                            <div class="service-item-header">
                                <h3 class="service-item-title"><?php echo esc_html($service->post_title); ?></h3>
                                <div class="service-item-actions">
                                    <button class="mvp-edit-service mvp-btn mvp-btn-sm mvp-btn-outline" 
                                            data-id="<?php echo $service->ID; ?>"
                                            data-title="<?php echo esc_attr($service->post_title); ?>"
                                            data-description="<?php echo esc_attr($service->post_content); ?>"
                                            data-price="<?php echo esc_attr($price); ?>"
                                            data-duration="<?php echo esc_attr($duration); ?>"
                                            data-categories='<?php echo json_encode($category_ids); ?>'>
                                        <?php _e('Edit', 'service-manager'); ?>
                                    </button>
                                    <button class="mvp-delete mvp-btn mvp-btn-sm mvp-btn-danger" 
                                            data-id="<?php echo $service->ID; ?>">
                                        <?php _e('Delete', 'service-manager'); ?>
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($category_names)): ?>
                                <div class="service-item-categories">
                                    <?php foreach ($category_names as $cat_name): ?>
                                        <span class="service-category-badge"><?php echo esc_html($cat_name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="service-item-content">
                                <?php echo wp_trim_words(wp_strip_all_tags($service->post_content), 20, '...'); ?>
                            </div>
                            <div class="service-item-meta">
                                <?php if ($price): ?>
                                    <span class="service-item-price"><?php echo esc_html($price); ?></span>
                                <?php endif; ?>
                                <?php if ($duration): ?>
                                    <span class="service-item-duration"><?php echo esc_html($duration); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mvp-no-services">
                    <p><?php _e('No services found. Add your first service using the form above.', 'service-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize Select2
        $('#mvp-service-categories').select2({
            placeholder: '<?php echo esc_js(__('Select categories', 'service-manager')); ?>',
            allowClear: true,
            width: '100%'
        });

        // Toggle add service form
        $('#mvp-toggle-form').on('click', function() {
            $('#mvp-form-container').slideToggle();
            $('#mvp-service-form')[0].reset();
            $('#mvp-service-id').val('');
            $('#mvp-submit-btn').text('<?php echo esc_js(__('Add Service', 'service-manager')); ?>');
        });

        // Cancel edit
        $('#mvp-cancel-edit').on('click', function() {
            $('#mvp-form-container').slideUp();
            $('#mvp-service-form')[0].reset();
            $('#mvp-service-id').val('');
        });

        // Add/Edit Service
        $('#mvp-service-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.prop('disabled', true).html('<span class="mvp-loading"></span> ' + $submitBtn.text());
            
            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvp_add_service',
                    nonce: mvp_ajax.nonce,
                    business_id: mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'),
                    title: $('#mvp-service-title').val(),
                    description: $('#mvp-service-description').val(),
                    price: $('#mvp-service-price').val(),
                    duration: $('#mvp-service-duration').val(),
                    is_featured: $('#mvp-service-featured').is(':checked') ? 1 : 0,
                    categories: $('#mvp-service-categories').val() || [],
                    service_id: $('#mvp-service-id').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to show updated list
                    } else {
                        alert(response.data || '<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Edit Service
        $(document).on('click', '.mvp-edit-service', function() {
            var $card = $(this).closest('.service-item');
            var id = $card.data('id');
            var title = $(this).data('title');
            var description = $(this).data('description');
            var price = $(this).data('price');
            var duration = $(this).data('duration');
            var categories = $(this).data('categories') || [];
            
            $('#mvp-service-id').val(id);
            $('#mvp-service-title').val(title);
            $('#mvp-service-description').val(description);
            $('#mvp-service-price').val(price);
            $('#mvp-service-duration').val(duration);
            $('#mvp-service-categories').val(categories).trigger('change');
            
            $('#mvp-form-container').slideDown();
            $('html, body').animate({
                scrollTop: $('#mvp-form-container').offset().top - 20
            }, 500);
            
            $('#mvp-submit-btn').text('<?php echo esc_js(__('Update Service', 'service-manager')); ?>');
        });

        // Delete Service
        $(document).on('click', '.mvp-delete', function() {
            if (!confirm(mvp_ajax.are_you_sure)) {
                return false;
            }
            
            var $button = $(this);
            var id = $button.data('id');
            
            if (!id) return;
            
            $button.prop('disabled', true).html('<span class="mvp-loading"></span>');
            
            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvp_delete_service',
                    nonce: mvp_ajax.nonce,
                    business_id: mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'),
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('.service-item').fadeOut(300, function() {
                            $(this).remove();
                            // If no services left, show message
                            if ($('#mvp-service-list .service-item').length === 0) {
                                $('#mvp-service-list').html('<div class="mvp-no-services"><p><?php echo esc_js(__('No services found. Add your first service using the form above.', 'service-manager')); ?></p></div>');
                            }
                        });
                    } else {
                        alert('<?php echo esc_js(__('Failed to delete service. Please try again.', 'service-manager')); ?>');
                        $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>');
                }
            });
        });

        // Add Category
        $('#mvp-add-category').on('click', function() {
            var $input = $('#mvp-new-category');
            var name = $input.val().trim();
            
            if (!name) {
                alert('<?php echo esc_js(__('Please enter a category name.', 'service-manager')); ?>');
                return;
            }
            
            var $button = $(this);
            var originalText = $button.html();
            
            $button.prop('disabled', true).html('<span class="mvp-loading"></span>');
            
            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvp_handle_category',
                    nonce: mvp_ajax.nonce,
                    business_id: mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'),
                    action_type: 'add',
                    name: name
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to update category lists
                    } else {
                        alert(response.data || '<?php echo esc_js(__('Failed to add category. Please try again.', 'service-manager')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });

        // Delete Category
        $(document).on('click', '.mvp-delete-category', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this category? This will not delete the services in this category.', 'service-manager')); ?>')) {
                return false;
            }
            
            var $button = $(this);
            var $li = $button.closest('li');
            var termId = $button.data('id');
            
            if (!termId) return;
            
            $button.prop('disabled', true).html('<span class="mvp-loading"></span>');
            
            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvp_handle_category',
                    nonce: mvp_ajax.nonce,
                    business_id: mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'),
                    action_type: 'delete',
                    term_id: termId
                },
                success: function(response) {
                    if (response.success) {
                        $li.fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Remove from all select2 dropdowns
                        $('select').each(function() {
                            $(this).find('option[value="' + termId + '"]').remove();
                            $(this).trigger('change');
                        });
                    } else {
                        alert('<?php echo esc_js(__('Failed to delete category. Please try again.', 'service-manager')); ?>');
                        $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>');
                }
            });
        });

        // Filter services by category
        $('#mvp-filter-category').on('change', function() {
            var categoryId = $(this).val();
            
            $('.service-item').each(function() {
                var $item = $(this);
                var itemCategories = $item.data('categories') || [];
                
                if (!categoryId || itemCategories.includes(parseInt(categoryId))) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        });

        // Search services
        var searchTimer;
        $('#mvp-search-services').on('input', function() {
            clearTimeout(searchTimer);
            var $input = $(this);
            
            searchTimer = setTimeout(function() {
                var searchTerm = $input.val().toLowerCase();
                
                if (!searchTerm) {
                    $('.service-item').show();
                    return;
                }
                
                $('.service-item').each(function() {
                    var $item = $(this);
                    var title = $item.find('.mvp-service-title').text().toLowerCase();
                    var content = $item.find('.mvp-service-content').text().toLowerCase();
                    
                    if (title.includes(searchTerm) || content.includes(searchTerm)) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
            }, 300);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('manager_add_service', 'mvp_manager_shortcode');

// Include upload debug functionality for testing
if (defined('WP_DEBUG') && WP_DEBUG) {
    include_once plugin_dir_path(__FILE__) . 'test-upload-debug.php';
}


/* Debug Info: Services Data
add_action('wp_footer', function() {
    if (!is_user_logged_in()) return;
    
    $business_id = payndle_get_current_business_id();
    $services = get_posts(array(
        'post_type' => 'service',
        'meta_key' => '_business_id',
        'meta_value' => $business_id,
        'posts_per_page' => -1
    ));
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<!-- Debug Info:';
        echo 'Business ID: ' . esc_html($business_id) . "\n";
        echo 'Services Found: ' . count($services) . "\n";
        foreach ($services as $service) {
            echo $service->post_title . ' (ID: ' . $service->ID . ")\n";
        }
        echo '-->';
    }
}); */