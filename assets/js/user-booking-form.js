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
                // include a deterministic schedule key so client and server can map rows reliably
                const sk = (serviceId || '') + '|' + (staffId || '') + '|' + $('<div>').text(existing.date).html() + '|' + $('<div>').text(existing.time).html();
                row += '<input type="hidden" name="schedule_key[]" value="'+ $('<div>').text(sk).html() +'" />';
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
            // read selected preferred date/time from the legacy form if available
            const prefDate = $('#preferred_date').val() || '';
            const prefTime = $('#preferred_time').val() || '';
            const keys = []; // Initialize keys array

            $.ajax({
                url: userBookingAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_staff_for_service',
                    nonce: userBookingAjax.nonce,
                    service_id: serviceId,
                    preferred_date: prefDate,
                    preferred_time: prefTime
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
                            // Notify any listeners (e.g. v3 schedule renderer) that staff selection changed
                            try { $hidden.trigger('change'); } catch(e){}
                        });
                        // If a staff id was previously set on the hidden input, auto-select that card
                        try {
                            const prev = String($hidden.val() || '').trim();
                            if (prev) {
                                const $match = $grid.find('.staff-card').filter(function(){ return String($(this).data('id')) === prev; }).first();
                                if ($match.length) {
                                    $match.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false');
                                    $hidden.val(prev);
                                    try { $hidden.trigger('change'); } catch(e){}
                                }
                            }
                        } catch(e) { /* ignore */ }
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
                const openTime = 9 * 60; // 9:00 AM
                const closeTime = 19 * 60; // 7:00 PM

                if (totalMinutes < openTime || totalMinutes > closeTime) {
                    this.showFieldError(formGroup, errorDiv, 'Please select a time between 9:00 AM and 7:00 PM');
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
            formData.append('schedule_key', this.form.find('input[name="schedule_key[]"]').val() || ''); // Add schedule_key to form data
            
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
                const msg = errors[fieldName];
                const field = this.form.find(`[name="${fieldName}"]`);
                if (field.length) {
                    const formGroup = field.closest('.form-group');
                    const errorDiv = formGroup.find('.form-error');
                    this.showFieldError(formGroup, errorDiv, msg);
                } else {
                    // General error
                    // Special-case overlap/conflict messages so we show schedule/staff hints
                    if (typeof msg === 'string' && /overlap|not available|conflict/i.test(msg)) {
                        // Highlight schedule area if present
                        const scheduleErr = this.form.find('.ubf-schedule-error');
                        if (scheduleErr.length) {
                            scheduleErr.text(msg).show();
                            // Jump to schedule step in v3
                            if (this.form.hasClass('ubf-v3-form')) {
                                try { const ubf = this.form.data('ubf-instance'); if (ubf) ubf.showStep(3); } catch(e) {}
                            }
                        }

                        // For legacy form, highlight preferred_time and staff
                        const prefTime = this.form.find('#preferred_time');
                        const staffHidden = this.form.find('#staff_id');
                        if (prefTime.length) { prefTime.closest('.form-group').addClass('error'); prefTime.closest('.form-group').find('.form-error').text(msg).addClass('show'); }
                        if (staffHidden.length) {
                            const sid = staffHidden.val();
                            if (sid) {
                                const card = this.form.find('#staff-grid').find('.staff-card[data-id="'+sid+'"]');
                                if (card.length) { card.addClass('error-selected'); }
                            }
                        }

                        this.showMessage(msg, 'error');
                    } else {
                        this.showMessage(errors[fieldName], 'error');
                    }
                }
            });
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
        // Always override window.alert on pages where booking forms may run so any
        // remaining alert() calls get converted to non-blocking inline messages.
        if (!window.__ubf_alert_overridden) {
            window.__ubf_original_alert = window.alert;
            window.alert = function(msg) {
                try {
                    // Prefer the legacy form if present, otherwise the v3 form
                    const $form = $('#user-booking-form').length ? $('#user-booking-form') : $('.ubf-v3-form').first();
                    if ($form && $form.length) {
                        // Try to show schedule-specific hint if available
                        const $hint = $form.find('.ubf-schedule-error');
                        if ($hint.length) {
                            $hint.text(String(msg)).show();
                        } else {
                            // Generic booking message fallback
                            const safe = $('<div>').text(String(msg)).html();
                            $form.prepend('<div class="booking-message booking-error">' + safe + '</div>');
                        }
                    } else {
                        // If no booking form present, fall back to original alert
                        try { window.__ubf_original_alert(msg); } catch(e){ console.warn('Fallback alert failed', e); }
                    }
                } catch (e) {
                    try { console.warn('Alert interception error', e); } catch(_){}
                }
            };
            window.__ubf_alert_overridden = true;
        }

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
        .staff-card.error-selected {
            outline: 2px solid rgba(231,76,60,0.9);
            box-shadow: 0 6px 18px rgba(231,76,60,0.08);
            transform: translateY(-2px);
        }
        .ubf-schedule-error { display: none; color: #c43d3d; margin-bottom: 8px; }
        .ubf-schedule-block.error { border: 1px solid rgba(196,57,61,0.9); padding: 8px; border-radius:6px; background: rgba(231,76,60,0.03); }

        /* Simple styled modal for schedule conflicts */
    /* modal rules removed - conflicts now shown inline per-row */
    .ubf-row-error { display:block; color:#c43d3d; margin-top:6px; font-size:0.95em; line-height:1.18; }
    .ubf-row-error::before { content: '\\26A0\\00A0'; /* warning icon */ font-weight:600; }
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
        // expose instance on form for external handlers
        try { this.form.data('ubf-instance', this); } catch(e) {}
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

    // Support multiple service blocks: cloneable blocks with per-block staff grid
    this.blocksContainer = this.form.closest('.ubf-v3-form-wrapper').find('.ubf-service-blocks');
    const $blocksContainer = this.blocksContainer;
        function populateBlock($block, serviceId){
            const $grid = $block.find('.ubf-staff-grid');
            const $hidden = $block.find('.ubf-staff-input');
            if (!$grid.length || !$hidden.length) {
                console.warn('[Booking v3] Staff grid or hidden input not found for block.');
                return;
            }
            $grid.html('<div class="staff-grid-empty">Loading staff...</div>');
            const ajaxSettings = window.userBookingAjax || window.userBookingV3 || {};
            // try to read v3 preferred date/time fields if present
            const prefDate = $('#ubf_preferred_date').val() || $('#preferred_date').val() || '';
            const prefTime = $('#ubf_preferred_time').val() || $('#preferred_time').val() || '';
            $.ajax({
                url: ajaxSettings.ajaxurl || '',
                method: 'POST',
                data: { action: 'get_staff_for_service', nonce: ajaxSettings.nonce || '', service_id: serviceId, preferred_date: prefDate, preferred_time: prefTime },
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
                                    $hidden.val('');
                                    $card.removeClass('selected').attr('aria-pressed','false');
                                } else {
                                    const id = $card.data('id');
                                    $hidden.val(id);
                                    $card.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false');
                                }
                                // Trigger change so the per-service schedule UI updates
                                try { $hidden.trigger('change'); } catch(e){}
                            });
                            // Auto-select from hidden value if present so schedule labels show staff names
                            try {
                                const preset = String($hidden.val() || '').trim();
                                if (preset) {
                                    const $m = $grid.find('.staff-card').filter(function(){ return String($(this).data('id')) === preset; }).first();
                                    if ($m.length) { $m.addClass('selected').attr('aria-pressed','true').siblings().removeClass('selected').attr('aria-pressed','false'); }
                                }
                            } catch(err) { /* ignore */ }
                        $grid.off('keydown.staff').on('keydown.staff', '.staff-card', function(e){
                            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
                        });
                    } else {
                        $hidden.val('');
                        $grid.html('<div class="staff-grid-empty">No staff available</div>');
                    }
                },
                error: function(){ $hidden.val(''); $grid.html('<div class="staff-grid-empty">Failed to load staff</div>'); }
            });
        }

        // wire up change handlers for existing and future blocks
        function wireBlock($block){
            const $select = $block.find('.ubf-service-select');
            $select.off('change.ubf').on('change.ubf', function(){
                const v = $(this).val();
                if (v){ populateBlock($block, v); } else { $block.find('.ubf-staff-input').val(''); $block.find('.ubf-staff-grid').html('<div class="staff-grid-empty">Select a service to choose staff</div>'); }
            });
            // if select already has value, populate
            if ($select.val()){ populateBlock($block, $select.val()); }
        }

        // Debounce helper
        function debounce(fn, wait) {
            let t;
            return function() {
                const ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
            };
        }

        // Instead of showing an overlapping modal, write a concise red inline error
        function showConflictModal(conflictsOrItems, existingMap) {
            console.debug('[UBF] showConflictModal called with:', conflictsOrItems, 'existingMap:', existingMap);
            // Clear all per-row errors first
            const $scheduleRoot = $('#ubf-per-service-schedule');
            $scheduleRoot.find('.ubf-row-error').hide().text('');
            // normalize to an array
            const list = Array.isArray(conflictsOrItems) ? conflictsOrItems : (conflictsOrItems ? [conflictsOrItems] : []);
            if (!list.length) {
                // also clear global schedule error
                $('.ubf-schedule-error').hide().text('');
                return;
            }

            // helper: try to find a row index from a descriptive item (service/staff/date/time)
            function findRowIndexFromItem(item) {
                try {
                    const rows = $scheduleRoot.find('.ubf-per-service-row');
                    for (let ri = 0; ri < rows.length; ri++) {
                        const $r = rows.eq(ri);
                        const svcText = $r.find('.ubf-per-service-label strong').text().trim();
                        const staffText = $r.find('.ubf-per-service-staff').text().trim();
                        const d = $r.find('.ubf-row-date').val() || '';
                        const t = $r.find('.ubf-row-time').val() || '';
                        // match by provided fields when available
                        if (item.service && String(item.service).trim() && String(item.service).trim() !== svcText) continue;
                        if (item.staff && String(item.staff).trim() && String(item.staff).trim() !== staffText) continue;
                        if (item.date && String(item.date).trim() && String(item.date).trim() !== d) continue;
                        if (item.time && String(item.time).trim() && String(item.time).trim() !== t) continue;
                        return ri;
                    }
                } catch (e) { console.warn('[UBF] findRowIndexFromItem error', e); }
                return -1;
            }

            // Build a set of indices to mark. Also keep a mapping from resolved DOM index -> original server index so we can surface existing booking ids
            const indicesToMark = new Set();
            const resolvedMap = {}; // resolvedDomIndex -> originalServerIndex
            list.forEach(function(it){
                if (typeof it === 'number') {
                    const orig = it;
                    let ii = orig;
                    const $byIndexRow = $scheduleRoot.find('.ubf-per-service-row').eq(ii);
                    if (!$byIndexRow.length) {
                        // Try nearby indices (server may be 1-based or off-by-one)
                        const $minus = $scheduleRoot.find('.ubf-per-service-row').eq(orig-1);
                        const $plus = $scheduleRoot.find('.ubf-per-service-row').eq(orig+1);
                        if ($minus.length) { ii = orig-1; }
                        else if ($plus.length) { ii = orig+1; }
                        else {
                            // try resolve via last payload stored on the form
                            const $form = $scheduleRoot.closest('.ubf-v3-form');
                            const last = $form.data('ubf-last-schedule-payload') || null;
                            if (last && Array.isArray(last.schedule_service_id) && last.schedule_service_id.length > orig) {
                                const svcId = String(last.schedule_service_id[orig] || '');
                                const staffId = String(last.schedule_staff_id && last.schedule_staff_id[orig] ? last.schedule_staff_id[orig] : '');
                                const pd = String(last.preferred_date && last.preferred_date[orig] ? last.preferred_date[orig] : '');
                                const pt = String(last.preferred_time && last.preferred_time[orig] ? last.preferred_time[orig] : '');
                                let resolved = -1;
                                $scheduleRoot.find('.ubf-per-service-row').each(function(rj){
                                    const $r = $(this);
                                    const rs = String($r.find('input[name="schedule_service_id[]"]').val() || '');
                                    const rstaff = String($r.find('input[name="schedule_staff_id[]"]').val() || '');
                                    const rd = String($r.find('.ubf-row-date').val() || '');
                                    const rt = String($r.find('.ubf-row-time').val() || '');
                                    if (rs === svcId && (staffId === '' || rstaff === staffId) && (pd === '' || rd === pd) && (pt === '' || rt === pt)) { resolved = rj; return false; }
                                });
                                if (resolved >= 0) {
                                    console.debug('[UBF] resolved server index', orig, 'to DOM row', resolved, 'via last payload');
                                    ii = resolved;
                                } else {
                                    console.warn('[UBF] could not resolve server index to DOM row using last payload', orig, last);
                                }
                            } else {
                                console.warn('[UBF] no last payload available to resolve server index', orig);
                            }
                        }
                    }
                    indicesToMark.add(ii);
                    resolvedMap[ii] = orig;
                } else if (it && typeof it === 'object') {
                    // server might return descriptive objects with index OR service_id/staff_id/date/time
                    if (typeof it.index === 'number') {
                        indicesToMark.add(it.index);
                        resolvedMap[it.index] = it.index;
                    } else {
                        // attempt to resolve by enriched fields first
                        let ri = -1;
                        if (it.service_id || it.staff_id || it.date || it.time) {
                            // match using hidden inputs and row fields
                            $scheduleRoot.find('.ubf-per-service-row').each(function(rj){
                                const $r = $(this);
                                const rs = String($r.find('input[name="schedule_service_id[]"]').val() || '');
                                const rstaff = String($r.find('input[name="schedule_staff_id[]"]').val() || '');
                                const rd = String($r.find('.ubf-row-date').val() || '');
                                const rt = String($r.find('.ubf-row-time').val() || '');
                                if (it.service_id && String(it.service_id).trim() && String(it.service_id).trim() !== rs) return true;
                                if (it.staff_id && String(it.staff_id).trim() && String(it.staff_id).trim() !== rstaff) return true;
                                if (it.date && String(it.date).trim() && String(it.date).trim() !== rd) return true;
                                if (it.time && String(it.time).trim() && String(it.time).trim() !== rt) return true;
                                ri = rj; return false;
                            });
                        }
                        if (ri >= 0) { indicesToMark.add(ri); resolvedMap[ri] = (it.original_index || it.index || null); }
                        else {
                            const found = findRowIndexFromItem(it);
                            if (found >= 0) { indicesToMark.add(found); resolvedMap[found] = (it.original_index || null); }
                            else console.warn('[UBF] could not resolve item to a row index', it);
                        }
                    }
                }
            });

            // If nothing resolved, try fallback heuristics: match by service_id only, then by service+staff
            if (indicesToMark.size === 0 && Array.isArray(list) && list.length) {
                console.debug('[UBF] attempting fallback resolution for conflict items');
                list.forEach(function(it){
                    if (!it || typeof it !== 'object') return;
                    // try service_id match first
                    if (it.service_id) {
                        $scheduleRoot.find('.ubf-per-service-row').each(function(rj){
                            const $r = $(this);
                            const rs = String($r.find('input[name="schedule_service_id[]"]').val() || '');
                            if (rs === String(it.service_id)) { indicesToMark.add(rj); resolvedMap[rj] = (it.original_index || null); return false; }
                        });
                    }
                    // if still not found, try service+staff match
                    if (indicesToMark.size === 0 && it.service_id && it.staff_id) {
                        $scheduleRoot.find('.ubf-per-service-row').each(function(rj){
                            const $r = $(this);
                            const rs = String($r.find('input[name="schedule_service_id[]"]').val() || '');
                            const rstaff = String($r.find('input[name="schedule_staff_id[]"]').val() || '');
                            if (rs === String(it.service_id) && rstaff === String(it.staff_id)) { indicesToMark.add(rj); resolvedMap[rj] = (it.original_index || null); return false; }
                        });
                    }
                });
            }

            // If still nothing resolved, show a global hint and highlight all rows as a last resort
            if (indicesToMark.size === 0) {
                console.warn('[UBF] showConflictModal: could not resolve any conflict items to specific rows; showing global hint');
                $('.ubf-schedule-error').text('One or more selected services conflict with existing bookings. Please review your selected times.').show();
                // show per-row generic error text
                $scheduleRoot.find('.ubf-per-service-row').each(function(rj){
                    const $r = $(this);
                    const svc = $r.find('.ubf-per-service-label strong').text() || 'Service';
                    const staff = $r.find('.ubf-per-service-staff').text() || 'Any staff';
                    const html = '<div>' + $('<div>').text(svc.trim() + ' – ' + staff.trim() + ' may conflict with existing bookings.').html() + '</div>';
                    $r.find('.ubf-row-error').html(html).show();
                    $r.addClass('error');
                });
                return;
            }

            // Populate per-row error messages for resolved indices
            Array.from(indicesToMark).forEach(function(i){
                // Map existing booking id from server's index to resolved DOM index
                const serverIdx = (resolvedMap && typeof resolvedMap[i] !== 'undefined') ? resolvedMap[i] : i;
                const existing = existingMap && typeof serverIdx !== 'undefined' && existingMap[serverIdx] ? (' (Existing booking #' + existingMap[serverIdx] + ')') : '';
                const $row = $scheduleRoot.find('.ubf-per-service-row').eq(i);
                if (!$row.length) {
                    console.warn('[UBF] showConflictModal: no row found for index', i);
                    return;
                }
                const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                const d = $row.find('.ubf-row-date').val() || '';
                const t = $row.find('.ubf-row-time').val() || '';
                const primary = svc.trim() + ' – ' + staff.trim() + ' is already booked.';
                const detail = (d || t || existing) ? ( (d ? ' ' + d : '') + (t ? ' ' + t : '') + (existing ? ' ' + existing : '') ) : '';
                const safePrimary = $('<div>').text(primary).html();
                const safeDetail = $('<div>').text(detail.trim()).html();
                const html = '<div>' + safePrimary + '</div>' + (safeDetail ? '<small style="display:block;opacity:0.9;margin-top:4px">' + safeDetail + '</small>' : '');
                $row.find('.ubf-row-error').html(html).show();
                // also mark the row visually
                $row.addClass('error');
                console.debug('[UBF] marked row', i, 'with error:', primary, detail);
            });

            // set a concise global hint without being intrusive
            $('.ubf-schedule-error').text('One or more selected services conflict with existing bookings.').show();
        }

        // When schedule inputs change, call server to validate availability (debounced)
        const debouncedCheck = debounce(function(){
            // collect per-service schedule rows (renderPerServiceSchedule produces .ubf-per-service-row)
            const $rows = $('#ubf-per-service-schedule').find('.ubf-per-service-row');
            const $scheduleForm = $('#ubf-per-service-schedule').closest('.ubf-v3-form');
            if (!$rows.length) {
                // clear any existing schedule errors
                $('.ubf-schedule-error').hide().text('');
                return;
            }
            const data = { action: 'check_schedule_availability_v3', nonce: window.userBookingAjax ? userBookingAjax.nonce : '', };
            const svc = [], staff = [], date = [], time = [];
            $rows.each(function(idx, el){
                const $el = $(el);
                // hidden inputs rendered into each row
                const s = $el.find('input[name="schedule_service_id[]"]').val() || '';
                const st = $el.find('input[name="schedule_staff_id[]"]').val() || '';
                const d = $el.find('.ubf-row-date').val() || '';
                const t = $el.find('.ubf-row-time').val() || '';
                svc.push(s);
                staff.push(st);
                date.push(d);
                time.push(t);
            });
            data['schedule_service_id'] = svc;
            data['schedule_staff_id'] = staff;
            data['preferred_date'] = date;
            data['preferred_time'] = time;

            // persist the last payload on the form so conflict indices can be robustly
            // mapped back to DOM rows even if DOM order changed between requests
            try { $scheduleForm.data('ubf-last-schedule-payload', { schedule_service_id: svc, schedule_staff_id: staff, preferred_date: date, preferred_time: time }); } catch(e){}

            $.ajax({
                url: userBookingAjax.ajaxurl,
                method: 'POST',
                data: data,
                success: function(resp){
                    console.debug('[UBF] check_schedule_availability_v3 response:', resp);
                    const $scheduleForm = $('#ubf-per-service-schedule').closest('.ubf-v3-form');
                    if (resp && resp.success) {
                // Clear any existing schedule error and per-row messages
                $('.ubf-schedule-error').hide().text('');
                // clear per-row inline error text and hide
                $('#ubf-per-service-schedule').find('.ubf-row-error').hide().text('');
                // remove error class on blocks/rows
                $('#ubf-per-service-schedule').find('.ubf-schedule-block, .ubf-per-service-row').removeClass('error');
                // clear stored conflict flags on the form
                try { $scheduleForm.data('ubf-has-schedule-conflict', false); $scheduleForm.data('ubf-schedule-conflicts', []); } catch(e){}
                // remove any modal we showed
                $('#ubf-conflict-modal').remove();
                    } else {
                        // Prefer descriptive conflict items when the server provides them
                        const conflictItems = resp && resp.conflict_items ? resp.conflict_items : (resp && resp.conflict_rows ? resp.conflict_rows : []);
                        const existing = resp && resp.conflict_existing ? resp.conflict_existing : {};
                        console.debug('[UBF] availability conflicts:', conflictItems, 'existing map:', existing);
                        // store conflict info on the form so UBFv3.next can block progression
                        try { $scheduleForm.data('ubf-has-schedule-conflict', true); $scheduleForm.data('ubf-schedule-conflicts', conflictItems || []); } catch(e){}
                        // write inline conflict message and mark blocks (showConflictModal handles marking)
                        try { showConflictModal(conflictItems, existing); } catch(e){}
                    }
                },
                error: function(){ /* ignore network failures for now */ }
            });
        }, 450);

        // Attach change listeners to per-service schedule container (delegated)
        // Use the actual classes rendered for rows (.ubf-row-date / .ubf-row-time)
        $(document).on('change', '#ubf-per-service-schedule .ubf-row-date, #ubf-per-service-schedule .ubf-row-time', function(){
            debouncedCheck();
        });

        // When the service blocks change (service select or staff selection), re-render rows and check availability
        $blocksContainer.on('change', '.ubf-service-select, .ubf-staff-input', function(){ renderPerServiceSchedule(); debouncedCheck(); });

        // Refresh all blocks when global v3 date/time changes OR when any block-specific date/time changes
        function refreshAllBlocks(){
            $blocksContainer.find('.ubf-service-block').each(function(){
                const $b = $(this);
                const s = $b.find('.ubf-service-select').val();
                if (s) populateBlock($b, s);
            });
            // also refresh per-service schedule list when services/staff change
            renderPerServiceSchedule();
        }

        $('#ubf_preferred_date, #ubf_preferred_time').on('change', refreshAllBlocks);

        // When staff selection or service selection changes, update the per-service schedule UI
    $blocksContainer.on('change', '.ubf-service-select, .ubf-staff-input', function(){ renderPerServiceSchedule(); });

        // initialize existing blocks
        if ($blocksContainer.length){
            $blocksContainer.find('.ubf-service-block').each(function(){ wireBlock($(this)); });

            // Add service button
            const $addBtn = this.form.closest('.ubf-v3-form-wrapper').find('.ubf-add-service');
            $addBtn.off('click.ubf').on('click.ubf', (e) => {
                e.preventDefault();
                const $first = $blocksContainer.find('.ubf-service-block').first();
                const $clone = $first.clone();
                // clear values in clone
                $clone.find('select').val('');
                $clone.find('.ubf-staff-input').val('');
                $clone.find('.ubf-staff-grid').html('<div class="staff-grid-empty">Select a service to choose staff</div>');
                // add a remove button
                $clone.append('<div style="margin-top:8px;text-align:right"><button type="button" class="ubf-remove-service">Remove</button></div>');
                $blocksContainer.append($clone);
                wireBlock($clone);
            });

            // Remove handler using event delegation
            $blocksContainer.on('click', '.ubf-remove-service', function(e){ e.preventDefault(); $(this).closest('.ubf-service-block').remove(); });
        }

        // Render per-service schedule rows in step 3
        function renderPerServiceSchedule(){
            const $scheduleRoot = $('#ubf-per-service-schedule');
            if (!$scheduleRoot.length) return;
            // Preserve any existing per-service date/time values keyed by block data-idx
            const existingValues = {};
            $scheduleRoot.find('.ubf-per-service-row').each(function(){
                const $r = $(this);
                const di = $r.data('idx');
                if (typeof di !== 'undefined') {
                    existingValues[String(di)] = {
                        date: $r.find('.ubf-row-date').val() || '',
                        time: $r.find('.ubf-row-time').val() || ''
                    };
                }
            });

            const rows = [];
            $blocksContainer.find('.ubf-service-block').each(function(idx){
                const $b = $(this);
                const serviceId = $b.find('.ubf-service-select').val() || '';
                const serviceText = $b.find('.ubf-service-select option:selected').text() || 'Service';
                const staffId = $b.find('.ubf-staff-input').val() || '';
                const staffName = $b.find('.ubf-staff-grid .staff-card.selected .staff-name').text() || '';

                if (!serviceId) return; // skip empty

                // each row includes hidden inputs so they're included in serializeArray()
                const idxEsc = idx;
                let row = '<div class="ubf-per-service-row" data-idx="'+idxEsc+'">';
                row += '<div class="ubf-per-service-label"><strong>' + $('<div>').text(serviceText).html() + '</strong>';
                if (staffName) row += ' &ndash; <span class="ubf-per-service-staff">' + $('<div>').text(staffName).html() + '</span>';
                // per-row inline error text (hidden by default) shown under the label when that row conflicts
                row += '<div class="ubf-row-error" style="display:none;color:#c43d3d;margin-top:6px;font-size:0.95em"></div>';
                row += '</div>';

                row += '<div class="ubf-per-service-controls">';
                // service_id[] and staff_id[] already exist on the service blocks in step 1
                // we do not duplicate hidden inputs here to avoid duplicated/misaligned arrays on submit

                // include schedule-scoped hidden ids so PHP can align arrays reliably
                row += '<input type="hidden" name="schedule_service_id[]" value="'+ serviceId +'" />';
                row += '<input type="hidden" name="schedule_staff_id[]" value="'+ staffId + '" />';
                // date (preserve existing value if present)
                const existing = existingValues[String(idx)] || {date:'', time: ''};
                row += '<label class="sr-only">Preferred date for '+ $('<div>').text(serviceText).html() +'</label>';
                row += '<input type="date" name="preferred_date[]" class="ubf-row-date" min="'+ ( $('#ubf_preferred_date').attr('min') || '' ) +'" value="'+ $('<div>').text(existing.date).html() +'" />';

                // time select (preserve existing selection)
                row += '<label class="sr-only">Preferred time for '+ $('<div>').text(serviceText).html() +'</label>';
                row += '<select name="preferred_time[]" class="ubf-row-time"><option value="">Select time (optional)</option>';
                for (let h=9; h<=19; h++){ const v = (h<10? '0'+h : h) + ':00'; const d = new Date('1970-01-01T'+v); const display = (d.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'}));
                    const sel = (existing.time === v) ? ' selected' : '';
                    row += '<option value="'+v+'"'+sel+'>'+display+'</option>'; }
                row += '</select>';

                row += '</div></div>';
                rows.push(row);
            });

            if (rows.length){
                $scheduleRoot.html(rows.join(''));
                // hide placeholder
                $scheduleRoot.find('.ubf-schedule-placeholder').remove();
            } else {
                $scheduleRoot.html('<div class="ubf-schedule-placeholder">Select and configure services in step 1 to set schedules per service.</div>');
            }
        }
    }

    // Show conflict modal wrapper -> uses inline error writer instead of overlay
    UBFv3.prototype.showConflictModal = function(title, messageOrItems, maybeItems){
        // Prefer using stored conflict indices; fallback to any items passed
        let conflicts = this.form.data('ubf-schedule-conflicts') || [];
        if ((!conflicts || !conflicts.length) && Array.isArray(messageOrItems)) {
            // try to map items back to indices by matching service labels
            conflicts = messageOrItems.map(function(it){ return it.index; }).filter(function(i){ return typeof i !== 'undefined'; });
        } else if ((!conflicts || !conflicts.length) && Array.isArray(maybeItems)) {
            conflicts = maybeItems.map(function(it){ return it.index; }).filter(function(i){ return typeof i !== 'undefined'; });
        }
        const existingMap = {};
        try { showConflictModal(conflicts || [], existingMap); } catch(e){ console.warn('Failed to render inline conflict message', e); }
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
        // Validate presence and basic correctness for per-service rows if present
        const $err = this.form.find('.ubf-schedule-error');
        $err.hide().text('');

        const $rows = this.form.find('#ubf-per-service-schedule .ubf-per-service-row');
        if (!$rows.length){
            // allow empty schedules (optional) but frontend uniqueness will be skipped
            return true;
        }

        const now = new Date();
        let ok = true;

        $rows.each(function(){
            const $r = $(this);
            const dateVal = $r.find('.ubf-row-date').val();
            const timeVal = $r.find('.ubf-row-time').val();

            // If one provided, require the other
            if ((dateVal && !timeVal) || (!dateVal && timeVal)){
                ok = false;
                $err.text('Please provide both date and time for each scheduled service, or leave both blank.').show();
                return false; // break
            }

            if (dateVal && timeVal){
                const selectedDate = new Date(dateVal + 'T' + timeVal);
                if (selectedDate < now){ ok = false; $err.text('One or more selected date/time values are in the past.').show(); return false; }

                // business hours via global element or row attributes (fallback)
                const businessStart = $('#ubf_preferred_date').attr('data-business-start') || '09:00';
                const businessEnd = $('#ubf_preferred_date').attr('data-business-end') || '19:00';
                if (timeVal < businessStart || timeVal > businessEnd){ ok = false; $err.text('One or more selected times fall outside business hours.').show(); return false; }
            }
        });

        return ok;
    }

    /**
     * Ensure no two blocks conflict: same staff at same date+time or same service+staff at same date+time
     */
    UBFv3.prototype.validateBlocksUniqueness = function(){
            const self = this;
            const $rows = this.form.find('#ubf-per-service-schedule .ubf-per-service-row');
            const seen = {};
            const conflicts = [];

            // clear previous highlights
            this.form.find('.ubf-per-service-row').removeClass('error');
            this.form.find('.ubf-staff-grid .staff-card').removeClass('error-selected');

            $rows.each(function(){
                const $r = $(this);
                const idx = $r.data('idx');
                let serviceId = $r.find('input[name="schedule_service_id[]"]').val() || '';
                let staffId = $r.find('input[name="schedule_staff_id[]"]') .val() || '';
                // fallback: if hidden staff id missing, try to read selected staff card from the corresponding block
                if (!staffId) {
                    try {
                        const $block = (self.blocksContainer).find('.ubf-service-block').eq(idx);
                        const $card = $block.find('.ubf-staff-grid .staff-card.selected');
                        if ($card.length) {
                            const bid = $card.data('id');
                            if (bid) staffId = String(bid);
                        }
                    } catch (e) { /* ignore */ }
                }

                const date = $r.find('.ubf-row-date').val() || '';
                const time = $r.find('.ubf-row-time').val() || '';

                // Normalize ids to trimmed strings for stable comparisons
                serviceId = String(serviceId).trim();
                staffId = (typeof staffId !== 'undefined' && staffId !== null) ? String(staffId).trim() : '';

                // Debug: verbose log to help diagnose mismatch between selected staff and hidden inputs
                if (window.console && window.console.log) {
                    console.log('[UBFv3] row', idx, 'serviceId=', serviceId, 'staffId=', staffId, 'date=', date, 'time=', time);
                }
                // Debug logging to help diagnose why conflicts may be missed in the wild
                if (window.console && window.console.log) {
                    console.log('[UBFv3] validateBlocksUniqueness row', idx, { serviceId: serviceId, staffId: staffId, date: date, time: time });
                }

                if (!date || !time) return; // only validate rows that have full schedule

                // Only consider conflicts when a staff is explicitly selected (we can't know 'any' assignments)
                if (staffId) {
                    const keyStaff = 'staff|' + staffId + '|' + date + '|' + time;
                    if (seen[keyStaff]) {
                        conflicts.push(idx);
                        seen[keyStaff].forEach(function(prev){ conflicts.push(prev); });
                    } else { seen[keyStaff] = []; }
                    seen[keyStaff].push(idx);

                    if (serviceId) {
                        const keyServiceStaff = 'service|' + serviceId + '|' + staffId + '|' + date + '|' + time;
                        if (seen[keyServiceStaff]) {
                            conflicts.push(idx);
                            seen[keyServiceStaff].forEach(function(prev){ conflicts.push(prev); });
                        } else { seen[keyServiceStaff] = []; }
                        seen[keyServiceStaff].push(idx);
                    }
                } else {
                    // log that this row had no staff selected so it won't be considered for staff-based conflicts
                    if (window.console && window.console.log) {
                        console.log('[UBFv3] row', idx, 'skipped conflict check because no staff selected');
                    }
                }
            });

            if (conflicts.length) {
                const unique = Array.from(new Set(conflicts));
                // do not add inline visual highlights here; rely on modal/error text only
                unique.forEach(function(i){ /* intentionally left blank for visual-only handling */ });

                const err = this.form.find('.ubf-schedule-error'); if (err.length) err.text('You assigned the same date & time to the same staff for multiple services. Please assign a different time or staff for one of them.').show();
                return { ok: false, conflicts: unique };
            }

            return { ok: true, conflicts: [] };
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

    // Inline message helper for UBF v3 forms (parity with legacy form.showMessage)
    UBFv3.prototype.showMessage = function(message, type){
        type = type || 'info';
        try {
            // remove any existing messages within this form
            this.form.find('.booking-message').remove();
            const iconClass = (type === 'error') ? 'fa-exclamation-triangle' : 'fa-info-circle';
            const safe = $('<div>').text(String(message)).html();
            const messageHtml = '<div class="booking-message booking-' + type + '">'
                + '<i class="fas ' + iconClass + '"></i> ' + safe + '</div>';
            this.form.prepend(messageHtml);
            const self = this;
            setTimeout(function(){ try { self.form.find('.booking-message').fadeOut(); } catch(e){} }, 5000);
        } catch (e) {
            try { console.warn('UBFv3.showMessage failed', e); } catch(_){ }
        }
    }

    UBFv3.prototype.next = function(){
        const self = this;

        // If moving from step 3 -> 4, perform a server availability check and only
        // continue after server confirms availability. This avoids races where the
        // debounced check hasn't fired before the user clicks Next.
        if (this.current === 3){
            const $nextBtn = this.form.find('.ubf-next');
            $nextBtn.prop('disabled', true).addClass('loading');

            // build payload like debouncedCheck
            const $rows = this.form.find('#ubf-per-service-schedule .ubf-per-service-row');
            const svc = [], staff = [], date = [], time = [];
            $rows.each(function(idx, el){
                const $el = $(el);
                svc.push($el.find('input[name="schedule_service_id[]"]').val() || '');
                staff.push($el.find('input[name="schedule_staff_id[]"]').val() || '');
                date.push($el.find('.ubf-row-date').val() || '');
                time.push($el.find('.ubf-row-time').val() || '');
            });

            const payload = {
                action: 'check_schedule_availability_v3',
                nonce: window.userBookingAjax ? userBookingAjax.nonce : '',
                schedule_service_id: svc,
                schedule_staff_id: staff,
                preferred_date: date,
                preferred_time: time
            };

            // store last payload on the form for robust mapping
            try { this.form.data('ubf-last-schedule-payload', payload); } catch(e){}

            // helper to continue client-side validation and progression
            function proceedAfterAvailability() {
                // re-enable next button just in case
                $nextBtn.prop('disabled', false).removeClass('loading');

                const scheduleOK = self.validateSchedule();
                if (!scheduleOK) return; // don't proceed

                // Run uniqueness check for per-service schedules and alert the user if conflicts exist
                if (typeof self.validateBlocksUniqueness === 'function'){
                    const result = self.validateBlocksUniqueness();
                    if (!result || result.ok === false){
                        try {
                            const err = self.form.find('.ubf-schedule-error'); if (err.length) err.show();

                            const items = (result && Array.isArray(result.conflicts)) ? result.conflicts.map(function(i){
                                const $row = $('.ubf-per-service-row').eq(i);
                                const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                                const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                                const d = $row.find('.ubf-row-date').val() || 'No date';
                                const t = $row.find('.ubf-row-time').val() || 'No time';
                                return { index: i, service: svc.trim(), staff: staff.trim(), date: d, time: t };
                            }) : [];

                            if (self.showConflictModal) {
                                try { self.showConflictModal('Schedule conflict', items, null); } catch (e) { console.warn('Failed to show schedule conflict alert', e); }
                            }
                        } catch (e) { console.warn('Failed to show schedule conflict alert', e); }
                        return; // block proceeding to checkout
                    }
                }

                if (self.current < self.total){
                    self.showStep(self.current+1);
                }
            }

            $.ajax({
                url: userBookingAjax.ajaxurl,
                method: 'POST',
                data: payload,
                success: function(resp){
                    console.debug('[UBF] forced availability check resp:', resp);
                    if (resp && resp.success) {
                        try { self.form.data('ubf-has-schedule-conflict', false); self.form.data('ubf-schedule-conflicts', []); } catch(e){}
                        proceedAfterAvailability();
                    } else {
                        const conflictItems = resp && resp.conflict_items ? resp.conflict_items : (resp && resp.conflict_rows ? resp.conflict_rows : []);
                        const existing = resp && resp.conflict_existing ? resp.conflict_existing : {};
                        try { self.form.data('ubf-has-schedule-conflict', true); self.form.data('ubf-schedule-conflicts', conflictItems || []); } catch(e){}
                        try { showConflictModal(conflictItems || [], existing || {}); } catch(e){}
                        try { self.showStep(3); } catch(e){}
                    }
                },
                error: function(){
                    // network failure - fall back to client-side validation
                    proceedAfterAvailability();
                },
                complete: function(){ $nextBtn.prop('disabled', false).removeClass('loading'); }
            });
            return; // wait for async server check
        }

        // if an async availability check previously flagged conflicts, block progression
        try {
            const hasConflict = this.form.data('ubf-has-schedule-conflict');
            if (hasConflict) {
                const conflicts = this.form.data('ubf-schedule-conflicts') || [];
                // Build items for modal as in other flows
                const items = conflicts.map(function(i){
                    const $row = $('.ubf-per-service-row').eq(i);
                    const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                    const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                    const d = $row.find('.ubf-row-date').val() || 'No date';
                    const t = $row.find('.ubf-row-time').val() || 'No time';
                    return { index: i, service: svc.trim(), staff: staff.trim(), date: d, time: t };
                });
                if (this.showConflictModal) this.showConflictModal('Schedule conflicts', items, null);
                // ensure schedule step is visible
                this.showStep(3);
                return;
            }
        } catch(e) { /* ignore data access errors */ }

        const scheduleOK = this.validateSchedule();
        if (!scheduleOK) return; // don't proceed

        // Run uniqueness check for per-service schedules and alert the user if conflicts exist
        if (typeof this.validateBlocksUniqueness === 'function'){
            const result = this.validateBlocksUniqueness();
            if (!result || result.ok === false){
                // validateBlocksUniqueness already sets the inline error and highlights rows
                try {
                    const err = this.form.find('.ubf-schedule-error'); if (err.length) err.show();

                    // build friendly item list from conflict indices if available
                    const items = (result && Array.isArray(result.conflicts)) ? result.conflicts.map(function(i){
                        const $row = $('.ubf-per-service-row').eq(i);
                        const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                        const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                        const d = $row.find('.ubf-row-date').val() || 'No date';
                        const t = $row.find('.ubf-row-time').val() || 'No time';
                        return { index: i, service: svc.trim(), staff: staff.trim(), date: d, time: t };
                    }) : [];

                    if (this.showConflictModal) {
                        this.showConflictModal('Schedule conflict', items, null);
                    }
                } catch (e) { console.warn('Failed to show schedule conflict alert', e); }
                return; // block proceeding to checkout
            }
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
            this.showMessage('Please fill required fields: service, name and email', 'error');
            return;
        }

        // validate schedule one more time before submitting
        if (!this.validateSchedule()){
            // if invalid schedule, jump to step 3
            this.showStep(3);
            return;
        }

        // validate per-block uniqueness (no duplicate staff/date/time)
        const uniqCheck = (typeof this.validateBlocksUniqueness === 'function') ? this.validateBlocksUniqueness() : { ok: true };
        if (!uniqCheck || uniqCheck.ok === false){
            // highlight was applied in the validator, show modal and jump back to schedule
            try {
                const items = (uniqCheck && Array.isArray(uniqCheck.conflicts)) ? uniqCheck.conflicts.map(function(i){
                    const $row = $('.ubf-per-service-row').eq(i);
                    const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                    const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                    const d = $row.find('.ubf-row-date').val() || 'No date';
                    const t = $row.find('.ubf-row-time').val() || 'No time';
                    return { index: i, service: svc.trim(), staff: staff.trim(), date: d, time: t };
                }) : [];
                if (this.showConflictModal) {
                    this.showConflictModal('Schedule conflict', items, null);
                }
            } catch(e){ console.warn('Failed to show conflict modal before submit', e); }
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
                    // If server returned field errors, display them inline
                    if (resp && resp.errors) {
                        self.handleErrors(resp.errors);
                        self.showMessage(resp.message || 'Please correct the highlighted fields', 'error');
                    } else if (resp && resp.conflict_rows && Array.isArray(resp.conflict_rows) && resp.conflict_rows.length) {
                        // Server returned conflict row indices
                        const conflictIdx = resp.conflict_rows;
                        const conflictExisting = resp.conflict_existing || {};

                        // Highlight rows in schedule UI
                        self.form.find('#ubf-per-service-schedule .ubf-per-service-row').removeClass('error');
                        // do not add inline visual highlights here; rely on modal/error text only
                        conflictIdx.forEach(function(i){ /* intentionally left blank */ });

                        // Build detailed items for modal
                        const items = conflictIdx.map(function(i){
                            const $row = self.form.find('#ubf-per-service-schedule .ubf-per-service-row').eq(i);
                            const svc = $row.find('.ubf-per-service-label strong').text() || 'Service';
                            const staff = $row.find('.ubf-per-service-staff').text() || 'Any staff';
                            const d = $row.find('.ubf-row-date').val() || 'No date';
                            const t = $row.find('.ubf-row-time').val() || 'No time';
                            const existing = conflictExisting[i] ? ('Existing booking #' + conflictExisting[i]) : '';
                            return { index: i, service: svc.trim(), staff: staff.trim(), date: d, time: t, existing: existing };
                        });

                        // Show detailed inline conflict messages using the UBFv3 instance
                        if (self && typeof self.showConflictModal === 'function') {
                            try { self.showConflictModal('Schedule conflicts', items, null); } catch(e) { console.warn('Failed to call self.showConflictModal', e); }
                        } else if (this && typeof this.showConflictModal === 'function') {
                            // fallback
                            try { this.showConflictModal('Schedule conflicts', items, null); } catch(e) { console.warn('Failed to call this.showConflictModal', e); }
                        }

                        try { self.showStep(3); } catch(e) {}
                    } else if (resp && resp.message && /overlap|not available|conflict/i.test(resp.message)) {
                        // old-style conflict message
                        const scheduleErr = self.form.find('.ubf-schedule-error');
                        if (scheduleErr.length) scheduleErr.text(resp.message).show();
                        self.showMessage(resp.message, 'error');
                        try { self.showStep(3); } catch(e) {}
                    } else {
                        // general error
                        self.showMessage(resp && resp.message ? resp.message : userBookingV3.messages.error, 'error');
                    }
                }
            },
            error: function(){
                self.showMessage(userBookingV3.messages.error, 'error');
            }
        });
    }

    // Initialize v3 form(s) if present
    // Support multiple admin forms that use the UBF v3 markup by initializing
    // a UBFv3 instance for each `.ubf-v3-form`. Fall back to the legacy
    // `#user-booking-form-v3` selector for backward compatibility.
    $(function(){
        // Initialize UBF v3 forms but skip any that are rendered inside the
        // admin/manager overlay (they use their own submit/update handlers).
        const $v3Forms = $('.ubf-v3-form');
        if ($v3Forms.length){
            $v3Forms.each(function(){
                try {
                    // skip manager/admin overlay forms to avoid duplicate submit handlers
                    if ($(this).closest('.manager-booking-container').length) {
                        console.log('[UBFv3] Skipping init for manager/admin overlay form to avoid conflicts');
                        return; // continue to next form
                    }
                    new UBFv3(this);
                } catch (e){ console.error('Failed to init UBFv3 on element', this, e); }
            });
            return;
        }

        // Backwards-compatible single-form init (skip admin overlay similarly)
        if ($('#user-booking-form-v3').length && !$('#user-booking-form-v3').closest('.manager-booking-container').length){
            new UBFv3('#user-booking-form-v3');
        }
    });

})(jQuery);
