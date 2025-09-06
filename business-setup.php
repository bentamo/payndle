<?php
/**
 * Business Setup Template
 * Description: A comprehensive business setup form for new business owners/managers
 * Shortcode: [business_setup]
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BusinessSetup {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('business_setup', [$this, 'render_business_setup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register AJAX actions
        add_action('wp_ajax_submit_business_setup', [$this, 'submit_business_setup_ajax']);
        add_action('wp_ajax_nopriv_submit_business_setup', [$this, 'submit_business_setup_ajax']);
        add_action('wp_ajax_check_business_exists', [$this, 'check_business_exists_ajax']);
        add_action('wp_ajax_nopriv_check_business_exists', [$this, 'check_business_exists_ajax']);
    }
    
    public function init() {
        // Ensure required tables and post types exist
        $this->ensure_business_post_type();
    }
    
    /**
     * Register business post type for storing business information
     */
    private function ensure_business_post_type() {
        if (!post_type_exists('payndle_business')) {
            register_post_type('payndle_business', [
                'label' => 'Business Profiles',
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'capability_type' => 'post',
                'supports' => ['title', 'custom-fields'],
                'meta_box_cb' => false
            ]);
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'business_setup')) {
            // Enqueue Google Fonts
            wp_enqueue_style('google-fonts-business', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], null);
            
            // Enqueue Font Awesome
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
            
            // Enqueue custom CSS
            wp_enqueue_style(
                'business-setup-css',
                plugin_dir_url(__FILE__) . 'assets/css/business-setup.css',
                [],
                '2.0.0' // Brand guide implementation
            );
            
            // Enqueue custom JS
            wp_enqueue_script(
                'business-setup-js',
                plugin_dir_url(__FILE__) . 'assets/js/business-setup.js',
                ['jquery'],
                '2.0.0', // Clean modern implementation
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('business-setup-js', 'businessSetupAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('business_setup_nonce'),
                'messages' => [
                    'success' => 'Your business has been set up successfully! You can now add services and staff.',
                    'error' => 'Something went wrong. Please try again.',
                    'validation_error' => 'Please fill in all required fields.',
                    'email_error' => 'Please enter a valid email address.',
                    'phone_error' => 'Please enter a valid phone number.'
                ]
            ]);
        }
    }
    
    /**
     * Check if business already exists for current user
     */
    public function check_business_exists_ajax() {
        check_ajax_referer('business_setup_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to access this feature.']);
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user already has a business
        $existing_business = get_posts([
            'post_type' => 'payndle_business',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_business_owner_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing_business)) {
            $business = $existing_business[0];
            $business_data = [
                'business_name' => get_post_meta($business->ID, '_business_name', true),
                'business_description' => get_post_meta($business->ID, '_business_description', true),
                'business_category' => get_post_meta($business->ID, '_business_category', true),
                'business_email' => get_post_meta($business->ID, '_business_email', true),
                'business_phone' => get_post_meta($business->ID, '_business_phone', true),
                'business_address' => get_post_meta($business->ID, '_business_address', true),
                'business_city' => get_post_meta($business->ID, '_business_city', true),
                'business_state' => get_post_meta($business->ID, '_business_state', true),
                'business_zip_code' => get_post_meta($business->ID, '_business_zip_code', true),
                'business_website' => get_post_meta($business->ID, '_business_website', true),
                'business_hours' => get_post_meta($business->ID, '_business_hours', true),
                'social_facebook' => get_post_meta($business->ID, '_social_facebook', true),
                'social_instagram' => get_post_meta($business->ID, '_social_instagram', true),
                'social_twitter' => get_post_meta($business->ID, '_social_twitter', true)
            ];
            wp_send_json_success(['exists' => true, 'business_data' => $business_data]);
        } else {
            wp_send_json_success(['exists' => false]);
        }
    }
    
    /**
     * Handle business setup form submission
     */
    public function submit_business_setup_ajax() {
        check_ajax_referer('business_setup_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to set up a business.']);
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Validate required fields - updated for step-by-step form
        $required_fields = ['business_name', 'business_type', 'business_email', 'business_phone'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => 'Please fill in all required fields.']);
                return;
            }
        }
        
        // Sanitize input data - updated field names
        $business_data = [
            'business_name' => sanitize_text_field($_POST['business_name']),
            'business_description' => sanitize_textarea_field($_POST['business_description'] ?? ''),
            'business_type' => sanitize_text_field($_POST['business_type']), // Changed from business_category
            'business_email' => sanitize_email($_POST['business_email']),
            'business_phone' => sanitize_text_field($_POST['business_phone']),
            'business_website' => esc_url_raw($_POST['business_website'] ?? ''),
            'business_address' => sanitize_textarea_field($_POST['business_address'] ?? ''),
            'business_city' => sanitize_text_field($_POST['business_city'] ?? ''),
            'business_state' => sanitize_text_field($_POST['business_state'] ?? ''),
            'business_zip' => sanitize_text_field($_POST['business_zip'] ?? ''), // Changed from business_zip_code
            'business_hours' => sanitize_textarea_field($_POST['business_hours'] ?? ''),
            'business_services' => sanitize_textarea_field($_POST['business_services'] ?? '')
        ];
        
        // Validate email
        if (!is_email($business_data['business_email'])) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
            return;
        }
        
        try {
            // Check if user already has a business
            $existing_business = get_posts([
                'post_type' => 'payndle_business',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => '_business_owner_id',
                        'value' => $user_id,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_business)) {
                // Update existing business
                $business_id = $existing_business[0]->ID;
                wp_update_post([
                    'ID' => $business_id,
                    'post_title' => $business_data['business_name'],
                    'post_status' => 'publish'
                ]);
            } else {
                // Create new business post
                $business_id = wp_insert_post([
                    'post_title' => $business_data['business_name'],
                    'post_type' => 'payndle_business',
                    'post_status' => 'publish',
                    'post_author' => $user_id
                ]);
                
                if (is_wp_error($business_id)) {
                    wp_send_json_error(['message' => 'Failed to create business profile.']);
                    return;
                }
                
                // Set business owner
                update_post_meta($business_id, '_business_owner_id', $user_id);
            }
            
            // Save all business meta data
            foreach ($business_data as $key => $value) {
                update_post_meta($business_id, '_' . $key, $value);
            }
            
            // Set setup completion status
            update_post_meta($business_id, '_business_setup_completed', current_time('mysql'));
            update_post_meta($business_id, '_business_status', 'active');
            
            wp_send_json_success([
                'message' => 'Your business has been set up successfully!',
                'business_id' => $business_id,
                'redirect_url' => home_url('/services-setup/') // Can be customized
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred while setting up your business.']);
        }
    }
    
    /**
     * Render the business setup form
     */
    public function render_business_setup($atts) {
        $atts = shortcode_atts([
            'redirect_after_setup' => '',
            'show_existing_data' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="business-setup-container">
            <div class="business-setup-header">
                <h1 class="setup-title">Business Setup</h1>
                <p class="setup-subtitle">Let's create your business profile step by step</p>
            </div>
            
            <!-- Progress Indicator -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="step-indicators">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Business Info</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Contact Details</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Additional Info</div>
                    </div>
                </div>
            </div>
            
            <form id="business-setup-form" class="business-setup-form">
                <?php wp_nonce_field('business_setup_nonce', 'business_setup_nonce'); ?>
                
                <!-- Step 1: Business Information -->
                <div class="form-step active" data-step="1">
                    <div class="step-header">
                        <h2>Business Information</h2>
                        <p>Tell us about your business and what services you provide</p>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input type="text" id="business_name" name="business_name" class="form-input" placeholder="Enter your business name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_type" class="form-label">Business Type</label>
                            <select id="business_type" name="business_type" class="form-select" required>
                                <option value="">Select your business type</option>
                                <option value="barbershop">Barbershop</option>
                                <option value="hair_salon">Hair Salon</option>
                                <option value="beauty_salon">Beauty Salon</option>
                                <option value="spa">Spa & Wellness</option>
                                <option value="nail_salon">Nail Salon</option>
                                <option value="massage_therapy">Massage Therapy</option>
                                <option value="fitness">Fitness & Training</option>
                                <option value="consulting">Consulting</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="automotive">Automotive</option>
                                <option value="education">Education & Training</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_description" class="form-label">Business Description</label>
                            <textarea id="business_description" name="business_description" class="form-textarea" rows="4" placeholder="Describe your business and the services you offer"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Contact Information -->
                <div class="form-step" data-step="2">
                    <div class="step-header">
                        <h2>Contact Information</h2>
                        <p>How can customers reach you?</p>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="business_email" class="form-label">Business Email</label>
                            <input type="email" id="business_email" name="business_email" class="form-input" placeholder="business@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="business_phone" name="business_phone" class="form-input" placeholder="(555) 123-4567" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_website" class="form-label">Website (Optional)</label>
                            <input type="url" id="business_website" name="business_website" class="form-input" placeholder="https://www.yourwebsite.com">
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Additional Details -->
                <div class="form-step" data-step="3">
                    <div class="step-header">
                        <h2>Additional Details</h2>
                        <p>Help customers find you and understand your business better</p>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="business_address" class="form-label">Business Address</label>
                            <textarea id="business_address" name="business_address" class="form-textarea" rows="3" placeholder="Enter your complete business address"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_city" class="form-label">City</label>
                            <input type="text" id="business_city" name="business_city" class="form-input" placeholder="City">
                        </div>
                        
                        <div class="form-group">
                            <label for="business_state" class="form-label">State/Province</label>
                            <input type="text" id="business_state" name="business_state" class="form-input" placeholder="State or Province">
                        </div>
                        
                        <div class="form-group">
                            <label for="business_zip" class="form-label">ZIP/Postal Code</label>
                            <input type="text" id="business_zip" name="business_zip" class="form-input" placeholder="ZIP or Postal Code">
                        </div>
                        
                        <div class="form-group">
                            <label for="business_hours" class="form-label">Business Hours (Optional)</label>
                            <textarea id="business_hours" name="business_hours" class="form-textarea" rows="3" placeholder="e.g., Mon-Fri: 9AM-6PM, Sat: 9AM-4PM, Sun: Closed"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_services" class="form-label">Main Services (Optional)</label>
                            <textarea id="business_services" name="business_services" class="form-textarea" rows="3" placeholder="List your main services (e.g., Haircuts, Coloring, Styling)"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Initial form actions (will be updated by JavaScript) -->
                <div class="form-actions">
                    <div></div> <!-- Spacer for first step -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-next">Next Step</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the class
new BusinessSetup();
?>
