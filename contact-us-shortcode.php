<?php
/**
 * Contact Us Shortcode [contact_us]
 * 
 * @package Payndle
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Contact Us Shortcode
 * 
 * Usage: [contact_us 
 *   heading="Contact Us" 
 *   address="123 Business St, City, Country"
 *   email="contact@example.com"
 *   phone="+1 (555) 123-4567"
 *   hours="Mon-Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM"
 * ]
 */
function payndle_contact_us_shortcode($atts) {
    global $post;
    
    // Resolve business context: if current page stores a _business_id, use that business' meta
    $context_post_id = !empty($post) ? intval($post->ID) : 0;
    if (!empty($post)) {
        $linked_business_id = intval(get_post_meta($post->ID, '_business_id', true));
        if ($linked_business_id > 0) {
            $context_post_id = $linked_business_id;
        }
    }
    
    // Get business information from resolved context meta
    $business_address = get_post_meta($context_post_id, '_business_address', true);
    $business_city = get_post_meta($context_post_id, '_business_city', true);
    $business_state = get_post_meta($context_post_id, '_business_state', true);
    $business_zip = get_post_meta($context_post_id, '_business_zip', true);
    $business_country = get_post_meta($context_post_id, '_business_country', true);
    $business_email = get_post_meta($context_post_id, '_business_email', true);
    $business_phone = get_post_meta($context_post_id, '_business_phone', true);
    $business_hours = get_post_meta($context_post_id, '_business_hours', true);
    
    // Format address if we have the components
    $formatted_address = '';
    if (!empty($business_address)) {
        $formatted_address = $business_address;
        if (!empty($business_city)) $formatted_address .= ", {$business_city}";
        if (!empty($business_state)) $formatted_address .= ", {$business_state}";
        if (!empty($business_zip)) $formatted_address .= " {$business_zip}";
        if (!empty($business_country)) $formatted_address .= ", {$business_country}";
    }
    
    // Default attributes with values from post meta
    $atts = shortcode_atts(
        array(
            'heading' => 'Contact Us',
            'show_heading' => 'true', // set to 'false' to suppress the heading when embedding inside another section
            'address' => $formatted_address ?: '123 Business Street, City, Country',
            'email' => $business_email ?: 'contact@example.com',
            'phone' => $business_phone ?: '+1 (555) 123-4567',
            'hours' => $business_hours ?: "Mon-Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM",
        ),
        $atts,
        'contact_us'
    );

    // Sanitize inputs
    $heading = esc_html($atts['heading']);
    $address = wp_kses_post($atts['address']);
    $email = esc_html($atts['email']);
    $phone = esc_html($atts['phone']);
    $hours = wp_kses_post($atts['hours']);

    // Start output buffering
    ob_start();
    ?>
    <style>
        .contact-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
            color: #0C1930;
            font-family: 'Inter', sans-serif;
        }
        
        .section-heading {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-heading h2 {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 36px;
            color: #0C1930;
            margin: 0 0 15px 0;
            position: relative;
            display: inline-block;
        }
        
        .section-heading h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -12px;
            width: 80px;
            height: 4px;
            background-color: #64C493;
            border-radius: 2px;
            transform: translateX(-50%);
        }
        
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .contact-info {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .contact-item:last-child {
            margin-bottom: 0;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            background-color: #F0F7F4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            color: #64C493;
        }
        
        .contact-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .contact-details h4 {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: #0C1930;
            margin: 0 0 5px 0;
        }
        
        .contact-details p, 
        .contact-details a {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.6;
            color: #4A5568;
            margin: 0;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .contact-details a:hover {
            color: #64C493;
        }
        
        .business-hours .hours-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .business-hours .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #F0F4F8;
        }
        
        .business-hours .hours-list li:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
            
            .section-heading h2 {
                font-size: 28px;
            }
            
            .contact-info {
                padding: 30px 20px;
            }
        }
    </style>
    
    <section class="contact-section">
        <?php if ($atts['show_heading'] !== 'false'): ?>
        <div class="section-heading">
            <h2><?php echo $heading; ?></h2>
        </div>
        <?php endif; ?>
        
        <div class="contact-container">
            <!-- Address -->
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Our Location</h4>
                        <p><?php echo nl2br($address); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Email Us</h4>
                        <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Call Us</h4>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>"><?php echo $phone; ?></a>
                    </div>
                </div>
            </div>
            
            <!-- Business Hours -->
            <div class="contact-info business-hours">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Business Hours</h4>
                        <div class="hours-list">
                            <?php echo wpautop($hours); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    
    return ob_get_clean();
}
add_shortcode('contact_us', 'payndle_contact_us_shortcode');
