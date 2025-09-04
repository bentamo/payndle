jQuery(document).ready(function($) {
    'use strict';

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle status updates
    $('.booking-status').on('change', function() {
        const $select = $(this);
        const bookingId = $select.data('booking-id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        
        // Show loading state
        const originalText = $select.find('option:selected').text();
        $select.prop('disabled', true).addClass('updating');
        
        // Send AJAX request
        $.ajax({
            url: booking_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'update_booking_status',
                booking_id: bookingId,
                status: newStatus,
                nonce: booking_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI on success
                    $select.removeClass('updating').prop('disabled', false);
                    $select.find('option:selected').text(response.data.status_label);
                    
                    // Add visual feedback
                    $row.addClass('status-updated');
                    setTimeout(function() {
                        $row.removeClass('status-updated');
                    }, 2000);
                    
                    // Show success message
                    showNotification('Status updated successfully', 'success');
                } else {
                    handleAjaxError(response);
                }
            },
            error: function(xhr, status, error) {
                handleAjaxError(xhr.responseJSON || { data: 'An error occurred. Please try again.' });
            }
        });
    });

    // Handle delete booking
    $('.delete-booking').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const bookingId = $button.data('booking-id');
        
        if (confirm(booking_vars.confirm_delete)) {
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: booking_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_booking',
                    booking_id: bookingId,
                    nonce: booking_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            // Check if table is empty
                            if ($('.bookings-table tbody tr').length === 0) {
                                $('.bookings-table tbody').html(
                                    '<tr><td colspan="8" class="no-bookings">No bookings found.</td></tr>'
                                );
                            }
                        });
                        showNotification('Booking deleted successfully', 'success');
                    } else {
                        handleAjaxError(response);
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr.responseJSON || { data: 'Failed to delete booking' });
                },
                complete: function() {
                    $button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        }
    });

    // View booking details
    $('.view-booking').on('click', function() {
        const bookingId = $(this).data('booking-id');
        const $modal = $('#booking-details-modal');
        const $content = $('#booking-details-content');
        
        // Show loading state
        $content.html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading booking details...</div>');
        $modal.show();
        
        // In a real implementation, you would fetch the booking details via AJAX
        // For now, we'll just show a placeholder
        setTimeout(function() {
            $content.html(`
                <h2>Booking #${bookingId} Details</h2>
                <div class="booking-details">
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value">John Doe</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time:</span>
                        <span class="detail-value">${new Date().toLocaleString()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge confirmed">Confirmed</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Services:</span>
                        <ul class="service-list">
                            <li>Haircut - $30.00</li>
                            <li>Beard Trim - $15.00</li>
                        </ul>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total:</span>
                        <span class="detail-value">$45.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Notes:</span>
                        <p class="notes">Customer prefers a skin fade on the sides.</p>
                    </div>
                </div>
            `);
        }, 500);
    });

    // Close modal
    $('.close-modal, .modal').on('click', function(e) {
        if ($(e.target).is('.modal, .close-modal')) {
            $('#booking-details-modal').hide();
        }
    });

    // Reset filters
    $('#reset-filters').on('click', function() {
        window.location.href = window.location.pathname;
    });

    // Keyboard navigation for modals
    $(document).keyup(function(e) {
        // ESC key
        if (e.keyCode === 27) {
            $('#booking-details-modal').hide();
        }
    });

    // Show notification message
    function showNotification(message, type = 'success') {
        const $notification = $(`
            <div class="booking-notification ${type}">
                ${message}
                <button type="button" class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Handle AJAX errors
    function handleAjaxError(response) {
        const message = response.data && response.data.message 
            ? response.data.message 
            : 'An error occurred. Please try again.';
        showNotification(message, 'error');
    }
    
    // Close notification button
    $(document).on('click', '.close-notification', function() {
        $(this).closest('.booking-notification').fadeOut(200, function() {
            $(this).remove();
        });
    });
});
