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

    // If the Payndle helper exists, prefer that for consistent context across the plugin
    if (function_exists('payndle_get_current_business_id')) {
        $pid = intval(payndle_get_current_business_id());
        if ($pid > 0) {
            return $pid;
        }
    }

    // If we're on a business single (or on a page that has a _business_id meta), use that context
    global $post;
    if ( isset( $post ) && ! empty( $post ) ) {
        if ( property_exists( $post, 'post_type' ) && $post->post_type === 'payndle_business' ) {
            return intval( $post->ID );
        }

        // Some pages may store a business reference in post meta (e.g. parent page or template)
        $meta_business = get_post_meta( $post->ID, '_business_id', true );
        if ( ! empty( $meta_business ) ) {
            return intval( $meta_business );
        }
    }

    // If user is logged in, try to get their primary business
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        // Query for businesses where this user is the owner
        $args = array(
            'post_type' => 'payndle_business',
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
        // Map taxonomy capabilities so non-admin business owners can create categories via the frontend.
        // By default, wp_insert_term requires 'manage_terms' (usually 'manage_categories'), which
        // contributors/authors do not have. Mapping to 'edit_posts' allows them to manage their own taxonomy terms
        // while our AJAX handler still enforces business ownership.
        'capabilities' => array(
            'manage_terms' => 'edit_posts',
            'edit_terms'   => 'edit_posts',
            'delete_terms' => 'edit_posts',
            'assign_terms' => 'edit_posts',
        ),
    ));
}
add_action('init', 'mvp_register_service_post_type');


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
    /* Modern compact header styles (shared with admin pages) */
    .elite-cuts-header { background: var(--bg-white); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1rem; box-shadow: var(--box-shadow); border-left: 4px solid var(--primary-green); }
    .elite-cuts-header.modern-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding: 0.6rem 0.9rem; }
    .elite-cuts-header .brand { display:flex; align-items:center; gap:0.75rem; }
    .elite-cuts-header .brand-icon, .brand-icon { width:46px; height:46px; border-radius:10px; background: linear-gradient(180deg, rgba(100,196,147,0.12), rgba(79,176,122,0.06)); display:flex; align-items:center; justify-content:center; font-size:18px; }
    .brand-title { font-size:1.05rem; font-weight:700; color:var(--text-dark); }
    .brand-sub { font-size:0.85rem; color:var(--text-muted); margin-top:2px; }
    .header-actions { margin-left:auto; }

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

        /* Ensure the Cancel button (edit form) has readable white text */
        #mvp-cancel-edit,
        button#mvp-cancel-edit,
        .mvp-btn-secondary#mvp-cancel-edit {
            color: #ffffff !important;
        }

        /* Force service/item price text to primary green (higher specificity to override inline styles) */
        .wrap.elite-cuts-admin .service-item .service-item-price,
        .wrap.elite-cuts-admin .service-item .mvp-service-price,
        .mvp-service-manager-card .service-item-price,
        .mvp-service-manager-card .mvp-service-price {
            color: var(--primary-green) !important;
            font-weight: 700 !important;
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
            box-shadow: 0 4px 12px rgba(16, 255, 132, 0.88);
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
        
        /* ======================
           RESPONSIVE DESIGN
           ====================== */
        
        /* Service Manager Layout Responsiveness */
        .mvp-card-body .mvp-manager-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        
        .mvp-manager-sidebar {
            flex: 0 0 320px;
            max-width: 320px;
            display: flex;
            flex-direction: column;
        }
        
        .mvp-manager-content {
            flex: 1;
            min-width: 300px;
        }
        
        /* Tablet (768px - 1199px) */
        @media (max-width: 1199px) {
            .mvp-manager-sidebar {
                flex: 0 0 280px;
                max-width: 280px;
            }
            
            .elite-cuts-header.modern-header {
                padding: 0.8rem;
            }
            
            .brand-title {
                font-size: 1rem;
            }
            
            .brand-sub {
                font-size: 0.8rem;
            }
            
            .mvp-service-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
            }
        }
        
        /* Small Tablet / Large Mobile (768px - 991px) */
        @media (max-width: 991px) {
            .elite-cuts-header.modern-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .header-actions button {
                width: 100%;
            }
            
            .mvp-manager-sidebar {
                flex: 0 0 100%;
                max-width: 100%;
                order: 1;
                margin-bottom: 16px;
            }
            
            /* Hide the form container on mobile (collapsed by default anyway) */
            .mvp-manager-sidebar .mvp-form-container {
                order: 2;
            }
            
            /* Show categories section first in sidebar on mobile */
            .mvp-manager-sidebar .mvp-section {
                order: 1;
                margin-top: 0;
                margin-bottom: 16px;
                padding: 14px;
                background: var(--bg-white);
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }
            
            /* Make category input and button responsive */
            .mvp-manager-sidebar .mvp-add-category {
                display: flex;
                gap: 8px;
                max-width: 100%;
                margin-bottom: 14px;
            }
            
            .mvp-manager-sidebar .mvp-add-category .mvp-form-control {
                flex: 1;
                height: 44px;
            }
            
            .mvp-manager-sidebar #mvp-add-category {
                width: 44px;
                height: 44px;
                flex-shrink: 0;
            }
            
            .mvp-manager-sidebar .mvp-section-title {
                font-size: 1.05rem;
                margin-bottom: 12px;
                padding-bottom: 10px;
            }
            
            .mvp-manager-sidebar .mvp-category-list {
                max-height: 180px;
                overflow-y: auto;
                gap: 8px;
            }
            
            .mvp-manager-sidebar .mvp-category-list li {
                font-size: 13px;
                padding: 8px 12px 8px 14px;
            }
            
            .mvp-manager-content {
                flex: 0 0 100%;
                order: 2;
            }
            
            .mvp-section {
                padding: 1rem;
            }
            
            /* Service cards adjustment */
            .service-item {
                min-height: auto !important;
            }
            
            .mvp-service-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
                gap: 12px !important;
            }
        }
        
        /* Mobile (480px - 767px) */
        @media (max-width: 767px) {
            .mvp-services {
                padding: 0.75rem 0.5rem;
            }
            
            .elite-cuts-header {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }
            
            .brand-icon {
                width: 36px !important;
                height: 36px !important;
                font-size: 16px !important;
            }
            
            .brand-title {
                font-size: 0.95rem;
            }
            
            .brand-sub {
                font-size: 0.75rem;
                display: none; /* Hide subtitle on mobile */
            }
            
            .mvp-card-body {
                padding: 12px !important;
            }
            
            /* Form controls */
            .mvp-form-control {
                font-size: 14px;
                padding: 0.5rem;
            }
            
            /* Filters section */
            .mvp-filters-wrapper {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            
            .mvp-filters-controls {
                width: 100%;
                flex-direction: column !important;
            }
            
            .mvp-service-count {
                text-align: center;
                padding: 8px;
                background: var(--accent-gray);
                border-radius: 6px;
            }
            
            #mvp-filter-category,
            #mvp-search-services {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            /* Service grid - single column on mobile */
            .mvp-service-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            
            /* Service item cards */
            .service-item {
                min-height: auto !important;
                overflow: visible;
            }
            
            .service-item > div:first-child {
                min-height: auto !important;
                display: flex;
                flex-direction: column;
            }
            
            .service-item > div:first-child > div:first-child {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
                padding: 14px 12px !important;
            }
            
            /* Service title - ensure visible */
            .service-item-title,
            .mvp-service-title {
                font-size: 16px !important;
                line-height: 1.4 !important;
                margin-bottom: 8px !important;
                word-break: break-word;
            }
            
            /* Service content/description - ensure fully visible */
            .service-item-content,
            .mvp-service-content {
                font-size: 14px !important;
                line-height: 1.6 !important;
                color: var(--text-body) !important;
                margin-bottom: 12px !important;
                display: block !important;
                -webkit-line-clamp: unset !important;
                overflow: visible !important;
                max-height: none !important;
                white-space: normal !important;
                word-break: break-word;
            }
            
            /* Category badges - ensure visible */
            .service-category-badge,
            .mvp-category-badge {
                font-size: 12px !important;
                padding: 4px 10px !important;
                display: inline-flex !important;
                align-items: center;
                margin-right: 6px;
                margin-bottom: 6px;
            }
            
            .service-item-categories {
                margin-bottom: 10px !important;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            
            /* Price and duration in same row on mobile */
            .service-item .service-item-price,
            .service-item .service-item-duration {
                display: inline-block;
            }
            
            .service-item > div:first-child > div:first-child > div:nth-child(2) {
                width: 100% !important;
                flex-direction: row !important;
                justify-content: flex-start !important;
                align-items: center !important;
                gap: 12px !important;
            }
            
            .service-item-price {
                font-size: 16px !important;
                font-weight: 700 !important;
                padding: 6px 12px !important;
            }
            
            .service-item-duration {
                font-size: 13px !important;
            }
            
            /* Service actions */
            .service-item > div:first-child > div:last-child {
                flex-direction: column;
                align-items: stretch !important;
                padding: 14px 12px !important;
                gap: 10px !important;
            }
            
            .service-item > div:first-child > div:last-child > div:first-child {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .service-item > div:first-child > div:last-child button {
                flex: 1;
                width: 100%;
            }
            
            /* Category badges */
            .service-category-badge,
            .mvp-category-badge {
                font-size: 11px !important;
                padding: 3px 6px !important;
            }
            
            /* Form in sidebar */
            .mvp-form-container {
                padding: 12px;
            }
            
            .mvp-form-group {
                margin-bottom: 12px;
            }
            
            .mvp-form-group label {
                font-size: 13px;
                margin-bottom: 4px;
            }
            
            /* Add category input */
            .mvp-add-category {
                flex-direction: column;
                max-width: 100% !important;
            }
            
            .mvp-add-category .mvp-form-control {
                width: 100% !important;
            }
            
            #mvp-add-category {
                width: 100% !important;
            }
            
            /* Category list */
            .mvp-category-list {
                gap: 6px;
            }
            
            .mvp-category-list li {
                font-size: 12px;
            }
            
            /* Buttons */
            .mvp-btn {
                font-size: 13px;
                padding: 0.45rem 0.9rem;
            }
            
            .mvp-btn-sm {
                font-size: 12px;
                padding: 0.35rem 0.7rem;
            }
            
            /* Form actions */
            .mvp-form-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .mvp-form-actions button {
                width: 100%;
            }
        }
        
        /* Extra Small Mobile (< 480px) */
        @media (max-width: 479px) {
            .mvp-services {
                padding: 0.5rem 0.25rem;
            }
            
            .elite-cuts-header {
                padding: 0.6rem;
            }
            
            .elite-cuts-header .brand {
                gap: 0.5rem;
            }
            
            .brand-icon {
                width: 32px !important;
                height: 32px !important;
                font-size: 14px !important;
            }
            
            .brand-title {
                font-size: 0.9rem;
            }
            
            .mvp-card-body {
                padding: 8px !important;
            }
            
            /* Service cards */
            .service-item {
                border-radius: 8px;
                font-size: 13px;
            }
            
            .service-item-title {
                font-size: 14px !important;
            }
            
            .service-item-content {
                font-size: 13px !important;
                line-height: 1.5 !important;
            }
            
            .service-item-price {
                font-size: 14px !important;
            }
            
            .service-item-duration {
                font-size: 11px !important;
            }
            
            /* Reduce padding everywhere */
            .service-item > div:first-child > div:first-child {
                padding: 8px 10px !important;
            }
            
            .service-item > div:first-child > div:last-child {
                padding: 6px 8px !important;
            }
            
            /* Section styling */
            .mvp-section {
                padding: 0.75rem;
            }
            
            .mvp-section-title {
                font-size: 1rem;
            }
            
            /* Category list - allow scrolling */
            .mvp-category-list {
                max-height: 150px;
            }
            
            .mvp-manager-sidebar .mvp-category-list {
                max-height: 150px;
            }
            
            .mvp-manager-sidebar .mvp-category-list li {
                font-size: 12px;
                padding: 6px 10px 6px 12px;
            }
            
            /* Input fields */
            input.mvp-form-control,
            textarea.mvp-form-control,
            select.mvp-form-control {
                font-size: 14px;
            }
            
            /* Dashicons sizing */
            .dashicons {
                width: 18px;
                height: 18px;
                font-size: 18px;
            }
        }
        
        /* Fix for customer-facing services view */
        @media (max-width: 767px) {
            .mvp-services-grid {
                grid-template-columns: 1fr !important;
            }
            
            .mvp-category-filter {
                max-width: 100%;
            }
            
            .mvp-service-title {
                font-size: 1.1rem;
            }
            
            .mvp-service-content {
                font-size: 0.875rem;
            }
        }
        
        /* Landscape mobile optimization */
        @media (max-width: 767px) and (orientation: landscape) {
            .service-item {
                min-height: auto !important;
            }
            
            .service-item-content {
                max-height: 80px;
                overflow: hidden;
            }
        }
        
        /* Ensure icons are properly sized */
        .dashicons,
        .dashicons-before:before {
            width: 20px;
            height: 20px;
            font-size: 20px;
            line-height: 1;
        }
        
        @media (max-width: 767px) {
            .dashicons,
            .dashicons-before:before {
                width: 18px;
                height: 18px;
                font-size: 18px;
            }
        }
        
        /* Fix text overflow issues */
        .service-item-title,
        .mvp-service-title,
        .brand-title {
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }
        
        /* Improve touch targets on mobile */
        @media (max-width: 767px) {
            button,
            .mvp-btn,
            a.mvp-btn {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        /* ======================
           IMPROVED ICON & BUTTON VISIBILITY
           ====================== */
        
        /* Ensure all dashicons are properly sized and visible */
        .dashicons,
        .dashicons-before:before {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
        }
        
        button .dashicons,
        .mvp-btn .dashicons,
        i.dashicons {
            flex-shrink: 0;
            pointer-events: none;
        }
        
        /* Service item action buttons - improved visibility */
        .service-item .mvp-edit-service,
        .service-item .mvp-delete {
            white-space: nowrap;
            overflow: visible;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-height: 36px;
            padding: 8px 14px;
        }
        
        .service-item > div:first-child > div:last-child {
            min-height: 54px;
            align-items: center;
            display: flex;
            padding: 10px 12px;
        }
        
        /* Category badges in service cards */
        .service-category-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 12px;
            background: var(--accent-gray);
            color: var(--primary-navy);
            border: 1px solid var(--border-color);
            white-space: nowrap;
            line-height: 1.4;
            font-weight: 500;
        }
        
        .service-item-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin-bottom: 8px;
        }
        
        /* Ensure emojis in category badges are visible */
        .service-category-badge:before,
        .mvp-category-badge:before {
            display: inline;
            margin-right: 4px;
        }
        
        /* Price and duration - no wrapping */
        .service-item-price,
        .service-item-duration {
            white-space: nowrap;
        }
        
        /* Prevent button content clipping */
        button,
        .mvp-btn,
        a.mvp-btn {
            overflow: visible;
        }
        
        /* Mobile improvements for service cards */
        @media (max-width: 767px) {
            .service-item .mvp-edit-service,
            .service-item .mvp-delete {
                min-height: 44px;
                padding: 10px 16px;
                font-size: 14px;
                font-weight: 500;
                display: flex !important;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
            }
            
            .service-item > div:first-child > div:last-child {
                padding: 12px;
                gap: 10px;
                flex-direction: column;
                align-items: stretch;
            }
            
            .service-item > div:first-child > div:last-child > div:first-child {
                width: 100%;
                display: flex;
                gap: 10px;
                flex-direction: column;
            }
            
            .service-item .mvp-edit-service,
            .service-item .mvp-delete {
                flex: 1;
                width: 100%;
            }
            
            /* Ensure delete button is visible */
            .service-item .mvp-delete {
                background-color: #fee2e2 !important;
                color: #b91c1c !important;
                border: 1px solid #fecaca !important;
            }
            
            .service-item .mvp-delete:hover {
                background-color: #fecaca !important;
            }
        }
        
        /* Fix for sidebar category section (keep original) */
        .mvp-manager-sidebar .mvp-section {
            margin-top: 18px;
            order: 2;
        }
        
        .mvp-manager-sidebar .mvp-form-container {
            order: 1;
        }
        
        .mvp-manager-sidebar .mvp-section-title {
            font-size: 1.1rem;
            margin: 0 0 1.25rem;
            color: var(--primary-navy);
            font-weight: var(--font-semibold);
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .mvp-manager-sidebar .mvp-add-category {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            max-width: 400px;
            align-items: center;
        }
        
        .mvp-manager-sidebar .mvp-add-category .mvp-form-control {
            flex: 1;
            min-width: 0;
            margin: 0;
            height: 40px;
            padding: 0.5rem 0.875rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease;
        }
        
        .mvp-manager-sidebar #mvp-add-category {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .mvp-manager-sidebar #mvp-add-category .dashicons {
            margin: 0;
            width: 20px;
            height: 20px;
            font-size: 20px;
        }
        
        .mvp-manager-sidebar .mvp-category-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .mvp-manager-sidebar .mvp-category-list li {
            background: var(--white);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            padding: 0.25rem 0.75rem 0.25rem 1rem;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
        }
        
        .mvp-manager-sidebar .mvp-category-list li:hover {
            border-color: var(--primary-green);
            background: rgba(100, 196, 147, 0.05);
        }
        
        .mvp-manager-sidebar .mvp-category-actions {
            display: flex;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }
        
        .mvp-manager-sidebar .mvp-category-actions .mvp-btn {
            width: 24px;
            height: 24px;
            min-width: auto;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .mvp-manager-sidebar .mvp-category-actions .mvp-btn i {
            font-size: 14px;
            line-height: 1;
        }
        
        /* Table responsiveness (if any tables exist) */
        @media (max-width: 767px) {
            table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
            }
            
            table thead {
                display: none;
            }
            
            table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }
            
            table td {
                display: block;
                text-align: right;
                padding: 0.5rem 1rem;
                border-bottom: 1px solid var(--border-color);
            }
            
            table td:last-child {
                border-bottom: none;
            }
            
            table td:before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: var(--primary-navy);
            }
        }
        
        /* Smooth scrolling for category list */
        .mvp-category-list {
            scroll-behavior: smooth;
        }
        
        /* Ensure images are responsive */
        .mvp-services img,
        .mvp-service-manager-card img,
        .service-item img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        /* Fix for very long words in service content */
        .service-item-content,
        .mvp-service-content {
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }
        
        /* Prevent horizontal scroll on mobile */
        @media (max-width: 767px) {
            .mvp-services,
            .mvp-service-manager-card,
            .mvp-card-body {
                overflow-x: hidden;
            }
            
            * {
                max-width: 100%;
            }
            
            /* Better spacing for small screens */
            .mvp-manager-layout {
                gap: 12px !important;
            }
        }
        
        /* Loading state improvements */
        .mvp-loading {
            border-width: 2px;
        }
        
        @media (max-width: 767px) {
            .mvp-loading {
                width: 16px;
                height: 16px;
                border-width: 2px;
            }
        }
        
        /* Select2 dropdown responsiveness */
        @media (max-width: 767px) {
            .select2-container {
                width: 100% !important;
            }
            
            .select2-dropdown {
                font-size: 14px;
            }
            
            .select2-results__option {
                padding: 10px 12px;
            }
        }
        
        /* Improved form layout on mobile */
        @media (max-width: 767px) {
            .mvp-form-group {
                width: 100%;
            }
            
            .mvp-form-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
            }
            
            .mvp-form-control {
                width: 100%;
                box-sizing: border-box;
            }
        }
        
        /* No services message */
        .mvp-no-services {
            padding: 40px 20px !important;
        }
        
        @media (max-width: 767px) {
            .mvp-no-services {
                padding: 30px 15px !important;
                font-size: 14px;
            }
        }
        
        /* Category badge responsiveness */
        @media (max-width: 767px) {
            .service-item-categories {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none; /* IE and Edge */
            }
            
            .service-item-categories::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
        }
        
        /* Elite button responsive styling */
        @media (max-width: 767px) {
            .elite-button,
            button.elite-button {
                font-size: 14px;
                padding: 10px 16px;
                width: 100%;
            }
        }
        
        /* Emoji and icon visibility improvements */
        @media (max-width: 767px) {
            /* Ensure emojis in service titles are visible */
            .mvp-service-title:after,
            .service-item-title:after {
                font-size: 18px;
            }
            
            /* Category badge emojis */
            .service-category-badge::before,
            .mvp-category-badge::before {
                margin-right: 4px;
            }
            
            /* Ensure service card icons are visible */
            .mvp-card .dashicons,
            .service-item .dashicons {
                width: 18px;
                height: 18px;
                font-size: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Ensure all text is readable on mobile */
        @media (max-width: 767px) {
            .service-item * {
                line-height: 1.5;
            }
            
            /* Make sure nothing gets hidden by overflow */
            .service-item,
            .service-item > *,
            .service-item > * > * {
                overflow: visible;
            }
            
            /* Ensure proper word wrapping */
            .service-item h3,
            .service-item p,
            .service-item div {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
        }
        
        /* Button text must be visible and centered */
        .mvp-btn,
        .mvp-edit-service,
        .mvp-delete {
            text-align: center;
            vertical-align: middle;
            line-height: normal;
        }
        
        @media (max-width: 767px) {
            .mvp-btn,
            .mvp-edit-service,
            .mvp-delete {
                font-size: 14px !important;
                letter-spacing: 0.3px;
            }
        }
        
        /* Ensure service count is visible */
        @media (max-width: 767px) {
            .mvp-service-count {
                font-size: 14px !important;
                font-weight: 600;
            }
        }
        
        /* Make sure filter controls are visible */
        @media (max-width: 767px) {
            #mvp-filter-category,
            #mvp-search-services {
                font-size: 14px;
                height: 44px;
                padding: 0 12px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }
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
    
    // Get business ID from request or current context
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : mvp_get_current_business_id();
    
    // Validate business ID
    if ($business_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid business context. Please select a business first.'));
    }
    
    // Verify user has access to this business
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $owner_id = get_post_meta($business_id, '_business_owner_id', true);
        
        if ($owner_id != $current_user->ID && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to manage services for this business.'));
        }
    } else {
        wp_send_json_error(array('message' => 'You must be logged in to perform this action.'));
    }

    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $desc  = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $categories = isset($_POST['categories']) ? array_map('intval', (array)$_POST['categories']) : array();

    if (empty($title) || empty($desc)) {
        wp_send_json_error(array('message' => 'Title and description are required.'));
        return;
    }

    $post_data = array(
        'post_title'   => $title,
        'post_content' => $desc,
        'post_status'  => 'publish',
        'post_type'    => 'service',
    );

    if ($service_id > 0) {
        // Verify the service belongs to the current business
        $service_business_id = get_post_meta($service_id, '_business_id', true);
        if ($service_business_id != $business_id) {
            wp_send_json_error(array('message' => 'You do not have permission to update this service.'));
        }
        
        $post_data['ID'] = $service_id;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    // Store business ID with the service
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

    // Set categories if any (only allow categories owned by this business)
    if (!empty($categories)) {
        $term_ids = array();
        foreach ((array)$categories as $category_id) {
            if (!term_exists((int)$category_id, 'service_category')) {
                continue;
            }
            $owner_bid = intval(get_term_meta((int)$category_id, '_business_id', true));
            if ($owner_bid === $business_id || current_user_can('manage_options')) {
                $term_ids[] = (int)$category_id;
            }
        }
        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, array_unique($term_ids), 'service_category');
        } else {
            // Clear categories if none allowed
            wp_set_object_terms($post_id, array(), 'service_category');
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
            return;
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
        'post_type'      => 'service',
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
    
    // Get all categories for filter that belong to this business
    $categories = get_terms(array(
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'meta_query' => array(
            array(
                'key' => '_business_id',
                'value' => $business_id,
                'compare' => '='
            )
        )
    ));

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
    
    // Helper: temporarily grant minimal cap needed for taxonomy ops
    if (!function_exists('mvp_temp_grant_edit_posts')) {
        function mvp_temp_grant_edit_posts($allcaps, $caps, $args, $user) {
            $allcaps['edit_posts'] = true;
            return $allcaps;
        }
    }

    if ($action === 'add') {
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error('Category name is required.');
        }
        
        // First attempt to insert the term normally
        // Temporarily grant capability for this operation
        add_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10, 4);
        $term = wp_insert_term($name, 'service_category');
        
        if (is_wp_error($term)) {
            // Handle duplicate names gracefully
            if ($term->get_error_code() === 'term_exists') {
                $existing_data = $term->get_error_data();
                $existing_id = is_array($existing_data) && isset($existing_data['term_id'])
                    ? intval($existing_data['term_id'])
                    : intval($existing_data);
                if ($existing_id > 0) {
                    // If the existing term belongs to this business, reuse it
                    $existing_bid = intval(get_term_meta($existing_id, '_business_id', true));
                    if ($existing_bid === $business_id) {
                        $term_id = $existing_id;
                    } else {
                        // Otherwise, create a new term with a unique slug
                        $base_slug = sanitize_title($name);
                        $unique_slug = $base_slug . '-' . $business_id;
                        $term_retry = wp_insert_term($name, 'service_category', array('slug' => $unique_slug));
                        if (is_wp_error($term_retry)) {
                            remove_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10);
                            wp_send_json_error($term_retry->get_error_message());
                        }
                        $term_id = intval($term_retry['term_id']);
                    }
                } else {
                    // Fallback if we can't get the existing ID
                    $base_slug = sanitize_title($name);
                    $unique_slug = $base_slug . '-' . $business_id;
                    $term_retry = wp_insert_term($name, 'service_category', array('slug' => $unique_slug));
                    if (is_wp_error($term_retry)) {
                        remove_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10);
                        wp_send_json_error($term_retry->get_error_message());
                    }
                    $term_id = intval($term_retry['term_id']);
                }
            } else {
                remove_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10);
                wp_send_json_error($term->get_error_message());
            }
        } else {
            $term_id = intval($term['term_id']);
        }
        // Remove temporary capability grant
        remove_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10);

        // Link category to this business (only add if not already set)
        $existing_bid_meta = get_term_meta($term_id, '_business_id', true);
        if (empty($existing_bid_meta)) {
            add_term_meta($term_id, '_business_id', $business_id, true);
        }
        
        $term_data = get_term($term_id, 'service_category');
        $response = array(
            'success' => true,
            'term_id' => $term_id,
            'name' => $term_data ? $term_data->name : $name,
            'slug' => $term_data ? $term_data->slug : sanitize_title($name),
            'count' => 0
        );
        
    } elseif ($action === 'delete') {
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        
        if (!$term_id) {
            wp_send_json_error('Invalid category ID.');
        }
        // Only allow deleting categories owned by this business
        $term_business_id = intval(get_term_meta($term_id, '_business_id', true));
        if ($term_business_id !== $business_id && ! current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to delete this category.');
        }
        
        add_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10, 4);
        $result = wp_delete_term($term_id, 'service_category');
        remove_filter('user_has_cap', 'mvp_temp_grant_edit_posts', 10);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $response = array('success' => true);
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_mvp_handle_category', 'mvp_handle_category');
add_action('wp_ajax_nopriv_mvp_handle_category', 'mvp_handle_category');

/* ======================
   MANAGER SHORTCODE
   ====================== */
function mvp_manager_shortcode() {
    // Accept optional business_id attribute but validate ownership below
    $atts = func_num_args() > 0 ? func_get_arg(0) : array();
    if (!is_array($atts)) { $atts = array(); }
    $atts = shortcode_atts(array(
        'business_id' => 0,
    ), $atts, 'manager_add_service');

    if (!is_user_logged_in()) {
        return '<div class="mvp-login-required">' . 
               '<p>' . __('Please log in to manage services.', 'service-manager') . '</p>' .
               wp_login_form(array('echo' => false)) .
               '</div>';
    }
    
    // Get the current business ID
    $business_id = intval($atts['business_id']) ?: mvp_get_current_business_id();
    
    // If no business ID is found, show an error
    if ($business_id <= 0) {
        return '<div class="error-message">' . __('No business context found. Please select a business first.', 'service-manager') . '</div>';
    }

    // Enforce that only the business owner (or admins) can access this manager
    $current_user = wp_get_current_user();
    $owner_id = intval(get_post_meta($business_id, '_business_owner_id', true));
    if ($owner_id !== intval($current_user->ID) && ! current_user_can('manage_options')) {
        return '<div class="error-message">' . esc_html__('You do not have permission to manage services for this business.', 'service-manager') . '</div>';
    }

    // Get services for the current business
    $services = get_posts(array(
        'post_type'      => 'service',
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

    // Get categories owned by this business
    $categories = get_terms(array(
        'taxonomy' => 'service_category',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => '_business_id',
                'value' => $business_id,
                'compare' => '='
            )
        )
    ));

    ob_start(); ?>
    <div class="wrap elite-cuts-admin">
    <div class="mvp-service-manager-card" data-business-id="<?php echo esc_attr($business_id); ?>">
        <div class="elite-cuts-header modern-header">
            <div class="brand">
                <div class="brand-icon" aria-hidden="true">🛠️</div>
                <div>
                    <div class="brand-title"><?php _e('Service Manager', 'service-manager'); ?></div>
                    <div class="brand-sub"><?php _e('Manage services, categories and availability for your business.', 'service-manager'); ?></div>
                </div>
            </div>
            <div class="header-actions">
                <button type="button" class="elite-button" id="mvp-toggle-form">
                    <?php _e('+ Add Service', 'service-manager'); ?>
                </button>
            </div>
        </div>

        <div class="mvp-card-body" style="padding:18px 20px;">
            <div class="mvp-manager-layout" style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                <!-- Left: Controls -->
                <div class="mvp-manager-sidebar">
                    <!-- Add/Edit Form (collapsed by default) -->
                    <div class="mvp-form-container ubf-v3-container" id="mvp-form-container" style="display: none;">
                        <form id="mvp-service-form" class="ubf-v3-form">
                            <input type="hidden" name="action" value="mvp_add_service">
                            <?php wp_nonce_field('mvp_nonce', 'mvp_nonce'); ?>
                            <input type="hidden" name="service_id" id="mvp-service-id" value="">
                            <input type="hidden" name="business_id" id="mvp-business-id" value="<?php echo esc_attr($business_id); ?>">

                            <div class="mvp-form-group">
                                <label for="mvp-service-title"><?php _e('Service Title', 'service-manager'); ?> *</label>
                                <input type="text" id="mvp-service-title" name="title" class="mvp-form-control" required>
                            </div>

                            <div class="mvp-form-group">
                                <label for="mvp-service-description"><?php _e('Description', 'service-manager'); ?> *</label>
                                <textarea id="mvp-service-description" name="description" class="mvp-form-control" rows="4" required></textarea>
                            </div>

                            <div style="display:flex;gap:8px;">
                                <div style="flex:1;">
                                    <label for="mvp-service-price"><?php _e('Price', 'service-manager'); ?></label>
                                    <input type="text" id="mvp-service-price" name="price" class="mvp-form-control" placeholder="e.g. 1200.00">
                                </div>
                                <div style="flex:1;">
                                    <label for="mvp-service-duration"><?php _e('Duration', 'service-manager'); ?></label>
                                    <input type="text" id="mvp-service-duration" name="duration" class="mvp-form-control" placeholder="e.g. 30 mins">
                                </div>
                            </div>

                            <div class="mvp-form-group" style="margin-top:8px;">
                                <label for="mvp-service-categories"><?php _e('Categories', 'service-manager'); ?></label>
                                <select id="mvp-service-categories" name="categories[]" class="mvp-form-control" multiple="multiple" style="width:100%;">
                                    <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                                <label style="margin:0;">
                                    <input type="checkbox" id="mvp-service-featured" name="is_featured" value="1"> <?php _e('Featured', 'service-manager'); ?>
                                </label>
                            </div>

                            <div class="mvp-form-actions" style="margin-top:12px;display:flex;gap:8px;">
                                <button type="submit" class="mvp-btn mvp-btn-primary" id="mvp-submit-btn"><?php _e('Save Service', 'service-manager'); ?></button>
                                <button type="button" class="mvp-btn mvp-btn-secondary" id="mvp-cancel-edit"><?php _e('Cancel', 'service-manager'); ?></button>
                            </div>
                        </form>
                    </div>

                    <!-- Compact category manager -->
                    <div class="mvp-section" style="margin-top:18px;padding:12px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-white);">
                        <h4 class="mvp-section-title"><?php _e('Categories', 'service-manager'); ?></h4>
                        <div class="mvp-add-category" style="display:flex;gap:8px;margin-top:8px;">
                            <input type="text" id="mvp-new-category" class="mvp-form-control" placeholder="<?php esc_attr_e('Add new category', 'service-manager'); ?>">
                            <button type="button" id="mvp-add-category" class="mvp-btn mvp-btn-primary"><i class="dashicons dashicons-plus"></i></button>
                        </div>

                        <ul class="mvp-category-list" style="list-style:none;padding:0;margin:12px 0 0;max-height:220px;overflow:auto;">
                            <?php foreach ($categories as $category): ?>
                                <li style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                                    <span><?php echo esc_html($category->name); ?></span>
                                    <div class="mvp-category-actions">
                                        <button class="mvp-delete-category mvp-btn mvp-btn-sm mvp-btn-text" data-id="<?php echo $category->term_id; ?>" title="<?php esc_attr_e('Delete', 'service-manager'); ?>">
                                            <i class="dashicons dashicons-trash"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Right: Filters + Grid -->
                <div class="mvp-manager-content">
                    <div class="mvp-filters-wrapper" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;">
                        <div class="mvp-filters-controls" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <select id="mvp-filter-category" class="mvp-form-control" style="width:220px;">
                                <option value=""><?php _e('All Categories', 'service-manager'); ?></option>
                                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <input type="text" id="mvp-search-services" class="mvp-form-control" placeholder="<?php esc_attr_e('Search services...', 'service-manager'); ?>" style="width:260px;">
                        </div>
                        <div class="mvp-service-count" style="color:var(--text-muted);font-size:13px;"><?php echo sprintf(esc_html__('%d services', 'service-manager'), count($services)); ?></div>
                    </div>

                    <?php if ($services): ?>
                        <div id="mvp-service-list" class="mvp-service-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
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
                                <div class="service-item" data-id="<?php echo $service->ID; ?>" data-categories='<?php echo json_encode($category_ids); ?>' style="background:var(--bg-white);border:1px solid var(--border-color);border-radius:10px;padding:0;overflow:visible;min-height:320px;">
                                    <div style="display:flex;flex-direction:column;height:100%;min-height:320px;">
                                        <!-- Use a two-column layout: description (flexible) + meta (fixed width) -->
                                        <div style="padding:10px 14px 12px 14px;display:grid;grid-template-columns:1fr 80px;gap:12px;align-items:start;flex:1;">
                                            <div style="min-width:0;">
                                                <h3 class="service-item-title mvp-service-title" style="margin:0 0 6px;font-size:16px;line-height:1.25;overflow-wrap:break-word;white-space:normal;">
                                                    <?php echo esc_html($service->post_title); ?>
                                                </h3>

                                                <?php if (!empty($category_names)): ?>
                                                    <div class="service-item-categories" style="margin-bottom:8px;display:flex;flex-wrap:wrap;gap:6px;">
                                                        <?php foreach ($category_names as $cat_name): ?>
                                                            <span class="service-category-badge" style="padding:4px 8px;font-size:12px;border-radius:12px;"><?php echo esc_html($cat_name); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="service-item-content mvp-service-content" style="color:var(--text-muted);font-size:14px;margin-bottom:12px;white-space:normal;line-height:1.6;max-height:none;overflow:visible;display:block;-webkit-line-clamp:unset;-webkit-box-orient:unset;">
                                                    <?php echo nl2br(esc_html(wp_strip_all_tags($service->post_content))); ?>
                                                </div>
                                            </div>

                                            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;width:80px;flex:0 0 80px;">
                                                <div style="text-align:right;color:var(--text-body);font-weight:600;">
                                                    <?php if ($price): ?>
                                                        <div class="service-item-price" style="font-weight:700;color:var(--primary-green);font-size:15px;">₱<?php echo esc_html($price); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($duration): ?>
                                                        <div class="service-item-duration" style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?php echo esc_html($duration); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="padding:8px 12px;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;gap:8px;">
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <button class="mvp-edit-service mvp-btn mvp-btn-sm mvp-btn-outline" 
                                                        data-id="<?php echo $service->ID; ?>"
                                                        data-title="<?php echo esc_attr($service->post_title); ?>"
                                                        data-description="<?php echo esc_attr($service->post_content); ?>"
                                                        data-price="<?php echo esc_attr($price); ?>"
                                                        data-duration="<?php echo esc_attr($duration); ?>"
                                                        data-categories='<?php echo json_encode($category_ids); ?>'><?php _e('Edit', 'service-manager'); ?></button>
                                                <button class="mvp-delete mvp-btn mvp-btn-sm mvp-btn-danger" data-id="<?php echo $service->ID; ?>"><?php _e('Delete', 'service-manager'); ?></button>
                                            </div>
                                            <div style="color:var(--text-muted);font-size:12px;">
                                                <span><?php echo sprintf('%s', ''); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mvp-no-services" style="padding:20px;border:1px dashed var(--border-color);border-radius:8px;text-align:center;color:var(--text-muted);">
                            <p><?php _e('No services found. Add your first service using the form on the left.', 'service-manager'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            // focus title after opening
            setTimeout(function(){ $('#mvp-service-title').focus(); }, 320);
        });

        // Cancel edit
        $('#mvp-cancel-edit').on('click', function() {
            $('#mvp-form-container').slideUp();
            $('#mvp-service-form')[0].reset();
            $('#mvp-service-id').val('');
        });

        // Add/Edit Service (AJAX)
        $('#mvp-service-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="mvp-loading"></span> ' + $submitBtn.text());

            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvp_add_service',
                    nonce: mvp_ajax.nonce,
                    business_id: $('#mvp-business-id').val() || mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'),
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
                        location.reload();
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

        // Edit Service: populate form and open
        $(document).on('click', '.mvp-edit-service', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            var title = $btn.data('title');
            var description = $btn.data('description');
            var price = $btn.data('price');
            var duration = $btn.data('duration');
            var categories = $btn.data('categories') || [];

            $('#mvp-service-id').val(id);
            $('#mvp-service-title').val(title);
            $('#mvp-service-description').val(description);
            $('#mvp-service-price').val(price);
            $('#mvp-service-duration').val(duration);
            $('#mvp-service-categories').val(categories).trigger('change');

            $('#mvp-form-container').slideDown();
            $('#mvp-submit-btn').text('<?php echo esc_js(__('Update Service', 'service-manager')); ?>');
            setTimeout(function(){ $('#mvp-service-title').focus(); }, 200);
        });

        // Delete Service
        $(document).on('click', '.mvp-delete', function() {
            if (!confirm(mvp_ajax.are_you_sure)) { return false; }
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
                        $button.closest('.service-item').fadeOut(300, function() { $(this).remove(); });
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
        $('#mvp-add-category').on('click', function(e) {
            e.preventDefault();
            var $input = $('#mvp-new-category');
            var name = $input.val().trim();
            if (!name) { alert('<?php echo esc_js(__('Please enter a category name.', 'service-manager')); ?>'); return; }
            var $button = $(this); var originalText = $button.html();
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
                    if (response.success) { location.reload(); } else { alert(response.data || '<?php echo esc_js(__('Failed to add category. Please try again.', 'service-manager')); ?>'); }
                },
                error: function() { alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>'); },
                complete: function() { $button.prop('disabled', false).html(originalText); }
            });
        });
        
        // Allow Enter key to add category
        $('#mvp-new-category').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#mvp-add-category').click();
            }
        });

        // Delete Category
        $(document).on('click', '.mvp-delete-category', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this category? This will not delete the services in this category.', 'service-manager')); ?>')) { return false; }
            var $button = $(this); var $li = $button.closest('li'); var termId = $button.data('id');
            if (!termId) return; $button.prop('disabled', true).html('<span class="mvp-loading"></span>');
            $.ajax({
                url: mvp_ajax.ajax_url,
                type: 'POST',
                data: { action: 'mvp_handle_category', nonce: mvp_ajax.nonce, business_id: mvp_ajax.business_id || $('.mvp-service-manager-card').data('business-id'), action_type: 'delete', term_id: termId },
                success: function(response) { if (response.success) { $li.fadeOut(300, function(){ $(this).remove(); }); $('select').each(function(){ $(this).find('option[value="'+termId+'"]').remove(); $(this).trigger('change'); }); } else { alert('<?php echo esc_js(__('Failed to delete category. Please try again.', 'service-manager')); ?>'); $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>'); } },
                error: function() { alert('<?php echo esc_js(__('An error occurred. Please try again.', 'service-manager')); ?>'); $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'service-manager')); ?>'); }
            });
        });

        // Filter services by category
        $('#mvp-filter-category').on('change', function() {
            var categoryId = $(this).val();
            $('.service-item').each(function() {
                var $item = $(this);
                var itemCategories = $item.data('categories') || [];
                if (!categoryId || itemCategories.includes(parseInt(categoryId))) { $item.show(); } else { $item.hide(); }
            });
        });

        // Search services (works with multiple title/content class names)
        var searchTimer;
        $('#mvp-search-services').on('input', function() {
            clearTimeout(searchTimer);
            var $input = $(this);
            searchTimer = setTimeout(function() {
                var searchTerm = $input.val().toLowerCase();
                if (!searchTerm) { $('.service-item').show(); return; }
                $('.service-item').each(function() {
                    var $item = $(this);
                    var title = $item.find('.service-item-title, .mvp-service-title').first().text().toLowerCase();
                    var content = $item.find('.service-item-content, .mvp-service-content').first().text().toLowerCase();
                    if (title.includes(searchTerm) || content.includes(searchTerm)) { $item.show(); } else { $item.hide(); }
                });
            }, 260);
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