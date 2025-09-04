// assets/js/custom-login.js
jQuery(document).ready(function($) {
    // Toggle password visibility
    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $icon = $button.find('i');
        const $input = $button.closest('.input-with-icon').find('input');
        
        // Toggle input type
        const type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        
        // Toggle icon
        $icon.toggleClass('fa-eye fa-eye-slash');
        
        // Update ARIA label for accessibility
        const label = type === 'password' ? 'Show password' : 'Hide password';
        $button.attr('aria-label', label);
    });

    // Handle login form submit (UI only for now)
    $(".custom-login-form").on("submit", function(e) {
        e.preventDefault();
        alert("Login functionality will be implemented later.");
    });

    // Google login placeholder
    $("#google-login").on("click", function() {
        alert("Google Authentication coming soon. Placeholder for API integration.");
    });
});

// assets/js/login-handler.js
jQuery(document).ready(function($) {
    // Handle login form submission
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $message = $('#login-message');
        
        // Show loading state
        $submitBtn.addClass('loading').prop('disabled', true);
        $message.hide().removeClass('error success');
        
        // Get form data
        const formData = {
            action: 'ajax_login',
            username: $('#username').val(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked') ? 1 : 0,
            security: ajax_login_object.nonce
        };
        
        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: ajax_login_object.ajaxurl,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $message.html('Login successful! Redirecting...')
                           .addClass('success')
                           .fadeIn();
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect || ajax_login_object.redirecturl;
                    }, 1000);
                } else {
                    showError(response.data || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.data 
                    ? xhr.responseJSON.data 
                    : 'An error occurred. Please try again.';
                showError(errorMessage);
            },
            complete: function() {
                $submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    });
    
    // Handle logout
    $(document).on('click', '#logout-btn', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging out...');
        
        $.ajax({
            type: 'POST',
            url: ajax_login_object.ajaxurl,
            data: {
                action: 'ajax_logout',
                security: ajax_login_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect || ajax_login_object.redirecturl;
                } else {
                    alert('Logout failed. Please try again.');
                    $btn.prop('disabled', false).html('Logout');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).html('Logout');
            }
        });
    });
    
    // Google login button handler (placeholder)
    $('.btn-google').on('click', function() {
        alert('Google login functionality will be implemented here.');
        // Future implementation for Google OAuth
    });
    
    // Helper function to show error messages
    function showError(message) {
        const $message = $('#login-message');
        $message.html(message)
               .addClass('error')
               .fadeIn();
        
        // Scroll to error message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 300);
    }
});
