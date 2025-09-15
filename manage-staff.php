<?php
/**
 * Elite Cuts - Manage Staff
 * Admin interface for managing staff members (barbers) with consistent branding
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function elite_cuts_manage_staff_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap elite-cuts-admin">
        <div class="elite-cuts-header">
            <div class="shop-info">
                <h1 class="shop-name">Elite Cuts Barbershop</h1>
                <p class="shop-slogan">Precision Cuts & Grooming</p>
            </div>
            <div class="header-actions">
                <h1 class="elite-cuts-title">
                    <i class="fas fa-user-friends"></i> Manage Staff
                </h1>
                <button id="add-staff-btn" class="elite-button primary">
                    <i class="fas fa-plus"></i> New Staff
                </button>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="elite-cuts-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="filter-role">Role</label>
                        <select id="filter-role" class="elite-select">
                            <option value="">All Roles</option>
                            <option value="Master Barber">Master Barber</option>
                            <option value="Barber">Barber</option>
                            <option value="Stylist">Stylist</option>
                            <option value="Receptionist">Receptionist</option>
                        </select>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-item">
                        <label for="filter-availability">Availability</label>
                        <select id="filter-availability" class="elite-select">
                            <option value="">Any</option>
                            <option value="Available">Available</option>
                            <option value="Busy">Busy</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-item">
                        <label for="filter-status">Status</label>
                        <select id="filter-status" class="elite-select">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="filter-group search-filter">
                    <div class="filter-item search-box">
                        <label for="staff-search">Search</label>
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="staff-search" class="elite-input search-input" placeholder="Search staff by name, role, or contact...">
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" id="apply-staff-filters" class="elite-button primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" id="reset-staff-filters" class="elite-button secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Give space below the filters so buttons don't touch the table -->
        <style>
            .elite-cuts-filters { margin-bottom: 14px; }
        </style>

        <!-- Toast container -->
        <div id="elite-toast-container" aria-live="polite" aria-atomic="true"></div>

        <!-- Staff Table -->
        <div class="table-container">
            <table class="elite-cuts-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="staff-list">
                    <tr class="loading-row">
                        <td colspan="6">
                            <div class="loading-spinner"></div>
                            <span>Loading staff...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Shared Staff Form Template (rendered by shortcode file) -->
    <?php
    // Ensure the shortcode file is loaded so the renderer function exists
    if (!function_exists('payndle_render_staff_form')) {
        include_once plugin_dir_path(__FILE__) . 'manage-staff-shortcode.php';
    }
    if (function_exists('payndle_render_staff_form')) payndle_render_staff_form();
    ?>

    <!-- Assign Schedule Modal -->
    <div id="schedule-modal" class="elite-modal" style="display: none;">
        <div class="elite-modal-content">
            <div class="elite-modal-header">
                <h3>Assign Schedule</h3>
                <span class="elite-close schedule-close">&times;</span>
            </div>
            <div class="elite-modal-body">
                <form id="schedule-form">
                    <input type="hidden" id="schedule-staff-id" value="">
                    <div class="form-group">
                        <label for="schedule-date">Date</label>
                        <input type="date" id="schedule-date" class="elite-input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="schedule-start">Start Time</label>
                            <input type="time" id="schedule-start" class="elite-input" required>
                        </div>
                        <div class="form-group">
                            <label for="schedule-end">End Time</label>
                            <input type="time" id="schedule-end" class="elite-input" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="elite-button secondary schedule-cancel">Cancel</button>
                        <button type="submit" class="elite-button primary">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Theme variables - updated to Payndle palette */
        :root {
            --bg-primary: #FFFFFF; /* Secondary White as page base */
            --bg-secondary: #FFFFFF;
            --bg-tertiary: #F7F9FB; /* subtle off-white */
            --text-primary: #0C1930; /* Primary Navy */
            --text-secondary: #62708a; /* muted navy gray */
            --accent: #64C493; /* Primary Green */
            --accent-hover: #4FB07A; /* darker green */
            --border-color: #E6E9EE; /* Accent Gray */
            --success: #64C493;
            --warning: #FFB020;
            --danger: #F44336;
            --info: #2196f3;
            --radius: 8px; /* Button Radius requested */
            --shadow: 0 2px 10px rgba(12,25,48,0.06);
            --card-bg: #FFFFFF;
            --input-bg: #FFFFFF;
        }

        .elite-cuts-admin { background: var(--bg-secondary); color: var(--text-primary); padding: 1.5rem; font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; line-height: 1.6; }
        .elite-cuts-header { background: var(--card-bg); padding: 1.25rem 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow); border-left: 4px solid var(--accent); }
        .shop-name { color: var(--accent); margin: 0 0 0.25rem 0; font-size: 1.5rem; font-weight: 700; font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; letter-spacing: 0.2px; }
        .shop-slogan { color: var(--text-secondary); margin: 0; font-size: 0.875rem; font-weight: 400; }

        /* Table and cells */
        .table-container { background: var(--card-bg); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
        .elite-cuts-table { width: 100%; border-collapse: collapse; color: var(--text-primary); }
        .elite-cuts-table th { background: var(--bg-tertiary); color: var(--text-secondary); font-weight: 500; text-align: left; padding: 1rem 1.25rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1; }
        .elite-cuts-table td { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .elite-cuts-table tbody tr:last-child td { border-bottom: none; }
        .elite-cuts-table tbody tr:hover { background: rgba(201, 167, 77, 0.05); }

        .staff-cell { display: flex; align-items: center; gap: 0.9rem; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(201, 167, 77, 0.35); }
        /* Fallback initial-based avatar */
    .avatar-initial { width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; color: var(--color-white, #ffffff); border: 2px solid rgba(100,196,147,0.25); background: linear-gradient(135deg, var(--accent), var(--accent-hover)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.12); }
        .staff-meta { display: flex; flex-direction: column; }
        .staff-name { font-weight: 600; color: var(--text-primary); }
        .staff-sub { color: var(--text-secondary); font-size: 0.85rem; }

    .status-badge { display: inline-flex; align-items: center; padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.3px; }
    .status-active { background: rgba(100,196,147,0.12); color: var(--accent); border: 1px solid rgba(100,196,147,0.2); }
    .status-inactive { background: rgba(12,25,48,0.04); color: var(--text-secondary); border: 1px solid rgba(12,25,48,0.06); }

        .availability-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.2px; gap: 0.4rem; }
        .avail-Available { background: rgba(76, 175, 80, 0.1); color: var(--success); border: 1px solid rgba(76, 175, 80, 0.2); }
        .avail-Busy { background: rgba(255, 152, 0, 0.1); color: var(--warning); border: 1px solid rgba(255, 152, 0, 0.2); }
        /* Stylish On Leave pill */
        .avail-On\ Leave { 
            background: linear-gradient(90deg, rgba(33,150,243,0.10) 0%, rgba(156,39,176,0.08) 100%);
            color: #1f5fbf; /* richer info tone */
            border: 1px dashed rgba(33,150,243,0.35);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
            font-style: italic;
        }
        .availability-badge i { font-size: 0.9em; opacity: 0.9; }

        /* Modern action buttons */
        .action-buttons { display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end; }
    .icon-btn { --btn-bg: #fafafa; --btn-color: var(--text-secondary); --btn-border: rgba(12,25,48,0.06); display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: var(--radius); border: 1px solid var(--btn-border); background: var(--btn-bg); color: var(--btn-color); cursor: pointer; transition: transform 0.12s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease; position: relative; }
        .icon-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .icon-btn:focus { outline: none; box-shadow: 0 0 0 3px rgba(100,196,147,0.18); }
    .icon-btn.edit { --btn-bg: #f6fff9; --btn-color: var(--accent); border-color: rgba(100,196,147,0.25); }
    .icon-btn.activate { --btn-bg: rgba(100,196,147,0.08); --btn-color: var(--accent); border-color: rgba(100,196,147,0.18); }
    .icon-btn.deactivate { --btn-bg: rgba(244,67,54,0.06); --btn-color: var(--danger); border-color: rgba(244,67,54,0.12); }
    .icon-btn.schedule { --btn-bg: rgba(12,25,48,0.04); --btn-color: var(--text-secondary); border-color: rgba(12,25,48,0.06); }
        /* Tooltips */
        .icon-btn[data-tooltip]::after { content: attr(data-tooltip); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); background: #111; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.15s ease, transform 0.15s ease; transform-origin: bottom center; }
        .icon-btn[data-tooltip]:hover::after { opacity: 1; transform: translateX(-50%) translateY(-2px); }

        /* Buttons, inputs, forms (consistent with manage-bookings) */
    .elite-button { display: inline-flex; align-items: center; gap: 0.5rem; background: var(--accent); color: var(--color-navy, #1e1e1e); border: none; padding: 0.6rem 1.25rem; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem; }
    .elite-button:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 2px 8px rgba(100,196,147,0.18); color: var(--bg-secondary); }
    .elite-button.secondary { background: transparent; color: var(--text-secondary); border: 1px solid var(--border-color); }
    .elite-button.secondary:hover { background: var(--bg-tertiary); color: var(--text-primary); border-color: var(--text-secondary); }

        .filter-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 200px; }
        .filter-item label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); }
        .search-filter { min-width: 250px; max-width: 350px; }
        .search-container { position: relative; width: 100%; display: flex; align-items: center; }
        .search-icon { position: absolute; left: 5px; color: var(--text-secondary); font-size: 0.9rem; pointer-events: none; z-index: 2; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; }
        .search-input { width: 100%; padding: 0.6rem 1rem 0.6rem 3rem; border: 1px solid var(--border-color); border-radius: var(--radius); background-color: var(--input-bg); color: var(--text-primary); font-size: 0.9rem; transition: all 0.2s ease; position: relative; z-index: 1; box-sizing: border-box; text-indent: 0.5rem; }
        .search-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(201, 167, 77, 0.2); }
        .filter-actions { display: flex; gap: 0.75rem; margin-left: auto; align-self: flex-end; }

        /* Modal */
    .elite-modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; display: none; background: rgba(0, 0, 0, 0.35); align-items: center; justify-content: center; padding: 1rem; }
    .elite-modal-content { background: var(--card-bg); border-radius: var(--radius); box-shadow: var(--shadow); max-width: 720px; width: 100%; overflow: hidden; }
        .elite-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); }
        .elite-modal-header h3 { margin: 0; font-size: 1.1rem; }
        .elite-close { cursor: pointer; font-size: 1.2rem; color: var(--text-secondary); }
        .elite-modal-body { padding: 1rem 1.25rem; }
        .form-row { display: flex; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; flex: 1; }
    .elite-input, .elite-select { padding: 0.55rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--input-bg); }
    .elite-input:focus, .elite-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(100,196,147,0.14); }
        .form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.75rem; }

        /* Loading State */
    .loading-row td { text-align: center; padding: 2rem; color: var(--text-secondary); }
    .loading-spinner { display: inline-block; width: 1.5rem; height: 1.5rem; border: 2px solid rgba(12,25,48,0.06); border-radius: 50%; border-top-color: var(--accent); animation: spin 0.8s linear infinite; margin-right: 0.5rem; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 1024px) { .filter-row { flex-direction: column; align-items: stretch; } .filter-group { min-width: 100%; } .search-filter { max-width: 100%; } .filter-actions { margin-left: 0; margin-top: 0.5rem; justify-content: flex-end; } }
        @media (max-width: 480px) { .form-row { flex-direction: column; } .filter-actions { flex-direction: column; gap: 0.5rem; } .filter-actions .elite-button { width: 100%; } }

        /* Toasts */
        #elite-toast-container { position: fixed; right: 18px; bottom: 18px; z-index: 10000; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .elite-toast { min-width: 240px; max-width: 360px; background: #1f1f1f; color: #fff; border-radius: 10px; padding: 10px 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); border-left: 4px solid var(--accent); opacity: 0; transform: translateY(10px); transition: opacity .18s ease, transform .18s ease; pointer-events: auto; }
        .elite-toast.show { opacity: 1; transform: translateY(0); }
        .elite-toast.success { border-left-color: var(--success); }
        .elite-toast.info { border-left-color: var(--info); }
        .elite-toast.warning { border-left-color: var(--warning); }
        .elite-toast.danger { border-left-color: var(--danger); }

        /* Avatar preview */
        .avatar-preview-row { display:flex; align-items:center; gap:0.6rem; margin-top:0.5rem; }
        .avatar-preview { width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(201, 167, 77, 0.35); background:#f6f6f6; }
        .elite-button.small { padding: 0.4rem 0.7rem; font-size: 0.75rem; }
        .help-text { color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.35rem; }
    </style>

    <?php if ( is_admin() ) : ?>
    <script>
    jQuery(document).ready(function($) {
        const staffTbody = $('#staff-list');

        function renderStaff(list) {
            staffTbody.empty();
            if (!list || list.length === 0) {
                staffTbody.append('<tr><td colspan="6" class="no-staff">No staff found</td></tr>');
                return;
            }
            list.forEach(member => {
                const statusLabel = member.status === 'active' ? 'Active' : 'Inactive';
                const initial = (member.name || '').trim().charAt(0).toUpperCase() || '?';
                const avatarHtml = `<div class="avatar-initial" aria-hidden="true">${initial}</div>`;
                const servicesHtml = (member.services || []).map(s => `<span class="mvp-category-badge">${s.title}</span>`).join(' ');
                const row = `
                    <tr>
                        <td>
                            <div class="staff-cell">
                                ${avatarHtml}
                                <div class="staff-meta">
                                    <span class="staff-name">${member.name}</span>
                                    <span class="staff-sub">${servicesHtml}</span>
                                </div>
                            </div>
                        </td>
                        <td></td>
                        <td>${member.email}<br><span class="staff-sub">${member.phone}</span></td>
                        <td></td>
                        <td><span class="status-badge status-${member.status}">${statusLabel}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="icon-btn edit" aria-label="Edit staff" data-tooltip="Edit" data-id="${member.id}"><i class="fa-solid fa-pen-to-square"></i></button>
                                ${member.status === 'active'
                                    ? `<button class=\"icon-btn deactivate toggle-status\" aria-label=\"Deactivate\" data-tooltip=\"Deactivate\" data-id=\"${member.id}\" data-status=\"inactive\"><i class=\"fa-solid fa-user-slash\"></i></button>`
                                    : `<button class=\"icon-btn activate toggle-status\" aria-label=\"Activate\" data-tooltip=\"Activate\" data-id=\"${member.id}\" data-status=\"active\"><i class=\"fa-solid fa-user-check\"></i></button>`}
                                <button class="icon-btn schedule assign-schedule" aria-label="Assign schedule" data-tooltip="Assign schedule" data-id="${member.id}"><i class="fa-solid fa-calendar-plus"></i></button>
                            </div>
                        </td>
                    </tr>`;
                staffTbody.append(row);
            });
        }

        function loadStaff(filters = {}) {
            const payload = { action_type: 'get_staff', data: filters };
            $.post(ajaxurl, { action: 'manage_staff', nonce: '<?php echo wp_create_nonce('staff_management_nonce'); ?>', action_type: 'get_staff', data: JSON.stringify(filters) }, function(resp) {
                if (!resp || !resp.success) {
                    staffTbody.html('<tr><td colspan="6">Could not load staff</td></tr>');
                    return;
                }
                renderStaff(resp.data.staff || []);
            }).fail(function(){ staffTbody.html('<tr><td colspan="6">Could not load staff (server error)</td></tr>'); });
        }

        // Initial load
        loadStaff();

        // Apply filters button
        $('#apply-staff-filters').on('click', function(){
            const serviceId = $('#filter-service').length ? parseInt($('#filter-service').val() || '0') : 0;
            const status = $('#filter-status').val() || '';
            const q = $('#staff-search').val() || '';
            loadStaff({ service_id: serviceId, status: status, search: q });
        });

        $('#reset-staff-filters').on('click', function(){
            $('#filter-service').val('');
            $('#filter-status').val('');
            $('#staff-search').val('');
            loadStaff();
        });

        // Filter actions
        $('#apply-staff-filters').on('click', applyFilters);
        $('#reset-staff-filters').on('click', resetFilters);
        $('#staff-search').on('keyup', function(e){ if (e.key === 'Enter') applyFilters(); });

        // Modal elements
        const staffModal = document.getElementById('staff-modal');
        const scheduleModal = document.getElementById('schedule-modal');

        function openModal(modalEl) { modalEl.style.display = 'flex'; }
        function closeModal(modalEl) { modalEl.style.display = 'none'; }

        // Add new staff
        $('#add-staff-btn').on('click', function() {
            document.querySelector('#staff-modal h3').textContent = 'Add New Staff';
            document.getElementById('staff-form').reset();
            $('#staff-id').val('');
            openModal(staffModal);
        });

        // Close handlers
        $('.staff-close, .staff-cancel').on('click', function(){ closeModal(staffModal); });
        $('.schedule-close, .schedule-cancel').on('click', function(){ closeModal(scheduleModal); });
        window.onclick = function(event) {
            if (event.target === staffModal) closeModal(staffModal);
            if (event.target === scheduleModal) closeModal(scheduleModal);
        };

        // Save staff (simulate create/update)
        $('#staff-form').on('submit', function(e) {
            e.preventDefault();
            const id = parseInt($('#staff-id').val());
            const member = {
                id: id || (Math.max(0, ...staffData.map(s => s.id)) + 1),
                name: $('#staff-name').val(),
                role: $('#staff-role').val(),
                email: $('#staff-email').val(),
                phone: $('#staff-phone').val(),
                availability: $('#staff-availability').val(),
                status: $('#staff-status').val(),
                avatar: ($('#staff-avatar').val() || '').trim()
            };
            if (id) {
                const idx = staffData.findIndex(s => s.id === id);
                if (idx > -1) staffData[idx] = member;
            } else {
                staffData.push(member);
            }
            showToast(id ? 'Staff updated successfully' : 'New staff created', 'success');
            closeModal(staffModal);
            applyFilters();
        });

        // Edit staff handler
        $(document).on('click', '.edit-staff, .icon-btn.edit', function(e) {
            e.preventDefault();
            const id = parseInt($(this).data('id'));
            const m = staffData.find(x => x.id === id);
            if (!m) return;
            document.querySelector('#staff-modal h3').textContent = 'Edit Staff';
            $('#staff-id').val(m.id);
            $('#staff-name').val(m.name);
            $('#staff-role').val(m.role);
            $('#staff-email').val(m.email);
            $('#staff-phone').val(m.phone);
            $('#staff-availability').val(m.availability);
            $('#staff-status').val(m.status);
            $('#staff-avatar').val(m.avatar);
            openModal(staffModal);
        });

        // Toggle status (activate/deactivate)
        $(document).on('click', '.toggle-status', function(e) {
            e.preventDefault();
            const id = parseInt($(this).data('id'));
            const newStatus = $(this).data('status');
            const idx = staffData.findIndex(s => s.id === id);
            if (idx > -1) {
                staffData[idx].status = newStatus;
                const msg = newStatus === 'active' ? 'Staff activated' : 'Staff deactivated';
                showToast(`ID #${id}: ${msg}`, newStatus === 'active' ? 'success' : 'warning');
                applyFilters();
            }
        });

        // Assign schedule
        $(document).on('click', '.assign-schedule', function(e) {
            e.preventDefault();
            const id = parseInt($(this).data('id'));
            $('#schedule-staff-id').val(id);
            document.querySelector('#schedule-modal h3').textContent = `Assign Schedule (ID #${id})`;
            openModal(scheduleModal);
        });

        $('#schedule-form').on('submit', function(e) {
            e.preventDefault();
            const id = parseInt($('#schedule-staff-id').val());
            const date = $('#schedule-date').val();
            const start = $('#schedule-start').val();
            const end = $('#schedule-end').val();
            // Simulate schedule save
            showToast(`Schedule assigned to #${id} on ${date} (${start}–${end})`, 'success');
            closeModal(scheduleModal);
        });

        $(document).on('click', '#staff-avatar-upload', function(e){
            e.preventDefault();
            const canUpload = $(this).data('can-upload') === 1 || $(this).data('can-upload') === '1';
            if (!canUpload || typeof wp === 'undefined' || !wp.media) {
                alert('You do not have permission to upload files or the Media Library is unavailable.');
                return;
            }
            // Admin media frame
            if (typeof wp !== 'undefined' && wp.media) {
                const frame = wp.media({ title: 'Select Profile Photo', multiple: false });
                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first().toJSON();
                    if (!attachment) return;
                    const url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    $('#staff-avatar').val(attachment.url);
                    $('#staff-avatar-id').val(attachment.id);
                    $('#staff-avatar-preview').attr('src', url).show();
                    $('#staff-avatar-placeholder').hide();
                    $('#staff-avatar-clear').show();
                });
                frame.open();
            }
        });

        // Toast helper
        function showToast(message, type = 'success') {
            const $container = $('#elite-toast-container');
            const $el = $(`<div class="elite-toast ${type}"></div>`).text(message);
            $container.append($el);
            // animate in
            requestAnimationFrame(() => $el.addClass('show'));
            // auto dismiss
            setTimeout(() => {
                $el.removeClass('show');
                setTimeout(() => $el.remove(), 200);
            }, 2600);
        }

        // Helper: get first letter initial from full name
        function getInitial(name) {
            if (!name || typeof name !== 'string') return '?';
            const first = name.trim().charAt(0).toUpperCase();
            return first || '?';
        }
    });
    </script>
    <?php endif; ?>
    <?php
}

// Register submenu under existing Elite Cuts menu (created in manage-bookings.php)
function elite_cuts_add_staff_submenu() {
    add_submenu_page(
        'elite-cuts-bookings',
        'Manage Staff',
        'Staff',
        'manage_options',
        'elite-cuts-staff',
        'elite_cuts_manage_staff_page',
        31
    );
}
add_action('admin_menu', 'elite_cuts_add_staff_submenu');

// Enqueue admin assets for staff page
function elite_cuts_staff_admin_assets($hook) {
    // Target submenu page hook suffix
    if ('elite-cuts-bookings_page_elite-cuts-staff' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    // Use Inter for primary UI typography (weights: 400,500,600,700) and keep Playfair only if needed for legacy headings
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap');
    // Media Library for avatar upload (only if the user can upload files)
    if (function_exists('wp_enqueue_media') && current_user_can('upload_files')) { wp_enqueue_media(); }
}
add_action('admin_enqueue_scripts', 'elite_cuts_staff_admin_assets');

// Optional: Front-end shortcode gated by permissions
function elite_cuts_manage_staff_shortcode() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p>You need to be logged in with the right permissions to view this page.</p>';
    }
    ob_start();
    elite_cuts_manage_staff_page();
    return ob_get_clean();
}
add_shortcode('elite_cuts_manage_staff', 'elite_cuts_manage_staff_shortcode');