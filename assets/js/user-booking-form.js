/**
 * User Booking Form JavaScript
 * Enhanced functionality for the user booking form
 */

(function($) {
    'use strict';

    class UserBookingForm {
        constructor() {
            this.form = $('#user-booking-form');
            this.submitButton = $('#submit-booking');
            this.resetButton = $('#reset-form');
            this.serviceSelect = $('#service_id');
            this.serviceInfo = $('#selected-service-info');
            this.successContainer = $('#booking-success');
            this.formWrapper = $('.booking-form-wrapper');
            this.customerEmail = '';
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initValidation();
            this.setMinDate();
            this.initPaymentMethods();
        }

        bindEvents() {
            // Form submission
            this.form.on('submit', (e) => this.handleSubmit(e));
            
            // Reset form
            this.resetButton.on('click', () => this.resetForm());
            
            // Service selection
            this.serviceSelect.on('change', () => this.handleServiceChange());
            
            // Real-time validation
            this.form.find('input, textarea, select').on('blur', (e) => this.validateField($(e.target)));
            this.form.find('input[type="email"]').on('input', (e) => this.validateEmail($(e.target)));
            
            // Payment method selection
            this.form.find('input[name="payment_method"]').on('change', () => this.handlePaymentChange());
            
            // Book another service
            $('#book-another').on('click', () => this.showForm());
            
            // View my bookings
            $('#view-my-bookings').on('click', () => this.viewMyBookings());
            
            // Auto-format phone number
            $('#customer_phone').on('input', (e) => this.formatPhoneNumber($(e.target)));
            
            // Date/time validation
            $('#preferred_date').on('change', () => this.validateDate());
            $('#preferred_time').on('change', () => this.validateTime());
        }

        initValidation() {
            // Add real-time validation styles
            this.form.find('input[required], select[required]').each(function() {
                $(this).on('invalid', function(e) {
                    e.preventDefault();
                    $(this).closest('.form-group').addClass('error');
                });
            });
        }

        setMinDate() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const dateInput = $('#preferred_date');
            dateInput.attr('min', this.formatDate(today));
            
            // Set max date to 3 months from now
            const maxDate = new Date(today);
            maxDate.setMonth(maxDate.getMonth() + 3);
            dateInput.attr('max', this.formatDate(maxDate));
        }

        initPaymentMethods() {
            // Add click animation to payment options
            $('.payment-option').on('click', function() {
                const radio = $(this).find('input[type="radio"]');
                if (!radio.prop('checked')) {
                    radio.prop('checked', true).trigger('change');
                }
            });
        }

        handleServiceChange() {
            const serviceId = this.serviceSelect.val();
            
            if (serviceId) {
                this.loadServiceInfo(serviceId);
            } else {
                this.serviceInfo.hide();
            }
        }

        loadServiceInfo(serviceId) {
            const option = this.serviceSelect.find(`option[value="${serviceId}"]`);
            const name = option.text().split(' - ')[0];
            const price = option.data('price');
            const duration = option.data('duration');
            const description = option.data('description');
            
            const infoHtml = `
                <h4>${name}</h4>
                <p><strong>Description:</strong> ${description || 'No description available'}</p>
                <p><strong>Duration:</strong> ${duration} minutes</p>
                <p><strong>Price:</strong> <span class="service-price">₱${this.formatPrice(price)}</span></p>
            `;
            
            this.serviceInfo.html(infoHtml).fadeIn();
        }

        handlePaymentChange() {
            const selectedMethod = this.form.find('input[name="payment_method"]:checked').val();
            
            // Add visual feedback for selected payment method
            $('.payment-option').removeClass('selected');
            $(`.payment-option input[value="${selectedMethod}"]`).closest('.payment-option').addClass('selected');
        }

        validateField(field) {
            const fieldName = field.attr('name');
            const value = field.val().trim();
            const isRequired = field.prop('required');
            const formGroup = field.closest('.form-group');
            const errorDiv = formGroup.find('.form-error');
            
            // Clear previous errors
            formGroup.removeClass('error');
            errorDiv.removeClass('show').text('');
            
            // Check if required field is empty
            if (isRequired && !value) {
                this.showFieldError(formGroup, errorDiv, 'This field is required');
                return false;
            }
            
            // Specific validation based on field type
            switch (fieldName) {
                case 'customer_email':
                    return this.validateEmail(field);
                case 'customer_phone':
                    return this.validatePhone(field);
                case 'preferred_date':
                    return this.validateDate();
                case 'preferred_time':
                    return this.validateTime();
                default:
                    return true;
            }
        }

        validateEmail(field) {
            const email = field.val().trim();
            const formGroup = field.closest('.form-group');
            const errorDiv = formGroup.find('.form-error');
            
            if (email && !this.isValidEmail(email)) {
                this.showFieldError(formGroup, errorDiv, 'Please enter a valid email address');
                return false;
            }
            
            formGroup.removeClass('error');
            errorDiv.removeClass('show');
            return true;
        }

        validatePhone(field) {
            const phone = field.val().trim();
            const formGroup = field.closest('.form-group');
            const errorDiv = formGroup.find('.form-error');
            
            if (phone && !this.isValidPhone(phone)) {
                this.showFieldError(formGroup, errorDiv, 'Please enter a valid phone number');
                return false;
            }
            
            formGroup.removeClass('error');
            errorDiv.removeClass('show');
            return true;
        }

        validateDate() {
            const dateField = $('#preferred_date');
            const selectedDate = new Date(dateField.val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const formGroup = dateField.closest('.form-group');
            const errorDiv = formGroup.find('.form-error');
            
            if (dateField.val() && selectedDate < today) {
                this.showFieldError(formGroup, errorDiv, 'Please select a future date');
                return false;
            }
            
            formGroup.removeClass('error');
            errorDiv.removeClass('show');
            return true;
        }

        validateTime() {
            const timeField = $('#preferred_time');
            const time = timeField.val();
            const formGroup = timeField.closest('.form-group');
            const errorDiv = formGroup.find('.form-error');
            
            if (time) {
                const [hours, minutes] = time.split(':').map(Number);
                const totalMinutes = hours * 60 + minutes;
                const openTime = 8 * 60; // 8:00 AM
                const closeTime = 18 * 60; // 6:00 PM
                
                if (totalMinutes < openTime || totalMinutes > closeTime) {
                    this.showFieldError(formGroup, errorDiv, 'Please select a time between 8:00 AM and 6:00 PM');
                    return false;
                }
            }
            
            formGroup.removeClass('error');
            errorDiv.removeClass('show');
            return true;
        }

        showFieldError(formGroup, errorDiv, message) {
            formGroup.addClass('error');
            errorDiv.text(message).addClass('show');
        }

        formatPhoneNumber(field) {
            let value = field.val().replace(/\D/g, '');
            
            // Format as Philippine number
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+63' + value.substring(1);
            } else if (value.length >= 10 && !value.startsWith('+')) {
                value = '+63' + value;
            }
            
            field.val(value);
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        isValidPhone(phone) {
            // Basic phone validation (Philippine format)
            const phoneRegex = /^(\+63|0)?[0-9]{10}$/;
            return phoneRegex.test(phone.replace(/\s/g, ''));
        }

        formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        formatPrice(price) {
            return parseFloat(price || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        async handleSubmit(e) {
            e.preventDefault();
            
            // Disable submit button
            this.submitButton.prop('disabled', true);
            this.showLoader(true);
            
            // Validate all fields
            let isValid = true;
            this.form.find('input[required], select[required]').each((i, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });
            
            // Validate non-required fields if they have values
            this.form.find('input:not([required]), textarea').each((i, field) => {
                const $field = $(field);
                if ($field.val().trim()) {
                    if (!this.validateField($field)) {
                        isValid = false;
                    }
                }
            });
            
            if (!isValid) {
                this.showLoader(false);
                this.submitButton.prop('disabled', false);
                this.showMessage('Please correct the errors above', 'error');
                return;
            }
            
            try {
                const formData = this.getFormData();
                const response = await this.submitBooking(formData);
                
                if (response.success) {
                    this.customerEmail = this.form.find('#customer_email').val();
                    this.showSuccess(response.message);
                } else {
                    this.handleErrors(response.errors || { general: response.message });
                }
            } catch (error) {
                console.error('Booking submission error:', error);
                this.showMessage('Something went wrong. Please try again.', 'error');
            } finally {
                this.showLoader(false);
                this.submitButton.prop('disabled', false);
            }
        }

        getFormData() {
            const formData = new FormData();
            
            // Manually add form fields to ensure they're included
            formData.append('action', 'submit_user_booking');
            formData.append('nonce', userBookingAjax.nonce);
            
            // Required fields
            const serviceId = this.form.find('#service_id').val();
            const customerName = this.form.find('#customer_name').val();
            const customerEmail = this.form.find('#customer_email').val();
            
            formData.append('service_id', serviceId || '');
            formData.append('customer_name', customerName || '');
            formData.append('customer_email', customerEmail || '');
            
            // Optional fields
            formData.append('customer_phone', this.form.find('#customer_phone').val() || '');
            formData.append('preferred_date', this.form.find('#preferred_date').val() || '');
            formData.append('preferred_time', this.form.find('#preferred_time').val() || '');
            formData.append('message', this.form.find('#booking_message').val() || '');
            
            // Payment method
            const paymentMethod = this.form.find('input[name="payment_method"]:checked').val();
            formData.append('payment_method', paymentMethod || 'cash');
            
            return formData;
        }

        async submitBooking(formData) {
            // Debug: log what we're sending
            console.log('Submitting booking with data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            const response = await fetch(userBookingAjax.ajaxurl, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            
            const jsonResponse = await response.json();
            console.log('Server response:', jsonResponse);
            return jsonResponse;
        }

        handleErrors(errors) {
            // Clear all previous errors
            this.form.find('.form-group').removeClass('error');
            this.form.find('.form-error').removeClass('show');
            
            // Show specific field errors
            Object.keys(errors).forEach(fieldName => {
                const field = this.form.find(`[name="${fieldName}"]`);
                if (field.length) {
                    const formGroup = field.closest('.form-group');
                    const errorDiv = formGroup.find('.form-error');
                    this.showFieldError(formGroup, errorDiv, errors[fieldName]);
                } else {
                    // General error
                    this.showMessage(errors[fieldName], 'error');
                }
            });
        }

        showLoader(show) {
            const loader = this.submitButton.find('.btn-loader');
            const icon = this.submitButton.find('i:not(.btn-loader i)');
            
            if (show) {
                icon.hide();
                loader.show();
            } else {
                icon.show();
                loader.hide();
            }
        }

        showMessage(message, type = 'info') {
            // Remove existing messages
            $('.booking-message').remove();
            
            const messageHtml = `
                <div class="booking-message booking-${type}">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                    ${message}
                </div>
            `;
            
            this.form.prepend(messageHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('.booking-message').fadeOut();
            }, 5000);
        }

        showSuccess(message) {
            this.formWrapper.fadeOut(() => {
                this.successContainer.fadeIn();
            });
        }

        showForm() {
            this.successContainer.fadeOut(() => {
                this.resetForm();
                this.formWrapper.fadeIn();
            });
        }

        viewMyBookings() {
            // Construct URL with customer email filter
            const bookingHistoryUrl = this.getBookingHistoryUrl();
            
            // Option 1: Navigate to booking history page
            if (bookingHistoryUrl) {
                window.location.href = bookingHistoryUrl;
                return;
            }
            
            // Option 2: If booking history is on the same page, scroll to it and filter
            const bookingHistoryElement = $('[id*="booking-history"], .booking-history-container');
            if (bookingHistoryElement.length > 0) {
                // Scroll to booking history
                $('html, body').animate({
                    scrollTop: bookingHistoryElement.offset().top - 100
                }, 800);
                
                // If there's a way to trigger filtering, do it here
                // This would require the booking history to expose a filtering method
                if (window.BookingHistoryFilter && this.customerEmail) {
                    setTimeout(() => {
                        window.BookingHistoryFilter.applyEmailFilter(this.customerEmail);
                    }, 1000);
                }
                return;
            }
            
            // Option 3: Show a message with instructions
            this.showMessage(
                'To view your bookings, please visit the booking history page or contact us with your email: ' + this.customerEmail,
                'info'
            );
        }

        getBookingHistoryUrl() {
            // You can customize these URLs based on your site structure
            const possibleUrls = [
                '/booking-history/',
                '/my-bookings/',
                '/customer-bookings/',
                '/bookings/'
            ];
            
            // If you have a specific booking history page, return it with email filter
            if (userBookingAjax.bookingHistoryUrl) {
                return userBookingAjax.bookingHistoryUrl + '?customer_email=' + encodeURIComponent(this.customerEmail);
            }
            
            // Try the first common URL pattern
            return possibleUrls[0] + '?customer_email=' + encodeURIComponent(this.customerEmail);
        }

        resetForm() {
            // Reset form data
            this.form[0].reset();
            
            // Clear errors
            this.form.find('.form-group').removeClass('error');
            this.form.find('.form-error').removeClass('show');
            
            // Hide service info
            this.serviceInfo.hide();
            
            // Remove messages
            $('.booking-message').remove();
            
            // Reset payment method selection
            $('.payment-option').removeClass('selected');
            $('#payment_cash').prop('checked', true).trigger('change');
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.user-booking-container').offset().top - 100
            }, 500);
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#user-booking-form').length) {
            new UserBookingForm();
        }
    });

    // Add CSS for dynamic elements
    const dynamicStyles = `
        <style>
        .booking-message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .booking-message i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .booking-error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        
        .booking-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
        }
        
        .payment-option.selected .payment-label {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(201, 167, 77, 0.2);
        }
        
        .form-group input:focus + .input-icon,
        .form-group textarea:focus + .input-icon {
            color: #c9a74d;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .form-group.error {
            animation: shake 0.5s ease-in-out;
        }
        </style>
    `;
    
    $('head').append(dynamicStyles);

})(jQuery);
