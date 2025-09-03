jQuery(document).ready(function($) {
    console.log('Services Booking script loaded');
    
    // Initialize the services booking
    initServicesBooking();
    
    function initServicesBooking() {
        console.log('Initializing services booking');
        
        // Load services on page load
        loadPublicServices();
        
        // Event listeners
        $('#refresh-services-public').on('click', function() {
            console.log('Refresh services clicked');
            loadPublicServices();
        });
        
        // Service card interactions
        $(document).on('click', '.btn-view-details', function() {
            var serviceId = $(this).data('service-id');
            console.log('View details clicked for service:', serviceId);
            showServiceDetails(serviceId);
        });
        
        $(document).on('click', '.btn-book-service', function() {
            var serviceId = $(this).data('service-id');
            console.log('Book service clicked for service:', serviceId);
            showBookingForm(serviceId);
        });
        
        // Modal events
        $('.close, #close-details, #cancel-booking').on('click', function() {
            closeAllModals();
        });
        
        $('#book-service-btn').on('click', function() {
            var serviceId = getCurrentServiceId();
            showBookingForm(serviceId);
        });
        
        // Form submission
        $('#booking-form').on('submit', handleBookingSubmit);
        
        // Close modal when clicking outside
        $('.modal').on('click', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });
        
        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }
    
    // Load public services
    function loadPublicServices() {
        console.log('Loading public services');
        
        $('#public-services-grid').html('<div class="loading">Loading services...</div>');
        
        $.ajax({
            url: servicesBooking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_public_services',
                nonce: servicesBooking.nonce
            },
            success: function(response) {
                console.log('Services loaded:', response);
                if (response.success) {
                    displayServices(response.data);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Failed to load services';
                    showError(errorMessage);
                    $('#public-services-grid').html('<div class="loading">' + errorMessage + '</div>');
                }
            },
            error: function() {
                showError('Failed to load services');
            }
        });
    }
    
    // Display services in grid
    function displayServices(services) {
        console.log('Displaying services:', services);
        
        var grid = $('#public-services-grid');
        
        if (!services || services.length === 0) {
            grid.html(`
                <div class="loading">
                    <h3>No services available</h3>
                    <p>Services will appear here once they are added in the manager panel.</p>
                    <p><strong>For administrators:</strong> Use the <code>[manager_panel]</code> shortcode to add services.</p>
                </div>
            `);
            return;
        }
        
        var html = '';
        services.forEach(function(service) {
            var price = parseFloat(service.price || 0);
            var isFeatured = service.is_featured == '1';
            var thumbnailHtml = '';
            
            if (service.thumbnail && service.thumbnail !== '') {
                thumbnailHtml = `<img src="${service.thumbnail}" alt="${service.name}">`;
            }
            
            var description = service.description || 'No description available';
            var duration = service.duration || '';
            
            html += `
                <div class="card${isFeatured ? ' is-featured' : ''}">
                    ${isFeatured ? '<div class="badge">FEATURED</div>' : ''}
                    <div class="tilt">
                        <div class="img">${thumbnailHtml}</div>
                    </div>
                    <div class="info">
                        <h2 class="title">${service.name}</h2>
                        <p class="desc">${description}</p>
                        <div class="feats">
                            ${duration ? `<span class="feat">${duration}</span>` : ''}
                            <span class="feat">Available</span>
                        </div>
                        <div class="bottom">
                            <div class="price">
                                <span class="new">₱${price.toFixed(2)}</span>
                            </div>
                            <button class="btn btn-view-details" data-service-id="${service.id}" type="button">
                                <span>View</span>
                                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <button class="btn btn-book-service" data-service-id="${service.id}" type="button" style="margin-left: 8px; background: linear-gradient(45deg, #2563eb, #1d4ed8);">
                                <span>Book</span>
                                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="meta">
                            <div class="rating">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="0.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="0.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="0.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="0.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="0.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <span class="rcount">Service</span>
                            </div>
                            <div class="stock" style="color: #22C55E">Available</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        grid.html(html);
    }
    
    // Show service details modal
    function showServiceDetails(serviceId) {
        console.log('Showing service details for:', serviceId);
        
        $.ajax({
            url: servicesBooking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_public_service_details',
                service_id: serviceId,
                nonce: servicesBooking.nonce
            },
            success: function(response) {
                console.log('Service details response:', response);
                if (response.success) {
                    populateServiceDetails(response.data);
                    $('#service-details-modal').show();
                } else {
                    showError('Failed to load service details');
                }
            },
            error: function() {
                showError('Failed to load service details');
            }
        });
    }
    
    // Populate service details modal
    function populateServiceDetails(service) {
        $('#service-details-title').text(service.name);
        $('#service-details-name').text(service.name);
        $('#service-details-price').text('₱' + parseFloat(service.price || 0).toFixed(2));
        $('#service-details-duration').text(service.duration || '');
        $('#service-details-description').text(service.description || 'No description available');
        
        // Handle thumbnail
        if (service.thumbnail && service.thumbnail !== '') {
            $('#service-details-thumbnail').attr('src', service.thumbnail).attr('alt', service.name).show();
            $('#service-placeholder').hide();
        } else {
            $('#service-details-thumbnail').hide();
            $('#service-placeholder').show();
        }
        
        // Store service ID for booking
        $('#book-service-btn').data('service-id', service.id);
    }
    
    // Show booking form modal
    function showBookingForm(serviceId) {
        console.log('Showing booking form for service:', serviceId);
        
        // Close details modal if open
        $('#service-details-modal').hide();
        
        // Load service details for booking form
        $.ajax({
            url: servicesBooking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_public_service_details',
                service_id: serviceId,
                nonce: servicesBooking.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateBookingForm(response.data);
                    loadBusinessContactInfo();
                    $('#booking-modal').show();
                } else {
                    showError('Failed to load service details for booking');
                }
            },
            error: function() {
                showError('Failed to load service details for booking');
            }
        });
    }
    
    // Populate booking form
    function populateBookingForm(service) {
        $('#booking-modal-title').text('Book: ' + service.name);
        $('#booking_service_id').val(service.id);
        
        var price = parseFloat(service.price || 0);
        var duration = service.duration ? ` (${service.duration})` : '';
        
        $('#booking-service-info').html(`
            <div class="booking-service-summary">
                <div class="booking-service-name">${service.name}${duration}</div>
                <div class="booking-service-price">₱${price.toFixed(2)}</div>
            </div>
        `);
    }
    
    // Load business contact info
    function loadBusinessContactInfo() {
        $.ajax({
            url: servicesBooking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_business_contact_info',
                nonce: servicesBooking.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#booking-contact-info').show();
                }
            }
        });
    }
    
    // Handle booking form submission
    function handleBookingSubmit(e) {
        e.preventDefault();
        console.log('Submitting booking form');
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Disable submit button and show loading
        $submitBtn.addClass('loading').prop('disabled', true);
        
        var formData = new FormData(this);
        formData.append('action', 'submit_booking_request');
        formData.append('nonce', servicesBooking.nonce);
        
        $.ajax({
            url: servicesBooking.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Booking response:', response);
                
                if (response.success) {
                    showSuccess(response.data.message);
                    $('#booking-form')[0].reset();
                    $('#booking-modal').hide();
                } else {
                    showError(response.data.message || 'Failed to submit booking request');
                }
            },
            error: function() {
                showError('Failed to submit booking request. Please try again.');
            },
            complete: function() {
                $submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }
    
    // Close all modals
    function closeAllModals() {
        $('.modal').hide();
    }
    
    // Get current service ID from details modal
    function getCurrentServiceId() {
        return $('#book-service-btn').data('service-id');
    }
    
    // Show success notification
    function showSuccess(message) {
        showNotification(message, 'success');
    }
    
    // Show error notification
    function showError(message) {
        showNotification(message, 'error');
    }
    
    // Show info notification
    function showInfo(message) {
        showNotification(message, 'info');
    }
    
    // Show notification
    function showNotification(message, type = 'info') {
        console.log('Notification:', type, message);
        
        // Remove existing notifications
        $('.notification').remove();
        
        var notification = $(`
            <div class="notification ${type}" role="alert">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Allow manual close by clicking
        notification.on('click', function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
});
