/**
 * Elite Cuts - Manage Bookings JavaScript
 * Handles the frontend functionality for managing bookings
 */

jQuery(document).ready(function($) {
    'use strict';

    // Handle edit button click
    $(document).on('click', '.edit-booking', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const bookingId = $button.data('booking-id');
        
        if (!bookingId) {
            alert('Error: Missing booking ID');
            return;
        }

        // Show loading state
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        // Make AJAX request to get booking details
        $.ajax({
            url: eliteManageBookings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elite_cuts_get_booking',
                id: bookingId,
                nonce: eliteManageBookings.nonce
            },
            dataType: 'json',
            success: function(response) {
                console.log('Booking details response:', response); // Debug log
                
                // Check if we have a valid booking object in the response
                const bookingData = response.booking || (response.data && response.data.booking) || response.data;
                
                if (bookingData && (bookingData.id || bookingData.ID)) {
                    console.log('Opening edit modal with booking data:', bookingData);
                    openEditModal(bookingData);
                    return; // Exit early since we successfully opened the modal
                }
                
                // If we got here, something went wrong
                const errorMsg = response.data?.message || response.message || 'Failed to load booking details';
                console.error('Error loading booking:', errorMsg, response);
                alert('Error: ' + errorMsg);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred while loading booking details. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="fas fa-edit"></i> Edit');
            }
        });
    });

    // Handle form submission for updating a booking
    $(document).on('submit', '#elite-edit-booking-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        // Get form data
        const formData = $form.serializeArray();
        formData.push({
            name: 'action',
            value: 'elite_cuts_update_booking'
        }, {
            name: 'nonce',
            value: eliteManageBookings.nonce
        });
        
        // Submit the form via AJAX
        $.ajax({
            url: eliteManageBookings.ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close the modal and refresh the bookings list
                    $('#elite-edit-booking-modal').modal('hide');
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to update booking'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred while updating the booking. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Function to open the edit modal with booking data
    function openEditModal(booking) {
        // Get the new booking form elements
        const $overlay = $('#manager-booking-overlay');
        const $form = $('#manager-booking-form');
        const $steps = $('.ubf-step');
        
        // Update the form title and button text
        $('.booking-title').text('Edit Booking');
        $('.booking-subtitle').text('Update the booking details');
        
        // Populate the form fields with booking data
        $form.find('#booking-id').val(booking.id);
        $form.find('#ubf_customer_name').val(booking.customer_name || '');
        $form.find('#ubf_customer_email').val(booking.customer_email || '');
        $form.find('#ubf_customer_phone').val(booking.customer_phone || '');
        
        // Set the service if available
        if (booking.service_id) {
            const $serviceSelect = $form.find('select[name="service_id"]');
            const $staffSelect = $form.find('select[name="staff_id"]');
            
            // Store the staff ID and name before changing anything
            const staffIdToSet = booking.staff_id || booking.staff_ID; // Check both staff_id and staff_ID
            const staffName = booking.staff_name || `Staff #${staffIdToSet}`;
            
            // First, ensure the staff option exists
            if (staffIdToSet && $staffSelect.find(`option[value="${staffIdToSet}"]`).length === 0) {
                $staffSelect.append(new Option(staffName, staffIdToSet, true, true));
            }
            
            // Set the service value
            $serviceSelect.val(booking.service_id);
            
            // Function to safely set staff selection
            const handleStaffSelection = () => {
                if (!staffIdToSet || !$staffSelect.length) {
                    console.warn('Cannot set staff: missing staff ID or staff select element');
                    return;
                }
                
                try {
                    // Set the staff value and trigger change
                    $staffSelect.val(staffIdToSet).trigger('change');
                    
                    // Try to set custom validity if the element supports it
                    const staffSelectEl = $staffSelect[0];
                    if (staffSelectEl && typeof staffSelectEl.setCustomValidity === 'function') {
                        staffSelectEl.setCustomValidity('');
                    }
                    
                    // If still not set, try again with a delay
                    if ($staffSelect.val() != staffIdToSet) {
                        setTimeout(() => {
                            $staffSelect.val(staffIdToSet).trigger('change');
                            if (staffSelectEl && typeof staffSelectEl.setCustomValidity === 'function') {
                                staffSelectEl.setCustomValidity('');
                            }
                        }, 300);
                    }
                } catch (e) {
                    console.error('Error setting staff selection:', e);
                }
            };
            
            // If we already have the staff in the dropdown, set it immediately
            if (staffIdToSet && $staffSelect.find(`option[value="${staffIdToSet}"]`).length > 0) {
                handleStaffSelection();
            }
            
            // Set up a one-time handler for when the staff list is populated
            const staffChangeHandler = function() {
                if (staffIdToSet) {
                    handleStaffSelection();
                }
                $staffSelect.off('change', staffChangeHandler);
            };
            
            // Attach the handler before triggering the change
            $staffSelect.on('change', staffChangeHandler);
            
            // Then trigger the service change to load staff
            $serviceSelect.trigger('change');
            
            // Fallback in case the above doesn't work
            setTimeout(handleStaffSelection, 500);
            setTimeout(handleStaffSelection, 1000);
            
        } else if (booking.staff_id) {
            // If no service_id but we have staff_id, try to set it directly
            const $staffSelect = $form.find('select[name="staff_id"]');
            if ($staffSelect.length && booking.staff_id) {
                if ($staffSelect.find(`option[value="${booking.staff_id}"]`).length === 0) {
                    const staffName = booking.staff_name || `Staff #${booking.staff_id}`;
                    $staffSelect.append(new Option(staffName, booking.staff_id, true, true));
                }
                $staffSelect.val(booking.staff_id).trigger('change');
                try {
                    if ($staffSelect[0] && typeof $staffSelect[0].setCustomValidity === 'function') {
                        $staffSelect[0].setCustomValidity('');
                    }
                } catch (e) {
                    console.error('Error setting custom validity:', e);
                }
            }
        }
        
        // Set the date and time
        if (booking.preferred_date) {
            $form.find('input[name="preferred_date"]').val(booking.preferred_date);
        }
        if (booking.preferred_time) {
            $form.find('input[name="preferred_time"]').val(booking.preferred_time);
        }
        
        // Set the status
        if (booking.booking_status) {
            $form.find('select[name="booking_status"]').val(booking.booking_status);
        }
        
        // Set any notes
        if (booking.notes) {
            $form.find('textarea[name="notes"]').val(booking.notes);
        }
        
        // Show the overlay and set the first step as active
        $overlay.addClass('active');
        $('body').addClass('no-scroll');
        
        // Activate the first step
        $steps.removeClass('active');
        $steps.first().addClass('active');
        $('.ubf-form-step').removeClass('active');
        $('.ubf-form-step[data-step="1"]').addClass('active');
        $('.ubf-progress-fill').css('width', '25%');
    }

    // Handle delete button click
    $(document).on('click', '.delete-booking', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            return;
        }
        
        const $button = $(this);
        const bookingId = $button.data('booking-id');
        
        if (!bookingId) {
            alert('Error: Missing booking ID');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Make AJAX request to delete booking
        $.ajax({
            url: eliteManageBookings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elite_cuts_delete_booking',
                booking_id: bookingId,
                nonce: eliteManageBookings.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove the booking row from the table
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to delete booking'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the booking. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });
});

// Add some basic styles for the modal
const style = document.createElement('style');
style.textContent = `
    #elite-edit-booking-modal .modal-dialog {
        max-width: 600px;
    }
    #elite-edit-booking-modal .form-group {
        margin-bottom: 1rem;
    }
    #elite-edit-booking-modal label {
        font-weight: 500;
        margin-bottom: 0.25rem;
        display: block;
    }
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .status-confirmed {
        background-color: #d4edda;
        color: #155724;
    }
    .status-completed {
        background-color: #cce5ff;
        color: #004085;
    }
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
`;
document.head.appendChild(style);
