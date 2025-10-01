<?php
/**
 * Unified Business Landing Page Wrapper
 * Shortcode: [business_landing]
 *
 * Public-facing template that stitches together:
 * - [business_header]
 * - [user_services]
 * - [user_booking_form]
 * - [contact_us]
 *
 * It auto-detects the business context using (in order):
 * - business_id attribute
 * - URL ?business_id=
 * - Current page meta _business_id
 * - mvp_get_current_business_id() fallback
 */
if (!defined('ABSPATH')) { exit; }

function payndle_resolve_business_id_for_landing($atts = []) {
    $atts = is_array($atts) ? $atts : [];
    // 1) Explicit attribute
    if (!empty($atts['business_id'])) {
        $bid = intval($atts['business_id']);
        if ($bid > 0) return $bid;
    }
    // 2) URL param
    if (isset($_GET['business_id'])) {
        $bid = intval($_GET['business_id']);
        if ($bid > 0) return $bid;
    }
    // 3) Current page meta _business_id
    global $post;
    if (!empty($post)) {
        $meta_bid = intval(get_post_meta($post->ID, '_business_id', true));
        if ($meta_bid > 0) return $meta_bid;
    }
    // 4) Fallback helper if available
    if (function_exists('mvp_get_current_business_id')) {
        $ctx = intval(mvp_get_current_business_id());
        if ($ctx > 0) return $ctx;
    }
    return 0;
}

function payndle_business_landing_wrapper_shortcode($atts) {
    $atts = shortcode_atts([
        'business_id' => 0,
        'show_services' => 'true',
        'show_booking' => 'true',
        'show_contact' => 'true',
    ], $atts, 'business_landing');

    $business_id = payndle_resolve_business_id_for_landing($atts);

    // Start output buffering
    ob_start();
    ?>
    <style>
        .payndle-landing-wrap { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color:#0C1930; }
        .payndle-landing-section { padding: 48px 16px; }
        .payndle-landing-container { max-width: 1200px; margin: 0 auto; }
        .payndle-landing-h2 { font-weight: 700; font-size: 28px; margin: 0 0 16px; text-align:center; }
        .payndle-landing-divider { width: 80px; height: 4px; background: #64C493; margin: 0 auto 24px; border-radius:2px; }
        .payndle-landing-sub { color:#4A5568; text-align:center; margin: 0 0 24px; }
        .payndle-landing-hero { background:#F8FAFC; padding-top: 12px; padding-bottom: 12px; }
        .payndle-landing-services { background:#ffffff; }
        .payndle-landing-booking { background:#F8FAFC; }
        .payndle-landing-contact { background:#0C1930; color:#fff; }
        .payndle-landing-contact .payndle-landing-h2 { color:#fff; }
    </style>

    <div class="payndle-landing-wrap" data-business-id="<?php echo esc_attr($business_id); ?>">
        <section class="payndle-landing-hero payndle-landing-section">
            <div class="payndle-landing-container">
                <?php echo do_shortcode('[business_header]'); ?>
            </div>
        </section>

        <?php if ($atts['show_services'] === 'true'): ?>
        <section class="payndle-landing-services payndle-landing-section" id="services">
            <div class="payndle-landing-container">
                <h2 class="payndle-landing-h2">Our Services</h2>
                <div class="payndle-landing-divider"></div>
                <?php echo do_shortcode('[user_services]'); ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($atts['show_booking'] === 'true'): ?>
        <section class="payndle-landing-booking payndle-landing-section" id="book">
            <div class="payndle-landing-container">
                <h2 class="payndle-landing-h2">Book an Appointment</h2>
                <div class="payndle-landing-divider"></div>
                <?php echo do_shortcode('[user_booking_form]'); ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($atts['show_contact'] === 'true'): ?>
        <section class="payndle-landing-contact payndle-landing-section" id="contact">
            <div class="payndle-landing-container">
                <h2 class="payndle-landing-h2">Contact Us</h2>
                <div class="payndle-landing-divider"></div>
                <?php echo do_shortcode('[contact_us]'); ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('business_landing', 'payndle_business_landing_wrapper_shortcode');

?>
