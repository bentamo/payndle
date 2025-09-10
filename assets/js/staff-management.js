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

            const row = `
                <tr data-id="${member.id}">
                    <td class="staff-name">${member.first_name} ${member.last_name}</td>
                    <td class="staff-role">${member.role}</td>
                    <td class="staff-email">${member.email}</td>
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
            $('#modal-title').text('Edit Staff Member');
            $form.find('button[type="submit"]').text('Update Staff');

            // Fetch single staff
            $.ajax({
                url: staffManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'manage_staff',
                    nonce: staffManager.nonce,
                    action_type: 'get_single_staff',
                    staff_id: staffId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const staff = response.data;
                        $('#staff-id').val(staff.id);
                        $('#first-name').val(staff.first_name);
                        $('#last-name').val(staff.last_name);
                        $('#email').val(staff.email);
                        $('#phone').val(staff.phone);
                        $('#role').val(staff.role);
                        $('#status').val(staff.status);
                        $('#bio').val(staff.bio);
                    } else {
                        showPopup('error', response.message || 'Failed to load staff details');
                    }
                },
                error: function(xhr, status, error) {
                    showPopup('error', 'Error: ' + error);
                }
            });
        } else {
            $('#modal-title').text('Add New Staff');
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

        const formData = {
            first_name: $('#first-name').val().trim(),
            last_name: $('#last-name').val().trim(),
            email: $('#email').val().trim(),
            phone: $('#phone').val().trim(),
            role: $('#role').val(),
            status: $('#status').val(),
            bio: $('#bio').val()
        };

        if (!formData.first_name || !formData.last_name || !formData.email) {
            showPopup('error', 'Please fill in all required fields');
            return;
        }

        const actionType = isEdit ? 'update_staff' : 'add_staff';

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: {
                action: 'manage_staff',
                nonce: staffManager.nonce,
                action_type: actionType,
                staff_id: staffId,
                data: formData
            },
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message || (isEdit ? 'Staff updated' : 'Staff added'));
                    closeStaffModal();
                    loadStaffList();
                } else {
                    showPopup('error', response.message || 'Operation failed');
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Error: ' + error);
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
