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
    let lastFocusedBeforeModal = null;
    let trapHandlerBound = false;

    // Initialize
    initStaffManagement();

    function initStaffManagement() {
        loadStaffList();
        bindEventListeners();
        initializeAvatarUpload();
        initializeCheckboxServiceList();
    }

    // Small helper to escape HTML in dynamic strings
    function escapeHtml(str) {
        if (str === null || typeof str === 'undefined') return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Initialize the checkbox-based service list: search filter, select-all, and helpers
    function initializeCheckboxServiceList() {
        // Filter services by text
        $(document).on('input', '#staff-service-search', function() {
            const q = $(this).val().toLowerCase().trim();
            $('#staff-service-list label').each(function() {
                const txt = $(this).text().toLowerCase();
                $(this).toggle(txt.indexOf(q) !== -1);
            });
        });

        // Select-all checkbox
        $(document).on('change', '#staff-service-selectall', function() {
            const checked = $(this).is(':checked');
            $('#staff-service-list input.service-checkbox:visible').prop('checked', checked);
        });

        // If user manually toggles checkboxes, update select-all state
        $(document).on('change', '#staff-service-list input.service-checkbox', function() {
            const total = $('#staff-service-list input.service-checkbox:visible').length;
            const checked = $('#staff-service-list input.service-checkbox:visible:checked').length;
            $('#staff-service-selectall').prop('checked', total > 0 && checked === total);
        });

        // Clear search and selections when modal opens
        $(document).on('click', '#add-staff-btn', function() {
            $('#staff-service-search').val('');
            $('#staff-service-list label').show();
            $('#staff-service-list input.service-checkbox').prop('checked', false);
            $('#staff-service-selectall').prop('checked', false);
        });

        // Also clear when opening modal for edit; edit flow will re-check relevant boxes when data loads
        $(document).on('click', '.edit-staff', function() {
            $('#staff-service-search').val('');
            $('#staff-service-list label').show();
            $('#staff-service-selectall').prop('checked', false);
        });
    }

    // New: manage dropdown + add-button + tags UX for services
    // Adds click handler, tag rendering, and helpers to read/write selected services
    (function initDropdownTags(){
        // Add button handler
        $(document).on('click', '#staff-service-add', function(){
            const $sel = $('#staff-service-dropdown');
            const id = $sel.val();
            const text = $sel.find('option:selected').text();
            if (!id) return;
            // Prevent duplicates
            if ($('#staff-service-tags').find(`input[type=hidden][value="${id}"]`).length) return;
            // Create tag element
            const $tag = $(`<span class="service-tag" data-id="${id}" style="display:inline-flex; align-items:center; gap:8px; padding:6px 8px; border-radius:20px; background:#eef6ff; border:1px solid #cfe6ff;"><span class="tag-label">${text}</span><button type="button" class="tag-remove button" aria-label="Remove service" style="background:transparent; border:none; padding:0; margin:0; font-size:14px; line-height:1;">✕</button></span>`);
            // Hidden input
            const $hidden = $(`<input type="hidden" name="services[]" value="${id}" />`);
            $tag.append($hidden);
            $('#staff-service-tags').append($tag);
        });

        // Remove tag handler
        $(document).on('click', '#staff-service-tags .tag-remove', function(){
            $(this).closest('.service-tag').remove();
        });

        // When opening modal for add, clear existing tags
        $(document).on('click', '#add-staff-btn', function(){
            $('#staff-service-tags').empty();
            $('#staff-service-dropdown').val('');
        });

        // When editing, tags are set in openStaffModal success handler (after AJAX loads services)
        // Ensure handleStaffFormSubmit collects services[] from hidden inputs already appended to form
    })();

    // Load staff list
    function loadStaffList() {
        const search = $('#staff-search').val();
    const role = $('#filter-role').val();
        const status = $('#filter-status').val();
        const availability = $('#filter-availability').val ? $('#filter-availability').val() : '';

        showLoading(true);

    $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: {
        action: (staffManager.ajax_action || 'manage_staff'),
                nonce: staffManager.nonce,
                action_type: 'get_staff',
                data: JSON.stringify({
                    paged: currentPage,
                    per_page: perPage,
                    search: search,
                    role: role,
                    // If role is a numeric ID, treat it as a service filter for backend
                    service_id: (/^\d+$/.test(role) ? parseInt(role, 10) : ''),
                    status: status,
                    availability: availability
                })
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
        // rendering staff list aligned with 6-column header (Staff, Role, Contact, Availability, Status, Actions)
        const $staffList = $('#staff-list');
        $staffList.empty();

            if (!staff || staff.length === 0) {
                // no staff found
            renderEmptyTable();
            return;
        }

        staff.forEach(function(member) {
            const statusClass = member.status === 'active' ? 'status-active' : 'status-inactive';
            const statusText = member.status === 'active' ? 'Active' : 'Inactive';

            // Render all assigned services/roles as compact badges instead of only the first
            let roleHtml = '';
            if (member.services && member.services.length) {
                roleHtml = member.services.map(function(s){
                    const title = escapeHtml(s.title || s || '');
                    return '<span class="role-badge" style="display:inline-block;padding:4px 8px;border-radius:12px;background:#eef6ff;color:#0b2540;margin-right:6px;font-size:12px;">' + title + '</span>';
                }).join('');
            } else {
                roleHtml = escapeHtml(member.role || '');
            }

            // avatar inside name cell
            let avatarHtml = '';
            if (member.avatar && member.avatar.trim() !== '') {
                avatarHtml = `<img src="${member.avatar}" alt="${member.name}" class="avatar" />`;
            } else {
                const initials = member.name ? member.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0,2) : '??';
                avatarHtml = `<div class="avatar-initial">${initials}</div>`;
            }

            // contact cell (email + phone)
            const contactEmail = member.email ? `${member.email}` : '';
            const contactPhone = member.phone ? `${member.phone}` : '';
            const contactHtml = `<div class="contact-cell">${contactEmail ? `<div class="contact-email">${contactEmail}</div>` : ''}${contactPhone ? `<div class="contact-phone">${contactPhone}</div>` : ''}</div>`;

            // availability cell (badge, default Available)
            const availability = member.availability || 'Available';
            const availabilityHtml = `<span class="availability-badge avail-${availability.replace(/\s+/g,'')}">${availability}</span>`;

            const row = `
                <tr data-id="${member.id}">
                    <td class="staff-name"><div class="staff-cell">${avatarHtml}<div class="staff-meta"><div class="staff-name-text">${member.name || ''}</div></div></div></td>
                    <td class="staff-role">${roleHtml}</td>
                    <td class="staff-contact">${contactHtml || '—'}</td>
                    <td class="staff-availability">${availabilityHtml}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td class="staff-actions">
                        <button class="button button-small edit-staff" data-id="${member.id}" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="button button-small schedule-staff" data-id="${member.id}" title="Assign Schedule">
                            <span class="dashicons dashicons-calendar"></span>
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
        currentPage = pagination.current_page;

        var total = (typeof pagination.total !== 'undefined') ? pagination.total : 0;
        $('.displaying-num').text(total + ' items');
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
    // Clear Select2 selection if present
    try { $('#staff-service').val(null).trigger('change'); } catch(e) {}

        if (staffId) {
            $('#staff-modal-title').text('Edit Staff');
            $form.find('button[type="submit"]').text('Update Staff');

            // Fetch single staff using the existing get_staff action with id filter
            $.ajax({
                url: staffManager.ajax_url,
                type: 'POST',
                data: {
                    action: (staffManager.ajax_action || 'manage_staff'),
                    nonce: staffManager.nonce,
                    action_type: 'get_staff',
                    data: JSON.stringify({ id: staffId })
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
                            // mark all assigned services as checked
                            const svcIds = staff.services.map(s => String(s.id || s));
                            $('#staff-service-list input.service-checkbox').each(function(){
                                const val = String($(this).val());
                                $(this).prop('checked', svcIds.indexOf(val) !== -1);
                            });
                            // Update select-all state for visible items
                            const total = $('#staff-service-list input.service-checkbox:visible').length;
                            const checked = $('#staff-service-list input.service-checkbox:visible:checked').length;
                            $('#staff-service-selectall').prop('checked', total > 0 && checked === total);
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

        $modal.css('display', 'flex');
        $('body').css('overflow', 'hidden'); // Prevent background scrolling

        // Accessibility: set focus trap inside modal and focus first input
        lastFocusedBeforeModal = document.activeElement;
        const $focusables = $modal.find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible:enabled');
        if ($focusables.length) { $focusables.first().focus(); }
        enableFocusTrap($modal);
    }

    function closeStaffModal() {
        $('#staff-modal').css('display', 'none');
        $('body').css('overflow', ''); // Restore scrolling
        disableFocusTrap();
        if (lastFocusedBeforeModal && typeof lastFocusedBeforeModal.focus === 'function') {
            try { lastFocusedBeforeModal.focus(); } catch(e) {}
        }
    }

    // Save (Add/Update)
    function handleStaffFormSubmit(e) {
        e.preventDefault();

    const staffId = $('#staff-id').val();
        const isEdit = !!staffId;

    // handle staff form submit

        var $form = $('#staff-form');
        var $submitBtn = $form.find('button[type="submit"]');
        if ($form.data('submitting')) {
            return;
        }
        $form.data('submitting', true);
        $submitBtn.prop('disabled', true);

        // Read fields from the shared template (single full name, service select)
        const nameVal = $('#staff-name').length ? $('#staff-name').val().trim() : '';
        const emailVal = $('#staff-email').length ? $('#staff-email').val().trim() : '';
        const phoneVal = $('#staff-phone').length ? $('#staff-phone').val().trim() : '';
        const statusVal = $('#staff-status').length ? $('#staff-status').val() : 'active';
        // Gather multiple selected service IDs from several sources:
        // 1) hidden inputs created by tag UI (#staff-service-tags)
        // 2) checked checkboxes in #staff-service-list (if checkbox list is used)
        // 3) fallback to the dropdown #staff-service-dropdown (if user selected but didn't click Add)
        let serviceVal = [];
        $('#staff-service-tags input[type="hidden"]').each(function(){ serviceVal.push(String($(this).val())); });
        $('#staff-service-list input.service-checkbox:checked').each(function(){ serviceVal.push(String($(this).val())); });
        const dropdownVal = ($('#staff-service-dropdown').length) ? String($('#staff-service-dropdown').val()) : '';
        if (dropdownVal && serviceVal.indexOf(dropdownVal) === -1) {
            serviceVal.push(dropdownVal);
        }
        // Normalize: remove empty values and duplicates
        serviceVal = serviceVal.filter(function(v){ return v !== null && typeof v !== 'undefined' && String(v).trim() !== ''; });
        serviceVal = Array.from(new Set(serviceVal));

        // Form validation
        const errors = [];
        
        if (!nameVal) {
            errors.push('Staff name is required.');
        }

        // Require at least one service to be assigned
        if (!serviceVal || (Array.isArray(serviceVal) && serviceVal.length === 0)) {
            errors.push('Please assign at least one service/role to the staff member.');
        }

        // Require at least one contact method (email or phone)
        if (!emailVal && !phoneVal) {
            errors.push('Please provide at least one contact method: email or phone.');
        }
        
        if (emailVal && !isValidEmail(emailVal)) {
            errors.push('Please enter a valid email address.');
        }
        
        // Phone validation relaxed per requirements
        
        if (errors.length > 0) {
            showPopup('error', errors.join(' '));
            $form.data('submitting', false);
            $submitBtn.prop('disabled', false);
            return;
        }

        const formData = {
            name: nameVal,
            email: emailVal,
            phone: phoneVal,
            status: statusVal,
            services: serviceVal || []
        };

        if (isEdit) { formData.id = staffId; }

        // Include avatar fields from the shared template
        if ($('#staff-avatar').length) formData.avatar = $('#staff-avatar').val();
        if ($('#staff-avatar-id').length) formData.avatar_id = $('#staff-avatar-id').val();

        const actionType = isEdit ? 'update_staff' : 'add_staff';

        var payload = {
            action: (staffManager.ajax_action || 'manage_staff'),
            nonce: (typeof staffManager !== 'undefined' ? staffManager.nonce : ''),
            action_type: actionType,
            staff_id: staffId,
            data: JSON.stringify(formData)  // Convert to JSON string as PHP expects
        };
        

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: payload,
            success: function(response) {
                if (response && response.success) {
                    showPopup('success', response.message || (isEdit ? 'Staff updated' : 'Staff added'));
                    closeStaffModal();
                    loadStaffList();
                } else {
                    showPopup('error', response && response.message ? response.message : 'Operation failed');
                }
            },
            error: function(xhr, status, error) {
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
        // Use confirmation modal if present; otherwise fallback to confirm
        const modal = document.getElementById('confirm-modal');
        const msg = document.getElementById('confirm-message');
        const confirmBtn = document.getElementById('confirm-action');
        const cancelBtn = document.getElementById('confirm-cancel');
        const closeBtn = document.querySelector('#confirm-modal .close');

        const performDelete = function() {
            $.ajax({
                url: staffManager.ajax_url,
                type: 'POST',
                data: {
                    action: (staffManager.ajax_action || 'manage_staff'),
                    nonce: staffManager.nonce,
                    action_type: 'delete_staff',
                    data: JSON.stringify({ id: staffId })
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
        };

        if (!modal || !confirmBtn || !cancelBtn) {
            if (!confirm(staffManager.confirm_delete)) return;
            return performDelete();
        }

        if (msg) msg.textContent = staffManager.confirm_delete || 'Are you sure you want to delete this staff member?';

        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const cleanup = function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            confirmBtn.removeEventListener('click', onConfirm);
            cancelBtn.removeEventListener('click', onCancel);
            if (closeBtn) closeBtn.removeEventListener('click', onCancel);
        };
        const onCancel = function() { cleanup(); };
        const onConfirm = function() { cleanup(); performDelete(); };

        confirmBtn.addEventListener('click', onConfirm);
        cancelBtn.addEventListener('click', onCancel);
        if (closeBtn) closeBtn.addEventListener('click', onCancel);
    }

    // Avatar Upload System
    function initializeAvatarUpload() {
        // Check if we're in admin context with wp.media available
        const hasWpMedia = typeof wp !== 'undefined' && wp.media;
        
        // Upload button click handler
        $(document).on('click', '#staff-avatar-upload', function(e) {
            e.preventDefault();
            
            if (hasWpMedia) {
                // Use WordPress media library
                const frame = wp.media({
                    title: 'Select Profile Photo',
                    multiple: false,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    if (attachment) {
                        updateAvatarPreview(attachment.url, attachment.id);
                        showPopup('success', 'Image selected successfully!');
                    }
                });
                
                frame.open();
            } else {
                // Fallback to file input for frontend
                $('#staff-avatar-file').trigger('click');
            }
        });
        
        // File input change handler for frontend uploads
        $(document).on('change', '#staff-avatar-file', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                showPopup('error', 'Please select a valid image file.');
                return;
            }
            
            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                showPopup('error', 'File size must be less than 5MB.');
                return;
            }
            
            uploadAvatarFile(file);
        });
        
        // Clear avatar button
        $(document).on('click', '#staff-avatar-clear', function(e) {
            e.preventDefault();
            updateAvatarPreview('', '');
            showPopup('success', 'Avatar cleared successfully!');
        });
    }
    
    // Upload file via REST API
    function uploadAvatarFile(file) {
        const $uploadBtn = $('#staff-avatar-upload');
        const originalText = $uploadBtn.text();
        
        // Show loading state
        $uploadBtn.html('<span class="spinner"></span> Uploading...').prop('disabled', true);
        
        const formData = new FormData();
        formData.append('file', file);
        
        $.ajax({
            url: staffManager.rest_url + 'payndle/v1/upload-avatar',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-WP-Nonce': staffManager.rest_nonce
            },
            success: function(response) {
                updateAvatarPreview(response.url, response.id);
                showPopup('success', 'Image uploaded successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Upload failed. Please try again.';
                
                // Try to get more specific error message
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 413) {
                    errorMessage = 'File too large. Please choose a smaller image.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied. Please check your login status.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                }
                
                showPopup('error', errorMessage);
            },
            complete: function() {
                // Restore button state
                $uploadBtn.text(originalText).prop('disabled', false);
                $('#staff-avatar-file').val(''); // Clear file input
            }
        });
    }
    
    // Update avatar preview
    function updateAvatarPreview(url, id) {
        $('#staff-avatar').val(url);
        $('#staff-avatar-id').val(id);
        
        if (url) {
            $('#staff-avatar-preview').attr('src', url).show();
            $('#staff-avatar-placeholder').hide();
            $('#staff-avatar-clear').show();
        } else {
            $('#staff-avatar-preview').hide();
            $('#staff-avatar-placeholder').show();
            $('#staff-avatar-clear').hide();
        }
    }

    // Events
    function bindEventListeners() {
        $('#add-staff-btn').on('click', function() { openStaffModal(); });
        $(document).on('click', '.edit-staff', function() { openStaffModal($(this).data('id')); });
        $(document).on('click', '.delete-staff', function() { deleteStaff($(this).data('id')); });
    $(document).on('click', '.schedule-staff', function() { openScheduleModal($(this).data('id')); });
        $('.elite-close, .staff-close, .staff-cancel').on('click', closeStaffModal);
        $('#staff-form').on('submit', handleStaffFormSubmit);

        // Close modal when clicking on backdrop
        $(document).on('click', '.elite-modal', function(e) {
            if (e.target === this) {
                closeStaffModal();
            }
        });

        // Close confirm modal when clicking on its backdrop
        $(document).on('click', '#confirm-modal', function(e){
            if (e.target === this) {
                closeConfirmModal();
            }
        });

        // Close on Escape (staff modal or confirm modal)
        $(document).on('keydown', function(e){
            if (e.key === 'Escape' || e.keyCode === 27) {
                const $confirm = $('#confirm-modal');
                if ($confirm.is(':visible')) { closeConfirmModal(); return; }
                const $staff = $('#staff-modal');
                if ($staff.is(':visible')) { closeStaffModal(); }
            }
        });

        $('#apply-staff-filters').on('click', function() { currentPage = 1; loadStaffList(); });
        $('#reset-staff-filters').on('click', function() {
            $('#staff-search, #filter-role, #filter-status').val('');
            currentPage = 1; loadStaffList();
        });
        // Auto-apply on role/status change
        $('#filter-role, #filter-status').on('change', function(){ currentPage = 1; loadStaffList(); });
        // Debounced live search
        let searchDebounce;
        $('#staff-search').on('input', function(){
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function(){ currentPage = 1; loadStaffList(); }, 400);
        }).on('keyup', function(e){ if (e.key === 'Enter') { currentPage = 1; loadStaffList(); } });

        $('.first-page').on('click', function(e) { e.preventDefault(); if (currentPage > 1) { currentPage = 1; loadStaffList(); } });
        $('.prev-page').on('click', function(e) { e.preventDefault(); if (currentPage > 1) { currentPage--; loadStaffList(); } });
        $('.next-page').on('click', function(e) { e.preventDefault(); if (currentPage < totalPages) { currentPage++; loadStaffList(); } });
        $('.last-page').on('click', function(e) { e.preventDefault(); if (currentPage < totalPages) { currentPage = totalPages; loadStaffList(); } });
    }

    // Open schedule modal for staff
    function openScheduleModal(staffId) {
        $('#schedule-staff-id').val(staffId);
        $('#schedule-date').val('');
        $('#schedule-start').val('');
        $('#schedule-end').val('');
        $('#schedule-modal').css('display', 'flex');
        $('body').css('overflow', 'hidden');
    }

    // Close schedule modal
    function closeScheduleModal() {
        $('#schedule-modal').hide();
        $('body').css('overflow', '');
    }

    // Wire schedule modal buttons
    $(document).on('click', '.schedule-cancel, .schedule-close', function(){ closeScheduleModal(); });
    $(document).on('click', '#schedule-modal', function(e){ if (e.target === this) closeScheduleModal(); });
    $(document).on('submit', '#schedule-form', function(e){
        e.preventDefault();
        const staffId = $('#schedule-staff-id').val();
        const date = $('#schedule-date').val();
        const start = $('#schedule-start').val();
        const end = $('#schedule-end').val();

        if (!staffId || !date || !start || !end) {
            showPopup('error', 'Please provide date, start and end times');
            return;
        }
        // Basic time ordering check
        if (start >= end) {
            showPopup('error', 'End time must be after start time');
            return;
        }

        const payload = {
            action: (staffManager.ajax_action || 'manage_staff'),
            nonce: staffManager.nonce,
            action_type: 'save_staff_schedule',
            data: JSON.stringify({ staff_id: staffId, date: date, start: start, end: end })
        };

        $.ajax({
            url: staffManager.ajax_url,
            type: 'POST',
            data: payload,
            success: function(resp){
                if (resp && resp.success) {
                    showPopup('success', resp.data && resp.data.message ? resp.data.message : 'Schedule saved');
                    closeScheduleModal();
                } else {
                    const msg = resp && resp.data && resp.data.message ? resp.data.message : (resp && resp.message ? resp.message : 'Failed to save schedule');
                    showPopup('error', msg);
                }
            },
            error: function(xhr, status, err){
                let m = 'Error saving schedule';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) m = xhr.responseJSON.message;
                showPopup('error', m);
            }
        });
    });

    // Simple focus trap within the staff modal
    function enableFocusTrap($modal){
        if (trapHandlerBound) return;
        trapHandlerBound = true;
        $(document).on('keydown.focusTrap', function(e){
            if (e.key !== 'Tab') return;
            const $visibleModal = $('#staff-modal:visible');
            if (!$visibleModal.length) return;
            const $focusables = $visibleModal.find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible:enabled');
            if (!$focusables.length) return;
            const first = $focusables[0];
            const last = $focusables[$focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    }

    function disableFocusTrap(){
        if (!trapHandlerBound) return;
        trapHandlerBound = false;
        $(document).off('keydown.focusTrap');
    }

    function closeConfirmModal(){
        const $confirm = $('#confirm-modal');
        if ($confirm.length && $confirm.is(':visible')){
            $confirm.hide();
            $('body').css('overflow','');
        }
    }

    // Validation helper functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        // Allow various phone formats: (123) 456-7890, 123-456-7890, 123.456.7890, 123 456 7890, +1234567890
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$|^[\(]?[\d]{3}[\)]?[-.\s]?[\d]{3}[-.\s]?[\d]{4}$/;
        return phoneRegex.test(phone.replace(/[\s\-\.\(\)]/g, ''));
    }
});
