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
            // Populate staff if a service is preselected
            const initialService = this.serviceSelect.val();
            if (initialService) {
                this.populateStaffForService(initialService);
            }
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
                this.populateStaffForService(serviceId);
            } else {
                this.serviceInfo.hide();
                this.clearStaffOptions();
            }
        }

        populateStaffForService(serviceId) {
            const $grid = $('#staff-grid');
            const $hidden = $('#staff_id');
            if (!$grid.length || !$hidden.length) {
                console.warn('[Booking] Staff grid or hidden input not found. grid:', $grid.length, ' hidden:', $hidden.length);
                return;
            }
            console.log('[Booking] Loading staff grid for service', serviceId);
            // Show loading state
            $grid.html('<div class="staff-grid-empty">Loading staff...</div>');
            $.ajax({
                url: userBookingAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_staff_for_service',
                    nonce: userBookingAjax.nonce,
                    service_id: serviceId
                },
                success: function(resp){
                    if (resp && resp.success && Array.isArray(resp.staff) && resp.staff.length) {
                        const cards = resp.staff.map(function(s){
                            const safeName = String(s.name||'').replace(/</g,'&lt;');
                            const initials = (safeName.trim().split(/\s+/).map(function(p){ return p.charAt(0); }).join('').substring(0,2).toUpperCase()) || '?';
                            const avatar = s.avatar ? '<img src="'+ s.avatar +'" alt="'+ safeName +'" />' : '<div class="staff-initials">'+ initials +'</div>';
                            return '<div class="staff-card" tabindex="0" role="button" aria-pressed="false" data-id="'+ s.id +'">'+
                                   '  <div class="staff-avatar">'+ avatar +'</div>'+ 
                                   '  <div class="staff-name">'+ safeName +'</div>'+ 
                                   '</div>';
                        });
                        $grid.html(cards.join(''));
                        // bind click/select
                        $grid.off('click.staff').on('click.staff', '.staff-card', function(){
                            const $card = $(this);
                            if ($card.hasClass('selected')){
                                $hidden.val('');
                                $card.removeClass('selected').attr('aria-pressed','false');
                            } else {
                                const id = $card.data('id');
                                $hidden.val(id);
                                $card.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false');
                            }
                        });
                        $grid.off('keydown.staff').on('keydown.staff', '.staff-card', function(e){
                            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
                        });
                    } else {
                        $hidden.val('');
                        $grid.html('<div class="staff-grid-empty">No staff available</div>');
                    }
                },
                error: function(){
                    $hidden.val('');
                    $grid.html('<div class="staff-grid-empty">Failed to load staff</div>');
                }
            });
        }

        clearStaffOptions(){
            const $grid = $('#staff-grid');
            const $hidden = $('#staff_id');
            if ($grid.length){ $grid.html('<div class="staff-grid-empty">Select a service to choose staff</div>'); }
            if ($hidden.length){ $hidden.val(''); }
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
            // Preferred staff (optional)
            formData.append('staff_id', this.form.find('#staff_id').val() || '');
            
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
            box-shadow: 0 6px 18px rgba(100,196,147,0.08);
        }
        
        .form-group input:focus + .input-icon,
        .form-group textarea:focus + .input-icon {
            color: var(--accent);
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

    // ============================
    // V3 Wizard/Stepper Functionality
    // ============================

    function UBFv3(selector){
        this.form = $(selector);
        this.current = 1;
        this.total = this.form.find('.ubf-form-step').length;
        this.init();
    }

    UBFv3.prototype.init = function(){
        const self = this;
        this.showStep(1);

        this.form.on('click', '.ubf-next', function(e){
            e.preventDefault();
            self.next();
        });
        this.form.on('click', '.ubf-prev', function(e){
            e.preventDefault();
            self.prev();
        });

        this.form.on('submit', function(e){
            e.preventDefault();
            self.submit();
        });

        // payment option click to toggle radio
        this.form.on('click', '.payment-option', function(){
            const $input = $(this).find('input[type="radio"]');
            if ($input.length){
                $input.prop('checked', true).trigger('change');
                // update visual
                $(this).closest('.payment-methods').find('.payment-option').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        // sync visual on change (in case user clicks the label/input directly)
        this.form.on('change', 'input[name="payment_method"]', function(){
            const val = $(this).val();
            const $group = $(this).closest('.payment-methods');
            $group.find('.payment-option').removeClass('selected');
            $group.find('input[value="'+val+'"]').closest('.payment-option').addClass('selected');
        });

        // update progress on load
        this.updateProgress();

        // Populate v3 staff when service changes or on init if preselected (grid UI)
        const $service = this.form.find('#ubf_service_id');
        const $hiddenStaff = this.form.find('#ubf_staff_id');
        const $grid = this.form.find('#ubf_staff_grid');
        function populateV3(serviceId){
            if (!$grid.length || !$hiddenStaff.length) {
                console.warn('[Booking v3] Staff grid or hidden input not found. grid:', $grid.length, ' hidden:', $hiddenStaff.length);
                return;
            }
            console.log('[Booking v3] Loading staff grid for service', serviceId);
            $grid.html('<div class="staff-grid-empty">Loading staff...</div>');
            // Support both frontend (userBookingAjax) and admin/localized (userBookingV3)
            const ajaxSettings = window.userBookingAjax || window.userBookingV3 || {};
            $.ajax({
                url: ajaxSettings.ajaxurl || '',
                method: 'POST',
                data: { action: 'get_staff_for_service', nonce: ajaxSettings.nonce || '', service_id: serviceId },
                success: function(resp){
                    if (resp && resp.success && Array.isArray(resp.staff) && resp.staff.length){
                        const cards = resp.staff.map(function(s){
                            const safeName = String(s.name||'').replace(/</g,'&lt;');
                            const initials = (safeName.trim().split(/\s+/).map(function(p){ return p.charAt(0); }).join('').substring(0,2).toUpperCase()) || '?';
                            const avatar = s.avatar ? '<img src="'+ s.avatar +'" alt="'+ safeName +'" />' : '<div class="staff-initials">'+ initials +'</div>';
                            return '<div class="staff-card" tabindex="0" role="button" aria-pressed="false" data-id="'+ s.id +'">'+
                                   '  <div class="staff-avatar">'+ avatar +'</div>'+ 
                                   '  <div class="staff-name">'+ safeName +'</div>'+ 
                                   '</div>';
                        });
                        $grid.html(cards.join(''));
                        $grid.off('click.staff').on('click.staff', '.staff-card', function(){
                            const $card = $(this);
                            if ($card.hasClass('selected')){
                                $hiddenStaff.val('');
                                $card.removeClass('selected').attr('aria-pressed','false');
                            } else {
                                const id = $card.data('id');
                                $hiddenStaff.val(id);
                                $card.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false');
                            }
                        });
                        $grid.off('keydown.staff').on('keydown.staff', '.staff-card', function(e){
                            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
                        });
                    } else {
                        $hiddenStaff.val('');
                        $grid.html('<div class="staff-grid-empty">No staff available</div>');
                    }
                },
                error: function(){ $hiddenStaff.val(''); $grid.html('<div class="staff-grid-empty">Failed to load staff</div>'); }
            });
        }
        $service.on('change', function(){ const v = $(this).val(); if (v){ populateV3(v); } else { $hiddenStaff.val(''); $grid.html('<div class="staff-grid-empty">Select a service to choose staff</div>'); } });
        if ($service.val()) { populateV3($service.val()); } else { $grid.html('<div class="staff-grid-empty">Select a service to choose staff</div>'); }
    }

    UBFv3.prototype.showStep = function(step){
        this.form.find('.ubf-form-step').hide();
        this.form.find('.ubf-form-step[data-step="'+step+'"]').show();
        this.current = step;
        this.updateStepper();
        this.updateProgress();
    }

    /**
     * Validate schedule: date not in past, time between business hours, and if date is today time must be future
     */
    UBFv3.prototype.validateSchedule = function(){
        const $date = this.form.find('#ubf_preferred_date');
        const $time = this.form.find('#ubf_preferred_time');
        const $err = this.form.find('.ubf-schedule-error');

        $err.hide().text('');

        const dateVal = $date.val();
        const timeVal = $time.val();

        if (!dateVal && !timeVal){
            // allow empty schedule (optional) but if one is provided the other must be present
            return true;
        }

        if (!dateVal){
            $err.text('Please select a preferred date.').show();
            return false;
        }
        if (!timeVal){
            $err.text('Please select a preferred time.').show();
            return false;
        }

        // business hours from attributes (default 08:00 - 18:00)
        const businessStart = $date.attr('data-business-start') || '08:00';
        const businessEnd = $date.attr('data-business-end') || '18:00';

        // parse dates
        const selectedDate = new Date(dateVal + 'T' + timeVal);
        const now = new Date();

        // check past datetime
        if (selectedDate < now){
            $err.text('Selected date/time is in the past. Please choose a future time.').show();
            return false;
        }

        // compare time only to business hours
        const timeOnly = timeVal;
        if (timeOnly < businessStart || timeOnly > businessEnd){
            $err.text('Selected time is outside of business hours ('+businessStart+' - '+businessEnd+').').show();
            return false;
        }

        // if date is today, ensure time is in the future (already covered by selectedDate < now)

        return true;
    }

    UBFv3.prototype.updateStepper = function(){
        const steps = this.form.closest('.ubf-v3-form-wrapper').find('.ubf-step');
        steps.removeClass('active');
        steps.each(function(){
            const s = parseInt($(this).attr('data-step'),10);
            if (s === window.ubfTempCurrent) return; // unused
        });
        steps.filter('[data-step="'+this.current+'"]').addClass('active');
    }

    UBFv3.prototype.updateProgress = function(){
        const denom = Math.max(this.total-1,1);
        const pct = ((this.current-1)/denom)*100;
        this.form.closest('.ubf-v3-form-wrapper').find('.ubf-progress-fill').css('width', pct+'%');
    }

    UBFv3.prototype.next = function(){
        // if moving from step 3 -> 4 validate schedule
        if (this.current === 3){
            const scheduleOK = this.validateSchedule();
            if (!scheduleOK) return; // don't proceed
        }

        if (this.current < this.total){
            this.showStep(this.current+1);
        }
    }

    UBFv3.prototype.prev = function(){
        if (this.current > 1){
            this.showStep(this.current-1);
        }
    }

    UBFv3.prototype.submit = function(){
        const self = this;
        // basic validation
        const name = this.form.find('#ubf_customer_name').val() || '';
        const email = this.form.find('#ubf_customer_email').val() || '';
        const service = this.form.find('#ubf_service_id').val() || '';
        if (!name || !email || !service){
            alert('Please fill required fields: service, name and email');
            return;
        }

        // validate schedule one more time before submitting
        if (!this.validateSchedule()){
            // if invalid schedule, jump to step 3
            this.showStep(3);
            return;
        }

        const data = this.form.serializeArray();
        data.push({name:'action',value:'submit_user_booking_v3'});
        data.push({name:'nonce',value:userBookingV3.nonce});

        $.ajax({
            url: userBookingV3.ajaxurl,
            method: 'POST',
            data: $.param(data),
            success: function(resp){
                if (resp && resp.success){
                    // show inline success UI
                    $('.ubf-v3-form-wrapper').hide();
                    $('#ubf-v3-success').fadeIn();
                    // attach actions
                    $('#ubf-v3-success-new').off('click').on('click', function(){
                        $('#ubf-v3-success').hide();
                        $('.ubf-v3-form-wrapper').show();
                        self.form[0].reset();
                        self.showStep(1);
                    });
                    $('#ubf-v3-success-view').off('click').on('click', function(){
                        // try to go to booking history if available
                        if (userBookingV3.bookingHistoryUrl) {
                            window.location.href = userBookingV3.bookingHistoryUrl;
                        } else {
                            // fallback: just hide success and show form
                            $('#ubf-v3-success').hide();
                            $('.ubf-v3-form-wrapper').show();
                        }
                    });
                } else {
                    alert(resp && resp.message ? resp.message : userBookingV3.messages.error);
                }
            },
            error: function(){
                alert(userBookingV3.messages.error);
            }
        });
    }

    // Initialize v3 form(s) if present
    // Support multiple admin forms that use the UBF v3 markup by initializing
    // a UBFv3 instance for each `.ubf-v3-form`. Fall back to the legacy
    // `#user-booking-form-v3` selector for backward compatibility.
    $(function(){
        const $v3Forms = $('.ubf-v3-form');
        if ($v3Forms.length){
            $v3Forms.each(function(){
                try { new UBFv3(this); } catch (e){ console.error('Failed to init UBFv3 on element', this, e); }
            });
            return;
        }

        // Backwards-compatible single-form init
        if ($('#user-booking-form-v3').length){
            new UBFv3('#user-booking-form-v3');
        }
    });

})(jQuery);
