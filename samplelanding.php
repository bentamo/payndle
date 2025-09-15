<?php
// Place this inside functions.php or a custom plugin file

// Main Landing Page Wrapper
function payndle_landing_page_shortcode($atts, $content = null) {
    return '<div class="payndle-landing">'.do_shortcode($content).'</div>';
}
add_shortcode('', 'payndle_landing_page_shortcode');

// Hero Section
function payndle_hero_shortcode($atts) {
    $atts = shortcode_atts(array(
        'headline' => 'Accept bookings. Get paid. All in one page.',
        'subheadline' => 'Payndle helps small businesses accept bookings and payments.',
        'primary_cta' => 'Create my free booking page',
        'secondary_cta' => 'See a live demo',
    ), $atts);

    ob_start(); ?>
    <section class="hero">
        <h1><?php echo esc_html($atts['headline']); ?></h1>
        <p><?php echo esc_html($atts['subheadline']); ?></p>
        <a href="#" class="btn-primary"><?php echo esc_html($atts['primary_cta']); ?></a>
        <a href="#" class="btn-secondary"><?php echo esc_html($atts['secondary_cta']); ?></a>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('hero_section', 'payndle_hero_shortcode');

// Features
function payndle_features_shortcode($atts, $content = null) {
    return '<section class="features">'.do_shortcode($content).'</section>';
}
add_shortcode('features', 'payndle_features_shortcode');

function payndle_feature_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Feature Title',
        'text'  => 'Feature description goes here',
    ), $atts);

    return '<div class="feature"><h3>'.esc_html($atts['title']).'</h3><p>'.esc_html($atts['text']).'</p></div>';
}
add_shortcode('feature', 'payndle_feature_shortcode');

// Pricing
function payndle_pricing_shortcode($atts, $content = null) {
    return '<section class="pricing">'.do_shortcode($content).'</section>';
}
add_shortcode('pricing', 'payndle_pricing_shortcode');

function payndle_plan_shortcode($atts) {
    $atts = shortcode_atts(array(
        'name'     => 'Plan Name',
        'price'    => '₱0',
        'features' => '',
        'cta'      => 'Choose Plan',
    ), $atts);

    $features_list = '';
    if (!empty($atts['features'])) {
        $features = explode(',', $atts['features']);
        $features_list .= '<ul>';
        foreach ($features as $f) {
            $features_list .= '<li>'.esc_html(trim($f)).'</li>';
        }
        $features_list .= '</ul>';
    }

    return '<div class="plan"><h3>'.esc_html($atts['name']).'</h3><p class="price">'.esc_html($atts['price']).'</p>'.$features_list.'<a href="#" class="btn-plan">'.esc_html($atts['cta']).'</a></div>';
}
add_shortcode('plan', 'payndle_plan_shortcode');
