<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="payndle-landing">
  <!-- Hero / Branding -->
  <header class="pl-hero pl-hero--center" id="home">
    <nav class="pl-nav">
      <a href="#home" class="brand">
        <span class="pl-logo" aria-hidden="true">
          <!-- Payndle Horizontal Logo -->
          <svg width="28" height="28" viewBox="0 0 121 121" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M43.3671 100.25H20.6921C14.2441 100.25 9.01709 95.023 9.01709 88.575V32.425C9.01709 25.977 14.2441 20.75 20.6921 20.75H43.3671C49.8151 20.75 55.0421 25.977 55.0421 32.425V49.5C55.0421 55.948 49.8151 61.175 43.3671 61.175H32.0296" stroke="#64C493" stroke-width="17" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M77.2418 20.75H99.9168C106.365 20.75 111.592 25.977 111.592 32.425V88.575C111.592 95.023 106.365 100.25 99.9168 100.25H77.2418C70.7938 100.25 65.5668 95.023 65.5668 88.575V71.5C65.5668 65.052 70.7938 59.825 77.2418 59.825H88.5793" stroke="#0C192F" stroke-width="17" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span class="pl-wordmark">Payndle</span>
      </a>
      <div class="pl-nav-actions">
        <a href="#search" class="btn btn-ghost">Book an appointment</a>
        <a href="#partner" class="btn btn-primary">Partner With Us</a>
      </div>
    </nav>

    <div class="pl-hero-content container">
      <h1 class="pl-hero-title">Book appointments with trusted local businesses</h1>
      <p class="lead">Discover barbers, salons, clinics, stylists, and more—schedule online in seconds.</p>

      <!-- Fresha-like large search in hero -->
      <form class="search-form search-form--hero" onsubmit="return false;">
        <div class="field-group field-group--hero">
          <input type="text" id="pl-search-query" placeholder="Search a service or business (e.g., haircut, salon, clinic)"/>
          <select id="pl-search-category">
            <option value="">All Categories</option>
            <option value="barber">Barbers</option>
            <option value="salon">Salons</option>
            <option value="clinic">Clinics</option>
            <option value="stylist">Stylists</option>
            <option value="spa">Spas</option>
          </select>
          <button class="btn btn-primary" id="pl-search-btn" type="button">Search</button>
        </div>
      </form>
    </div>
  </header>

  <!-- Companies / Carousel -->
  <section class="pl-section pl-carousel" id="partners">
    <div class="container">
      <h2>Trusted by Local Businesses</h2>
      <div class="carousel" data-autoplay="true" data-interval="3000">
        <div class="carousel-track">
          <!-- Items will loop; add duplicates for smooth scroll -->
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.96 1.02a1 1 0 0 0-1.41 0l-6.35 6.35a3.5 3.5 0 1 0 1.41 1.41l6.35-6.35a1 1 0 0 0 0-1.41zM5.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm13 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M10.46 8.82 3.04 1.41a1 1 0 0 0-1.41 1.41l7.41 7.42a3.5 3.5 0 1 0 1.42-1.42z"/></svg>
              </div>
              <span>Barber Bros</span>
            </div>
          </div>
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 2.5a8.49 8.49 0 0 0-7.41 12.09l-2.73 2.73a1 1 0 0 0 1.41 1.41l2.73-2.73A8.5 8.5 0 1 0 12.5 2.5zm0 15a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13z"/><path d="M18 6h-2v2h2zm-2-2h2v2h-2zm4 4h-2v2h2z"/></svg>
              </div>
              <span>Glow Salon</span>
            </div>
          </div>
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm4 11h-3v3h-2v-3H8v-2h3V8h2v3h3z"/></svg>
              </div>
              <span>QuickClinic</span>
            </div>
          </div>
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 16h-15a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h15a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5zM5 15h14V5H5v10z"/><path d="M16 2h-8a1 1 0 0 0 0 2h8a1 1 0 0 0 0-2z"/><path d="M14 17h-4a1 1 0 0 0 0 2h4a1 1 0 0 0 0-2z"/></svg>
              </div>
              <span>StyleLab</span>
            </div>
          </div>
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21.71 11.29a1 1 0 0 0-1.42 0l-4.13 4.13a4.5 4.5 0 0 0-6.36-6.36L14 5.12a1 1 0 1 0-1.41-1.41L8.46 7.83a6.5 6.5 0 0 0 9.19 9.19l4.13-4.13a1 1 0 0 0 0-1.42z"/><path d="M4.46 9.24a1 1 0 0 0-1.41 1.41l4.13 4.13a4.5 4.5 0 0 0 6.36 0 1 1 0 0 0-1.41-1.41 2.5 2.5 0 0 1-3.54 0z"/></svg>
              </div>
              <span>Spa Haven</span>
            </div>
          </div>
          <div class="carousel-item">
            <div class="partner-card">
              <div class="partner-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.5 2h-13A2.5 2.5 0 0 0 3 4.5v15A2.5 2.5 0 0 0 5.5 22h13a2.5 2.5 0 0 0 2.5-2.5v-15A2.5 2.5 0 0 0 18.5 2zM12 14.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zM16 9H8a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2z"/></svg>
              </div>
              <span>NailCraft</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Results -->
  <section class="pl-section pl-search" id="search">
    <div class="container">
      <h2>Results</h2>
      <div id="pl-search-results" class="results-grid" aria-live="polite">
        <p class="results-placeholder">Your search results will appear here.</p>
      </div>
    </div>
  </section>

  <!-- Partnership Form -->
  <section class="pl-section pl-partner" id="partner">
    <div class="container">
      <div class="pl-partner-grid">
        <div class="pl-partner-info">
          <h2>Partner with Payndle</h2>
          <p>Ready to streamline bookings and payments? Join our growing network of local businesses.</p>
          <ul class="pl-benefits-list">
            <li>
              <div class="benefit-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></svg></div>
              <div class="benefit-text"><h4>Streamline Bookings</h4><p>Manage your appointments with our simple, intuitive calendar.</p></div>
            </li>
            <li>
              <div class="benefit-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
              <div class="benefit-text"><h4>Accept Digital Payments</h4><p>Easily take payments from GCash, Maya, cards, and more.</p></div>
            </li>
            <li>
              <div class="benefit-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
              <div class="benefit-text"><h4>Grow Your Customer Base</h4><p>Get discovered by new customers searching for services in your area.</p></div>
            </li>
          </ul>
        </div>
        <form class="pl-form" id="pl-partner-form" onsubmit="return false;">
          <div class="form-grid">
            <div class="form-row">
              <label for="pl-biz-name">Business Name</label>
              <input id="pl-biz-name" type="text" placeholder="Enter your business name" required />
            </div>
            <div class="form-row">
              <label for="pl-contact-person">Contact Person</label>
              <input id="pl-contact-person" type="text" placeholder="e.g., Juan Dela Cruz" required />
            </div>
            <div class="form-row">
              <label for="pl-email">Email</label>
              <input id="pl-email" type="email" placeholder="you@example.com" required />
            </div>
            <div class="form-row">
              <label for="pl-service-type">Service Type</label>
              <select id="pl-service-type" required>
                <option value="">Select category</option>
                <option value="barber">Barbershop</option>
                <option value="salon">Salon</option>
                <option value="clinic">Clinic</option>
                <option value="stylist">Stylist</option>
                <option value="spa">Spa</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <label for="pl-message">Message</label>
            <textarea id="pl-message" rows="4" placeholder="Tell us about your business and needs"></textarea>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Submit Inquiry</button>
          </div>
          <p class="form-note">This is a demo form. No data is stored—submitting will show a confirmation message.</p>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="pl-footer">
    <div class="container">
      <p>  Payndle. All rights reserved.</p>
      <div class="footer-links">
        <a href="#about">About</a>
        <a href="#partners">Partners</a>
        <a href="#partner">Become a Partner</a>
      </div>
    </div>
  </footer>
</div>
