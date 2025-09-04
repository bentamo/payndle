/**
 * Manager Business Panel JavaScript
 * Handles all frontend interactions for the business management panel
 */

jQuery(document).ready(function($) {
    
    console.log('Manager Panel JavaScript loaded');
    console.log('managerPanel object:', managerPanel);
    
    // Initialize the manager panel
    function initManagerPanel() {
        // Handle tab switching
        $('.tab-link').on('click', function(e) {
            e.preventDefault();
            const tab = $(this).attr('href').split('=')[1];
            loadTabContent(tab);
        });

        // Initialize the current tab
        const currentTab = getUrlParameter('tab') || 'bookings';
        loadTabContent(currentTab);

        // Initialize modals
        initModals();
    }

    // Load content for the selected tab
    function loadTabContent(tab) {
        $('.tab-link').removeClass('active');
        $(`.tab-link[href*="tab=${tab}"]`).addClass('active');
        
        // Show loading state
        $('.manager-content').html(`<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>`);

        // Load appropriate content
        if (tab === 'calendar') {
            loadCalendarView();
        } else if (tab === 'staff') {
            loadStaffManagement();
        } else if (tab === 'reports') {
            loadReports();
        } else {
            loadBookingsList();
        }
    }

    // Initialize modals
    function initModals() {
        $(document).on('click', '.close-modal, .modal', function(e) {
            if ($(e.target).hasClass('modal') || $(e.target).hasClass('close-modal')) {
                closeModal();
            }
        });
        $('.modal-content').on('click', function(e) { e.stopPropagation(); });
    }

    // Modal functions
    function openModal(title, content) {
        $('#modal-title').text(title);
        $('#booking-form-container').html(content);
        $('#booking-modal').fadeIn(200);
        $('body').addClass('modal-open');
    }

    function closeModal() {
        $('#booking-modal').fadeOut(200);
        $('body').removeClass('modal-open');
    }

    // Event handlers
    function initEventHandlers() {
        // Booking actions
        $(document).on('click', '#add-new-booking', (e) => { e.preventDefault(); loadBookingForm(); });
        $(document).on('click', '.view-booking', () => viewBookingDetails($(this).data('booking-id')));
        $(document).on('click', '.edit-booking', () => loadBookingForm($(this).data('booking-id')));
        $(document).on('click', '.cancel-booking', () => cancelBooking($(this).data('booking-id')));
        
        // Form submission
        $(document).on('submit', '#booking-form', function(e) {
            e.preventDefault();
            saveBooking($(this));
        });
    }

    // Core functions
    function loadBookingsList() {
        const filters = {
            status: getUrlParameter('status') || '',
            date: getUrlParameter('date') || '',
            staff_id: getUrlParameter('staff') || 0,
            page: getUrlParameter('paged') || 1
        };

        // AJAX call to get bookings
        $.ajax({
            url: managerPanel.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_bookings',
                nonce: managerPanel.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    renderBookingsList(response.data);
                } else {
                    showNotification('error', response.data?.message || 'Failed to load bookings');
                }
            },
            error: () => showNotification('error', 'Error loading bookings')
        });
    }

    function renderBookingsList(data) {
        // Simplified rendering - implement based on your HTML structure
        let html = `
            <div class="bookings-list">
                <!-- Add filters and table structure here -->
                <table class="bookings-table">
                    <thead><tr><th>Booking #</th><th>Customer</th><th>Service</th><th>Date/Time</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
        `;
        
        // Add rows for each booking
        data.bookings.forEach(booking => {
            html += `
                <tr data-booking-id="${booking.id}">
                    <td>#${booking.booking_number}</td>
                    <td>${escapeHtml(booking.customer_name)}</td>
                    <td>${escapeHtml(booking.service_name)}</td>
                    <td>${formatDateTime(booking.start_time)}</td>
                    <td><span class="status-badge status-${booking.status}">${booking.status}</span></td>
                    <td class="actions">
                        <button class="button view-booking" data-booking-id="${booking.id}"><i class="fas fa-eye"></i></button>
                        <button class="button edit-booking" data-booking-id="${booking.id}"><i class="fas fa-edit"></i></button>
                        <button class="button cancel-booking" data-booking-id="${booking.id}"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        $('.manager-content').html(html);
    }

    // Utility functions
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    function escapeHtml(unsafe) {
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDateTime(dateTimeString) {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit', 
            minute: '2-digit' 
        };
        return new Date(dateTimeString).toLocaleDateString(undefined, options);
    }

    function showNotification(type, message) {
        // Implement notification system
        console.log(`${type}: ${message}`);
    }

    // Initialize everything
    initEventHandlers();
    initManagerPanel();
});
