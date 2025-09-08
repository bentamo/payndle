<?php
/**
 * Payndle Business Landing Page Shortcode
 * Reusable template for Payndle partner businesses
 */
function payndle_business_landing_shortcode($atts) {
    // Parse shortcode attributes with defaults
    $atts = shortcode_atts(
        array(
            'business_name' => 'Business Name',
            'tagline' => 'Your business tagline here',
            'logo_url' => '',
            'primary_color' => '#4a6cf7',
            'secondary_color' => '#6c757d',
            'show_services' => 'true',
            'show_about' => 'true',
            'show_contact' => 'false',
            'contact_email' => '',
            'contact_phone' => '',
            'address' => '',
            'service1_image' => '',
            'service1_title' => 'Service 1',
            'service1_description' => 'Description of service 1.',
            'service2_image' => '',
            'service2_title' => 'Service 2',
            'service2_description' => 'Description of service 2.',
            'service3_image' => '',
            'service3_title' => 'Service 3',
            'service3_description' => 'Description of service 3.'
        ),
        $atts,
        'payndle_business_landing'
    );

    // Start output buffering
    ob_start();
    
    // Enqueue necessary styles and scripts
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    ?>
    
    <style>
        /* Base Styles */
        .payndle-business-landing {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Navigation */
        .pbl-navbar {
            padding: 1.2rem 5%;
            background: rgba(255, 255, 255, 0.98);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
        }

        .pbl-navbar.scrolled {
            padding: 0.8rem 5%;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .pbl-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 5%;
        }

        .pbl-nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .pbl-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            margin: 0 auto; /* Center the logo when menu is toggled */
        }

        .pbl-logo-img {
            height: 40px;
            margin-right: 10px;
        }

        .pbl-logo-placeholder {
            height: 40px;
            margin-right: 10px;
            background: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #666;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.5rem;
        }

        .pbl-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: <?php echo esc_attr($atts['primary_color']); ?>;
            margin: 0;
        }

        .pbl-menu-toggle {
            display: none; /* Hidden by default, shown on mobile */
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1000;
            position: relative;
            padding: 0.5rem;
            margin: -0.5rem;
        }

        .pbl-nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .pbl-nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }

        .pbl-nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: <?php echo esc_attr($atts['primary_color']); ?>;
            transition: width 0.3s ease;
        }

        .pbl-nav-links a:hover {
            color: <?php echo esc_attr($atts['primary_color']); ?>;
        }

        .pbl-nav-links a:hover::after {
            width: 100%;
        }

        .pbl-button {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            color: white !important;
            padding: 0.6rem 1.5rem !important;
            border-radius: 30px;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            border: 2px solid <?php echo esc_attr($atts['primary_color']); ?>;
            text-decoration: none;
            display: inline-block;
        }

        .pbl-button:hover {
            background: transparent;
            color: <?php echo esc_attr($atts['primary_color']); ?> !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px <?php echo esc_attr(hex2rgba($atts['primary_color'], 0.3)); ?>;
        }

        /* Hero Section */
        .pbl-hero {
            padding: 180px 0 100px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            text-align: center;
        }

        .pbl-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: <?php echo esc_attr($atts['primary_color']); ?>;
        }

        .pbl-hero p {
            font-size: 1.25rem;
            color: <?php echo esc_attr($atts['secondary_color']); ?>;
            max-width: 700px;
            margin: 0 auto 2rem;
        }

        /* Sections */
        .pbl-section {
            padding: 6rem 0;
        }

        .pbl-section-title {
            text-align: center;
            margin-bottom: 3rem;
            color: <?php echo esc_attr($atts['primary_color']); ?>;
        }

        /* Services Grid */
        .pbl-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .pbl-service-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .pbl-service-image {
            width: 100%;
            height: 200px;
            background-color: #f8f9fa;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 14px;
        }

        .pbl-service-image i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .pbl-service-content {
            padding: 2rem;
        }

        .pbl-service-content p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .pbl-service-actions {
            padding: 1rem;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pbl-service-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid <?php echo esc_attr($atts['primary_color']); ?>;
            text-decoration: none;
            display: inline-block;
        }

        .pbl-btn-primary {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            color: white !important;
        }

        .pbl-btn-primary:hover {
            background: transparent;
            color: <?php echo esc_attr($atts['primary_color']); ?> !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px <?php echo esc_attr(hex2rgba($atts['primary_color'], 0.3)); ?>;
        }

        .pbl-btn-outline {
            background: transparent;
            color: <?php echo esc_attr($atts['primary_color']); ?>;
        }

        .pbl-btn-outline:hover {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px <?php echo esc_attr(hex2rgba($atts['primary_color'], 0.3)); ?>;
        }

        /* Contact Form */
        .pbl-contact-form {
            display: none;
        }

        .pbl-form-group {
            margin-bottom: 1.5rem;
        }

        .pbl-form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .pbl-form-control:focus {
            outline: none;
            border-color: <?php echo esc_attr($atts['primary_color']); ?>;
            box-shadow: 0 0 0 2px <?php echo esc_attr(hex2rgba($atts['primary_color'], 0.2)); ?>;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .pbl-hero h1 {
                font-size: 2.5rem;
            }
            
            .pbl-menu-toggle {
                display: block; /* Show on mobile */
                order: -1; /* Move to the start of the flex container */
            }

            .pbl-logo {
                margin: 0 auto; /* Center the logo */
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }
            
            .pbl-nav-links {
                position: fixed;
                top: 0;
                left: -300px;
                width: 280px;
                height: 100vh;
                background: white;
                flex-direction: column;
                padding: 80px 2rem 2rem;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                transition: left 0.3s ease-in-out;
                z-index: 999;
                overflow-y: auto;
            }
            
            .pbl-nav-links.active {
                left: 0;
            }
            
            /* Overlay when menu is open */
            .pbl-menu-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
            }

            .pbl-menu-overlay.active {
                display: block;
                opacity: 1;
            }

            /* Adjust menu items for mobile */
            .pbl-nav-links a {
                width: 100%;
                padding: 1rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                text-align: left;
            }

            .pbl-nav-links .pbl-button {
                margin-top: 1rem;
                text-align: center;
                width: 100%;
            }
        }

        /* Footer Styles */
        .pbl-footer {
            background: #1a202c;
            color: #fff;
            padding: 5rem 0 2rem;
            position: relative;
        }

        .pbl-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, <?php echo esc_attr($atts['primary_color']); ?>, #4a6cf7);
        }

        .pbl-footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .pbl-footer-column h3 {
            color: #fff;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .pbl-footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: <?php echo esc_attr($atts['primary_color']); ?>;
        }

        .pbl-contact-info {
            display: none;
        }

        .pbl-business-hours {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pbl-business-hours li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .pbl-business-hours li:last-child {
            border-bottom: none;
        }

        .pbl-business-hours span:first-child {
            color: #cbd5e0;
        }

        .pbl-business-hours span:last-child {
            color: #fff;
            font-weight: 500;
        }

        .pbl-social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .pbl-social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            transition: all 0.3s ease;
        }

        .pbl-social-link:hover {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            transform: translateY(-3px);
        }

        .pbl-newsletter p {
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .pbl-newsletter-form {
            display: flex;
            gap: 0.5rem;
        }

        .pbl-newsletter-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pbl-newsletter-input:focus {
            outline: none;
            border-color: <?php echo esc_attr($atts['primary_color']); ?>;
            box-shadow: 0 0 0 2px rgba(74, 108, 247, 0.2);
        }

        .pbl-newsletter-button {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pbl-newsletter-button:hover {
            background: <?php echo esc_attr($atts['primary_color']); ?>;
            transform: translateY(-2px);
        }

        .pbl-footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 2rem;
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .pbl-footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .pbl-newsletter-form {
                flex-direction: column;
            }

            .pbl-newsletter-button {
                padding: 0.75rem 1.5rem;
            }
        }
    </style>

    <div class="payndle-business-landing">
        <!-- Navigation -->
        <nav class="pbl-navbar" id="pblNavbar">
            <div class="pbl-container">
                <div class="pbl-nav-container">
                    <button class="pbl-menu-toggle" id="pblMenuToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="pbl-logo">
                        <?php if (!empty($atts['logo_url'])) : ?>
                            <img src="<?php echo esc_url($atts['logo_url']); ?>" alt="<?php echo esc_attr($atts['business_name']); ?>" class="pbl-logo-img">
                        <?php else : ?>
                            <div class="pbl-logo-placeholder">
                                Your Logo
                            </div>
                        <?php endif; ?>
                        <h1 class="pbl-logo-text"><?php echo esc_html($atts['business_name']); ?></h1>
                    </div>
                    
                    <div class="pbl-nav-links" id="pblNavLinks">
                        <a href="#home">Home</a>
                        <?php if ($atts['show_services'] === 'true') : ?>
                            <a href="#services">Services</a>
                        <?php endif; ?>
                        <?php if ($atts['show_about'] === 'true') : ?>
                            <a href="#about">About Us</a>
                        <?php endif; ?>
                        <a href="#" class="pbl-button">Log In</a>
                    </div>
                    
                    <div class="pbl-menu-overlay" id="pblMenuOverlay"></div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="pbl-hero" id="home">
            <div class="pbl-container">
                <?php if (!empty($atts['logo_url'])) : ?>
                    <img src="<?php echo esc_url($atts['logo_url']); ?>" alt="<?php echo esc_attr($atts['business_name']); ?>" style="max-height: 120px; margin-bottom: 2rem;">
                <?php endif; ?>
                <h1><?php echo esc_html($atts['business_name']); ?></h1>
                <p><?php echo esc_html($atts['tagline']); ?></p>
                <a href="#services" class="pbl-button">Our Services</a>
            </div>
        </section>

        <!-- Services Section -->
        <?php if ($atts['show_services'] === 'true') : ?>
            <section class="pbl-section" id="services">
                <div class="pbl-container">
                    <h2 class="pbl-section-title">Our Services</h2>
                    <div class="pbl-services-grid">
                        <!-- Service 1 -->
                        <div class="pbl-service-card">
                            <?php if (!empty($atts['service1_image'])) : ?>
                                <div class="pbl-service-image" style="background-image: url('<?php echo esc_url($atts['service1_image']); ?>');"></div>
                            <?php else : ?>
                                <div class="pbl-service-image">
                                    <div style="text-align: center; padding: 20px;">
                                        <i class="fas fa-image"></i>
                                        <div>Service 1 Image</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="pbl-service-content">
                                <h3><?php echo esc_html($atts['service1_title']); ?></h3>
                                <p><?php echo esc_html($atts['service1_description']); ?></p>
                            </div>
                            <div class="pbl-service-actions">
                                <a href="#services" class="pbl-service-btn pbl-btn-primary">Book Now</a>
                                <button class="pbl-service-btn pbl-btn-outline" onclick="showServiceDetails(1)">View Details</button>
                            </div>
                        </div>

                        <!-- Service 2 -->
                        <div class="pbl-service-card">
                            <?php if (!empty($atts['service2_image'])) : ?>
                                <div class="pbl-service-image" style="background-image: url('<?php echo esc_url($atts['service2_image']); ?>');"></div>
                            <?php else : ?>
                                <div class="pbl-service-image">
                                    <div style="text-align: center; padding: 20px;">
                                        <i class="fas fa-image"></i>
                                        <div>Service 2 Image</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="pbl-service-content">
                                <h3><?php echo esc_html($atts['service2_title']); ?></h3>
                                <p><?php echo esc_html($atts['service2_description']); ?></p>
                            </div>
                            <div class="pbl-service-actions">
                                <a href="#services" class="pbl-service-btn pbl-btn-primary">Book Now</a>
                                <button class="pbl-service-btn pbl-btn-outline" onclick="showServiceDetails(2)">View Details</button>
                            </div>
                        </div>

                        <!-- Service 3 -->
                        <div class="pbl-service-card">
                            <?php if (!empty($atts['service3_image'])) : ?>
                                <div class="pbl-service-image" style="background-image: url('<?php echo esc_url($atts['service3_image']); ?>');"></div>
                            <?php else : ?>
                                <div class="pbl-service-image">
                                    <div style="text-align: center; padding: 20px;">
                                        <i class="fas fa-image"></i>
                                        <div>Service 3 Image</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="pbl-service-content">
                                <h3><?php echo esc_html($atts['service3_title']); ?></h3>
                                <p><?php echo esc_html($atts['service3_description']); ?></p>
                            </div>
                            <div class="pbl-service-actions">
                                <a href="#services" class="pbl-service-btn pbl-btn-primary">Book Now</a>
                                <button class="pbl-service-btn pbl-btn-outline" onclick="showServiceDetails(3)">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- About Section -->
        <?php if ($atts['show_about'] === 'true') : ?>
            <section class="pbl-section" id="about" style="background: #f8f9fa;">
                <div class="pbl-container">
                    <h2 class="pbl-section-title">About Us</h2>
                    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                        <p>Welcome to <?php echo esc_html($atts['business_name']); ?>, your trusted partner for quality services. We are committed to excellence and customer satisfaction.</p>
                        <!-- Add more about content here -->
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="pbl-footer">
            <div class="pbl-container">
                <div class="pbl-footer-grid">
                    <div class="pbl-footer-column">
                        <h3>Business Hours</h3>
                        <ul class="pbl-business-hours">
                            <li><span>Monday - Friday</span> <span>9:00 AM - 5:00 PM</span></li>
                            <li><span>Saturday</span> <span>10:00 AM - 2:00 PM</span></li>
                            <li><span>Sunday</span> <span>Closed</span></li>
                        </ul>
                    </div>
                    <div class="pbl-footer-column">
                        <h3>Follow Us</h3>
                        <div class="pbl-social-links">
                            <a href="#" class="pbl-social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="pbl-social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="pbl-social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="pbl-social-link"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="pbl-footer-column">
                        <h3>Newsletter</h3>
                        <p>Stay up-to-date with our latest news and promotions.</p>
                        <div class="pbl-newsletter-form">
                            <input type="email" class="pbl-newsletter-input" placeholder="Your Email">
                            <button class="pbl-newsletter-button">Subscribe</button>
                        </div>
                    </div>
                </div>
                <div class="pbl-footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($atts['business_name']); ?>. All rights reserved.</p>
                    <p>Powered by <a href="https://payndle.com" style="color: #fff; text-decoration: underline;">Payndle</a></p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('pblMenuToggle');
            const navLinks = document.getElementById('pblNavLinks');
            const menuOverlay = document.getElementById('pblMenuOverlay');
            const navbar = document.getElementById('pblNavbar');
            const body = document.body;
            
            function toggleMenu() {
                navLinks.classList.toggle('active');
                menuOverlay.classList.toggle('active');
                body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
                
                // Toggle menu icon between bars and times
                const icon = menuToggle.querySelector('i');
                if (navLinks.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
            
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', toggleMenu);
                menuOverlay.addEventListener('click', toggleMenu);
                
                // Close menu when clicking on a link
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        if (navLinks.classList.contains('active')) {
                            toggleMenu();
                        }
                    });
                });
            }
            
            // Close menu when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && navLinks.classList.contains('active')) {
                    toggleMenu();
                }
            });
            
            // Navbar scroll effect
            if (navbar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });
            }
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80, // Adjust for fixed header
                            behavior: 'smooth'
                        });
                        
                        // Close mobile menu if open
                        if (navLinks && navLinks.classList.contains('active')) {
                            toggleMenu();
                        }
                    }
                });
            });
        });
        
        // Function to show service details
        function showServiceDetails(serviceId) {
            // Add your logic here to show service details
            console.log(`Service ${serviceId} details`);
        }
    </script>
    <?php
    // Return the buffered content
    return ob_get_clean();
}

// Helper function to convert hex to rgba
function hex2rgba($color, $opacity = false) {
    $default = 'rgb(0,0,0)';
    
    // Return default if no color provided
    if (empty($color)) {
        return $default;
    }
    
    // Sanitize $color if "#" is provided 
    if ($color[0] == '#') {
        $color = substr($color, 1);
    }
    
    // Check if color has 6 or 3 characters and get values
    if (strlen($color) == 6) {
        $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
    } elseif (strlen($color) == 3) {
        $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
    } else {
        return $default;
    }
    
    // Convert hex to rgb
    $rgb = array_map('hexdec', $hex);
    
    // Check if opacity is set (0-1)
    if ($opacity) {
        if (abs($opacity) > 1) {
            $opacity = 1.0;
        }
        $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    } else {
        $output = 'rgb(' . implode(",", $rgb) . ')';
    }
    
    return $output;
}

// Register the shortcode
add_shortcode('payndle_business_landing', 'payndle_business_landing_shortcode');
?>
