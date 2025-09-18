jQuery(document).ready(function($) {
    // Localized script data
    const bookingData = window.assignedBookingsData || {};
    let currentStatusFilter = 'all';
    
    // Initialize status filters
    function initStatusFilters() {
        const $container = $('.assigned-bookings-container');
        if ($container.find('.status-filters').length === 0) {
            const filtersHtml = `
                <div class="status-filters">
                    <button class="status-filter-btn active" data-status="all">All Bookings</button>
                    <button class="status-filter-btn" data-status="pending">Pending</button>
                    <button class="status-filter-btn" data-status="confirmed">Confirmed</button>
                    <button class="status-filter-btn" data-status="completed">Completed</button>
                    <button class="status-filter-btn" data-status="cancelled">Cancelled</button>
                </div>
            `;
            $container.prepend(filtersHtml);
        }
    }
    
    // Handle status filter click
    $(document).on('click', '.status-filter-btn', function() {
        const status = $(this).data('status');
        currentStatusFilter = status;
        
        // Update active state
        $('.status-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide rows based on status
        if (status === 'all') {
            $('tr[data-status-category]').show();
        } else if (status === 'pending') {
            // For pending tab, only show rows with status 'pending' regardless of payment status
            $('tr[data-status-category]').hide();
            $('tr[data-status="pending"]').show();
        } else {
            $('tr[data-status-category]').hide();
            
            // Map the selected tab to the appropriate status categories
            const statusMapping = {
                'upcoming': ['upcoming'],
                'completed': ['completed'],
                'cancelled': ['cancelled']
            };
            
            // Show rows that match any of the mapped status categories
            const categoriesToShow = statusMapping[status] || [status];
            categoriesToShow.forEach(cat => {
                $(`tr[data-status-category="${cat}"]`).show();
            });
            
            // For backward compatibility, also show by direct status match
            $(`tr[data-status="${status}"]`).show();
        }
    });
    
    // Handle status change
    $(document).on('change', '.status-select', function() {
        const $select = $(this);
        const bookingId = $select.data('booking-id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        const $savingIndicator = $select.siblings('.status-saving');
        
        // Debug log
        console.log('Status change initiated:', { bookingId, newStatus, row: $row.length });
        
        // Show saving indicator
        $savingIndicator.show().text('Saving...');
        
        // Get the latest nonce from the table or the localized script data
        const nonce = $('#assigned-bookings-table').data('nonce') || (window.assignedBookingsData ? window.assignedBookingsData.nonce : '');
        
        if (!nonce) {
            console.error('Security nonce is missing');
            $savingIndicator.text('Error: Missing security token').fadeOut(3000);
            return;
        }
        
        // Update the global nonce in case it was refreshed
        if (bookingData) {
            bookingData.nonce = nonce;
        }
        
        console.log('Status change - Booking ID:', bookingId, 'New Status:', newStatus, 'Nonce:', nonce);
        
        // Update the status
        updateBookingStatus(bookingId, newStatus, function(success) {
            $savingIndicator.hide();
            
            if (success) {
                // Update the status badge
                const $statusBadge = $row.find('.status-badge');
                $statusBadge.removeClass('status-pending status-confirmed status-completed status-cancelled')
                           .addClass('status-' + newStatus)
                           .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                
                // Update the row's status attributes
                $row.attr('data-status', newStatus)
                    .attr('data-status-category', newStatus)
                    .removeClass('status-pending status-confirmed status-completed status-cancelled')
                    .addClass('status-' + newStatus);
                
                // If we're filtering, update the display
                if (currentStatusFilter !== 'all' && currentStatusFilter !== newStatus) {
                    $row.hide();
                }
            } else {
                // Revert the select to the original value
                const originalStatus = $row.attr('data-status');
                $select.val(originalStatus);
                // The error message is shown by the updateBookingStatus function
            }
        });
    });
    
    // Handle action item clicks
    $(document).on('click', '.action-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $item = $(this);
        const action = $item.hasClass('view-details') ? 'view' : 
                      $item.hasClass('complete-booking') ? 'complete' : 
                      $item.hasClass('start-booking') ? 'start' : 'cancel';
                      
        const $row = $item.closest('tr');
        const bookingId = $row.data('booking-id') || $item.data('id') || $row.find('[data-booking-id]').data('booking-id');
        
        if (!bookingId) {
            console.error('Could not determine booking ID');
            showNotice('error', 'Error: Could not identify the booking.');
            return;
        }
        
        // Close dropdown
        $item.closest('.action-dropdown').attr('data-visible', 'false');
        
        // Handle the action
        if (action === 'view') {
            showBookingDetails(bookingId);
        } else if (action === 'complete') {
            if (confirm(bookingData.i18n?.confirm_complete || 'Are you sure you want to mark this booking as complete?')) {
                updateBookingStatus(bookingId, 'completed');
            }
        } else if (action === 'start') {
            if (confirm(bookingData.i18n?.confirm_start || 'Mark this booking as in progress?')) {
                updateBookingStatus(bookingId, 'in_progress');
            }
        } else if (action === 'cancel') {
            if (confirm(bookingData.i18n?.confirm_cancel || 'Are you sure you want to cancel this booking?')) {
                updateBookingStatus(bookingId, 'cancelled');
            }
        }
    });
    
    // Close modal
    function closeModal() {
        const $modal = $("#booking-modal");
        $modal.removeClass('active');
        $('body').css('overflow', '');
        
        // Reset modal content after animation
        setTimeout(() => {
            $modal.hide();
        }, 300);
    }
    
    // Close modal when clicking close button
    $(".close-modal").on("click", closeModal);

    // Close modal when clicking outside
    $(document).on("click", "#booking-modal", function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $("#booking-modal").is(":visible")) {
            closeModal();
        }
    });
    
    // Initialize filters when document is ready
    initStatusFilters();
    
    // Show booking details in modal
    function showBookingDetails(bookingId) {
        const $modal = $("#booking-modal");
        
        // Show modal with loading state
        $modal.addClass('active');
        $('body').css('overflow', 'hidden');
        
        $("#booking-details-content").html(
            `<div class="booking-details">
                <div class="detail-item">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#${bookingId}</span>
                </div>
                <div class="loading-message">
                    <div class="spinner"></div>
                    <p>Loading booking details...</p>
                </div>
            </div>`
        );
        
        // Simulate loading data
        setTimeout(() => {
            // This would be replaced with actual AJAX call
            $("#booking-details-content").html(
                `<div class="booking-details">
                    <div class="detail-item">
                        <span class="detail-label">Booking ID:</span>
                        <span class="detail-value">#${bookingId}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value">John Doe</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Service:</span>
                        <span class="detail-value">Haircut & Styling</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time:</span>
                        <span class="detail-value">Sep 20, 2023 at 2:30 PM</span>
                    </div>
                </div>`
            );
        }, 500);
    }
    
    // Update booking status via AJAX
    function updateBookingStatus(bookingId, status, callback) {
        // Convert bookingId to integer to ensure it's a valid number
        bookingId = parseInt(bookingId, 10);
        
        if (isNaN(bookingId) || bookingId <= 0) {
            console.error('Invalid booking ID:', bookingId);
            showNotice('error', 'Invalid booking ID. Please refresh the page and try again.');
            if (typeof callback === 'function') callback(false);
            return;
        }
        
        // Debug: Log all booking rows for inspection
        console.log('All booking rows:', $('tr[data-booking-id]').map(function() {
            return $(this).attr('data-booking-id');
        }).get());
        
        // Find the row using different possible selectors
        let $row = $(`tr[data-booking-id="${bookingId}"]`);
        
        // If not found, try other possible selectors
        if ($row.length === 0) {
            $row = $(`tr[data-id="${bookingId}"]`);
        }
        if ($row.length === 0) {
            $row = $(`[data-booking-id="${bookingId}"]`).closest('tr');
        }
        if ($row.length === 0) {
            $row = $(`[data-id="${bookingId}"]`).closest('tr');
        }
        
        if ($row.length === 0) {
            console.error('Could not find booking row for ID:', bookingId);
            console.error('Available booking IDs on page:', 
                $('tr[data-booking-id]').map(function() { 
                    return $(this).data('booking-id'); 
                }).get().join(', ')
            );
            
            // Try to find any element with the booking ID
            const $anyElement = $(`[data-booking-id*="${bookingId}"],[data-id*="${bookingId}"]`);
            if ($anyElement.length) {
                console.log('Found element with similar ID:', $anyElement);
                $row = $anyElement.closest('tr');
                if ($row.length) {
                    console.log('Using closest row:', $row);
                }
            }
            
            if ($row.length === 0) {
                showNotice('error', 'Could not find the booking in the current view. Please refresh the page and try again.');
                if (typeof callback === 'function') callback(false);
                return;
            }
        }
        
        const $statusCell = $row.find('.status-badge');
        const $statusSelect = $row.find('.status-select');
        const originalStatus = $row.attr('data-status');
        
        // Show loading state
        $statusSelect.prop('disabled', true);
        if ($statusCell.length) {
            $statusCell.html('<span class="dashicons dashicons-update-alt spin"></span>');
        }
        
        // Show saving indicator
        $statusSelect.siblings('.status-saving').show();
        
        // Get the nonce from the localized script data
        const nonce = window.assignedBookingsData?.nonce || '';
        
        if (!nonce) {
            console.error('Security nonce is missing');
            showNotice('error', 'Security error: Missing nonce. Please refresh the page and try again.');
            $statusSelect.prop('disabled', false);
            $statusSelect.siblings('.status-saving').hide();
            if (typeof callback === 'function') callback(false);
            return;
        }
        
        console.log('Sending AJAX request with data:', {
            action: 'update_booking_status',
            booking_id: bookingId,
            status: status,
            nonce: nonce
        });
        
        // Prepare the request data with proper data types
        const requestData = {
            action: 'update_booking_status',
            booking_id: bookingId,  // Already converted to number
            status: String(status).toLowerCase(), // Ensure status is lowercase string
            nonce: String(nonce)    // Ensure nonce is string
        };
        
        // Debug logging
        console.log('Sending AJAX request to:', bookingData.ajax_url || ajaxurl);
        console.log('Request data:', JSON.stringify(requestData, null, 2));
        console.log('Booking row data:', {
            'data-booking-id': $row.attr('data-booking-id'),
            'data-id': $row.attr('data-id'),
            'data-status': $row.attr('data-status')
        });
        
        // Make AJAX request
        $.ajax({
            url: bookingData.ajax_url || ajaxurl,
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response, textStatus, jqXHR) {
                console.group('AJAX Response');
                console.log('Status:', textStatus);
                console.log('Response:', response);
                console.log('jqXHR:', jqXHR);
                console.groupEnd();
                
                // Re-enable the select and hide saving indicator
                $statusSelect.prop('disabled', false);
                $savingIndicator.hide();
                
                if (response && response.success) {
                    console.log('Update successful, updating UI...');
                    let statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
                    
                    // Update the status cell
                    if ($statusCell.length) {
                        $statusCell.removeClass('status-pending status-confirmed status-completed status-cancelled')
                                  .addClass('status-badge status-' + status)
                                  .text(statusLabel);
                    }
                    
                    // Map status to category
                    const statusToCategory = {
                        'completed': 'completed',
                        'cancelled': 'cancelled',
                        'confirmed': 'upcoming',
                        'pending': 'upcoming'
                    };
                    
                    const statusCategory = statusToCategory[status] || 'upcoming';
                    
                    // Update the row's data attributes
                    $row.attr('data-status', status)
                        .attr('data-status-category', statusCategory)
                        .removeClass('status-pending status-confirmed status-completed status-cancelled')
                        .addClass('status-' + status);
                    
                    // Update the select to show the new status
                    $statusSelect.val(status);
                    
                    // Show success message
                    const successMsg = response.data && response.data.message || 'Booking status updated successfully!';
                    console.log('Success:', successMsg);
                    showNotice('success', successMsg);
                    
                    // Call the callback with success
                    if (typeof callback === 'function') callback(true);
                } else {
                    console.error('Update failed:', response);
                    
                    // Revert to original status in the UI
                    $statusSelect.val(originalStatus);
                    
                    // Show error message
                    let errorMsg = 'Failed to update booking status. Please try again.';
                    let debugInfo = '';
                    
                    if (response) {
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        } else if (response.message) {
                            errorMsg = response.message;
                        }
                        
                        if (response.data && response.data.debug) {
                            debugInfo = response.data.debug;
                            console.error('Debug info:', debugInfo);
                        }
                    }
                    
                    console.error('Error details:', { errorMsg, response });
                    showNotice('error', errorMsg);
                    
                    // Call the callback with failure
                    if (typeof callback === 'function') callback(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr.responseText);
                
                // Revert to original status in the UI
                $statusSelect.val(originalStatus);
                
                // Call the callback with failure
                if (typeof callback === 'function') callback(false);
                
                // Try to parse the response for a better error message
                let errorMsg = 'An error occurred while updating the booking status. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response && response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                showNotice('error', errorMsg);
            },
            complete: function() {
                // Re-enable the status select and hide saving indicator
                $statusSelect.prop('disabled', false);
                $statusSelect.siblings('.status-saving').hide();
                
                // If we're filtering, update the display
                if (currentStatusFilter !== 'all' && currentStatusFilter !== status) {
                    $row.hide();
                }
            }
        });
    }
    
    // Show notice message
    function showNotice(type, message) {
        // Remove any existing notices
        $('.booking-notice').remove();
        
        // Create and show new notice
        const $notice = $(`
            <div class="booking-notice notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        // Add to page
        $('.assigned-bookings-container').prepend($notice);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
});
