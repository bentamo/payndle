/**
 * Staff Management JavaScript
 * Handles the front-end functionality for the staff management interface
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    let currentPage = 1;
    const perPage = 10;
    let totalPages = 1;
    let totalItems = 0;

    // Initialize
    initStaffManagement();

    function initStaffManagement() {
        loadStaffList();
        bindEventListeners();
    }

    // Load staff list
    function loadStaffList() {
        const search = $('#staff-search').val();
        const role = $('#filter-role').val();
        const status = $('#filter-status').val();

        showLoading(true);

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: {
                action: 'manage_staff',
                nonce: staffManager.nonce,
                action_type: 'get_staff',
                data: {
                    paged: currentPage,
                    per_page: perPage,
                    search: search,
                    role: role,
                    status: status
                }
            },
            success: function(response) {
                if (response.success) {
                    renderStaffList(response.data.staff);
                    updatePagination(response.data.pagination);
                } else {
                    renderEmptyTable();
                    showPopup('error', response.message || 'Failed to load staff list');
                }
            },
            error: function(xhr, status, error) {
                renderEmptyTable();
                showPopup('error', 'Error: ' + error);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    // Render staff table
    function renderStaffList(staff) {
        const $staffList = $('#staff-list');
        $staffList.empty();

        if (!staff || staff.length === 0) {
            renderEmptyTable();
            return;
        }

        staff.forEach(function(member) {
            const statusClass = member.status === 'active' ? 'status-active' : 'status-inactive';
            const statusText = member.status === 'active' ? 'Active' : 'Inactive';
            const serviceTitle = (member.services && member.services.length) ? member.services[0].title : '';

            const row = `
                <tr data-id="${member.id}">
                    <td class="staff-name">${member.name || ''}</td>
                    <td class="staff-role">${serviceTitle}</td>
                    <td class="staff-email">${member.email || ''}</td>
                    <td class="staff-phone">${member.phone || 'N/A'}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td class="staff-actions">
                        <button class="button button-small edit-staff" data-id="${member.id}" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="button button-small delete-staff" data-id="${member.id}" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;
            $staffList.append(row);
        });
    }

    function renderEmptyTable() {
        $('#staff-list').html(
            '<tr><td colspan="6" class="no-staff">No staff members found</td></tr>'
        );
    }

    // Pagination controls
    function updatePagination(pagination) {
        totalPages = pagination.total_pages;
        totalItems = pagination.total_items;
        currentPage = pagination.current_page;

        $('.displaying-num').text(totalItems + ' items');
        $('.total-pages').text(totalPages);
        $('.tablenav-pages .current-page').val(currentPage);

        $('.first-page, .prev-page').toggleClass('disabled', currentPage <= 1);
        $('.next-page, .last-page').toggleClass('disabled', currentPage >= totalPages);
    }

    // Loading state
    function showLoading(show) {
        if (show) {
            $('#staff-list').html(
                '<tr><td colspan="6" class="loading">' +
                '<span class="spinner is-active"></span> Loading staff...' +
                '</td></tr>'
            );
        }
    }

    // Unified popup (success/error)
    function showPopup(type, message) {
        const css = type === 'success' ? 'popup-success' : 'popup-error';
        const $popup = $(`
            <div class="staff-popup ${css}">
                <span>${message}</span>
            </div>
        `);
        $('body').append($popup);
        setTimeout(() => $popup.fadeOut(300, () => $popup.remove()), 2500);
    }

    // Open modal
    function openStaffModal(staffId = null) {
        const $modal = $('#staff-modal');
        const $form = $('#staff-form');
        $form[0].reset();

        if (staffId) {
            $('#staff-modal-title').text('Edit Staff');
            $form.find('button[type="submit"]').text('Update Staff');

            // Fetch single staff using the existing get_staff action with id filter
            $.ajax({
                url: staffManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'manage_staff',
                    nonce: staffManager.nonce,
                    action_type: 'get_staff',
                    data: { id: staffId }
                },
                success: function(response) {
                    if (response.success && response.data && response.data.staff && response.data.staff.length) {
                        const staff = response.data.staff[0];
                        $('#staff-id').val(staff.id);
                        $('#staff-name').val(staff.name || '');
                        $('#staff-email').val(staff.email || '');
                        $('#staff-phone').val(staff.phone || '');
                        $('#staff-status').val(staff.status || 'active');
                        if (staff.services && staff.services.length) {
                            $('#staff-service').val(staff.services[0].id);
                        }
                        
                        // avatar preview
                        if (staff.avatar) {
                            if ($('#staff-avatar-preview').length) {
                                $('#staff-avatar-preview').attr('src', staff.avatar).show();
                                $('#staff-avatar-placeholder').hide();
                                $('#staff-avatar-clear').show();
                            }
                            $('#staff-avatar').val(staff.avatar);
                        } else {
                            $('#staff-avatar-preview').hide();
                            $('#staff-avatar-placeholder').show();
                            $('#staff-avatar-clear').hide();
                            $('#staff-avatar').val('');
                        }
                        if (staff.avatar_id) {
                            $('#staff-avatar-id').val(staff.avatar_id);
                        } else {
                            $('#staff-avatar-id').val('');
                        }
                    } else {
                        showPopup('error', response.message || 'Failed to load staff details');
                    }
                },
                error: function(xhr, status, error) {
                    showPopup('error', 'Error: ' + error);
                }
            });
        } else {
            $('#staff-modal-title').text('Add New Staff');
            $form.find('button[type="submit"]').text('Add Staff');
            $('#staff-id').val('');
        }

        $modal.show();
    }

    function closeStaffModal() {
        $('#staff-modal').hide();
    }

    // Save (Add/Update)
    function handleStaffFormSubmit(e) {
        e.preventDefault();

        const staffId = $('#staff-id').val();
        const isEdit = !!staffId;

    console.log('handleStaffFormSubmit invoked', { staffId: staffId });

        var $form = $('#staff-form');
        var $submitBtn = $form.find('button[type="submit"]');
        if ($form.data('submitting')) {
            console.log('Form already submitting; ignoring duplicate submit');
            return;
        }
        $form.data('submitting', true);
        $submitBtn.prop('disabled', true);

        // Read fields from the shared template (single full name, service select)
        const nameVal = $('#staff-name').length ? $('#staff-name').val().trim() : '';
        const emailVal = $('#staff-email').length ? $('#staff-email').val().trim() : '';
        const phoneVal = $('#staff-phone').length ? $('#staff-phone').val().trim() : '';
        const statusVal = $('#staff-status').length ? $('#staff-status').val() : 'active';
        const serviceVal = $('#staff-service').length ? $('#staff-service').val() : '';

        const formData = {
            name: nameVal,
            email: emailVal,
            phone: phoneVal,
            status: statusVal,
            services: serviceVal ? [serviceVal] : []
        };

            // include avatar fields from the shared template
            if ($('#staff-avatar').length) formData.avatar = $('#staff-avatar').val();
            if ($('#staff-avatar-id').length) formData.avatar_id = $('#staff-avatar-id').val();

        if (!formData.name || !formData.email) {
            showPopup('error', 'Please fill in all required fields');
            // re-enable submit on validation failure
            $form.data('submitting', false);
            $submitBtn.prop('disabled', false);
            return;
        }

        const actionType = isEdit ? 'update_staff' : 'add_staff';

        // Debug: print payload before sending
        var payload = {
            action: 'manage_staff',
            nonce: (typeof staffManager !== 'undefined' ? staffManager.nonce : ''),
            action_type: actionType,
            staff_id: staffId,
            data: JSON.stringify(formData)  // Convert to JSON string as PHP expects
        };
        console.log('Sending AJAX payload', payload);

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: payload,
            success: function(response) {
                console.log('AJAX success response', response);
                if (response && response.success) {
                    showPopup('success', response.message || (isEdit ? 'Staff updated' : 'Staff added'));
                    closeStaffModal();
                    loadStaffList();
                } else {
                    showPopup('error', response && response.message ? response.message : 'Operation failed');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error', { xhr: xhr, status: status, error: error });
                showPopup('error', 'Error: ' + error);
            }
            ,
            complete: function() {
                // re-enable submit regardless of outcome
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false);
            }
        });
    }

    // Delete
    function deleteStaff(staffId) {
        if (!confirm(staffManager.confirm_delete)) return;

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: {
                action: 'manage_staff',
                nonce: staffManager.nonce,
                action_type: 'delete_staff',
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message || 'Staff deleted');
                    loadStaffList();
                } else {
                    showPopup('error', response.message || 'Failed to delete staff');
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Error: ' + error);
            }
        });
    }

    // Events
    function bindEventListeners() {
        $('#add-staff-btn').on('click', function() { openStaffModal(); });
        $(document).on('click', '.edit-staff', function() { openStaffModal($(this).data('id')); });
        $(document).on('click', '.delete-staff', function() { deleteStaff($(this).data('id')); });
        $('.close-modal, .staff-modal .button-secondary').on('click', closeStaffModal);
        $('#staff-form').on('submit', handleStaffFormSubmit);

        $('#apply-filters').on('click', function() { currentPage = 1; loadStaffList(); });
        $('#reset-filters').on('click', function() {
            $('#staff-search, #filter-role, #filter-status').val('');
            currentPage = 1; loadStaffList();
        });
        $('#staff-search').on('keyup', function(e) { if (e.key === 'Enter') { currentPage = 1; loadStaffList(); } });

        $('.first-page').on('click', function(e) { e.preventDefault(); if (currentPage > 1) { currentPage = 1; loadStaffList(); } });
        $('.prev-page').on('click', function(e) { e.preventDefault(); if (currentPage > 1) { currentPage--; loadStaffList(); } });
        $('.next-page').on('click', function(e) { e.preventDefault(); if (currentPage < totalPages) { currentPage++; loadStaffList(); } });
        $('.last-page').on('click', function(e) { e.preventDefault(); if (currentPage < totalPages) { currentPage = totalPages; loadStaffList(); } });
    }
});
