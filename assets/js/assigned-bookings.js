jQuery(document).ready(function($) {
    // Localized script data
    const bookingData = window.assignedBookingsData || {};
    
    // Toggle action dropdown
    $(document).on('click', '.action-dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $dropdown = $(this).closest('.action-dropdown');
        const isVisible = $dropdown.attr('data-visible') === 'true';
        
        // Close all other dropdowns
        $('.action-dropdown').attr('data-visible', 'false');
        
        // Toggle current dropdown
        if (!isVisible) {
            $dropdown.attr('data-visible', 'true');
        }
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.action-dropdown').length) {
            $('.action-dropdown').attr('data-visible', 'false');
        }
    });
    
    // Handle action item clicks
    $(document).on('click', '.action-item', function(e) {
        e.preventDefault();
        const $item = $(this);
        const action = $item.hasClass('view-details') ? 'view' : 
                      $item.hasClass('complete-booking') ? 'complete' : 'cancel';
        const bookingId = $item.data('id');
        
        // Close dropdown
        $item.closest('.action-dropdown').attr('data-visible', 'false');
        
        // Handle the action
        if (action === 'view') {
            showBookingDetails(bookingId);
        } else if (action === 'complete') {
            if (confirm('Are you sure you want to mark this booking as completed?')) {
                updateBookingStatus(bookingId, 'completed');
            }
        } else if (action === 'cancel') {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
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
    function updateBookingStatus(bookingId, status) {
        const $row = $(`[data-action-id="${bookingId}"]`).closest('tr');
        const $statusCell = $row.find('.status-badge');
        const $actionsCell = $row.find('.actions');
        const $actionButton = $(`[data-id="${bookingId}"]`);
        
        // Store original state for potential rollback
        const originalHtml = $row.html();
        const originalStatus = $statusCell.text().trim();
        const originalClass = $statusCell.attr('class');
        
        // Show loading state
        $actionButton.prop('disabled', true).html('<span class="spinner is-active"></span>');
        $statusCell.html('<span class="spinner is-active"></span>');
        
        // Make AJAX request
        $.ajax({
            url: bookingData.ajax_url || ajaxurl,
            type: 'POST',
            data: {
                action: 'update_booking_status',
                booking_id: bookingId,
                status: status,
                nonce: bookingData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.booking) {
                    const booking = response.data.booking;
                    
                    // Update status badge
                    $statusCell.removeClass()
                        .addClass('status-badge')
                        .addClass('status-' + booking.status_slug)
                        .text(booking.status);
                    
                    // Update payment status if needed
                    if (booking.payment_status) {
                        $row.find('.payment-status')
                            .removeClass('payment-paid payment-pending')
                            .addClass('payment-' + booking.payment_status.toLowerCase())
                            .text(booking.payment_status);
                    }
                    
                    // Show success message
                    showNotice('success', `Booking #${bookingId} has been ${status} successfully.`);
                    
                    // If status is completed or cancelled, remove action buttons
                    if (status === 'completed' || status === 'cancelled') {
                        $row.find('.action-dropdown').remove();
                        $actionsCell.html('<em>No actions available</em>');
                    }
                } else {
                    // Revert UI on error
                    $statusCell.text(originalStatus).attr('class', originalClass);
                    const errorMsg = response.data && response.data.message ? 
                        response.data.message : 'An error occurred. Please try again.';
                    showNotice('error', errorMsg);
                }
            },
            error: function(xhr, status, error) {
                // Revert UI on error
                $statusCell.text(originalStatus).attr('class', originalClass);
                showNotice('error', 'Failed to update booking status. Please try again.');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                $actionButton.prop('disabled', false);
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
