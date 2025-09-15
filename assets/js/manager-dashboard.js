jQuery(document).ready(function($) {
    // Toggle between view and edit modes with animation
    $(document).on('click', '#edit-business-info', function(e) {
        e.preventDefault();
        const $editBtn = $(this);
        
        // Add loading state
        $editBtn.html('<span class="spinner is-active"></span> Loading...').prop('disabled', true);
        
        // Smooth transition
        $('#business-info-display').fadeOut(200, function() {
            $('#business-info-form').fadeIn(200);
            $('html, body').animate({
                scrollTop: $('.main-content').offset().top - 20
            }, 300);
        });
    });

    // Cancel edit with animation
    $(document).on('click', '#cancel-edit', function(e) {
        e.preventDefault();
        
        // Smooth transition
        $('#business-info-form').fadeOut(200, function() {
            $('#business-info-display').fadeIn(200);
            $('#edit-business-info').removeClass('hidden');
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        });
    });

    // Handle form submission
    $(document).on('submit', '#business-info-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.prop('disabled', true).html('<span class="spinner is-active"></span> Saving...');
        
        // Send AJAX request
        $.ajax({
            url: managerDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'save_business_info',
                nonce: managerDashboard.nonce,
                business_id: $('input[name="business_id"]').val(),
                business_name: $('input[name="business_name"]').val(),
                business_description: $('textarea[name="business_description"]').val(),
                business_email: $('input[name="business_email"]').val(),
                business_phone: $('input[name="business_phone"]').val(),
                business_address: $('textarea[name="business_address"]').val(),
                business_city: $('input[name="business_city"]').val(),
                business_state: $('input[name="business_state"]').val(),
                business_zip: $('input[name="business_zip"]').val(),
                business_country: $('input[name="business_country"]').val(),
                business_website: $('input[name="business_website"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Update the display with new values
                    updateBusinessDisplay(response.data);
                    
                    // Show success message
                    showNotice('Changes saved successfully', 'success');
                    
                    // Switch back to view mode
                    $('#business-info-form').addClass('hidden');
                    $('#business-info-display').removeClass('hidden');
                    $('#edit-business-info').removeClass('hidden');
                } else {
                    showNotice('Failed to save changes', 'error');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Update the display with new values
    function updateBusinessDisplay(data) {
        // Update business name in the header
        if (data.business_name) {
            $('.dashboard-header h1').text(data.business_name + ' Dashboard');
        }
        
        // Update business details
        if (data.business_name) $('.info-card:nth-child(1) .info-row:has(.info-label:contains("Name")) .info-value').text(data.business_name);
        if (data.business_description) $('.info-card:nth-child(1) .info-row:has(.info-label:contains("Description")) .info-value').text(data.business_description);
        if (data.business_website) {
            const hostname = data.business_website.replace(/(https?:\/\/)?(www\.)?/i, '').split('/')[0];
            $('.info-card:nth-child(1) .info-row:has(.info-label:contains("Website")) .info-value').html(
                '<a href="' + data.business_website + '" target="_blank" class="text-primary hover:underline">' + 
                hostname + 
                '</a>'
            );
        }
        
        // Update contact information
        if (data.business_email) $('.info-card:nth-child(2) .info-row:has(.info-label:contains("Email")) .info-value').text(data.business_email);
        if (data.business_phone) $('.info-card:nth-child(2) .info-row:has(.info-label:contains("Phone")) .info-value').text(data.business_phone);
        if (data.business_address) {
            let address = data.business_address;
            if (data.business_city) address += ', ' + data.business_city;
            if (data.business_state) address += ', ' + data.business_state;
            if (data.business_zip) address += ' ' + data.business_zip;
            if (data.business_country) address += '<br>' + data.business_country;
            
            $('.info-card:nth-child(2) .info-row:has(.info-label:contains("Address")) .info-value').html(address);
        }
    }

    // Show notice message with modern styling
    function showNotice(message, type = 'success') {
        // Remove any existing notices
        $('.notice').remove();
        
        // Create notice element with icon
        const icon = type === 'success' ? 
            '<svg class="notice-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' :
            '<svg class="notice-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        
        const notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <div class="notice-content">
                    <div class="notice-icon">${icon}</div>
                    <p>${message}</p>
                </div>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        `);
        
        // Add to page and animate in
        $('.dashboard-header').after(notice.hide().slideDown(200));
        
        // Auto-hide after 5 seconds
        const autoHide = setTimeout(() => {
            notice.slideUp(200, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        notice.on('click', '.notice-dismiss', function(e) {
            clearTimeout(autoHide);
            notice.slideUp(200, function() {
                $(this).remove();
            });
            e.preventDefault();
        });
    }
});
