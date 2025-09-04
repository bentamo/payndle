<?php
/*
Plugin Name: Plan Page Shortcode
Description: Adds a shortcode [plan_page] to display Free & Premium plans with black & white UI. Buttons are placeholders for future payment gateway integration.
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// =============================
// Shortcode: [plan_page]
// =============================
function plan_page_shortcode($atts) {
    $plans = array(
        'free' => array(
            'title' => 'Free Plan',
            'price' => '$0',
            'benefits' => array(
                'Access to basic features',
                'Limited support',
                'Community access'
            ),
            'button_text' => 'Choose Free'
        ),
        'premium' => array(
            'title' => 'Premium Plan',
            'price' => '$49/month',
            'benefits' => array(
                'All Free Plan benefits',
                'Priority support',
                'Access to premium features',
                'Exclusive resources'
            ),
            'button_text' => 'Choose Premium'
        ),
    );

    ob_start();
    ?>
    <div class="plan-container">
        <?php foreach ($plans as $plan): ?>
            <div class="plan-box">
                <h2><?php echo esc_html($plan['title']); ?></h2>
                <p class="price"><?php echo esc_html($plan['price']); ?></p>
                <ul>
                    <?php foreach ($plan['benefits'] as $benefit): ?>
                        <li><?php echo esc_html($benefit); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="plan-button"><?php echo esc_html($plan['button_text']); ?></button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('plan_page', 'plan_page_shortcode');

// =============================
// Enqueue CSS + JS
// =============================
function plan_page_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);

    // CSS
    wp_enqueue_style(
        'plan-page-style',
        $plugin_url . 'assets/css/plan-page.css',
        array(),
        '1.0'
    );

    // JS
    wp_enqueue_script(
        'plan-page-script',
        $plugin_url . 'assets/js/plan-page.js',
        array('jquery'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'plan_page_enqueue_assets');
