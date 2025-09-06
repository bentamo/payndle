# Payndle WordPress Plugin - Complete Shortcodes Documentation

## Overview

This document contains all shortcodes available in the Payndle WordPress plugin for barbershop management. Each shortcode provides specific functionality for different aspects of the barbershop business operations.

---

## Table of Contents

1. [Landing Page & Frontend](#landing-page--frontend)
2. [Booking System](#booking-system)
3. [Business Setup](#business-setup)
4. [Authentication](#authentication)
5. [Management Panels](#management-panels)
6. [Plans & Pricing](#plans--pricing)
7. [Staff Management](#staff-management)

---

## Landing Page & Frontend

### [modern_barbershop_landing]

**Purpose**: Displays a complete modern barbershop landing page with professional design, service showcase, and booking integration.

**Shortcode**: `[modern_barbershop_landing]`

**Parameters**: None

**Features**:
- Professional barbershop landing page design
- Service showcase with booking integration
- Modern responsive design with gold accent color (#c9a74d)
- Typography: Poppins and Playfair Display fonts
- Mobile-friendly responsive layout
- Navigation menu and hero section
- About section, services display, and contact information

**Usage Example**:
```
[modern_barbershop_landing]
```

**File Location**: `landing_page.php`

---

## Booking System

### [services_booking]

**Purpose**: Public-facing service display and booking system for customers to view and book services.

**Shortcode**: `[services_booking]`

**Parameters**:
- `title` (optional) - Title for the services section (default: "Our Services")
- `show_featured_first` (optional) - Show featured services first (default: "true")
- `columns` (optional) - Number of columns for service display (default: "3")

**Features**:
- Real-time service selection and booking
- Payment method selection (Cash, Card, GCash, PayMaya, Online)
- Service filtering and search
- Responsive grid layout
- AJAX-powered interactions
- Integration with business contact information

**Usage Examples**:
```
[services_booking]
[services_booking title="Available Services" columns="2"]
[services_booking show_featured_first="false"]
```

**File Location**: `public-services-booking.php`

---

### [user_booking_form]

**Purpose**: Dedicated user information input form for booking services with modern design.

**Shortcode**: `[user_booking_form]`

**Parameters**:
- `service_id` (optional) - Pre-select a specific service by ID
- `show_service_selector` (optional) - Show/hide service dropdown (default: "true")
- `redirect_url` (optional) - Custom redirect URL after successful booking

**Features**:
- Beautiful form design matching landing page aesthetics
- Service selection with dynamic information display
- Personal information collection (name, email, phone)
- Preferred date and time selection
- Special requests/notes field
- Real-time validation
- AJAX form submission
- Mobile-responsive design

**Usage Examples**:
```
[user_booking_form]
[user_booking_form service_id="5"]
[user_booking_form service_id="5" show_service_selector="false"]
[user_booking_form redirect_url="/thank-you-page/"]
```

**File Location**: `user-booking-form.php`

---

### [booking_history] / [booking_log]

**Purpose**: Comprehensive booking history view with advanced filtering and management capabilities.

**Shortcode**: `[booking_history]` or `[booking_log]` (backward compatibility)

**Parameters**:
- `service_id` (optional) - Filter by specific service ID
- `status` (optional) - Filter by booking status (pending, confirmed, completed, cancelled)
- `limit` (optional) - Maximum number of bookings to display (default: 20)
- `show_filters` (optional) - Show filter controls (yes/no, default: yes)

**Features**:
- Advanced filtering by service, status, payment method, and date
- Booking status management (pending, confirmed, completed, cancelled)
- Payment method tracking (cash, card, GCash, PayMaya, online)
- Real-time search and filtering
- Responsive table design
- Export capabilities
- AJAX-powered updates

**Usage Examples**:
```
[booking_history]
[booking_history service_id="5" status="pending" limit="50"]
[booking_history show_filters="no" limit="10"]
[booking_log status="completed"]
```

**File Location**: `booking-history.php`

---

### [assigned_bookings]

**Purpose**: Display assigned bookings table for staff members to view their assigned appointments.

**Shortcode**: `[assigned_bookings]`

**Parameters**: None

**Features**:
- Table view of assigned bookings
- Customer information display
- Service details and scheduling
- Booking status indicators
- Action buttons for booking management
- Modal popup for detailed view

**Usage Example**:
```
[assigned_bookings]
```

**File Location**: `includes/class-assigned-bookings.php`

---

## Business Setup

### [business_setup]

**Purpose**: Comprehensive business setup form for new business owners/managers to configure their business profile before setting up services and staff.

**Shortcode**: `[business_setup]`

**Parameters**:
- `redirect_after_setup` (optional) - URL to redirect to after successful setup
- `show_existing_data` (optional) - Whether to load existing business data if found (default: "true")

**Features**:
- Multi-step form with progress indicator (3 steps)
- Modern UI following brand guidelines with Payndle color palette
- Uses WordPress post meta for data storage (creates 'payndle_business' post type)
- Real-time form validation and error handling
- Phone number auto-formatting
- Responsive design for all screen sizes
- AJAX form submission with loading states
- Checks for existing business data and pre-populates form
- Professional business categories selection
- Social media integration fields
- Business hours configuration

**Form Steps**:
1. **Business Information**: Name, description, category, website
2. **Contact Details**: Email, phone, address (street, city, state, ZIP)
3. **Social & Hours**: Business hours, Facebook, Instagram, Twitter

**Usage Examples**:
```
[business_setup]
[business_setup redirect_after_setup="/services-setup/"]
[business_setup show_existing_data="false"]
```

**File Location**: `business-setup.php`

---

## Authentication

### [custom_login]

**Purpose**: Modern barbershop-themed login form with enhanced security and user experience.

**Shortcode**: `[custom_login]`

**Parameters**: None

**Features**:
- Modern barbershop design theme
- AJAX-powered login functionality
- Password visibility toggle
- Remember me functionality
- Forgot password integration
- User registration link
- Responsive design
- Security nonce protection
- Auto-redirect after login

**Usage Example**:
```
[custom_login]
```

**File Location**: `custom-login.php` and `includes/class-custom-login.php`

---

### [business_setup]

**Purpose**: Comprehensive business setup form for new business owners/managers to configure their business profile before setting up services and staff.

**Shortcode**: `[business_setup]`

**Parameters**:
- `redirect_after_setup` (optional) - URL to redirect to after successful setup
- `show_existing_data` (optional) - Whether to load existing business data if found (default: "true")

**Features**:
- Multi-step form with progress indicator (3 steps)
- Modern UI following brand guidelines with Payndle color palette
- Uses WordPress post meta for data storage (creates 'payndle_business' post type)
- Real-time form validation and error handling
- Phone number auto-formatting
- Responsive design for all screen sizes
- AJAX form submission with loading states
- Checks for existing business data and pre-populates form
- Professional business categories selection
- Social media integration fields
- Business hours configuration

**Form Steps**:
1. **Business Information**: Name, description, category, website
2. **Contact Details**: Email, phone, address (street, city, state, ZIP)
3. **Social & Hours**: Business hours, Facebook, Instagram, Twitter

**Usage Examples**:
```
[business_setup]
[business_setup redirect_after_setup="/services-setup/"]
[business_setup show_existing_data="false"]
```

**File Location**: `business-setup.php`

---

## Management Panels

### [elite_cuts_manage_bookings]

**Purpose**: Complete booking management interface for administrators and staff.

**Shortcode**: `[elite_cuts_manage_bookings]`

**Parameters**: None

**Access Requirements**: 
- User must be logged in
- User must have `manage_options` capability (Administrator level)

**Features**:
- Complete booking management system
- View, edit, and delete bookings
- Status management (pending, confirmed, completed, cancelled)
- Customer information management
- Service assignment and scheduling
- Payment tracking and management
- Search and filtering capabilities
- AJAX-powered interface

**Usage Example**:
```
[elite_cuts_manage_bookings]
```

**File Location**: `manage-bookings.php`

---

### [elite_cuts_manage_staff]

**Purpose**: Staff management interface for administrators to manage team members.

**Shortcode**: `[elite_cuts_manage_staff]`

**Parameters**: None

**Access Requirements**: 
- User must be logged in
- User must have `manage_options` capability (Administrator level)

**Features**:
- Add, edit, and delete staff members
- Staff profile management
- Position and role assignment
- Contact information management
- Availability tracking
- Staff status management (active/inactive)
- Avatar/photo upload support

**Usage Example**:
```
[elite_cuts_manage_staff]
```

**File Location**: `manage-staff.php`

---

## Plans & Pricing

### [plan_page]

**Purpose**: Display pricing plans with Free and Premium options for future payment gateway integration.

**Shortcode**: `[plan_page]`

**Parameters**: None

**Features**:
- Free and Premium plan display
- Black and white UI design
- Feature comparison lists
- Placeholder buttons for payment integration
- Responsive design
- Professional pricing table layout

**Plans Included**:
- **Free Plan**: $0 with basic features, limited support, community access
- **Premium Plan**: $49/month with all features, priority support, premium resources

**Usage Example**:
```
[plan_page]
```

**File Location**: `plan-page.php`

---

## Database Integration

### Database Tables

The plugin creates and manages several database tables:

1. **`wp_service_bookings`** - Customer booking requests with payment info
2. **`wp_manager_services`** - Available services and their details
3. **`wp_manager_business`** - Business information and settings
4. **`wp_staff_members`** - Staff member information and profiles

### Payment Methods Supported

- Cash Payment
- Credit/Debit Card
- GCash
- PayMaya
- Online Payment

---

## Design Features

### Consistent Branding
- Gold accent color (#c9a74d) throughout all interfaces
- Professional dark theme elements
- Poppins and Playfair Display typography
- Responsive design for all screen sizes

### User Experience
- AJAX-powered interactions
- Real-time filtering and updates
- Mobile-friendly interface
- Accessibility considerations
- Security nonce protection
- Form validation and error handling

---

## Technical Requirements

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Dependencies
- jQuery (included with WordPress)
- Font Awesome 6.0.0+
- Google Fonts (Poppins, Playfair Display)

### File Structure
```
payndle/
├── assets/
│   ├── css/ (stylesheets for each component)
│   └── js/ (JavaScript files for each component)
├── includes/
│   ├── class-assigned-bookings.php
│   ├── class-custom-login.php
│   └── lib/ (additional library files)
├── booking-history.php
├── custom-login.php
├── landing_page.php
├── manage-bookings.php
├── manage-staff.php
├── plan-page.php
├── public-services-booking.php
├── user-booking-form.php
└── SHORTCODES_DOCUMENTATION.md (this file)
```

---

## Troubleshooting

### Common Issues

1. **Shortcode not displaying**: Ensure the plugin is activated and the shortcode is spelled correctly
2. **Database errors**: Check if database tables exist by visiting any management panel first
3. **AJAX errors**: Verify user permissions and nonce validation
4. **Styling issues**: Ensure CSS files are properly enqueued and no conflicts exist

### Debug Information

For debugging, check:
- WordPress debug logs
- Browser console for JavaScript errors
- Network tab for AJAX request failures
- Database queries using WordPress debugging tools

---

## Support and Updates

For support with this plugin:
1. Check the troubleshooting section above
2. Review the WordPress debug logs
3. Ensure all required permissions are set
4. Verify database table creation

**Plugin Version**: 1.0  
**Last Updated**: September 6, 2025  
**WordPress Compatibility**: 5.0+

---

*This documentation covers all available shortcodes in the Payndle WordPress plugin. Each shortcode is designed to work independently while maintaining consistent design and functionality across the entire barbershop management system.*
