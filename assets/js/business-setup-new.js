jQuery(document).ready(function($) {
    console.log('Business Setup JS Loaded');
    
    var currentStep = 1;
    var totalSteps = (function() {
        // Prefer localized total if present; else compute from DOM
        if (typeof businessSetupAjax !== 'undefined' && businessSetupAjax && businessSetupAjax.totalSteps) {
            return businessSetupAjax.totalSteps;
        }
        var domCount = $('.form-step').length;
        return (domCount && domCount > 0) ? domCount : 3;
    })();
    
    function showStep(stepNumber) {
        console.log('Showing step: ' + stepNumber);
        
        $('.form-step').removeClass('active').hide();
        $('.form-step[data-step="' + stepNumber + '"]').addClass('active').show();
        
        currentStep = stepNumber;
        updateButton();
        updateProgressBar();
        
        console.log('Step ' + stepNumber + ' is now visible');
    }
    
    function updateProgressBar() {
        var progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $('.progress-fill').css('width', progressPercentage + '%');
        
        // Update step indicators
        $('.step').each(function(index) {
            var stepNumber = index + 1;
            var $step = $(this);
            
            $step.removeClass('active completed');
            
            if (stepNumber === currentStep) {
                $step.addClass('active');
            } else if (stepNumber < currentStep) {
                $step.addClass('completed');
            }
        });
        
        console.log('Progress updated to: ' + progressPercentage + '%');
    }
    
    function updateButton() {
        // Find the step action button whether it's currently marked as .btn-next or already .btn-submit
        var $button = $('.btn-next, .btn-submit');
        var $btnGroup = $button.closest('.form-actions');
        // Ensure previous button exists
        if ($btnGroup.find('.btn-prev').length === 0) {
            $btnGroup.prepend('<button type="button" class="btn btn-secondary btn-prev" style="margin-right:8px; display:none;">Previous</button>');
        }

        var $prev = $btnGroup.find('.btn-prev');
        if (currentStep > 1) {
            $prev.show();
        } else {
            $prev.hide();
        }

        if (currentStep >= totalSteps) {
            $button.text('Complete Setup').removeClass('btn-next').addClass('btn-submit');
            // make it a true submit button so Enter key and form submission will trigger
            $button.prop('type', 'submit');
        } else {
            $button.text('Next Step').removeClass('btn-submit').addClass('btn-next');
            // regular button behavior for intermediate steps
            $button.prop('type', 'button');
        }
    }
    
    $(document).on('click', '.btn-prev', function(e) {
        e.preventDefault();
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    $(document).on('click', '.btn-next', function(e) {
        e.preventDefault();
        console.log('Next button clicked! Current step: ' + currentStep);
        
        // Validate current step before proceeding
        validateCurrentStep().done(function(isValid) {
            if (isValid) {
                if (currentStep < totalSteps) {
                    showStep(currentStep + 1);
                }
            } else {
                console.log('Validation failed - staying on current step');
            }
        });
    });
    
    function validateCurrentStep() {
        var deferred = $.Deferred();
        var $currentStep = $('.form-step[data-step="' + currentStep + '"]');
        var $requiredFields = $currentStep.find('input[required], select[required], textarea[required]');
        var isValid = true;
        
        console.log('Validating step ' + currentStep + ' - found ' + $requiredFields.length + ' required fields');
        
        // Clear previous error styles
        $requiredFields.removeClass('error');
        $currentStep.find('.error-message').remove();
        $currentStep.find('.validation-error').remove();
        
        // Check each required field
        $requiredFields.each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                isValid = false;
                $field.addClass('error');
                
                // Add error message
                if (!$field.next('.error-message').length) {
                    $field.after('<div class="error-message" style="color: red; font-size: 12px; margin-top: 5px;">This field is required</div>');
                }
                
                console.log('Field validation failed:', $field.attr('name') || $field.attr('id'));
            }
        });
        
        if (!isValid) {
            $currentStep.prepend('<div class="validation-error" style="background: #ffe6e6; border: 1px solid #e74c3c; color: #e74c3c; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;"><i class="fa fa-exclamation-triangle"></i> Please fill in all required fields to continue.</div>');
            deferred.resolve(false);
            return deferred.promise();
        }

        // Prepare per-field uniqueness checks for business_name, business_email, business_phone
        var ajaxChecks = [];
        $requiredFields.each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val().trim();
            if ((name === 'business_name' || name === 'business_email' || name === 'business_phone') && value) {
                var data = {
                    action: 'validate_business_field',
                    nonce: businessSetupAjax.nonce,
                    field: name,
                    value: value
                };
                // include business_id if present (editing)
                var bid = $('.business-setup-form').find('input[name="business_id"]').val();
                if (bid) data.exclude_id = bid;

                ajaxChecks.push($.post(businessSetupAjax.ajaxurl, data));
            }
        });

        if (ajaxChecks.length === 0) {
            deferred.resolve(true);
            return deferred.promise();
        }

        // Run all AJAX checks in parallel
        $.when.apply($, ajaxChecks).done(function() {
            var responses = arguments;
            // If a single AJAX call, arguments is the response array, normalize
            if (ajaxChecks.length === 1) {
                responses = [arguments];
            }

            var conflict = false;
            for (var i = 0; i < responses.length; i++) {
                var resp = responses[i][0]; // jQuery returns [data, statusText, jqXHR]
                if (!resp || !resp.success) continue;
                if (resp.data && resp.data.exists) {
                    conflict = true;
                    // find field and show message
                    var fieldName = (resp.data.field) ? resp.data.field : null;
                    // Try to show message adjacent to corresponding input
                    $currentStep.find('input[name="business_name"]').each(function() {
                        if ($(this).val().trim() === $('[name="business_name"]').val().trim()) {
                            if (!$(this).next('.error-message').length) {
                                $(this).after('<div class="error-message" style="color: red; font-size: 12px; margin-top: 5px;">This value is already taken</div>');
                            }
                        }
                    });
                    $currentStep.find('input[name="business_email"]').each(function() {
                        if ($(this).val().trim() === $('[name="business_email"]').val().trim()) {
                            if (!$(this).next('.error-message').length) {
                                $(this).after('<div class="error-message" style="color: red; font-size: 12px; margin-top: 5px;">This email is already in use</div>');
                            }
                        }
                    });
                    $currentStep.find('input[name="business_phone"]').each(function() {
                        if ($(this).val().trim() === $('[name="business_phone"]').val().trim()) {
                            if (!$(this).next('.error-message').length) {
                                $(this).after('<div class="error-message" style="color: red; font-size: 12px; margin-top: 5px;">This phone number is already in use</div>');
                            }
                        }
                    });
                }
            }

            if (conflict) {
                $currentStep.prepend('<div class="validation-error" style="background: #ffe6e6; border: 1px solid #e74c3c; color: #e74c3c; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;"><i class="fa fa-exclamation-triangle"></i> Some values are already taken. Please update them before continuing.</div>');
                deferred.resolve(false);
            } else {
                deferred.resolve(true);
            }
        }).fail(function() {
            // If AJAX fails, we allow progression but show a warning
            $currentStep.prepend('<div class="validation-error" style="background: #fff6e5; border: 1px solid #f0ad4e; color: #8a6d3b; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;"><i class="fa fa-exclamation-triangle"></i> Validation service temporarily unavailable. Please try again later.</div>');
            deferred.resolve(true);
        });

        return deferred.promise();
    }
    
    $(document).on('click', '.btn-submit', function(e) {
        e.preventDefault();
        console.log('Submit button clicked!');
        
        // Validate final step
        validateCurrentStep().done(function(isValid) {
            if (isValid) {
                submitForm();
            }
        });
    });

    // Ensure that any form submit (e.g. pressing Enter) also triggers validation + submit flow
    $(document).on('submit', '#business-setup-form', function(e) {
        e.preventDefault();
        console.log('Business setup form submit intercepted');
        validateCurrentStep().done(function(isValid) {
            if (isValid) {
                submitForm();
            }
        });
    });
    
    function submitForm() {
        // Show loading state
        var $submitBtn = $('.btn-submit');
        $submitBtn.text('Setting up...').prop('disabled', true);
        
        // Get form data
        var formData = new FormData($('.business-setup-form')[0]);
        formData.append('action', 'submit_business_setup');
        formData.append('nonce', businessSetupAjax.nonce);
        
        $.ajax({
            url: businessSetupAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Form submitted successfully:', response);
                if (response.success) {
                    // Pass business_id for redirect
                    var bid = response.data && response.data.business_id ? response.data.business_id : null;
                    showCompletionUI(bid);
                } else {
                    showErrorUI(response.data.message || 'Setup failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Form submission error:', error);
                showErrorUI('An error occurred. Please try again.');
            }
        });
    }
    
    function showCompletionUI(businessId) {
        $('.business-setup-container').html(`
            <div class="completion-ui">
                <div class="success-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h2>Business Setup Complete!</h2>
                <p>Your business profile has been successfully created. You can now:</p>
                <ul class="next-steps">
                    <li><i class="fa fa-plus-circle"></i> Add your services</li>
                    <li><i class="fa fa-users"></i> Manage your staff</li>
                    <li><i class="fa fa-calendar"></i> Start accepting bookings</li>
                </ul>
                <div class="action-buttons">
                    <button class="btn btn-primary btn-go-dashboard">
                        <i class="fa fa-dashboard"></i> Go to Dashboard
                    </button>
                </div>
            </div>
        `);

        // Wire up redirect to dynamic manager dashboard using handler
        var $btn = $('.btn-go-dashboard');
        $btn.on('click', function() {
            var base = (window.businessSetupAjax && businessSetupAjax.managerDashboardUrl) ? businessSetupAjax.managerDashboardUrl : '/manager-dashboard/';
            if (businessId) {
                var url = new URL(base, window.location.origin);
                url.searchParams.set('business_id', businessId);
                window.location.href = url.toString();
            } else {
                window.location.href = base;
            }
        });
    }
    
    function showErrorUI(message) {
        var $submitBtn = $('.btn-submit');
        $submitBtn.text('Complete Setup').prop('disabled', false);
        
        $('.business-setup-form').prepend(`
            <div class="error-banner">
                <i class="fa fa-exclamation-triangle"></i>
                <span>${message}</span>
                <button class="close-error" onclick="$(this).parent().remove()">×</button>
            </div>
        `);
    }
    
    showStep(1);
    console.log('Business Setup initialized');
});
