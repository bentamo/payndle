<?php
/**
 * Business Header Shortcode [business_header]
 * 
 * @package Payndle
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Business Header Shortcode
 * 
 * Usage: [business_header name="Your Business Name" description="Your business description"]
 */
function payndle_business_header_shortcode($atts) {
    // Default attributes
    $atts = shortcode_atts(
        array(
            'name' => 'Business Name',
            'description' => 'A short description of your business',
            'logo_url' => '',
        ),
        $atts,
        'business_header'
    );

    // Sanitize inputs
    $business_name = esc_html($atts['name']);
    $description = esc_html($atts['description']);
    $logo_url = esc_url($atts['logo_url']);

    // Start output buffering
    ob_start();
    ?>
    <style>
        .business-header {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 20px 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .business-logo {
            width: 80px;
            height: 80px;
            background-color: #f5f7fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid #e1e5eb;
        }
        
        .business-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .business-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .business-name {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 28px;
            line-height: 1.2;
            color: #0C1930;
            margin: 0;
        }
        
        .business-description {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.5;
            color: #4A5568;
            margin: 0;
            max-width: 600px;
        }
        
        @media (max-width: 768px) {
            .business-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .business-logo {
                width: 64px;
                height: 64px;
            }
            
            .business-name {
                font-size: 24px;
            }
            
            .business-description {
                font-size: 14px;
            }
        }
    </style>
    
    <div class="business-header">
        <div class="business-logo">
            <?php if (!empty($logo_url)) : ?>
                <img src="<?php echo $logo_url; ?>" alt="<?php echo esc_attr($business_name); ?> Logo">
            <?php else : ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V5H19V19Z" fill="#64C493"/>
                    <path d="M12 7C11.45 7 11 7.45 11 8V16C11 16.55 11.45 17 12 17C12.55 17 13 16.55 13 16V8C13 7.45 12.55 7 12 7Z" fill="#64C493"/>
                    <path d="M8 11C7.45 11 7 11.45 7 12V16C7 16.55 7.45 17 8 17C8.55 17 9 16.55 9 16V12C9 11.45 8.55 11 8 11Z" fill="#64C493"/>
                    <path d="M16 9C15.45 9 15 9.45 15 10V16C15 16.55 15.45 17 16 17C16.55 17 17 16.55 17 16V10C17 9.45 16.55 9 16 9Z" fill="#64C493"/>
                </svg>
            <?php endif; ?>
        </div>
        <div class="business-info">
            <h1 class="business-name"><?php echo $business_name; ?></h1>
            <p class="business-description"><?php echo $description; ?></p>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('business_header', 'payndle_business_header_shortcode');
