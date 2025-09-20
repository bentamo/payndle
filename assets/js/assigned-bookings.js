jQuery(document).ready(function($) {
    // Debug log to check if script is loaded
    console.log('Assigned Bookings script loaded');
    
    // Localized script data
    const bookingData = window.assignedBookingsData || {};
    console.log('Assigned Bookings Data:', bookingData);
    
    // Fallback for ajaxurl if not defined
    if (typeof ajaxurl === 'undefined' && bookingData.ajax_url) {
        window.ajaxurl = bookingData.ajax_url;
    }
    
    // Status change handler with immediate UI update
    $(document).on('change', '.status-select', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $select = $(this);
        const bookingId = $select.data('booking-id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        const originalStatus = $row.attr('data-status');
        const $statusBadge = $row.find('.status-badge');
        
        // If status didn't change, do nothing
        if (newStatus === originalStatus) {
            return;
        }
        
        // 1. Immediately update the UI with fade effect
        const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        const statusCategory = ['completed', 'cancelled'].includes(newStatus) ? newStatus : 'upcoming';
        
        // Add fade-out class to the row
        $row.addClass('status-changing');
        
        // After a short delay, update the status and fade back in
        setTimeout(() => {
            // Update status badge
            $statusBadge
                .removeClass('status-pending status-confirmed status-completed status-cancelled')
                .addClass('status-' + newStatus)
                .text(statusText);
                
            // Update row data attributes
            $row.attr({
                'data-status': newStatus,
                'data-status-category': statusCategory
            });
            
            // Remove fade class to trigger the fade-in effect
            setTimeout(() => {
                $row.removeClass('status-changing');
            }, 50);
        }, 300); // This matches the CSS transition duration
        
        // 2. Send AJAX request in the background
        $.ajax({
            url: window.assignedBookingsData?.ajax_url || ajaxurl,
            type: 'POST',
            data: {
                action: 'update_booking_status',
                booking_id: bookingId,
                status: newStatus,
                nonce: window.assignedBookingsData?.nonce || ''
            },
            dataType: 'json',
            success: function(response) {
                if (!response || !response.success) {
                    // Revert on failure
                    revertStatusUpdate($select, $row, $statusBadge, originalStatus);
                    const errorMsg = response?.data?.message || 'Failed to update booking status';
                    showNotice('error', errorMsg);
                } else {
                    // Show success message and refresh the page after a short delay
                    showNotice('success', 'Status updated successfully! Refreshing...');
                    setTimeout(function() {
                        window.location.reload();
                    }, 500); // 0.5 second (500ms) delay before refresh
                }
            },
            error: function() {
                // Revert on error
                revertStatusUpdate($select, $row, $statusBadge, originalStatus);
                showNotice('error', 'An error occurred while updating the status. Please try again.');
            }
        });
        
        // Helper function to revert UI changes
        function revertStatusUpdate($select, $row, $statusBadge, originalStatus) {
            const originalStatusText = originalStatus.charAt(0).toUpperCase() + originalStatus.slice(1);
            const originalCategory = ['completed', 'cancelled'].includes(originalStatus) ? originalStatus : 'upcoming';
            
            // Add fade-out class to the row
            $row.addClass('status-changing');
            
            // After a short delay, revert the changes and fade back in
            setTimeout(() => {
                // Revert the status badge
                $statusBadge
                    .removeClass('status-pending status-confirmed status-completed status-cancelled')
                    .addClass('status-' + originalStatus)
                    .text(originalStatusText);
                    
                // Revert row attributes
                $row.attr({
                    'data-status': originalStatus,
                    'data-status-category': originalCategory
                });
                
                // Revert select value
                $select.val(originalStatus);
                
                // Remove fade class to trigger the fade-in effect
                setTimeout(() => {
                    $row.removeClass('status-changing');
                }, 50);
            }, 300); // This matches the CSS transition duration
        }
    });
    
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
    
    // Debounce function to prevent rapid status changes
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // Handle status change with debouncing
    const handleStatusChange = function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $select = $(this);
        const bookingId = $select.data('booking-id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        const originalStatus = $row.attr('data-status');
        
        console.log('Status change initiated:', { bookingId, newStatus, originalStatus });
        
        // If status didn't change, do nothing
        if (newStatus === originalStatus) {
            console.log('Status unchanged, aborting');
            return;
        }
        
        // Disable the select during update
        $select.prop('disabled', true);
        
        // Store the original values in case of error
        $row.data('original-status', originalStatus);
        $select.data('original-value', originalStatus);
        
        // Fade out the row, update status, then fade back in
        $row.animate({ opacity: 0.4 }, 200, function() {
            // Call the update function
            updateBookingStatus(bookingId, newStatus, function(success) {
                if (!success) {
                    // On failure, revert the select to original status
                    $select.val(originalStatus);
                    $row.attr('data-status', originalStatus);
                }
                
                // Fade the row back in
                $row.animate({ opacity: 1 }, 200);
                
                // Re-enable the select
                $select.prop('disabled', false);
            });
        });
        
        // Get the nonce from the table or the localized script data
        const nonce = $('#assigned-bookings-table').data('nonce') || (window.assignedBookingsData ? window.assignedBookingsData.nonce : '');
        
        if (!nonce) {
            const errorMsg = 'Security nonce is missing';
            console.error(errorMsg);
            // Fade the row back in with an error state
            $row.animate({ opacity: 1 }, 200, function() {
                // Revert to original status
                $select.val(originalStatus);
                $row.attr('data-status', originalStatus);
                // Re-enable the select
                $select.prop('disabled', false);
            });
            showNotice('error', errorMsg);
            return;
        }
        
        // Prepare the data to send
        const data = {
            action: 'update_booking_status',
            booking_id: bookingId,
            status: newStatus,
            nonce: nonce
        };
        
        // Debug log the AJAX URL and data
        console.log('AJAX URL:', window.assignedBookingsData?.ajaxurl || ajaxurl);
        console.log('Sending AJAX request with data:', data);
        
        // Make the AJAX request
        $.ajax({
            url: window.assignedBookingsData?.ajaxurl || ajaxurl,
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                // Show loading state
                $savingIndicator.find('.saving-text').text('Updating...');
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                
                // Remove saving indicator
                $savingIndicator.remove();
                
                // WordPress AJAX responses have success/error in the root object
                if (response && response.success) {
                    const successMessage = (response.data && response.data.message) || 'Status updated successfully!';
                    
                    // Update the status badge with animation
                    $statusBadge
                        .removeClass('status-pending status-confirmed status-completed status-cancelled')
                        .addClass('status-badge status-' + newStatus)
                        .text(statusLabel)
                        .addClass('updated')
                        .on('animationend', function() {
                            $(this).removeClass('updated');
                        });
                    
                    // Update the row's data attributes
                    $row.attr({
                        'data-status': newStatus,
                        'data-status-category': newStatus
                    });
                    
                    // Show success indicator
                    const $successIndicator = $(`
                        <span class="success-indicator">
                            <span class="dashicons dashicons-yes"></span>
                            <span class="success-text">Updated</span>
                        </span>
                    `);
                    $select.after($successIndicator);
                    
                    // Hide success indicator after delay
                    setTimeout(() => {
                        $successIndicator.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 2000);
                    
                    // Show success message
                    showNotice('success', successMessage);
                    
                    // If we're filtering, update the display
                    if (currentStatusFilter !== 'all' && currentStatusFilter !== newStatus) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                    
                    // Re-enable the select
                    $select.prop('disabled', false);
                } else {
                    // Revert to original status on error with animation
                    const originalStatus = $row.data('original-status');
                    const originalValue = $select.data('original-value');
                    
                    // Revert the select value
                    $select.val(originalValue);
                    
                    // Get error message
                    const errorMessage = (response && response.data && response.data.message) || 
                                       (response && response.message) || 
                                       'Failed to update status. Please try again.';
                    
                    // Show error indicator
                    const $errorIndicator = $(`
                        <span class="error-indicator">
                            <span class="dashicons dashicons-warning"></span>
                            <span class="error-text">Error</span>
                        </span>
                    `);
                    
                    $savingIndicator.replaceWith($errorIndicator);
                    
                    // Show error notice
                    showNotice('error', errorMessage);
                    
                    // Revert status badge
                    $statusBadge
                        .removeClass('status-pending status-confirmed status-completed status-cancelled')
                        .addClass('status-badge status-' + originalStatus)
                        .addClass('error-animation')
                        .on('animationend', function() {
                            $(this).removeClass('error-animation');
                        });
                    
                    // Remove error indicator after delay
                    setTimeout(() => {
                        $errorIndicator.fadeOut(3000, function() {
                            $(this).remove();
                        });
                    }, 2000);
                    
                    // Re-enable the select
                    $select.prop('disabled', false);
                    $row.removeClass('updating').css('opacity', '1');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                
                // Revert to original status
                const originalStatus = $row.data('original-status');
                const originalValue = $select.data('original-value');
                
                $select.val(originalValue);
                
                // Show error indicator
                const $errorIndicator = $(`
                    <span class="error-indicator">
                        <span class="dashicons dashicons-warning"></span>
                        <span class="error-text">Network Error</span>
                    </span>
                `);
                
                $savingIndicator.replaceWith($errorIndicator);
                
                // Show error notice
                showNotice('error', 'Network error. Please check your connection and try again.');
                
                // Revert status badge with error animation
                $row.find('.status-badge')
                    .removeClass('status-pending status-confirmed status-completed status-cancelled')
                    .addClass('status-badge status-' + originalStatus)
                    .addClass('error-animation')
                    .on('animationend', function() {
                        $(this).removeClass('error-animation');
                    });
                
                // Remove error indicator after delay
                setTimeout(() => {
                    $errorIndicator.fadeOut(3000, function() {
                        $(this).remove();
                    });
                }, 2000);
                
                // Re-enable the select
                $select.prop('disabled', false);
                $row.removeClass('updating').css('opacity', '1');
                
                // Call the callback if provided
                if (typeof callback === 'function') {
                    callback(true);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showNotice('error', 'An error occurred while updating the booking status. Please try again.');
                
                // Re-enable the select and restore original value
                $select.val(originalStatus).prop('disabled', false);
                $row.removeClass('updating').css('opacity', '1');
                
                // Call the callback if provided
                if (typeof callback === 'function') {
                    callback(false);
                }
            },
            complete: function() {
                // Clean up any remaining loading states
                $row.removeClass('updating').css('opacity', '1');
                $select.prop('disabled', false);
            }
        });
    }
    
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
    
    // Add debounced status change handler
    $(document).on('change', '.status-select', debounce(handleStatusChange, 100));
    
    // Add CSS for new animations
    const statusChangeStyles = `
        @keyframes statusChangePulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        
        @keyframes statusErrorShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-3px); }
            40%, 80% { transform: translateX(3px); }
        }
        
        .status-changing {
            animation: statusChangePulse 0.6s ease-in-out;
        }
        
        .error-animation {
            animation: statusErrorShake 0.6s ease-in-out;
        }
        
        .saving-indicator,
        .success-indicator,
        .error-indicator {
            display: inline-flex;
            align-items: center;
            margin-left: 8px;
            font-size: 12px;
            color: #666;
            vertical-align: middle;
        }
        
        .success-indicator {
            color: #4caf50;
        }
        
        .error-indicator {
            color: #f44336;
        }
        
        .saving-indicator .spinner,
        .success-indicator .dashicons,
        .error-indicator .dashicons {
            margin-right: 4px;
            width: 16px;
            height: 16px;
            font-size: 16px;
            line-height: 1;
        }
        
        .success-indicator .dashicons {
            color: #4caf50;
        }
        
        .error-indicator .dashicons {
            color: #f44336;
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
    `;
    
    // Add styles to the head
    $('<style>').text(statusChangeStyles).appendTo('head');
    
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
        console.log('updateBookingStatus called with:', { bookingId, status });
        
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
        
        // Disable the select during update
        $statusSelect.prop('disabled', true);
        
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
                        'pending': 'pending'
                    };
                    
                    const statusCategory = statusToCategory[status] || 'upcoming';
                    
                    // Update the row's data attributes and classes
                    $row.attr({
                        'data-status': status,
                        'data-status-category': statusCategory
                    });
                    
                    // Update the select to show the new status and re-enable it
                    $statusSelect.val(status)
                               .prop('disabled', false)
                               .attr('data-status', status)
                               .siblings('.status-saving').hide();
                    
                    // Update any status badges in the row
                    $row.find('.status-badge')
                        .removeClass('status-pending status-confirmed status-completed status-cancelled')
                        .addClass('status-badge status-' + status)
                        .text(statusLabel);
                    
                    // If we're filtering, update the display
                    if (currentStatusFilter !== 'all' && currentStatusFilter !== status) {
                        $row.hide('fast');
                    }
                    
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
                // Re-enable the status select
                $statusSelect.prop('disabled', false);
                
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
