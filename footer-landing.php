<?php
/**
 * The template for displaying the footer
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get social media links and business hours
$social_links = function_exists('wordpress_plugin_template_get_social_links') ? wordpress_plugin_template_get_social_links() : array(
    'facebook' => 'https://facebook.com/elitebarberph',
    'instagram' => 'https://instagram.com/elitebarberph',
    'twitter' => 'https://twitter.com/elitebarberph'
);

$business_hours = function_exists('wordpress_plugin_template_get_business_hours') ? wordpress_plugin_template_get_business_hours() : array(
    'monday' => '9:00 AM - 8:00 PM',
    'saturday' => '9:00 AM - 9:00 PM',
    'sunday' => '10:00 AM - 6:00 PM'
);
?>

<footer class="footer-section" style="background: #0a0a0a; color: #ffffff; padding: 80px 0 30px; font-family: 'Poppins', sans-serif;">
    <div class="footer-container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div class="footer-top" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 50px;">
            <!-- About Section -->
            <div class="footer-about">
                <h3 style="font-size: 24px; color: #ffffff; margin-bottom: 25px; position: relative; padding-bottom: 15px;">
                    <span style="color: #c9a74d;">ELITE</span>BARBER
                    <span style="position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: #c9a74d; transition: width 0.3s ease;"></span>
                </h3>
                <p style="color: #b0b0b0; line-height: 1.7; margin-bottom: 25px; font-size: 14px;">
                    Premium barbershop in Northern Mindanao, offering top-notch grooming services with a blend of traditional techniques and modern styles.
                </p>
                <div class="social-links" style="display: flex; gap: 12px;">
                    <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="color: #ffffff; background: #1a1a1a; width: 40px; height: 40px; border-radius: 50%; 
                              display: flex; align-items: center; justify-content: center; 
                              transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fab fa-facebook-f" style="transition: transform 0.3s ease;"></i>
                    </a>
                    <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="color: #ffffff; background: #1a1a1a; width: 40px; height: 40px; border-radius: 50%; 
                              display: flex; align-items: center; justify-content: center; 
                              transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fab fa-instagram" style="transition: transform 0.3s ease;"></i>
                    </a>
                    <a href="<?php echo esc_url($social_links['twitter']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="color: #ffffff; background: #1a1a1a; width: 40px; height: 40px; border-radius: 50%; 
                              display: flex; align-items: center; justify-content: center; 
                              transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fab fa-twitter" style="transition: transform 0.3s ease;"></i>
                    </a>
                </div>
                <style>
                    .social-links a:hover {
                        background: #c9a74d !important;
                        transform: translateY(-3px);
                        box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
                    }
                    .social-links a:hover i {
                        transform: scale(1.1);
                    }
                </style>
            </div>

            <!-- Quick Links -->
            <div class="footer-links">
                <h4 style="font-size: 18px; color: #ffffff; margin-bottom: 25px; position: relative; padding-bottom: 10px;">
                    Quick Links
                    <span style="position: absolute; bottom: 0; left: 0; width: 40px; height: 2px; background: #c9a74d; transition: width 0.3s ease;"></span>
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 12px;">
                        <a href="#home" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; 
                                          font-size: 14px; display: block; position: relative; padding: 5px 0 5px 20px;">
                            <i class="fas fa-chevron-right" style="position: absolute; left: 0; top: 8px; font-size: 10px; 
                                 color: #c9a74d; transition: all 0.3s ease;"></i>
                            Home
                        </a>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <a href="#services" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; 
                                          font-size: 14px; display: block; position: relative; padding: 5px 0 5px 20px;">
                            <i class="fas fa-chevron-right" style="position: absolute; left: 0; top: 8px; font-size: 10px; 
                                 color: #c9a74d; transition: all 0.3s ease;"></i>
                            Services
                        </a>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <a href="#gallery" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; 
                                          font-size: 14px; display: block; position: relative; padding: 5px 0 5px 20px;">
                            <i class="fas fa-chevron-right" style="position: absolute; left: 0; top: 8px; font-size: 10px; 
                                 color: #c9a74d; transition: all 0.3s ease;"></i>
                            Gallery
                        </a>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <a href="#team" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; 
                                          font-size: 14px; display: block; position: relative; padding: 5px 0 5px 20px;">
                            <i class="fas fa-chevron-right" style="position: absolute; left: 0; top: 8px; font-size: 10px; 
                                 color: #c9a74d; transition: all 0.3s ease;"></i>
                            Our Team
                        </a>
                    </li>
                    <li>
                        <a href="#contact" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; 
                                          font-size: 14px; display: block; position: relative; padding: 5px 0 5px 20px;">
                            <i class="fas fa-chevron-right" style="position: absolute; left: 0; top: 8px; font-size: 10px; 
                                 color: #c9a74d; transition: all 0.3s ease;"></i>
                            Contact
                        </a>
                    </li>
                </ul>
                <style>
                    .footer-links a:hover {
                        color: #ffffff !important;
                        transform: translateX(5px);
                    }
                    .footer-links a:hover i {
                        transform: translateX(3px);
                    }
                </style>
            </div>

            <!-- Contact Info -->
            <div class="footer-contact">
                <h4 style="font-size: 18px; color: #ffffff; margin-bottom: 25px; position: relative; padding-bottom: 10px;">
                    Contact Us
                    <span style="position: absolute; bottom: 0; left: 0; width: 40px; height: 2px; background: #c9a74d; transition: width 0.3s ease;"></span>
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 20px; display: flex; align-items: flex-start;">
                        <div style="width: 40px; height: 40px; background: rgba(201, 167, 77, 0.1); border-radius: 50%; 
                                 display: flex; align-items: center; justify-content: center; margin-right: 15px; 
                                 transition: all 0.3s ease;">
                            <i class="fas fa-map-marker-alt" style="color: #c9a74d; font-size: 14px;"></i>
                        </div>
                        <div>
                            <h5 style="color: #ffffff; margin: 0 0 5px; font-size: 16px;">Our Location</h5>
                            <p style="color: #b0b0b0; margin: 0; font-size: 13px; line-height: 1.5;">
                                123 P. Gomez Street,<br>
                                Cagayan de Oro City,<br>
                                Misamis Oriental, 9000
                            </p>
                        </div>
                    </li>
                    <li style="margin-bottom: 20px; display: flex; align-items: center;">
                        <div style="width: 40px; height: 40px; background: rgba(201, 167, 77, 0.1); border-radius: 50%; 
                                 display: flex; align-items: center; justify-content: center; margin-right: 15px; 
                                 transition: all 0.3s ease;">
                            <i class="fas fa-phone-alt" style="color: #c9a74d; font-size: 14px;"></i>
                        </div>
                        <div>
                            <h5 style="color: #ffffff; margin: 0 0 5px; font-size: 16px;">Call Us</h5>
                            <a href="tel:+639123456789" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; font-size: 13px;">
                                +63 912 345 6789
                            </a>
                        </div>
                    </li>
                    <li style="display: flex; align-items: center;">
                        <div style="width: 40px; height: 40px; background: rgba(201, 167, 77, 0.1); border-radius: 50%; 
                                 display: flex; align-items: center; justify-content: center; margin-right: 15px; 
                                 transition: all 0.3s ease;">
                            <i class="fas fa-envelope" style="color: #c9a74d; font-size: 14px;"></i>
                        </div>
                        <div>
                            <h5 style="color: #ffffff; margin: 0 0 5px; font-size: 16px;">Email Us</h5>
                            <a href="mailto:info@elitebarber.ph" style="color: #b0b0b0; text-decoration: none; transition: all 0.3s ease; font-size: 13px;">
                                info@elitebarber.ph
                            </a>
                        </div>
                    </li>
                </ul>
                <style>
                    .footer-contact a:hover {
                        color: #c9a74d !important;
                    }
                    .footer-contact li:hover div:first-child {
                        background: #c9a74d !important;
                        transform: translateY(-3px);
                    }
                    .footer-contact li:hover i {
                        color: #ffffff !important;
                    }
                </style>
            </div>

            <!-- Business Hours -->
            <div class="footer-hours">
                <h4 style="font-size: 18px; color: #ffffff; margin-bottom: 25px; position: relative; padding-bottom: 10px;">
                    Business Hours
                    <span style="position: absolute; bottom: 0; left: 0; width: 40px; height: 2px; background: #c9a74d; transition: width 0.3s ease;"></span>
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: #b0b0b0; font-size: 14px;">Monday - Friday</span>
                        <span style="color: #c9a74d; font-size: 14px;"><?php echo esc_html($business_hours['monday']); ?></span>
                    </li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: #b0b0b0; font-size: 14px;">Saturday</span>
                        <span style="color: #c9a74d; font-size: 14px;"><?php echo esc_html($business_hours['saturday']); ?></span>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: #b0b0b0; font-size: 14px;">Sunday</span>
                        <span style="color: #c9a74d; font-size: 14px;"><?php echo esc_html($business_hours['sunday']); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; text-align: center;">
            <p style="color: #666666; margin: 0; font-size: 13px; line-height: 1.5;">
                &copy; <?php echo date('Y'); ?> ELITEBARBER. All Rights Reserved.
                <span style="display: block; margin-top: 5px;">
                    <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>" style="color: #c9a74d; text-decoration: none; margin: 0 10px; font-size: 13px; transition: all 0.3s ease;">Privacy Policy</a> 
                    <span style="color: #666666;">|</span> 
                    <a href="<?php echo esc_url(home_url('/terms-of-service')); ?>" style="color: #c9a74d; text-decoration: none; margin: 0 10px; font-size: 13px; transition: all 0.3s ease;">Terms of Service</a>
                </span>
            </p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
