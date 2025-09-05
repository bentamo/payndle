# User Booking Form Setup Guide

## Overview

The new User Booking Form provides a separated, beautifully designed interface for customers to input their information when booking services. It follows the same design principles as your landing page with the elegant gold color scheme (#c9a74d) and modern typography.

## Usage

### Basic Usage
Add the shortcode to any page or post:
```
[user_booking_form]
```

### With Service Pre-selection
To pre-select a specific service:
```
[user_booking_form service_id="5"]
```

### Without Service Selector
If you want to use it for a specific service only and hide the service dropdown:
```
[user_booking_form service_id="5" show_service_selector="false"]
```

### With Custom Redirect
To redirect users after successful booking:
```
[user_booking_form redirect_url="/thank-you-page/"]
```

## Features

### Design Elements
- **Color Scheme**: Follows landing page design with gold (#c9a74d) accent color
- **Typography**: Uses Poppins and Playfair Display fonts
- **Responsive**: Mobile-first design that works on all devices
- **Animations**: Smooth transitions and hover effects

### Form Sections
1. **Service Selection** (optional)
   - Dropdown with all available services
   - Dynamic service information display
   - Price and duration details

2. **Personal Information**
   - Full name (required)
   - Email address (required) 
   - Phone number (optional, auto-formatted for Philippine numbers)

3. **Appointment Preferences**
   - Preferred date (with date validation)
   - Preferred time (business hours only: 8 AM - 6 PM)
   - Additional message/requests

4. **Payment Method Selection**
   - Cash payment
   - Credit/Debit card
   - GCash
   - PayMaya
   - Online payment

### Validation Features
- Real-time field validation
- Email format validation
- Phone number formatting
- Date range validation (future dates only)
- Time validation (business hours)
- Required field checking

### User Experience
- **Progressive Enhancement**: Works without JavaScript
- **Accessibility**: Proper ARIA labels and keyboard navigation
- **Loading States**: Visual feedback during form submission
- **Success Feedback**: Beautiful success message after booking
- **Error Handling**: Clear error messages with field highlighting

## Backend Integration

### Database Tables
The form automatically creates and uses these tables:
- `wp_services` - Service information
- `wp_service_bookings` - Booking requests

### Email Notifications
Automatically sends:
- **Admin notification** with booking details
- **Customer confirmation** with booking reference

### Status Management
All bookings start with 'pending' status and can be managed through the admin panel.

## Customization

### CSS Customization
The form uses the file `assets/css/user-booking-form.css`. Key customizable elements:

```css
/* Primary color (gold) */
--primary-color: #c9a74d;

/* Text colors */
--text-dark: #1a1a1a;
--text-normal: #333333;
--text-light: #666666;

/* Background */
--bg-white: #ffffff;
```

### JavaScript Customization
The form behavior is controlled by `assets/js/user-booking-form.js`. You can modify:
- Validation rules
- Animation effects
- AJAX behavior
- Form submission handling

## Examples

### Example 1: Service-Specific Booking Page
Create a page for "Haircut Booking" with:
```
[user_booking_form service_id="1" show_service_selector="false"]
```

### Example 2: General Booking Page
Create a general booking page with:
```
[user_booking_form]
```

### Example 3: Embedded in Landing Page
Add to your landing page:
```html
<div class="booking-section">
    <h2>Book Your Appointment</h2>
    [user_booking_form]
</div>
```

## Admin Management

### Viewing Bookings
All booking requests can be viewed and managed through the existing booking management system in the WordPress admin.

### Booking Status
- **Pending**: New booking request (default)
- **Confirmed**: Appointment confirmed by admin
- **Completed**: Service completed
- **Cancelled**: Booking cancelled

### Customer Communication
The system automatically:
1. Sends confirmation email to customer
2. Notifies admin of new booking
3. Stores all communication preferences

## Technical Details

### WordPress Integration
- Uses WordPress AJAX for form submission
- Follows WordPress coding standards
- Includes proper nonce verification
- Sanitizes all user input

### Security Features
- CSRF protection with nonces
- Input sanitization and validation
- SQL injection prevention
- XSS protection

### Performance
- Optimized CSS and JavaScript
- Minimal external dependencies
- Efficient database queries
- Caching-friendly

## Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Internet Explorer 11+ (with graceful degradation)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Form not displaying**
   - Ensure the shortcode is correct: `[user_booking_form]`
   - Check if the plugin is activated
   - Verify file permissions

2. **Styles not loading**
   - Check if CSS file exists: `assets/css/user-booking-form.css`
   - Clear any caching plugins
   - Verify WordPress asset loading

3. **JavaScript not working**
   - Check browser console for errors
   - Ensure jQuery is loaded
   - Verify JavaScript file exists: `assets/js/user-booking-form.js`

4. **Email not sending**
   - Check WordPress email configuration
   - Verify SMTP settings
   - Test with a simple WordPress email function

### Debug Mode
Add to wp-config.php for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

Planned features:
- Calendar integration
- SMS notifications
- Payment gateway integration
- Multi-language support
- Advanced booking rules
- Staff assignment
- Recurring appointments
