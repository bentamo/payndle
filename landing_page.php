<?php
/**
 * Modern Barbershop Landing Page
 * Clean, professional design for a barbershop business
 */
function modern_barbershop_landing_shortcode() {
    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Styles */
        .modern-barbershop {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            color: #333333;
            overflow-x: hidden;
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Navigation */
        .navbar {
            padding: 1.2rem 5%;
            background: rgba(255, 255, 255, 0.98);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            transition: all 0.4s ease-in-out;
        }

        .navbar.scrolled {
            padding: 0.8rem 5%;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .logo span {
            color: #c9a74d;
        }

        .logo:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            position: relative;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #c9a74d;
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: #c9a74d;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .cta-button {
            background: #c9a74d;
            color: white !important;
            padding: 0.6rem 1.5rem !important;
            border-radius: 30px;
            font-weight: 600 !important;
            letter-spacing: 0.5px;
            transition: all 0.3s ease !important;
            border: 2px solid #c9a74d;
            text-transform: uppercase;
            font-size: 0.85rem !important;
        }

        .cta-button:hover {
            background: transparent;
            color: #c9a74d !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
        }

        .cta-button::after {
            display: none !important;
        }

        /* Mobile Menu */
        .menu-toggle {
            display: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        .menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background: #1a1a1a;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 80%;
                height: calc(100vh - 80px);
                background: white;
                flex-direction: column;
                justify-content: flex-start;
                padding: 2rem;
                transition: all 0.5s ease;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }

            .nav-links.active {
                left: 0;
            }

            .nav-links a {
                width: 100%;
                padding: 1rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .nav-links a::after {
                display: none;
            }

            .menu-toggle {
                display: block;
            }
        }

        /* Content Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 5%;
        }

        /* Section Styling */
        section {
            padding: 6rem 0;
        }

        /* Responsive Typography */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem !important;
            }
            
            h2 {
                font-size: 2rem !important;
            }
            
            .logo {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 2rem !important;
            }
            
            h2 {
                font-size: 1.75rem !important;
            }
            
            section {
                padding: 4rem 0;
            }
        }

        /* Add padding to the first section to account for fixed navbar */
        #home {
            padding-top: 100px; /* Adjust this value based on your navbar height */
        }

        /* Adjust section padding for mobile */
        @media (max-width: 768px) {
            #home {
                padding-top: 80px;
            }
        }

        /* Services Section Hover Effects */
        .service-card {
            background: #ffffff;
            padding: 2.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #c9a74d;
            transform: scaleX(0);
            transition: transform 0.4s ease;
            transform-origin: left;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: rgba(201, 167, 77, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .service-card:hover .service-icon {
            background: #c9a74d;
            transform: rotateY(180deg);
        }

        .service-card:hover .service-icon i {
            color: #ffffff;
        }

        .service-card h3 {
            font-size: 1.5rem;
            margin: 0 0 1rem;
            color: #1a1a1a;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .service-card:hover h3 {
            color: #c9a74d;
        }

        .service-card .price {
            font-weight: 700;
            color: #c9a74d;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .service-card .btn-service {
            display: inline-block;
            padding: 0.8rem 2rem;
            border: 2px solid #c9a74d;
            color: #c9a74d;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.4s ease;
            margin-top: auto;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .service-card .btn-service::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: #c9a74d;
            transition: width 0.4s ease;
            z-index: -1;
        }

        .service-card .btn-service:hover {
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
        }

        .service-card .btn-service:hover::before {
            width: 100%;
        }

        /* Staff Section Hover Effects */
        .staff-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }

        .staff-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .staff-image {
            height: 300px;
            position: relative;
            overflow: hidden;
        }

        .staff-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .staff-card:hover .staff-image img {
            transform: scale(1.05);
        }

        .staff-info {
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .staff-info::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: #c9a74d;
            transition: width 0.3s ease;
        }

        .staff-card:hover .staff-info::before {
            width: 80px;
        }

        .staff-social {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .staff-card:hover .staff-social {
            opacity: 1;
            top: -30px;
        }

        .staff-social a {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c9a74d;
            text-decoration: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .staff-social a:hover {
            background: #c9a74d;
            color: #ffffff;
            transform: translateY(-3px);
        }

        .book-staff {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #c9a74d;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 4px;
        }

        .book-staff:hover {
            background: rgba(201, 167, 77, 0.1);
            transform: translateX(5px);
        }

        .book-staff i {
            transition: transform 0.3s ease;
        }

        .book-staff:hover i {
            transform: translateX(3px);
        }

        /* View All Button */
        .view-all-btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: transparent;
            color: #c9a74d;
            border: 2px solid #c9a74d;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .view-all-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: #c9a74d;
            transition: width 0.4s ease;
            z-index: -1;
        }

        .view-all-btn:hover {
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
        }

        .view-all-btn:hover::before {
            width: 100%;
        }
        
        /* New button classes */
        .btn-primary {
            background: #c9a74d;
            color: #ffffff;
            padding: 0.8rem 2rem;
            border: 2px solid #c9a74d;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: #b8860b;
            transition: width 0.4s ease;
            z-index: -1;
        }
        
        .btn-primary:hover {
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
        }
        
        .btn-primary:hover::before {
            width: 100%;
        }
        
        .btn-outline {
            background: transparent;
            color: #c9a74d;
            padding: 0.8rem 2rem;
            border: 2px solid #c9a74d;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: #c9a74d;
            transition: width 0.4s ease;
            z-index: -1;
        }
        
        .btn-outline:hover {
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.3);
        }
        
        .btn-outline:hover::before {
            width: 100%;
        }
    </style>
    
    <div class="modern-barbershop">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <a href="#home" class="logo"><span>ELITE</span>BARBER</a>
                <div class="menu-toggle" id="mobile-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="nav-links" id="nav-links">
                    <a href="#home" class="active">Home</a>
                    <a href="#about">About Us</a>
                    <a href="#services">Services</a>
                    <a href="#staff">Our Barbers</a>
                    <a href="#contact">Contact Us</a>
                    <a href="#book-now" class="cta-button">Book Now</a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="home" style="padding: 6rem 5% 8rem; text-align: center; background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80'); background-size: cover; background-position: center; color: #ffffff;">
            <div style="max-width: 800px; margin: 0 auto;">
                <h1 style="font-family: 'Playfair Display', serif; font-size: 4rem; font-weight: 700; line-height: 1.2; margin-bottom: 1.5rem; color: #ffffff; letter-spacing: -1px;">
                    Precision Cuts & Classic Styles
                </h1>
                <p style="font-size: 1.25rem; color: #e0e0e0; margin-bottom: 2.5rem; line-height: 1.6; max-width: 700px; margin-left: auto; margin-right: auto;">
                    Experience the art of traditional barbering with modern techniques. Your perfect look starts here.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
                    <a href="#book-now" class="btn-primary">Book Appointment</a>
                    <a href="#services" class="btn-outline">Our Services</a>
                </div>
                <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 3rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #b8860b;">15+</div>
                        <div style="color: #cccccc;">Years Experience</div>
                    </div>
                    <div style="width: 1px; background: rgba(255,255,255,0.2);"></div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #b8860b;">5,000+</div>
                        <div style="color: #cccccc;">Happy Clients</div>
                    </div>
                    <div style="width: 1px; background: rgba(255,255,255,0.2);"></div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #b8860b;">50+</div>
                        <div style="color: #cccccc;">Awards Won</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" style="padding: 6rem 5%; background: #f9f9f9;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="text-align: center; max-width: 700px; margin: 0 auto 5rem;">
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #1a1a1a; position: relative; display: inline-block;">
                        Our Services
                        <span style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 80px; height: 3px; background: #b8860b;"></span>
                    </h2>
                    <p style="color: #666666; font-size: 1.1rem; line-height: 1.6;">
                        Premium grooming services tailored to your unique style and preferences.
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <!-- Service 1 -->
                    <div class="service-card">
                        <div style="width: 80px; height: 80px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-cut" style="font-size: 2rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; margin: 0 0 1rem; color: #1a1a1a;">Haircut</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; min-height: 60px;">
                            Professional haircut with style consultation and finishing touches.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.25rem; margin-bottom: 1.5rem;">₱100+</div>
                        <a href="#book-now" class="btn-service">Book Now</a>
                    </div>
                    
                    <!-- Service 2 -->
                    <div class="service-card">
                        <div style="width: 80px; height: 80px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-user-tie" style="font-size: 2rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; margin: 0 0 1rem; color: #1a1a1a;">Beard Trim</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; min-height: 60px;">
                            Precision beard trimming and shaping for a polished look.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.25rem; margin-bottom: 1.5rem;">₱120+</div>
                        <a href="#book-now" class="btn-service">Book Now</a>
                    </div>
                    
                    <!-- Service 3 -->
                    <div class="service-card">
                        <div style="width: 80px; height: 80px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-air-freshener" style="font-size: 2rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; margin: 0 0 1rem; color: #1a1a1a;">Hot Towel Shave</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; min-height: 60px;">
                            Traditional hot towel shave with premium products for ultimate comfort.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.25rem; margin-bottom: 1.5rem;">₱130+</div>
                        <a href="#book-now" class="btn-service">Book Now</a>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 4rem;">
                    <a href="#all-services" class="view-all-btn">View All Services</a>
                </div>
            </div>
        </section>

        <!-- Staff Section -->
        <section id="staff" style="padding: 6rem 5%; background: #f9f9f9;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem;">
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #1a1a1a; position: relative; display: inline-block;">
                        Our Expert Barbers
                        <span style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 80px; height: 3px; background: #b8860b;"></span>
                    </h2>
                    <p style="color: #666666; font-size: 1.1rem; line-height: 1.6;">
                        Meet our talented team of professional barbers dedicated to giving you the perfect look.
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2.5rem; margin-top: 3rem;">
                    <!-- Barber 1 -->
                    <div class="staff-card">
                        <div class="staff-image">
                            <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Barber 1" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                                <h3 style="color: #ffffff; margin: 0; font-size: 1.5rem;">Miguel Santos</h3>
                                <p style="color: #b8860b; margin: 0.25rem 0 0; font-weight: 500;">Master Barber</p>
                            </div>
                        </div>
                        <div class="staff-info">
                            <p style="color: #666666; margin-bottom: 1.5rem;">10+ years of experience in classic and modern haircuts</p>
                            <div style="display: flex; justify-content: center; gap: 1rem;">
                                <a href="#" class="book-staff"><i class="fas fa-calendar-alt"></i> Book Now</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barber 2 -->
                    <div class="staff-card">
                        <div class="staff-image">
                            <img src="https://images.unsplash.com/photo-1583864692221-95a2c5ba9d5b?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Barber 2" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                                <h3 style="color: #ffffff; margin: 0; font-size: 1.5rem;">Antonio Cruz</h3>
                                <p style="color: #b8860b; margin: 0.25rem 0 0; font-weight: 500;">Beard Specialist</p>
                            </div>
                        </div>
                        <div class="staff-info">
                            <p style="color: #666666; margin-bottom: 1.5rem;">Expert in precision beard grooming and styling</p>
                            <div style="display: flex; justify-content: center; gap: 1rem;">
                                <a href="#" class="book-staff"><i class="fas fa-calendar-alt"></i> Book Now</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barber 3 -->
                    <div class="staff-card">
                        <div class="staff-image">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Barber 3" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                                <h3 style="color: #ffffff; margin: 0; font-size: 1.5rem;">Rafael Reyes</h3>
                                <p style="color: #b8860b; margin: 0.25rem 0 0; font-weight: 500;">Master Stylist</p>
                            </div>
                        </div>
                        <div class="staff-info">
                            <p style="color: #666666; margin-bottom: 1.5rem;">Specializes in modern and trendy haircuts</p>
                            <div style="display: flex; justify-content: center; gap: 1rem;">
                                <a href="#" class="book-staff"><i class="fas fa-calendar-alt"></i> Book Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Services Section -->
        <section style="padding: 6rem 5%; background: #ffffff;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem;">
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #1a1a1a; position: relative; display: inline-block;">
                        Related Services
                        <span style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 80px; height: 3px; background: #b8860b;"></span>
                    </h2>
                    <p style="color: #666666; font-size: 1.1rem; line-height: 1.6;">
                        Discover more services to complete your grooming experience.
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                        <div style="width: 70px; height: 70px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-shower" style="font-size: 1.75rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.25rem; margin: 0 0 1rem; color: #1a1a1a;">Hair Treatment</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; font-size: 0.95rem;">
                            Revitalize your hair with our premium treatment solutions.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.1rem;">Starting at ₱300</div>
                    </div>
                    
                    <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                        <div style="width: 70px; height: 70px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-magic" style="font-size: 1.75rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.25rem; margin: 0 0 1rem; color: #1a1a1a;">Facial Care</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; font-size: 0.95rem;">
                            Rejuvenate your skin with our professional facial treatments.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.1rem;">Starting at ₱400</div>
                    </div>
                    
                    <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                        <div style="width: 70px; height: 70px; background: rgba(184, 134, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-spa" style="font-size: 1.75rem; color: #b8860b;"></i>
                        </div>
                        <h3 style="font-size: 1.25rem; margin: 0 0 1rem; color: #1a1a1a;">Spa Services</h3>
                        <p style="color: #666666; margin: 0 0 1.5rem; line-height: 1.6; font-size: 0.95rem;">
                            Relax and unwind with our soothing spa treatments.
                        </p>
                        <div style="font-weight: 700; color: #b8860b; font-size: 1.1rem;">Starting at ₱500</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" style="padding: 6rem 5%; background: #ffffff;">
            <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
                <div>
                    <img src="https://images.unsplash.com/photo-1585747860715-2ba090e1d759?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Barbershop Interior" style="width: 100%; border-radius: 8px; box-shadow: 0 15px 30px rgba(0,0,0,0.1);">
                </div>
                <div>
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #1a1a1a; position: relative; display: inline-block;">
                        About Us
                        <span style="position: absolute; bottom: -10px; left: 0; width: 80px; height: 3px; background: #b8860b;"></span>
                    </h2>
                    <p style="color: #666666; margin-bottom: 1.5rem; line-height: 1.8;">
                        Welcome to EliteBarber, where classic barbering meets modern style. Established in 2010, we've been the go-to destination for gentlemen seeking premium grooming services in a welcoming atmosphere.
                    </p>
                    <p style="color: #666666; margin-bottom: 2rem; line-height: 1.8;">
                        Our team of master barbers brings years of experience and a passion for perfection to every cut, shave, and style. We believe that looking your best should be an enjoyable experience.
                    </p>
                    <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem;">
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #1a1a1a;">✓ Professional Staff</h4>
                            <p style="color: #666666; font-size: 0.95rem; margin: 0;">Certified and experienced barbers</p>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #1a1a1a;">✓ Quality Products</h4>
                            <p style="color: #666666; font-size: 0.95rem; margin: 0;">Premium grooming products</p>
                        </div>
                    </div>
                    <a href="#book-now" style="display: inline-block; padding: 1rem 2.5rem; background: #b8860b; color: #ffffff; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s;">
                        Book Appointment
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <?php
        $footer_path = plugin_dir_path(__FILE__) . 'footer-landing.php';
        if (file_exists($footer_path)) {
            include $footer_path;
        }
        ?>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            const navLinks = document.getElementById('nav-links');
            
            mobileMenu.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                this.querySelector('i').classList.toggle('fa-bars');
                this.querySelector('i').classList.toggle('fa-times');
            });

            // Close menu when clicking on a nav link
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    mobileMenu.querySelector('i').classList.add('fa-bars');
                    mobileMenu.querySelector('i').classList.remove('fa-times');
                });
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Active link highlighting
            const sections = document.querySelectorAll('section');
            const navItems = document.querySelectorAll('.nav-links a');

            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (pageYOffset >= (sectionTop - 200)) {
                        current = section.getAttribute('id');
                    }
                });

                navItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('href') === `#${current}`) {
                        item.classList.add('active');
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('modern_barbershop_landing', 'modern_barbershop_landing_shortcode');