<?php
/*
Plugin Name: Service Manager Pro
Description: Modern service management system with categories and AJAX interface.
Version: 2.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) exit;

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
        'show_in_rest' => true,
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
            --accent-gray: #F4F4F4;     /* Backgrounds, subtle dividers, dashboard UI */
            
            /* Semantic Colors */
            --primary: var(--primary-green);
            --secondary: var(--primary-navy);
            --accent: var(--primary-green);
            --light: var(--white);
            
            /* Background & Text */
            --bg-light: var(--accent-gray);
            --bg-white: var(--white);
            --text-dark: var(--primary-navy);
            --text-body: #333333;
            --text-muted: #666666;
            
            /* UI Colors */
            --success: var(--primary-green);
            --warning: #FFA726;         /* Orange for warnings */
            --danger: #F44336;          /* Red for errors */
            --border-color: rgba(12, 25, 48, 0.1);
            
            /* Effects */
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
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
        
        .mvp-services, .mvp-manager {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .mvp-card {
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .mvp-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .mvp-form-group {
            margin-bottom: 1rem;
        }
        
        .mvp-form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .mvp-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .mvp-btn-primary {
            background: var(--primary-green);
            color: white;
        }
        
        .mvp-btn-primary:hover {
            background: #4eaf7d;
            box-shadow: 0 4px 8px rgba(100, 196, 147, 0.3);
        }
        
        .mvp-btn-secondary {
            background: var(--primary-navy);
            color: white;
        }
        
        .mvp-btn-secondary:hover {
            background: #0a1427;
            box-shadow: 0 4px 8px rgba(12, 25, 48, 0.2);
        }
        
        .mvp-btn-danger {
            background: #F44336;
            color: white;
        }
        
        .mvp-btn-danger:hover {
            background: #e53935;
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }
        
        .mvp-category-badge {
            display: inline-block;
            background: var(--light-bg);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .mvp-category-selector {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .mvp-service-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        /* Responsive grid for services */
        .mvp-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Form styles */
        .mvp-form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        /* Category management */
        .mvp-category-list {
            list-style: none;
            padding: 0;
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

    // Localize script with AJAX URL and nonce
    wp_localize_script('mvp-script', 'mvp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mvp_nonce'),
        'are_you_sure' => __('Are you sure you want to delete this service?', 'service-manager')
    ));
}
add_action('wp_enqueue_scripts', 'mvp_enqueue_scripts');


/* ======================
   AJAX: ADD/UPDATE SERVICE
   ====================== */
function mvp_add_service() {
    // Debug: log entry and incoming POST for failures
    file_put_contents(plugin_dir_path(__FILE__) . 'service-debug.log', date('Y-m-d H:i:s') . " - mvp_add_service called\nPOST: " . print_r($_POST, true) . "\n\n", FILE_APPEND);
    check_ajax_referer('mvp_nonce', 'nonce');

    $title = sanitize_text_field($_POST['title']);
    $desc  = wp_kses_post($_POST['description']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $categories = isset($_POST['categories']) ? array_map('intval', (array)$_POST['categories']) : array();

    if (empty($title) || empty($desc)) {
        wp_send_json_error(array('message' => 'Title and description are required.'));
    }

    $post_data = array(
        'post_title'   => $title,
        'post_content' => $desc,
        'post_status'  => 'publish',
        'post_type'    => 'service',
    );

    // If we have a service ID, update the existing service
    if ($service_id > 0) {
        $post_data['ID'] = $service_id;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
    file_put_contents(plugin_dir_path(__FILE__) . 'service-debug.log', date('Y-m-d H:i:s') . " - wp_error when creating service: " . $post_id->get_error_message() . "\n", FILE_APPEND);
    wp_send_json_error(array('message' => $post_id->get_error_message()));
    }

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
            if (term_exists($category_id, 'service_category')) {
                $term_ids[] = $category_id;
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
function mvp_user_services_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'category' => '', // Comma-separated category slugs
        'limit' => -1,    // Number of services to show (-1 for all)
        'columns' => 3,   // Number of columns in grid
    ), $atts, 'user_services');

    // Build query args
    $args = array(
        'post_type' => 'service',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish',
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
    
    // Get all categories for filter
    $categories = get_terms(array(
        'taxonomy' => 'service_category',
        'hide_empty' => true,
    ));

    ob_start(); 
    ?>
    <div class="mvp-services">
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
                ?>
                    <div class="mvp-card service-item <?php echo esc_attr($category_classes); ?>" 
                         <?php echo $category_data; ?>>
                        <div class="mvp-card-inner">
                            <h3 class="mvp-service-title"><?php echo esc_html($service->post_title); ?></h3>
                            <div class="mvp-service-content">
                                <?php echo wpautop(wp_kses_post($service->post_content)); ?>
                            </div>
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
    
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $response = array('success' => false);
    
    if ($action === 'add') {
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error('Category name is required.');
        }
        
        $term = wp_insert_term($name, 'service_category');
        
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }
        
        $term_data = get_term($term['term_id'], 'service_category');
        $response = array(
            'success' => true,
            'term_id' => $term['term_id'],
            'name' => $term_data->name,
            'slug' => $term_data->slug,
            'count' => 0
        );
        
    } elseif ($action === 'delete') {
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        
        if (!$term_id) {
            wp_send_json_error('Invalid category ID.');
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

    // Get all services
    $services = get_posts(array(
        'post_type' => 'service',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    // Get all categories
    $categories = get_terms(array(
        'taxonomy' => 'service_category',
        'hide_empty' => false,
    ));

    ob_start(); ?>
    <div class="mvp-manager">
        <div class="mvp-manager-header">
            <h2><?php _e('Service Manager', 'service-manager'); ?></h2>
            <button type="button" class="mvp-btn mvp-btn-primary" id="mvp-toggle-form">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add New Service', 'service-manager'); ?>
            </button>
        </div>

        <!-- Add/Edit Form -->
        <div class="mvp-form-container" id="mvp-form-container" style="display: none;">
            <form id="mvp-service-form">
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

        <!-- Category Management -->
        <div class="mvp-category-management">
            <h3><?php _e('Manage Categories', 'service-manager'); ?></h3>
            <div class="mvp-add-category">
                <input type="text" id="mvp-new-category" class="mvp-form-control" style="width: 300px; display: inline-block;" 
                       placeholder="<?php esc_attr_e('New category name', 'service-manager'); ?>">
                <button type="button" id="mvp-add-category" class="mvp-btn mvp-btn-primary">
                    <?php _e('Add Category', 'service-manager'); ?>
                </button>
            </div>
            
            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                <ul class="mvp-category-list" style="margin-top: 15px;">
                    <?php foreach ($categories as $category): ?>
                        <li data-id="<?php echo $category->term_id; ?>">
                            <span class="mvp-category-name"><?php echo esc_html($category->name); ?></span>
                            <span class="mvp-category-count">(<?php echo $category->count; ?>)</span>
                            <button class="mvp-btn mvp-btn-danger mvp-btn-sm mvp-delete-category" 
                                    data-id="<?php echo $category->term_id; ?>">
                                <?php _e('Delete', 'service-manager'); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
                        $category_classes = '';
                        $category_ids = array();
                        $category_names = array();
                        
                        if (!empty($service_cats) && !is_wp_error($service_cats)) {
                            $category_classes = implode(' ', array_map(function($cat) use (&$category_ids, &$category_names) { 
                                $category_ids[] = $cat->term_id;
                                $category_names[] = $cat->name;
                                return 'category-' . $cat->term_id; 
                            }, $service_cats));
                        }
                    ?>
                        <div class="mvp-card service-item <?php echo esc_attr($category_classes); ?>" 
                             data-id="<?php echo $service->ID; ?>"
                             data-categories='<?php echo json_encode($category_ids); ?>'>
                            <div class="mvp-card-header">
                                <h3 class="mvp-service-title"><?php echo esc_html($service->post_title); ?></h3>
                                <div class="mvp-service-actions">
                                    <button class="mvp-edit-service mvp-btn mvp-btn-sm mvp-btn-secondary" 
                                            data-id="<?php echo $service->ID; ?>"
                                            data-title="<?php echo esc_attr($service->post_title); ?>"
                                            data-description="<?php echo esc_attr($service->post_content); ?>"
                                            data-categories='<?php echo json_encode($category_ids); ?>'>
                                        <?php _e('Edit', 'service-manager'); ?>
                                    </button>
                                    <button class="mvp-delete mvp-btn mvp-btn-sm mvp-btn-danger" 
                                            data-id="<?php echo $service->ID; ?>">
                                        <?php _e('Delete', 'service-manager'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="mvp-service-content">
                                <?php echo wpautop(wp_kses_post($service->post_content)); ?>
                            </div>
                            <?php if (!empty($service_cats) && !is_wp_error($service_cats)): ?>
                                <div class="mvp-service-categories">
                                    <?php foreach ($service_cats as $cat): ?>
                                        <span class="mvp-category-badge"><?php echo esc_html($cat->name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
            var categories = $(this).data('categories') || [];
            
            $('#mvp-service-id').val(id);
            $('#mvp-service-title').val(title);
            $('#mvp-service-description').val(description);
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
