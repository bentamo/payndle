/**
 * Services View JavaScript for Public Display
 * Handles loading and displaying services for public users
 */

jQuery(document).ready(function($) {
    // Initialize the services view
    init();
    
    function init() {
        console.log('Services View initialized');
        loadBusinessInfo();
        loadServices();
    }
    
    // Load business information
    function loadBusinessInfo() {
        if ($('#business-info-display-public').length === 0) return;
        
        $.ajax({
            url: servicesView.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_business_info',
                nonce: servicesView.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayBusinessInfo(response.data);
                } else {
                    $('#business-info-display-public').html('<div class="no-business-info">Business information not available.</div>');
                }
            },
            error: function() {
                $('#business-info-display-public').html('<div class="error">Failed to load business information.</div>');
            }
        });
    }
    
    // Display business information
    function displayBusinessInfo(business) {
        var html = `
            <div class="business-card-public">
                <div class="business-header">
                    <h3>${business.business_name || 'Business Name'}</h3>
                </div>
                <div class="business-details">
                    ${business.description ? `<p class="business-description">${business.description}</p>` : ''}
                    <div class="business-contact">
                        ${business.phone ? `<div class="contact-item"><span class="icon">📞</span> ${business.phone}</div>` : ''}
                        ${business.email ? `<div class="contact-item"><span class="icon">✉️</span> ${business.email}</div>` : ''}
                        ${business.address ? `<div class="contact-item"><span class="icon">📍</span> ${business.address}</div>` : ''}
                        ${business.website ? `<div class="contact-item"><span class="icon">🌐</span> <a href="${business.website}" target="_blank">${business.website}</a></div>` : ''}
                    </div>
                    ${business.business_hours ? `<div class="business-hours"><span class="icon">🕒</span> ${business.business_hours}</div>` : ''}
                </div>
            </div>
        `;
        $('#business-info-display-public').html(html);
    }
    
    // Load services
    function loadServices() {
        var $grid = $('#services-grid-public');
        var columns = $grid.data('columns') || 3;
        var featuredOnly = $grid.data('featured-only') === 'true';
        var showInactive = $grid.data('show-inactive') === 'true';
        
        $.ajax({
            url: servicesView.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_services',
                nonce: servicesView.nonce
            },
            success: function(response) {
                if (response.success) {
                    var services = response.data;
                    
                    // Filter services based on settings
                    if (featuredOnly) {
                        services = services.filter(service => service.is_featured == 1);
                    }
                    
                    if (!showInactive) {
                        services = services.filter(service => service.is_active == 1);
                    }
                    
                    displayServices(services, columns);
                } else {
                    $grid.html('<div class="no-services">No services available at the moment.</div>');
                }
            },
            error: function() {
                $grid.html('<div class="error">Failed to load services.</div>');
            }
        });
    }
    
    // Display services
    function displayServices(services, columns) {
        var $grid = $('#services-grid-public');
        
        if (services.length === 0) {
            $grid.html('<div class="no-services">No services available.</div>');
            return;
        }
        
        // Set grid columns
        $grid.css('grid-template-columns', `repeat(${columns}, 1fr)`);
        
        var html = '';
        services.forEach(function(service) {
            var price = parseFloat(service.price || 0);
            var thumbnail = service.thumbnail || '';
            var featuredClass = service.is_featured == 1 ? ' featured' : '';
            var inactiveClass = service.is_active == 0 ? ' inactive' : '';
            
            html += `
                <div class="service-card-public${featuredClass}${inactiveClass}" role="listitem">
                    ${service.is_featured == 1 ? '<div class="featured-badge">Featured</div>' : ''}
                    ${service.is_active == 0 ? '<div class="inactive-badge">Unavailable</div>' : ''}
                    
                    ${thumbnail ? `
                        <div class="service-image">
                            <img src="${thumbnail}" alt="${service.name}" loading="lazy">
                        </div>
                    ` : `
                        <div class="service-image placeholder">
                            <span>📋</span>
                        </div>
                    `}
                    
                    <div class="service-content">
                        <h3 class="service-name">${service.name}</h3>
                        ${service.description ? `<p class="service-description">${service.description}</p>` : ''}
                        
                        <div class="service-meta">
                            ${service.duration ? `<div class="service-duration"><span class="icon">⏱️</span> ${service.duration}</div>` : ''}
                            <div class="service-price">
                                <span class="price-label">Price:</span>
                                <span class="price-amount">₱${price.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $grid.html(html);
    }
});
