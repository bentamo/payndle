<?php

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Login {

    /**
     * Register hooks
     */
    public function register() {
        add_shortcode('custom_login', array($this, 'render_login_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue CSS & JS
     */
    public function enqueue_assets() {
        $plugin_url = plugin_dir_url(__FILE__) . '../';

        wp_enqueue_style(
            'custom-login-style',
            $plugin_url . 'assets/css/custom-login.css',
            array(),
            '1.0'
        );

        wp_enqueue_script(
            'custom-login-script',
            $plugin_url . 'assets/js/custom-login.js',
            array('jquery'),
            '1.0',
            true
        );
    }

    /**
     * Render the login form (UI only for now)
     */
    public function render_login_form() {
        ob_start(); ?>
        
        <div class="custom-login-container">
            <h2>Login</h2>
            
            <form class="custom-login-form">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="login-button">Login</button>
            </form>

            <div class="login-separator">or</div>

            <!-- Placeholder for Google Auth -->
            <button class="google-login-button" id="google-login">
                Continue with Google
            </button>
        </div>
        
        <?php
        return ob_get_clean();
    }
}
