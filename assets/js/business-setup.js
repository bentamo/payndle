/**
 * Business Setup Form - Step by Step JavaScript
 * Simplified approach for reliable functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
    /**
 * Business Setup Form - ULTRA SIMPLE TEST
 */

console.log('JS FILE LOADED!');

jQuery(document).ready(function($) {
    console.log('JQUERY READY!');
    
    // Test if form exists
    console.log('Form elements found:', $('.business-setup-form').length);
    console.log('Steps found:', $('.form-step').length);
    console.log('Button found:', $('.btn-next').length);
    
    // Bind click event to button
    $('.btn-next').on('click', function(e) {
        e.preventDefault();
        console.log('BUTTON CLICKED!');
        
        // Hide step 1, show step 2
        $('.form-step[data-step="1"]').removeClass('active');
        $('.form-step[data-step="2"]').addClass('active');
        
        console.log('Step 2 should now be visible');
    });
    
    // Test button existence every second
    setInterval(function() {
        const buttonExists = $('.btn-next').length > 0;
        console.log('Button exists:', buttonExists);
        if (buttonExists) {
            console.log('Button text:', $('.btn-next').text());
        }
    }, 2000);
});
    
    // Initialize immediately
    let currentStep = 1;
    const totalSteps = 3;
    
    // Initialize form
    initForm();
    
    /**
     * Initialize the form and event listeners
     */
    function initializeForm() {
        const $form = $('.business-setup-form');
        const $steps = $('.form-step');
        
        if ($form.length === 0 || $steps.length === 0) {
            console.error('Business setup form or steps not found!');
            return;
        }
        
        // Show first step
        showStep(1);
        
        // Setup event handlers for navigation
        setupEventHandlers();
        
        console.log('Business Setup form initialized with', $steps.length, 'steps');
    }
    
    /**
     * Setup all event handlers
     */
    function setupEventHandlers() {
        console.log('Setting up event handlers');
        
        // Next button click
        $(document).on('click', '.btn-next', function(e) {
            e.preventDefault();
            console.log('Next button clicked!');
            handleNextStep();
        });
        
        // Previous button click  
        $(document).on('click', '.btn-prev, .btn-previous', function(e) {
            e.preventDefault();
            console.log('Previous button clicked!');
            handlePreviousStep();
        });
        
        // Submit button click
        $(document).on('click', '.btn-submit', function(e) {
            e.preventDefault();
            console.log('Submit button clicked!');
            handleFormSubmit();
        });
        
        // Step indicator click
        $(document).on('click', '.step', function(e) {
            e.preventDefault();
            const targetStep = parseInt($(this).data('step'));
            console.log('Step indicator clicked:', targetStep);
            if (targetStep && targetStep <= currentStep) {
                showStep(targetStep);
            }
        });
        
        // Debug: Test if any button is clicked
        $(document).on('click', 'button', function(e) {
            console.log('ANY button clicked:', $(this).text(), $(this).attr('class'));
        });
    }
    
    /**
     * Show specific step
     */
    function showStep(step) {
        // Hide all steps
        $('.form-step').removeClass('active');
        
        // Show target step
        $('.form-step[data-step="' + step + '"]').addClass('active');
        
        // Update current step
        currentStep = step;
        
        // Update progress and buttons
        updateProgress();
        updateButtons();
        
        console.log('Showing step:', step);
    }
    
    /**
     * Update progress bar and indicators
     */
    function updateProgress() {
        // Update progress bar
        const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $('.progress-fill').css('width', progressPercentage + '%');
        
        // Update step indicators
        $('.step').each(function(index) {
            const stepNumber = index + 1;
            const $step = $(this);
            
            $step.removeClass('active completed');
            
            if (stepNumber === currentStep) {
                $step.addClass('active');
            } else if (stepNumber < currentStep) {
                $step.addClass('completed');
            }
        });
    }
    
    /**
     * Update navigation buttons - work with existing HTML structure
     */
    function updateButtons() {
        console.log('updateButtons called for step:', currentStep);
        
        const $form = $('.business-setup-form');
        const $actions = $form.find('.form-actions');
        
        console.log('Form found:', $form.length, 'Actions found:', $actions.length);
        
        if ($actions.length === 0) {
            console.error('No form-actions found in HTML!');
            return;
        }
        
        // Work with existing button structure
        const $btnGroup = $actions.find('.btn-group');
        const $prevContainer = $actions.children().first();
        
        console.log('Button group found:', $btnGroup.length);
        
        // Update main action button
        let $mainBtn = $btnGroup.find('button');
        if ($mainBtn.length === 0) {
            // If no button exists, create one
            $mainBtn = $('<button type="button" class="btn btn-primary"></button>');
            $btnGroup.append($mainBtn);
        }
        
        // Update button based on current step
        if (currentStep < totalSteps) {
            $mainBtn.removeClass('btn-submit').addClass('btn-next').text('Next Step');
            console.log('Updated button to Next Step for step', currentStep);
        } else {
            $mainBtn.removeClass('btn-next').addClass('btn-submit').text('Complete Setup');
            console.log('Updated button to Complete Setup for final step');
        }
        
        // Handle previous button
        if (currentStep > 1) {
            let $prevBtn = $prevContainer.find('.btn-prev');
            if ($prevBtn.length === 0) {
                $prevBtn = $('<button type="button" class="btn btn-prev">Previous</button>');
                $prevContainer.html($prevBtn);
            }
        } else {
            $prevContainer.empty(); // Empty spacer for first step
        }
        
        console.log('Buttons updated. Current step:', currentStep, 'Main button text:', $mainBtn.text());
    }
    
    /**
     * Handle next step navigation
     */
    function handleNextStep() {
        console.log('handleNextStep called, currentStep:', currentStep);
        
        if (validateCurrentStep()) {
            console.log('Validation passed, moving to next step');
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
                showNotification('Step completed!', 'success');
            }
        } else {
            console.log('Validation failed for step:', currentStep);
        }
    }
    
    /**
     * Handle previous step navigation
     */
    function handlePreviousStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }
    
    /**
     * Validate current step
     */
    function validateCurrentStep() {
        console.log('Validating step:', currentStep);
        
        const $currentStepElement = $('.form-step[data-step="' + currentStep + '"]');
        const $requiredFields = $currentStepElement.find('input[required], select[required], textarea[required]');
        let isValid = true;
        
        console.log('Found', $requiredFields.length, 'required fields in step', currentStep);
        
        // Clear previous errors
        $('.error-message').remove();
        $requiredFields.removeClass('error');
        
        // Validate each required field
        $requiredFields.each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                isValid = false;
                addFieldError($field, 'This field is required');
            } else if ($field.attr('type') === 'email' && !isValidEmail(value)) {
                isValid = false;
                addFieldError($field, 'Please enter a valid email address');
            } else if ($field.attr('type') === 'tel' && !isValidPhone(value)) {
                isValid = false;
                addFieldError($field, 'Please enter a valid phone number');
            } else if ($field.attr('type') === 'url' && value && !isValidUrl(value)) {
                isValid = false;
                addFieldError($field, 'Please enter a valid URL');
            }
        });
        
        if (!isValid) {
            showNotification('Please fix the errors below', 'error');
        }
        
        return isValid;
    }
    
    /**
     * Add error styling and message to field
     */
    function addFieldError($field, message) {
        $field.addClass('error');
        if (!$field.next('.error-message').length) {
            $field.after('<div class="error-message">' + message + '</div>');
        }
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validate phone format
     */
    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    /**
     * Validate URL format
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmit() {
        // Validate all steps
        let allValid = true;
        for (let step = 1; step <= totalSteps; step++) {
            const tempCurrentStep = currentStep;
            currentStep = step;
            if (!validateCurrentStep()) {
                allValid = false;
                showStep(step); // Show first invalid step
                break;
            }
            currentStep = tempCurrentStep;
        }
        
        if (!allValid) {
            showNotification('Please complete all required fields', 'error');
            return;
        }
        
        // Show loading
        showLoading('Setting up your business...');
        
        // Prepare form data
        const formData = new FormData($('.business-setup-form')[0]);
        formData.append('action', 'submit_business_setup');
        formData.append('nonce', $('input[name="business_setup_nonce"]').val());
        
        // Submit via AJAX
        $.ajax({
            url: businessSetupAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showSuccessMessage(
                        'Business Setup Complete!', 
                        'Your business profile has been created successfully.',
                        [
                            {
                                text: 'Continue to Dashboard',
                                action: function() {
                                    window.location.href = '/wp-admin/';
                                }
                            }
                        ]
                    );
                } else {
                    showNotification(response.data.message || 'Something went wrong. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showNotification('Network error. Please check your connection and try again.', 'error');
                console.error('AJAX Error:', error);
            }
        });
    }
    
    /**
     * Show loading overlay
     */
    function showLoading(message = 'Processing...') {
        const loadingHtml = `
            <div class="loading-overlay active">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>${message}</p>
                </div>
            </div>
        `;
        $('body').append(loadingHtml);
    }
    
    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('.loading-overlay').remove();
    }
    
    /**
     * Show success message overlay
     */
    function showSuccessMessage(title, message, actions = []) {
        let actionsHtml = '';
        if (actions.length > 0) {
            actionsHtml = '<div class="success-actions">';
            actions.forEach(action => {
                actionsHtml += `<button type="button" class="btn btn-primary" onclick="(${action.action})()">${action.text}</button>`;
            });
            actionsHtml += '</div>';
        }
        
        const successHtml = `
            <div class="success-message active">
                <div class="success-content">
                    <i class="fas fa-check-circle"></i>
                    <h3>${title}</h3>
                    <p>${message}</p>
                    ${actionsHtml}
                </div>
            </div>
        `;
        $('body').append(successHtml);
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="notification notification-${type}">
                ${message}
                <button type="button" class="notification-close">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 5000);
        
        // Manual close
        $notification.find('.notification-close').on('click', function() {
            $notification.fadeOut(() => $notification.remove());
        });
    }

});
        
        if ($form.length === 0) {
            console.error('Business setup form not found!');
            return;
        }
        
        if ($steps.length === 0) {
            console.error('Form steps not found!');
            return;
        }
        
        alert('Form initialized! Found ' + $steps.length + ' steps');
        
        // Show first step only
        showStep(1);
        
        // Add initial navigation buttons
        updateButtons();
        
        // Button event listeners - use document delegation for dynamic content
        $(document).off('click', '.btn-next').on('click', '.btn-next', function(e) {
            e.preventDefault();
            console.log('Next button clicked - Current step:', currentStep);
            alert('Next button clicked! Current step: ' + currentStep);
            handleNextStep();
        });
        
        $(document).off('click', '.btn-prev').on('click', '.btn-prev', function(e) {
            e.preventDefault();
            console.log('Previous button clicked - Current step:', currentStep);
            alert('Previous button clicked! Current step: ' + currentStep);
            handlePrevStep();
        });
        
        $(document).off('click', '.btn-submit').on('click', '.btn-submit', function(e) {
            e.preventDefault();
            console.log('Submit button clicked - Current step:', currentStep);
            alert('Submit button clicked! Current step: ' + currentStep);
            handleFormSubmission();
        });
        
        // Real-time validation
        $(document).on('blur', '.form-input, .form-textarea, .form-select', validateField);
        $(document).on('input', '.form-input[type="tel"]', formatPhoneNumber);
        
        // Enter key navigation (except in textareas)
        $(document).on('keypress', '.form-input, .form-select', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                handleNextStep();
            }
        });
        
        console.log('Business Setup Form initialized - Step', currentStep);
    }
    
    /**
     * Show specific step and hide others
     */
    function showStep(step) {
        console.log('Showing step:', step);
        alert('Showing step: ' + step);
        
        // Hide all steps immediately
        $('.form-step').removeClass('active');
        
        // Show target step immediately (no delay)
        $('.form-step[data-step="' + step + '"]').addClass('active');
        
        currentStep = step;
        updateProgress();
        updateStepIndicators();
        updateButtons();
        
        console.log('Step ' + step + ' is now active');
        
        // Focus first input in the step
        setTimeout(function() {
            const $activeStep = $('.form-step.active');
            const $firstInput = $activeStep.find('.form-input, .form-textarea, .form-select').first();
            if ($firstInput.length) {
                $firstInput.focus();
            }
        }, 200);
    }
    
    /**
     * Update progress bar
     */
    function updateProgress() {
        const progressPercentage = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercentage + '%');
        updateStepIndicators(); // Call step indicators update
        console.log('Progress updated:', progressPercentage + '%');
    }
    
    /**
     * Update step indicators
     */
    function updateStepIndicators() {
        $('.step').each(function(index) {
            const stepNumber = index + 1;
            const $step = $(this);
            
            $step.removeClass('active completed');
            
            if (stepNumber === currentStep) {
                $step.addClass('active');
            } else if (stepNumber < currentStep) {
                $step.addClass('completed');
            }
        });
    }
    
    /**
     * Update button states
     */
    function updateButtons() {
        console.log('updateButtons called, currentStep:', currentStep);
        
        const $form = $('.business-setup-form');
        const $existingActions = $form.find('.form-actions');
        
        console.log('Form element found:', $form.length);
        console.log('Existing actions found:', $existingActions.length);
        
        // If no form actions exist, create them
        if ($existingActions.length === 0) {
            const $actions = $('<div class="form-actions" style="display: flex !important; margin-top: 2rem !important; padding-top: 1rem !important; border-top: 1px solid #f0f0f0 !important; justify-content: space-between !important; gap: 1rem !important;"></div>');
            $form.append($actions);
        }
        
        const $actions = $form.find('.form-actions');
        
        // Clear existing buttons
        $actions.empty();
        
        // Previous button (except for first step)
        if (currentStep > 1) {
            $actions.append('<button type="button" class="btn btn-ghost btn-prev" style="display: inline-block !important; padding: 12px 24px !important; border: 2px solid #ddd !important; background: transparent !important; color: #999 !important; border-radius: 8px !important; cursor: pointer !important;">Previous</button>');
        } else {
            $actions.append('<div></div>'); // Spacer
        }
        
        // Next/Submit button group
        const $btnGroup = $('<div class="btn-group" style="display: flex !important; gap: 1rem !important;"></div>');
        
        if (currentStep < totalSteps) {
            $btnGroup.append('<button type="button" class="btn btn-primary btn-next" style="display: inline-block !important; padding: 12px 24px !important; background-color: #64C493 !important; color: white !important; border: 2px solid #64C493 !important; border-radius: 8px !important; cursor: pointer !important; font-family: Inter, sans-serif !important; font-weight: 500 !important;">Next Step</button>');
        } else {
            $btnGroup.append('<button type="button" class="btn btn-primary btn-submit" style="display: inline-block !important; padding: 12px 24px !important; background-color: #64C493 !important; color: white !important; border: 2px solid #64C493 !important; border-radius: 8px !important; cursor: pointer !important; font-family: Inter, sans-serif !important; font-weight: 500 !important;">Complete Setup</button>');
        }
        
        $actions.append($btnGroup);
        
        console.log('Buttons updated for step', currentStep);
        console.log('Final actions HTML:', $actions[0].outerHTML);
        console.log('Button count in DOM:', $form.find('button').length);
    }
    
    /**
     * Handle next step
     */
    function handleNextStep() {
        console.log('Next step clicked, current step:', currentStep);
        alert('Handling next step. Current: ' + currentStep + ', Total: ' + totalSteps);
        
        // For now, skip validation to test navigation
        // if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
                showNotification('Step ' + (currentStep - 1) + ' completed!', 'success');
            } else {
                alert('Already at last step!');
            }
        // } else {
        //     console.log('Validation failed for step', currentStep);
        // }
    }
    
    /**
     * Handle previous step
     */
    function handlePrevStep() {
        console.log('Previous step clicked, current step:', currentStep);
        
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }
    
    /**
     * Validate current step
     */
    function validateCurrentStep() {
        const $currentStepForm = $('.form-step[data-step="' + currentStep + '"]');
        let isValid = true;
        
        console.log('Validating step', currentStep);
        
        // Remove previous error states
        $currentStepForm.find('.form-group').removeClass('error');
        $currentStepForm.find('.error-message').remove();
        
        // Validate required fields in current step
        $currentStepForm.find('.form-input[required], .form-textarea[required], .form-select[required]').each(function() {
            if (!validateField.call(this)) {
                isValid = false;
            }
        });
        
        // Step-specific validation
        switch (currentStep) {
            case 1: // Business Information
                isValid = validateStep1($currentStepForm) && isValid;
                break;
            case 2: // Contact Information
                isValid = validateStep2($currentStepForm) && isValid;
                break;
            case 3: // Additional Details
                isValid = validateStep3($currentStepForm) && isValid;
                break;
        }
        
        if (!isValid) {
            showNotification('Please fill in all required fields correctly.', 'error');
        }
        
        console.log('Step', currentStep, 'validation result:', isValid);
        return isValid;
    }
    
    /**
     * Validate Step 1 - Business Information
     */
    function validateStep1($step) {
        let isValid = true;
        
        const businessName = $step.find('#business_name').val().trim();
        const businessType = $step.find('#business_type').val();
        
        if (!businessName) {
            addFieldError($step.find('#business_name'), 'Business name is required');
            isValid = false;
        } else if (businessName.length < 2) {
            addFieldError($step.find('#business_name'), 'Business name must be at least 2 characters');
            isValid = false;
        }
        
        if (!businessType) {
            addFieldError($step.find('#business_type'), 'Please select a business type');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validate Step 2 - Contact Information
     */
    function validateStep2($step) {
        let isValid = true;
        
        const email = $step.find('#business_email').val().trim();
        const phone = $step.find('#business_phone').val().trim();
        
        if (!email) {
            addFieldError($step.find('#business_email'), 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            addFieldError($step.find('#business_email'), 'Please enter a valid email address');
            isValid = false;
        }
        
        if (!phone) {
            addFieldError($step.find('#business_phone'), 'Phone number is required');
            isValid = false;
        } else if (!isValidPhone(phone)) {
            addFieldError($step.find('#business_phone'), 'Please enter a valid phone number');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validate Step 3 - Additional Details
     */
    function validateStep3($step) {
        // Step 3 is optional, so always valid
        return true;
    }
    
    /**
     * Validate individual field
     */
    function validateField() {
        const $field = $(this);
        const $group = $field.closest('.form-group');
        const value = $field.val().trim();
        const fieldType = $field.attr('type');
        const fieldName = $field.attr('name');
        
        // Remove previous error
        $group.removeClass('error');
        $group.find('.error-message').remove();
        
        // Skip validation if field is not required and empty
        if (!$field.attr('required') && !value) {
            return true;
        }
        
        // Required field validation
        if ($field.attr('required') && !value) {
            addFieldError($field, 'This field is required');
            return false;
        }
        
        // Type-specific validation
        switch (fieldType) {
            case 'email':
                if (value && !isValidEmail(value)) {
                    addFieldError($field, 'Please enter a valid email address');
                    return false;
                }
                break;
            case 'tel':
                if (value && !isValidPhone(value)) {
                    addFieldError($field, 'Please enter a valid phone number');
                    return false;
                }
                break;
            case 'url':
                if (value && !isValidUrl(value)) {
                    addFieldError($field, 'Please enter a valid website URL');
                    return false;
                }
                break;
        }
        
        // Field-specific validation
        if (fieldName === 'business_name' && value && value.length < 2) {
            addFieldError($field, 'Business name must be at least 2 characters');
            return false;
        }
        
        return true;
    }
    
    /**
     * Add error styling and message to field
     */
    function addFieldError($field, message) {
        const $group = $field.closest('.form-group');
        $group.addClass('error');
        $group.append('<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' + message + '</div>');
    }
    
    /**
     * Email validation
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Phone validation
     */
    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        return phoneRegex.test(cleanPhone) && cleanPhone.length >= 10;
    }
    
    /**
     * URL validation
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    /**
     * Format phone number as user types
     */
    function formatPhoneNumber() {
        let value = $(this).val().replace(/\D/g, '');
        let formattedValue = '';
        
        if (value.length > 0) {
            if (value.length <= 3) {
                formattedValue = value;
            } else if (value.length <= 6) {
                formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3);
            } else {
                formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
            }
        }
        
        $(this).val(formattedValue);
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmission() {
        console.log('Form submission started');
        
        if (!validateCurrentStep()) {
            return;
        }
        
        // Show loading
        showLoading('Setting up your business...');
        
        // Collect form data
        const formData = {
            action: 'submit_business_setup',
            nonce: businessSetupAjax.nonce,
            business_name: $('#business_name').val(),
            business_type: $('#business_type').val(),
            business_description: $('#business_description').val(),
            business_email: $('#business_email').val(),
            business_phone: $('#business_phone').val(),
            business_website: $('#business_website').val(),
            business_address: $('#business_address').val(),
            business_city: $('#business_city').val(),
            business_state: $('#business_state').val(),
            business_zip: $('#business_zip').val(),
            business_hours: $('#business_hours').val(),
            business_services: $('#business_services').val()
        };
        
        console.log('Submitting form data:', formData);
        
        // Submit via AJAX
        $.ajax({
            url: businessSetupAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                hideLoading();
                console.log('Form submission response:', response);
                
                if (response.success) {
                    showSuccessMessage(
                        'Business Setup Complete!',
                        'Your business has been successfully created. You can now start adding services and staff.',
                        [
                            { text: 'Add Services', class: 'btn-primary', action: 'redirect', url: '/services' },
                            { text: 'View Dashboard', class: 'btn-outline', action: 'redirect', url: '/dashboard' }
                        ]
                    );
                } else {
                    showNotification(response.data || 'Failed to create business. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('Business setup error:', error);
                
                if (status === 'timeout') {
                    showNotification('Request timed out. Please try again.', 'error');
                } else {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            }
        });
    }
    
    /**
     * Show loading overlay
     */
    function showLoading(message = 'Processing...') {
        const loadingHtml = `
            <div class="loading-overlay active">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>${message}</p>
                </div>
            </div>
        `;
        
        $('body').append(loadingHtml);
    }
    
    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('.loading-overlay').remove();
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(title, message, actions = []) {
        let actionsHtml = '';
        
        actions.forEach(function(action) {
            actionsHtml += `<button class="btn ${action.class}" data-action="${action.action}" data-url="${action.url || ''}">${action.text}</button>`;
        });
        
        const successHtml = `
            <div class="success-message active">
                <div class="success-content">
                    <i class="fas fa-check-circle"></i>
                    <h3>${title}</h3>
                    <p>${message}</p>
                    <div class="success-actions">
                        ${actionsHtml}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(successHtml);
        
        // Handle action buttons
        $(document).on('click', '.success-actions .btn', function() {
            const action = $(this).data('action');
            const url = $(this).data('url');
            
            if (action === 'redirect' && url) {
                window.location.href = url;
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.notification').remove();
        
        const notificationHtml = `
            <div class="notification notification-${type}">
                ${message}
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        $('body').append(notificationHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $('.notification').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual close
        $(document).on('click', '.notification-close', function() {
            $(this).closest('.notification').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    // Debug helper - expose step functions to console
    window.businessSetupDebug = {
        showStep: showStep,
        getCurrentStep: function() { return currentStep; },
        validateCurrentStep: validateCurrentStep
    };
});
