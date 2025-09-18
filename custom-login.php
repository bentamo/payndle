<?php
/**
 * Modern Barbershop Login
 * Custom login page with modern barbershop design
 * Shortcode: [custom_login]
 * Version: 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Modern_Barbershop_Login {
    
    public function __construct() {
        add_shortcode('custom_login', [$this, 'render_login_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_nopriv_ajax_login', [$this, 'ajax_login']);
        add_action('wp_ajax_ajax_login', [$this, 'ajax_login']);
        add_action('wp_ajax_ajax_logout', [$this, 'ajax_logout']);
    }

    public function enqueue_assets() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'custom_login')) {
            // Enqueue Google Fonts
            wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap', [], null);
            
            // Enqueue Font Awesome
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
            
            // Enqueue custom CSS
            wp_enqueue_style(
                'modern-barbershop-login', 
                plugins_url('assets/css/custom-login.css', __FILE__), 
                [], 
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/custom-login.css')
            );
            
            // Enqueue JS with jQuery as a dependency
            wp_enqueue_script(
                'modern-barbershop-login', 
                plugins_url('assets/js/custom-login.js', __FILE__), 
                ['jquery'], 
                filemtime(plugin_dir_path(__FILE__) . 'assets/js/custom-login.js'), 
                true
            );
            
            // Localize the script with data
            wp_localize_script('modern-barbershop-login', 'ajax_login_object', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'redirecturl' => home_url(),
                'loadingmessage' => __('Please wait...', 'payndle'),
                'nonce' => wp_create_nonce('ajax-login-nonce')
            ]);
        }
    }

    public function render_login_form() {
        if (is_user_logged_in()) {
            return $this->render_logged_in_view();
        }
        
        ob_start();
        ?>
        <div class="modern-barbershop">
            <div class="login-hero">
                <div class="login-container">
                    <div class="login-box">
                        <div class="login-header">
                            <h2>Welcome to</h2>
                            <h2 style="color: var(--secondary-color); margin: 0.5rem 0 1rem; font-size: 2.5rem;">Elite Cuts</h2>
                            <p>Sign in to your account</p>
                        </div>
                        
                        <form id="login-form" class="login-form" method="post">
                            <div class="form-group">
                                <label for="username">Username or Email</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="username" name="username" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="forgot-password-container" style="text-align: right; margin-top: 5px;">
                                    <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">Forgot Password?</a>
                                </div>
                            </div>
                            
                            <div class="form-group remember-me">
                                <input type="checkbox" id="remember" name="remember" value="1">
                                <label for="remember">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <span class="btn-text">Sign In</span>
                                <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                            
                            <div class="login-separator">
                                <span>or continue with</span>
                            </div>
                            
                            <button type="button" class="btn btn-google">
                                <i class="fab fa-google"></i> Sign in with Google
                            </button>
                            
                            <div class="login-footer">
                                Don't have an account? <a href="<?php echo wp_registration_url(); ?>">Sign up</a>
                            </div>
                        </form>
                        
                        <div id="login-message" class="login-message" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_logged_in_view() {
        $current_user = wp_get_current_user();
        ob_start();
        ?>
        <div class="modern-barbershop">
            <div class="login-container">
                <div class="logged-in-box">
                    <div class="welcome-message">
                        <h2>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</h2>
                        <p>You are already logged in.</p>
                    </div>
                    <div class="button-group">
                        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-primary">Go to Homepage</a>
                        <button id="logout-btn" class="btn btn-secondary">Logout</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function ajax_login() {
        check_ajax_referer('ajax-login-nonce', 'security');
        
        $credentials = [
            'user_login'    => sanitize_user($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['remember']) ? true : false
        ];
        
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        } else {
            wp_send_json_success(['redirect' => home_url()]);
        }
    }
    
    public function ajax_logout() {
        wp_logout();
        wp_send_json_success(['redirect' => home_url()]);
    }
}

// Initialize the plugin
function modern_barbershop_login_init() {
    new Modern_Barbershop_Login();
}
add_action('plugins_loaded', 'modern_barbershop_login_init');
