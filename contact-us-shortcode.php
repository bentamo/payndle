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
 * Usage: [contact_us phone="+1 234 567 890" email="info@example.com" address="123 Business St, City, Country"]
 */
function payndle_contact_us_shortcode($atts) {
    // Default attributes
    $atts = shortcode_atts(
        array(
            'heading' => 'Get In Touch',
            'phone' => '+1 234 567 890',
            'email' => 'info@example.com',
            'address' => '123 Business St, City, Country',
            'hours' => 'Monday - Friday: 9:00 AM - 6:00 PM',
        ),
        $atts,
        'contact_us'
    );

    // Sanitize inputs
    $heading = esc_html($atts['heading']);
    $phone = esc_html($atts['phone']);
    $email = esc_html($atts['email']);
    $address = esc_html($atts['address']);
    $hours = esc_html($atts['hours']);

    // Start output buffering
    ob_start();
    ?>
    <style>
        .contact-us-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            color: #0C1930;
        }
        
        .contact-us-heading {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 36px;
            line-height: 1.2;
            color: #0C1930;
            margin: 0 0 40px 0;
            text-align: center;
            position: relative;
            padding-bottom: 20px;
        }
        
        .contact-us-heading:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: #64C493;
            border-radius: 2px;
        }
        
        .contact-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .contact-card {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            border: 1px solid #E2E8F0;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .contact-icon {
            width: 70px;
            height: 70px;
            background-color: #F0F9F4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #64C493;
        }
        
        .contact-icon svg {
            width: 32px;
            height: 32px;
        }
        
        .contact-title {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: #0C1930;
            margin: 0 0 15px 0;
        }
        
        .contact-info {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.6;
            color: #4A5568;
            margin: 0;
        }
        
        .contact-info a {
            color: #64C493;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .contact-info a:hover {
            color: #4CAF50;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .contact-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .contact-card {
                padding: 25px 20px;
            }
            
            .contact-us-heading {
                font-size: 28px;
                margin-bottom: 30px;
            }
        }
    </style>
    
    <section class="contact-us-section">
        <h2 class="contact-us-heading"><?php echo $heading; ?></h2>
        
        <div class="contact-cards">
            <!-- Phone Card -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                    </svg>
                </div>
                <h3 class="contact-title">Phone</h3>
                <p class="contact-info">
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>">
                        <?php echo $phone; ?>
                    </a>
                </p>
            </div>
            
            <!-- Email Card -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <h3 class="contact-title">Email</h3>
                <p class="contact-info">
                    <a href="mailto:<?php echo $email; ?>">
                        <?php echo $email; ?>
                    </a>
                </p>
            </div>
            
            <!-- Address Card -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>
                <h3 class="contact-title">Location</h3>
                <p class="contact-info"><?php echo nl2br($address); ?></p>
            </div>
            
            <!-- Hours Card -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-.22-13h-.06c-.4 0-.72.32-.72.72v4.72c0 .35.18.68.49.86l4.15 2.49c.34.2.78.1.98-.24.21-.34.1-.79-.25-.99l-3.87-2.3V7.72c0-.4-.32-.72-.72-.72z"/>
                    </svg>
                </div>
                <h3 class="contact-title">Business Hours</h3>
                <p class="contact-info"><?php echo $hours; ?></p>
            </div>
        </div>
    </section>
    <?php
    
    return ob_get_clean();
}
add_shortcode('contact_us', 'payndle_contact_us_shortcode');
