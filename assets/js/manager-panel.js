/**
 * Manager Business Panel JavaScript
 * Handles all frontend interactions for the business management panel
 */

jQuery(document).ready(function($) {
    
    console.log('Manager Panel JavaScript loaded');
    console.log('managerPanel object:', managerPanel);
    
    // Debug function - can be called from browser console
    window.testThumbnailUpload = function() {
        console.log('=== TESTING THUMBNAIL UPLOAD ===');
        console.log('Preview element exists:', !!document.getElementById('thumbnail-preview'));
        console.log('Upload element exists:', !!document.getElementById('thumbnail-upload'));
        
        var uploadInput = document.getElementById('thumbnail-upload');
        if (uploadInput) {
            console.log('Triggering file input click...');
            uploadInput.click();
        } else {
            console.error('Upload input not found!');
        }
    };
    
    // Simple click test
    window.clickThumbnail = function() {
        document.getElementById('thumbnail-preview').click();
    };
    
    // Initialize the business panel
    initializeBusinessPanel();
    
    // Business management functions
    function initializeBusinessPanel() {
        loadBusinessInfo();
        loadServices();
        setupEventHandlers();
    }
    
    // Event handlers
    function setupEventHandlers() {
        console.log('Setting up event handlers');
        
        // Business management events
        $('.btn-edit-business, #edit-business-btn').on('click', openBusinessModal);
        $('#add-service-btn').on('click', function() {
            console.log('Add service button clicked');
            openServiceModal();
        });
        
    // Refresh buttons
    $('#refresh-panel').on('click', function() {
            console.log('Refreshing panel');
            loadBusinessInfo();
            loadServices();
        });
        $('#refresh-services').on('click', function() {
            console.log('Refreshing data');
            loadBusinessInfo();
            loadServices();
        });
        
        // Debug button
        $('#debug-panel').on('click', function() {
            console.log('Debug panel clicked');
            debugManagerPanel();
        });
        
        // Modal events
        $('.close, .btn-cancel, #cancel-business, #cancel-service').on('click', closeModals);
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('modal')) {
                closeModals();
            }
        });
        
        // Form submissions
        $('#business-form').on('submit', handleBusinessSubmit);
        $('#service-form').on('submit', handleServiceSubmit);
        
        // Service management events - using event delegation
        $(document).on('click', '.btn-edit-service', function() {
            var serviceId = $(this).data('service-id');
            console.log('Edit service clicked, ID:', serviceId);
            openServiceModal(serviceId);
        });
        
        $(document).on('click', '.btn-toggle-service', function() {
            var serviceId = $(this).data('service-id');
            console.log('Toggle service clicked, ID:', serviceId);
            toggleServiceStatus(serviceId);
        });
        
        $(document).on('click', '.btn-delete-service', function() {
            var serviceId = $(this).data('service-id');
            console.log('Delete service clicked, ID:', serviceId);
            deleteService(serviceId);
        });
        
        $(document).on('click', '.btn-feature-service', function() {
            var serviceId = $(this).data('service-id');
            console.log('Feature service clicked, ID:', serviceId);
            toggleServiceFeatured(serviceId);
        });
        
        // Delete service from form
        $(document).on('click', '#delete-service-form', function() {
            var serviceId = $(this).data('service-id');
            console.log('Delete service from form clicked, ID:', serviceId);
            if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                deleteService(serviceId);
                $('#service-modal').hide(); // Close modal after deletion
            }
        });
        
        // Thumbnail management events - DIRECT BUTTON APPROACH
        $(document).on('click', '#thumbnail-upload-btn', function() {
            console.log('Direct upload button clicked');
            document.getElementById('thumbnail-upload').click();
        });
        
        $(document).on('click', '#thumbnail-preview', function() {
            console.log('Thumbnail preview clicked - opening file picker');
            document.getElementById('thumbnail-upload').click();
        });
        
        $(document).on('change', '#thumbnail-upload', function(e) {
            console.log('File selected:', e.target.files[0]);
            if (e.target.files && e.target.files[0]) {
                handleThumbnailSelect(e);
            }
        });
        
        $(document).on('click', '#remove-thumbnail', function() {
            console.log('Remove thumbnail clicked');
            removeThumbnail();
        });
        
        // Service card thumbnail events (for existing services)
        $(document).on('click', '.service-thumbnail.placeholder', function() {
            var serviceId = $(this).data('service-id');
            if (serviceId) {
                triggerServiceThumbnailUpload(serviceId);
            }
        });
        
        $(document).on('click', '.thumbnail-btn.edit', function(e) {
            e.stopPropagation();
            var serviceId = $(this).data('service-id');
            triggerServiceThumbnailUpload(serviceId);
        });
        
        $(document).on('click', '.thumbnail-btn.delete', function(e) {
            e.stopPropagation();
            var serviceId = $(this).data('service-id');
            deleteServiceThumbnail(serviceId);
        });
    }
    
    // Load business information
    function loadBusinessInfo() {
        console.log('Loading business info...');
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_business_info',
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Business info response:', response);
                if (response.success) {
                    displayBusinessInfo(response.data);
                } else {
                    showError('Failed to load business information: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Business info AJAX error:', error);
                showError('Failed to load business information');
            }
        });
    }
    
    // Display business information
    function displayBusinessInfo(data) {
        console.log('Displaying business info:', data);
        var businessHtml = '';
        
        if (data && Object.keys(data).length > 0) {
            const emailHtml = data.email ? `<a href="mailto:${data.email}">${data.email}</a>` : '<em>Not set</em>';
            const telHref = data.phone ? data.phone.replace(/[^0-9+]/g, '') : '';
            const phoneHtml = data.phone ? `<a href="tel:${telHref}">${data.phone}</a>` : '<em>Not set</em>';
            const websiteHtml = data.website ? `<a href="${data.website}" target="_blank" rel="noopener">${data.website}</a>` : '<em>Not set</em>';

            const addressParts = [];
            if (data.address) addressParts.push(data.address);
            const cityStateZip = [data.city, data.state, data.zip_code].filter(Boolean).join(', ').trim();
            if (cityStateZip) addressParts.push(cityStateZip);
            const addressHtml = addressParts.length ? addressParts.join('<br>') : '<em>Not set</em>';

            const socials = [];
            if (data.facebook) socials.push({ name: 'Facebook', url: data.facebook });
            if (data.twitter) socials.push({ name: 'Twitter', url: data.twitter });
            if (data.instagram) socials.push({ name: 'Instagram', url: data.instagram });
            if (data.linkedin) socials.push({ name: 'LinkedIn', url: data.linkedin });
            const socialsHtml = socials.length
                ? `<div class="social-links">${socials.map(s => `<a class="social-link" href="${s.url}" target="_blank" rel="noopener">${s.name}</a>`).join('')}</div>`
                : '<em>No social links added</em>';

            businessHtml = `
                <div class="business-info-grid">
                    <div class="info-group">
                        <h4>Basic Information</h4>
                        <div class="info-item">
                            <span class="info-label">Business Name:</span>
                            <span class="info-value">${data.business_name || '<em>Not set</em>'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Description:</span>
                            <span class="info-value">${data.description || '<em>Not set</em>'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">${emailHtml}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">${phoneHtml}</span>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <h4>Location & Hours</h4>
                        <div class="info-item">
                            <span class="info-label">Address:</span>
                            <span class="info-value">${addressHtml}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Business Hours:</span>
                            <span class="info-value">${data.business_hours || '<em>Not set</em>'}</span>
                        </div>
                    </div>

                    <div class="info-group">
                        <h4>Online & Social</h4>
                        <div class="info-item">
                            <span class="info-label">Website:</span>
                            <span class="info-value">${websiteHtml}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Social Links:</span>
                            <span class="info-value">${socialsHtml}</span>
                        </div>
                    </div>
                </div>
            `;
        } else {
            businessHtml = '<div class="empty-state"><h3>No Business Information</h3><p>Click "Edit Business Info" to get started.</p></div>';
        }
        
        $('#business-info-display').html(businessHtml);
    }
    
    // Load services
    function loadServices() {
        console.log('Loading services...');
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_services',
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Services response:', response);
                if (response.success) {
                    displayServices(response.data);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Failed to load services: ' + (response.data || 'Unknown error');
                    showError(errorMessage);
                    $('#services-grid').html('<div class="loading">' + errorMessage + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Services AJAX error:', status, error, xhr.responseText);
                var errorMessage = 'Failed to load services. Status: ' + status;
                if (xhr.status === 403) {
                    errorMessage = 'Access denied. Please refresh the page and try again.';
                } else if (xhr.status === 404) {
                    errorMessage = 'AJAX endpoint not found. Please check plugin installation.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please check error logs.';
                }
                showError(errorMessage);
                $('#services-grid').html('<div class="loading">' + errorMessage + '</div>');
            }
        });
    }
    
    // Display services
        function displayServices(services) {
                console.log('Displaying services:', services);
                var servicesHtml = '';

                if (services && services.length > 0) {
                        services.forEach(function(service) {
                                var price = parseFloat(service.price || 0);
                                var isFeatured = service.is_featured == '1';
                                var isActive = service.status === 'active';

                                servicesHtml += `
                                <div class="card${isFeatured ? ' is-featured' : ''}${!isActive ? ' is-inactive' : ''}">
                                    ${isFeatured ? '<div class="badge">HOT SALE</div>' : ''}
                                    <div class="tilt">
                                        <div class="img">${service.thumbnail ? `<img src="${service.thumbnail}" alt="${service.name}">` : ''}</div>
                                    </div>
                                    <div class="info">
                                        <h2 class="title">${service.name}</h2>
                                        <p class="desc">${service.description || ''}</p>
                                        <div class="feats">
                                            ${service.duration ? `<span class="feat">${service.duration}</span>` : ''}
                                            ${service.status ? `<span class="feat">${service.status}</span>` : ''}
                                        </div>
                                        <div class="bottom">
                                                                    <div class="price">
                                                                        <span class="new">₱${price.toFixed(2)}</span>
                                                                    </div>
                                            <button class="btn btn-edit-service" data-service-id="${service.id}" title="Edit Service">
                                                <span>Edit</span>
                                                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4"/>
                                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                                    <path d="M16 10a4 4 0 01-8 0"/>
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
                                            <div class="stock" style="color:${isActive ? '#22C55E' : '#6b7280'}">${isActive ? 'Active' : 'Inactive'}</div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                } else {
                        servicesHtml = '<div class="empty-state"><h3>No Services Found</h3><p>Click "Add New Service" to get started.</p></div>';
                }

                $('#services-grid').html(servicesHtml);
        }
    
    // Open business modal
    function openBusinessModal() {
        console.log('Opening business modal');
        loadBusinessFormData();
        $('#business-modal').show();
    }
    
    // Load business form data
    function loadBusinessFormData() {
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_business_info',
                nonce: managerPanel.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateBusinessForm(response.data);
                }
            },
            error: function() {
                showError('Failed to load business data');
            }
        });
    }
    
    // Populate business form
    function populateBusinessForm(data) {
        $('#business_name').val(data.business_name || '');
        $('#business_description').val(data.description || '');
        $('#business_email').val(data.email || '');
        $('#business_phone').val(data.phone || '');
        $('#business_address').val(data.address || '');
    $('#business_city').val(data.city || '');
    $('#business_state').val(data.state || '');
    $('#business_zip_code').val(data.zip_code || '');
        $('#business_website').val(data.website || '');
        $('#business_hours').val(data.business_hours || '');
    $('#business_timezone').val(data.timezone || '');
    $('#social_facebook').val(data.facebook || '');
    $('#social_twitter').val(data.twitter || '');
    $('#social_instagram').val(data.instagram || '');
    $('#social_linkedin').val(data.linkedin || '');
    }
    
    // Open service modal
    function openServiceModal(serviceId = null) {
        console.log('Opening service modal, serviceId:', serviceId);
        
        if (serviceId) {
            loadServiceData(serviceId);
            $('#delete-service-form').show().data('service-id', serviceId);
        } else {
            clearServiceForm();
            $('#delete-service-form').hide();
        }
        
        $('#service-modal').show();
        console.log('Service modal opened');
    }
    
    // Load service data for editing
    function loadServiceData(serviceId) {
        console.log('Loading service data for ID:', serviceId);
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_service',
                service_id: serviceId,
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Service data response:', response);
                if (response.success) {
                    populateServiceForm(response.data);
                    $('#service-modal-title').text('Edit Service');
                } else {
                    showError('Failed to load service data: ' + response.data);
                }
            },
            error: function() {
                showError('Failed to load service data');
            }
        });
    }
    
    // Populate service form
    function populateServiceForm(data) {
        $('#service_id').val(data.id);
        $('#service_name').val(data.name);
        $('#service_category').val(data.category);
        $('#service_description').val(data.description);
        $('#service_price').val(data.price);
        $('#service_duration').val(data.duration);
        $('#sort_order').val(data.sort_order || 0);
        $('#is_featured').prop('checked', data.is_featured == '1');
        
        // Handle existing thumbnail
        if (data.thumbnail && data.thumbnail !== '') {
            var preview = $('#thumbnail-preview');
            preview.removeClass('placeholder');
            preview.html('<img src="' + data.thumbnail + '" alt="Current thumbnail" style="width: 100%; height: 100%; object-fit: cover;">');
            $('#remove-thumbnail').show();
        } else {
            removeThumbnail();
        }
    }
    
    // Clear service form
    function clearServiceForm() {
        $('#service-form')[0].reset();
        $('#service_id').val('');
        $('#service-modal-title').text('Add New Service');
        $('#delete-service-form').hide(); // Hide delete button for new services
        removeThumbnail(); // Reset thumbnail
        currentThumbnailFile = null; // Clear selected file
    }
    
    // Handle business form submission
    function handleBusinessSubmit(e) {
        e.preventDefault();
        console.log('Submitting business form');
        
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.addClass('loading').prop('disabled', true);
        
        var formData = {
            action: 'save_business_info',
            nonce: managerPanel.nonce,
            business_name: $('#business_name').val(),
            description: $('#business_description').val(),
            email: $('#business_email').val(),
            phone: $('#business_phone').val(),
            address: $('#business_address').val(),
            city: $('#business_city').val(),
            state: $('#business_state').val(),
            zip_code: $('#business_zip_code').val(),
            website: $('#business_website').val(),
            business_hours: $('#business_hours').val(),
            timezone: $('#business_timezone').val(),
            facebook: $('#social_facebook').val(),
            twitter: $('#social_twitter').val(),
            instagram: $('#social_instagram').val(),
            linkedin: $('#social_linkedin').val()
        };
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Business form response:', response);
                if (response.success) {
                    closeModals();
                    loadBusinessInfo();
                    showSuccess('Business information saved successfully');
                } else {
                    showError('Failed to save business information: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to save business information');
            },
            complete: function() {
                $submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }
    
    // Handle service form submission
    function handleServiceSubmit(e) {
        e.preventDefault();
        console.log('Submitting service form');
        
        var serviceId = $('#service_id').val();
        var isEdit = serviceId !== '';
        
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.addClass('loading').prop('disabled', true);
        
        // Use FormData to handle file uploads
        var formData = new FormData();
        formData.append('action', isEdit ? 'update_service' : 'create_service');
        formData.append('nonce', managerPanel.nonce);
        formData.append('name', $('#service_name').val());
        formData.append('category', $('#service_category').val());
        formData.append('description', $('#service_description').val());
        formData.append('price', $('#service_price').val());
        formData.append('duration', $('#service_duration').val());
        formData.append('sort_order', $('#sort_order').val());
        formData.append('is_featured', $('#is_featured').is(':checked') ? '1' : '0');
        
        if (isEdit) {
            formData.append('service_id', serviceId);
        }
        
        // Add thumbnail if selected
        if (currentThumbnailFile) {
            formData.append('thumbnail', currentThumbnailFile);
            console.log('Adding thumbnail to form data');
        }
        
        console.log('Service form data prepared for submission');
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Service form response:', response);
                if (response.success) {
                    closeModals();
                    loadServices();
                    showSuccess(isEdit ? 'Service updated successfully' : 'Service created successfully');
                    // Reset thumbnail file
                    currentThumbnailFile = null;
                } else {
                    showError('Failed to save service: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to save service');
            },
            complete: function() {
                $submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }
    
    // Toggle service status
    function toggleServiceStatus(serviceId) {
        if (!confirm('Are you sure you want to change this service status?')) {
            return;
        }
        
        console.log('Toggling service status for ID:', serviceId);
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_service_status',
                service_id: serviceId,
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Toggle status response:', response);
                if (response.success) {
                    loadServices();
                    showSuccess('Service status updated successfully');
                } else {
                    showError('Failed to update service status: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to update service status');
            }
        });
    }
    
    // Toggle service featured status
    function toggleServiceFeatured(serviceId) {
        console.log('Toggling featured status for service ID:', serviceId);
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_service_featured',
                service_id: serviceId,
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Toggle featured response:', response);
                if (response.success) {
                    loadServices();
                    showSuccess('Service featured status updated successfully');
                } else {
                    showError('Failed to update featured status: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to update featured status');
            }
        });
    }
    
    // Delete service
    function deleteService(serviceId) {
        if (!confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
            return;
        }
        
        console.log('Deleting service ID:', serviceId);
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_service',
                service_id: serviceId,
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Delete service response:', response);
                if (response.success) {
                    loadServices();
                    showSuccess('Service deleted successfully');
                } else {
                    showError('Failed to delete service: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to delete service');
            }
        });
    }
    
    // Thumbnail Management Functions
    var currentThumbnailFile = null;
    
    // Handle thumbnail file selection in the service form with cropping
    var cropperInstance = null;
    var pendingFileForCrop = null;
    function handleThumbnailSelect(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        console.log('File selected for thumbnail:', file.name);
        pendingFileForCrop = file;
        
        // Open cropper modal
        var reader = new FileReader();
        reader.onload = function(ev) {
            var $img = $('#cropper-image');
            $img.attr('src', ev.target.result);
            $('#cropper-modal').show();
            // init cropper after image set
            setTimeout(function(){
                if (cropperInstance) {
                    try { cropperInstance.destroy(); } catch(err) {}
                }
                cropperInstance = new Cropper($img[0], {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 0.9,
                    background: false,
                });
                console.log('Cropper initialized');
            }, 50);
        };
        reader.readAsDataURL(file);
    }
    
    // Preview selected thumbnail in the form
    function previewThumbnail(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = $('#thumbnail-preview');
            preview.show();
            preview.html('<img src="' + e.target.result + '" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">');
            $('#remove-thumbnail').show();
            $('#upload-btn-text').text('Change Image');
            console.log('Thumbnail preview updated');
        };
        reader.readAsDataURL(file);
    }
    
    // Remove thumbnail preview from form
    function removeThumbnail() {
        $('#thumbnail-preview').hide().empty();
        $('#thumbnail-upload').val('');
        $('#remove-thumbnail').hide();
        $('#upload-btn-text').text('Choose Image');
        currentThumbnailFile = null;
        console.log('Thumbnail removed');
    }

    // Cropper modal controls
    $(document).on('click', '#cropper-cancel, #cropper-modal .close', function(){
        if (cropperInstance) { try { cropperInstance.destroy(); } catch(err) {} cropperInstance = null; }
        $('#cropper-modal').hide();
        pendingFileForCrop = null;
    });

    $(document).on('click', '#cropper-skip', function(){
        console.log('Using original image without cropping');
        if (pendingFileForCrop) {
            currentThumbnailFile = pendingFileForCrop;
            previewThumbnail(pendingFileForCrop);
            // cleanup
            if (cropperInstance) { try { cropperInstance.destroy(); } catch(err) {} cropperInstance = null; }
            pendingFileForCrop = null;
            $('#cropper-modal').hide();
        }
    });

    $(document).on('click', '#cropper-apply', function(){
        console.log('Applying cropped image');
        if (!cropperInstance) return;
        // Get cropped canvas and convert to Blob
        var canvas = cropperInstance.getCroppedCanvas({ width: 600, height: 600 });
        if (!canvas) return;
        canvas.toBlob(function(blob){
            if (!blob) return;
            // Create a File from blob to keep filename semantics
            var fileName = (pendingFileForCrop && pendingFileForCrop.name) ? pendingFileForCrop.name : 'thumbnail.png';
            var croppedFile = new File([blob], fileName, { type: blob.type || 'image/png' });
            currentThumbnailFile = croppedFile;
            previewThumbnail(croppedFile);
            console.log('Cropped file ready:', croppedFile.name);
            // cleanup
            try { cropperInstance.destroy(); } catch(err) {}
            cropperInstance = null;
            pendingFileForCrop = null;
            $('#cropper-modal').hide();
        }, 'image/png', 0.92);
    });
    
    // Trigger thumbnail upload for existing services
    function triggerServiceThumbnailUpload(serviceId) {
        var input = $('<input type="file" accept="image/*" style="display: none;">');
        $('body').append(input);
        
        input.on('change', function() {
            var file = this.files[0];
            if (file) {
                uploadServiceThumbnail(serviceId, file);
            }
            $(this).remove();
        });
        
        input.click();
    }
    
    // Upload thumbnail for existing service
    function uploadServiceThumbnail(serviceId, file) {
        console.log('Uploading thumbnail for service:', serviceId);
        
        var formData = new FormData();
        formData.append('action', 'upload_service_thumbnail');
        formData.append('service_id', serviceId);
        formData.append('thumbnail', file);
        formData.append('nonce', managerPanel.nonce);
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Thumbnail upload response:', response);
                if (response.success) {
                    showSuccess('Thumbnail uploaded successfully');
                    loadServices(); // Reload services to show new thumbnail
                } else {
                    showError(response.data?.message || 'Failed to upload thumbnail');
                }
            },
            error: function() {
                showError('Failed to upload thumbnail');
            }
        });
    }
    
    // Delete thumbnail for existing service
    function deleteServiceThumbnail(serviceId) {
        if (!confirm('Are you sure you want to delete this thumbnail?')) {
            return;
        }
        
        console.log('Deleting thumbnail for service:', serviceId);
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_service_thumbnail',
                service_id: serviceId,
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Thumbnail delete response:', response);
                if (response.success) {
                    showSuccess('Thumbnail deleted successfully');
                    loadServices(); // Reload services to remove thumbnail
                } else {
                    showError(response.data?.message || 'Failed to delete thumbnail');
                }
            },
            error: function() {
                showError('Failed to delete thumbnail');
            }
        });
    }
    
    // Close all modals
    function closeModals() {
        $('.modal').hide();
    }
    
    // Show success message
    function showSuccess(message) {
        showNotification(message, 'success');
    }
    
    // Show error message
    function showError(message) {
        showNotification(message, 'error');
    }
    
    // Show notification
    function showNotification(message, type) {
        console.log('Showing notification:', type, message);
        
        // Remove existing notifications
        $('.manager-notification').remove();
        
        var cssClass = 'manager-notification-info';
        if (type === 'success') cssClass = 'manager-notification-success';
        if (type === 'error') cssClass = 'manager-notification-error';

        var notification = $(`
            <div class="manager-notification ${cssClass}">
                <span>${message}</span>
                <button class="notification-close" aria-label="Close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto remove after 4 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
        
        // Allow manual dismiss
        notification.find('.notification-close').on('click', function(e) {
            e.stopPropagation();
            notification.fadeOut(200, function() { $(this).remove(); });
        });
    }

    // Debug function
    function debugManagerPanel() {
        console.log('Starting debug check...');
        
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'debug_manager_panel',
                nonce: managerPanel.nonce
            },
            success: function(response) {
                console.log('Debug response:', response);
                if (response.success) {
                    showNotification('Debug results logged to console. Check console for details.', 'success');
                } else {
                    showNotification('Debug failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Debug AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
                showNotification('Debug request failed: ' + error, 'error');
            }
        });
    }

});
