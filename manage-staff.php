<?php
/**
 * Elite Cuts - Manage Staff
 * Admin interface for managing staff members (barbers) with consistent branding
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Shared renderer for the modern Staff UI so admin and frontend shortcodes look identical
if (!function_exists('elite_cuts_render_staff_ui')) {
    function elite_cuts_render_staff_ui() {
        ?>
    <div class="wrap elite-cuts-admin">
        <div class="elite-cuts-header modern-header">
            <div class="header-left">
                <!-- Compact brand / icon area removed business name and description per request -->
                <div class="brand">
                    <div class="brand-icon" aria-hidden="true">🧑‍🔧</div>
                    <div class="brand-meta">
                        <div class="brand-title">Manage Staff</div>
                        <div class="brand-sub">Add, edit and schedule staff members</div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="elite-button" id="add-staff-btn">+ New Staff</button>
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
                            <?php
                            // Populate from Service posts (actual services) scoped to current business
                            $business_id = function_exists('payndle_get_current_business_id') ? absint(payndle_get_current_business_id()) : 0;
                            $svc_args = array(
                                'post_type' => 'service',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                                'fields' => 'ids'
                            );
                            if ($business_id > 0) {
                                $svc_args['meta_query'] = array(
                                    array('key' => '_business_id', 'value' => $business_id, 'compare' => '=')
                                );
                            }
                            $services = get_posts($svc_args);
                            foreach ($services as $sid) {
                                $title = get_the_title($sid);
                                echo '<option value="' . esc_attr($sid) . '">' . esc_html($title) . '</option>';
                            }
                            ?>
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
                <colgroup>
                    <col style="width:20%;" /> <!-- Staff -->
                    <col style="width:20%;" /> <!-- Role -->
                    <col style="width:30%;" /> <!-- Contact -->
                    <col style="width:10%;" /> <!-- Availability -->
                    <col style="width:3%;"  /> <!-- Status -->
                    <col style="width:17%;" /> <!-- Actions -->
                </colgroup>
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Availability</th>
                        <th>Status</th>
                           <th><?php _e('Actions', 'payndle'); ?></th>
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

    <!-- Confirmation Modal (admin) -->
    <div id="confirm-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Confirm Action', 'payndle'); ?></h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirm-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" id="confirm-cancel" class="button"><?php _e('Cancel', 'payndle'); ?></button>
                <button type="button" id="confirm-action" class="button button-danger"><?php _e('Delete', 'payndle'); ?></button>
            </div>
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
            <div class="elite-modal-body ubf-v3-container">
                <form id="schedule-form" class="ubf-v3-form">
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
    .elite-cuts-header { background: var(--card-bg); padding: 1.25rem 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem; box-shadow: var(--shadow); border-left: 4px solid var(--accent); }
    /* Modern compact header layout */
    .elite-cuts-header.modern-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding: 0.75rem 1rem; }
    .elite-cuts-header .brand { display:flex; align-items:center; gap:0.75rem; }
    .brand-icon { width:48px; height:48px; border-radius:10px; background: linear-gradient(180deg, rgba(100,196,147,0.12), rgba(79,176,122,0.06)); display:flex; align-items:center; justify-content:center; font-size:1.25rem; }
    .brand-title { font-size:1.125rem; font-weight:700; color:var(--text-primary); }
    .brand-sub { font-size:0.85rem; color:var(--text-secondary); }

    /* Table, avatar and action-button overrides were moved to assets/css/staff-management.css */

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
        .action-buttons { display: inline-flex; gap: 0.35rem; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
        .icon-btn { --btn-bg: #fafafa; --btn-color: var(--text-secondary); --btn-border: rgba(12,25,48,0.06); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; min-width: 32px; border-radius: var(--radius); border: 1px solid var(--btn-border); background: var(--btn-bg); color: var(--btn-color); cursor: pointer; transition: transform 0.12s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease; position: relative; padding: 0; }
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
    .elite-modal { 
        position: fixed !important; 
        z-index: 100000 !important; 
        left: 0 !important; 
        top: 0 !important; 
        width: 100% !important; 
        height: 100% !important; 
        display: none; 
        background: transparent !important; 
        align-items: center; 
        justify-content: center; 
        padding: 1rem;
        box-sizing: border-box;
    }
    .elite-modal-content { 
        background: var(--card-bg) !important; 
        border-radius: var(--radius); 
        box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important; 
        max-width: 720px; 
        width: 90%; 
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        margin: auto;
    }
        .elite-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); }
        .elite-modal-header h3 { margin: 0; font-size: 1.1rem; }
        .elite-close { cursor: pointer; font-size: 1.2rem; color: var(--text-secondary); }
        .elite-modal-body { padding: 1rem 1.25rem; }
        .form-row { display: flex; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; flex: 1; }
    .elite-input, .elite-select { padding: 0.55rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--input-bg); }
    .elite-input:focus, .elite-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(100,196,147,0.14); }
        .form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.75rem; }

        /* Ensure WordPress Media Library overlays our modal */
        .media-modal {
            z-index: 200000 !important;
        }
        .media-modal-backdrop {
            z-index: 199999 !important;
            background: transparent !important;
        }

        /* Loading State */
    .loading-row td { text-align: center; padding: 2rem; color: var(--text-secondary); }
    .loading-spinner { display: inline-block; width: 1.5rem; height: 1.5rem; border: 2px solid rgba(12,25,48,0.06); border-radius: 50%; border-top-color: var(--accent); animation: spin 0.8s linear infinite; margin-right: 0.5rem; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 1024px) { .filter-row { flex-direction: column; align-items: stretch; } .filter-group { min-width: 100%; } .search-filter { max-width: 100%; } .filter-actions { margin-left: 0; margin-top: 0.5rem; justify-content: flex-end; } }
        @media (max-width: 480px) { .form-row { flex-direction: column; } .filter-actions { flex-direction: column; gap: 0.5rem; } .filter-actions .elite-button { width: 100%; } }

        /* Boxed container adjustments: reduce padding and font sizes slightly so cells fit better */
        @media (min-width: 800px) and (max-width: 1400px) {
            .elite-cuts-table th, .elite-cuts-table td { padding: 0.5rem 0.6rem; font-size: 0.82rem; }
            .staff-name { font-size: 0.95rem; }
            .staff-sub { font-size: 0.78rem; }
            .action-buttons { gap: 0.25rem; }
        }

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

    /* Simple modal styles for confirmation dialog */
    #confirm-modal { position: fixed !important; z-index: 200000 !important; left: 0 !important; top: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; padding: 1rem; }
    #confirm-modal .modal-content { background: #fff; border-radius: var(--radius); width: 90%; max-width: 440px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    #confirm-modal .modal-header, #confirm-modal .modal-footer { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
    #confirm-modal .modal-footer { border-bottom: none; border-top: 1px solid var(--border-color); justify-content: flex-end; gap: .5rem; }
    #confirm-modal .modal-body { padding: 1rem; }
    #confirm-modal .close { cursor: pointer; font-size: 1.2rem; color: var(--text-secondary); }
    </style>
        <?php
    }
}

function elite_cuts_manage_staff_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    // Render the shared UI
    elite_cuts_render_staff_ui();
}

// Enqueue admin styles and scripts - now using same enhanced JS as shortcode
function elite_cuts_admin_scripts($hook) {
    // Load assets on our Staff submenu page; be tolerant of different hook formats
    if (strpos($hook, 'elite-cuts-staff') === false) {
        return;
    }
    
    // Use the same enhanced JavaScript and CSS as the shortcode for consistency
    wp_enqueue_media(); // Required for wp.media in admin
    // Enqueue staff management JS (no Select2 dependency)
    wp_enqueue_script('staff-management-js', plugin_dir_url(__FILE__) . 'assets/js/staff-management.js', array('jquery'), '1.0', true);
    wp_enqueue_style('staff-management-css', plugin_dir_url(__FILE__) . 'assets/css/staff-management.css', array(), '1.0');
    
    // Localize script with admin context variables (same as shortcode but fixed variable name)
    wp_localize_script('staff-management-js', 'staffManager', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        // Use root REST URL so JS can append custom routes like 'payndle/v1/upload-avatar'
        'rest_url' => rest_url(),
        'nonce' => wp_create_nonce('staff_management_nonce'),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'context' => 'admin',
        'has_media' => 'true',
        'confirm_delete' => 'Are you sure you want to delete this staff member?',
    ));
}
add_action('admin_enqueue_scripts', 'elite_cuts_admin_scripts');

// AJAX handler for staff management 
function handle_manage_staff_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'staff_management_nonce')) {
        wp_die('Security check failed');
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $action_type = sanitize_text_field($_POST['action_type']);
    $data = json_decode(stripslashes($_POST['data']), true);

    switch ($action_type) {
        case 'get_staff':
            elite_cuts_get_staff($data);
            break;
        case 'save_staff_schedule':
            elite_cuts_save_staff_schedule($data);
            break;
        case 'add_staff':
            elite_cuts_add_staff($data);
            break;
        case 'update_staff':
            elite_cuts_update_staff($data);
            break;
        case 'delete_staff':
            elite_cuts_delete_staff($data);
            break;
        default:
            wp_send_json_error('Invalid action');
    }
}
add_action('wp_ajax_manage_staff', 'handle_manage_staff_ajax');

// Get staff function
function elite_cuts_get_staff($filters = array()) {
    $args = array(
        'post_type' => 'staff',
        'post_status' => 'publish',
        'posts_per_page' => isset($filters['per_page']) ? intval($filters['per_page']) : 10,
        'paged' => isset($filters['paged']) ? intval($filters['paged']) : 1,
    );

    if (isset($filters['search']) && !empty($filters['search'])) {
        $args['s'] = sanitize_text_field($filters['search']);
    }

    // Build meta_query with AND relation; include status and service filters
    $meta_query = array('relation' => 'AND');

    if (isset($filters['status']) && !empty($filters['status'])) {
        $meta_query[] = array(
            'key' => 'staff_status',
            'value' => sanitize_text_field($filters['status']),
            'compare' => '='
        );
    }

    if (isset($filters['service_id']) && !empty($filters['service_id'])) {
        $service_id = intval($filters['service_id']);
        $service_title = '';
        $s_post = get_post($service_id);
        if ($s_post) { $service_title = $s_post->post_title; }

        // Match either: staff_services serialized array contains ID OR staff_role equals service title (legacy data)
        $service_clause = array('relation' => 'OR');
        // Case 1: staff_services stored as array of strings => serialized contains "123"
        $service_clause[] = array(
            'key' => 'staff_services',
            'value' => '"' . $service_id . '"',
            'compare' => 'LIKE'
        );
        // Case 2: staff_services stored as array of integers => serialized contains i:123;
        $service_clause[] = array(
            'key' => 'staff_services',
            'value' => 'i:' . $service_id . ';',
            'compare' => 'LIKE'
        );
        if (!empty($service_title)) {
            $service_clause[] = array(
                'key' => 'staff_role',
                'value' => $service_title,
                'compare' => '='
            );
        }

        $meta_query[] = $service_clause;
    }

    // If role is provided as a non-numeric string, match legacy staff_role directly
    if (isset($filters['role']) && !empty($filters['role']) && !is_numeric($filters['role'])) {
        $meta_query[] = array(
            'key' => 'staff_role',
            'value' => sanitize_text_field($filters['role']),
            'compare' => '='
        );
    }

    // Only set meta_query if we actually added conditions
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    if (isset($filters['id']) && !empty($filters['id'])) {
        $args['p'] = intval($filters['id']);
    }

    $query = new WP_Query($args);
    $staff = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $avatar = get_post_meta($post_id, 'staff_avatar', true);
            $avatar_id = get_post_meta($post_id, 'staff_avatar_id', true);
            // Resolve attachment ID to URL if avatar is empty
            if (empty($avatar) && !empty($avatar_id)) {
                if (function_exists('wp_get_attachment_image_url')) {
                    $resolved = wp_get_attachment_image_url($avatar_id, 'thumbnail');
                } else {
                    $resolved = wp_get_attachment_url($avatar_id);
                }
                if (empty($resolved)) {
                    $resolved = wp_get_attachment_url($avatar_id);
                }
                if (!empty($resolved)) {
                    $avatar = $resolved;
                }
            }
            // Fallback to featured image if no avatar meta is set
            if (empty($avatar)) {
                $thumb_id = function_exists('get_post_thumbnail_id') ? get_post_thumbnail_id($post_id) : 0;
                if ($thumb_id) {
                    $resolved = function_exists('wp_get_attachment_image_url') ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : wp_get_attachment_url($thumb_id);
                    if (empty($resolved)) { $resolved = wp_get_attachment_url($thumb_id); }
                    if (!empty($resolved)) {
                        $avatar = $resolved;
                        if (empty($avatar_id)) { $avatar_id = $thumb_id; }
                    }
                }
            }

            // Build service items (id + title) from staff_services meta
            $service_items = array();
            $services_meta = get_post_meta($post_id, 'staff_services', true) ?: array();
            if (!empty($services_meta) && is_array($services_meta)) {
                foreach ($services_meta as $sid) {
                    $s_post = get_post($sid);
                    if ($s_post) $service_items[] = array('id' => $sid, 'title' => $s_post->post_title);
                }
            }

            $staff[] = array(
                'id' => $post_id,
                'name' => get_the_title(),
                'email' => get_post_meta($post_id, 'staff_email', true) ?: get_post_meta($post_id, 'email', true),
                'phone' => get_post_meta($post_id, 'staff_phone', true) ?: get_post_meta($post_id, 'phone', true),
                'status' => get_post_meta($post_id, 'staff_status', true),
                'role' => get_post_meta($post_id, 'staff_role', true),
                'avatar' => $avatar,
                'avatar_id' => $avatar_id,
                'services' => $service_items,
            );
        }
        wp_reset_postdata();
    }

    $pagination = array(
        'total' => $query->found_posts,
        'current_page' => $args['paged'],
        'total_pages' => $query->max_num_pages,
    );

    wp_send_json_success(array(
        'staff' => $staff,
        'pagination' => $pagination
    ));
}

// Add staff function
function elite_cuts_add_staff($data) {
    // Server-side validation
    $name = isset($data['name']) ? trim(sanitize_text_field($data['name'])) : '';
    $services = isset($data['services']) && is_array($data['services']) ? array_map('absint', $data['services']) : array();
    $email = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
    $email = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';

    if (empty($name)) {
        wp_send_json_error(array('message' => 'Staff name is required.'));
    }

    if (empty($services)) {
        wp_send_json_error(array('message' => 'Please assign at least one service to the staff member.'));
    }

    // Require at least one contact method
    if (empty($email) && empty($phone)) {
        wp_send_json_error(array('message' => 'Please provide at least one contact method: email or phone.'));
    }

    // Require at least one contact method
    if (empty($email) && empty($phone)) {
        wp_send_json_error(array('message' => 'Please provide at least one contact method: email or phone.'));
    }

    $post_data = array(
        'post_title' => $name,
        'post_type' => 'staff',
        'post_status' => 'publish',
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
    update_post_meta($post_id, 'staff_email', sanitize_email($data['email']));
    update_post_meta($post_id, 'staff_phone', sanitize_text_field($data['phone']));
        update_post_meta($post_id, 'staff_status', sanitize_text_field($data['status']));
    update_post_meta($post_id, 'staff_role', sanitize_text_field($data['role']));
    // services from admin form (validated above)
    update_post_meta($post_id, 'staff_services', $services);
        
        if (isset($data['avatar']) && !empty($data['avatar'])) {
            update_post_meta($post_id, 'staff_avatar', esc_url_raw($data['avatar']));
        }
        if (isset($data['avatar_id']) && !empty($data['avatar_id'])) {
            update_post_meta($post_id, 'staff_avatar_id', intval($data['avatar_id']));
            set_post_thumbnail($post_id, intval($data['avatar_id']));
        }

        // Sync assigned_staff on each service post
        foreach ($services as $sid) {
            $assigned = get_post_meta($sid, 'assigned_staff', true) ?: array();
            if (!in_array($post_id, $assigned)) {
                $assigned[] = $post_id;
                update_post_meta($sid, 'assigned_staff', $assigned);
            }
        }

        wp_send_json_success(array('message' => 'Staff member added successfully', 'id' => $post_id));
    } else {
        wp_send_json_error('Failed to add staff member');
    }
}

// Update staff function
function elite_cuts_update_staff($data) {
    $post_id = intval($data['id']);

    // Server-side validation
    $name = isset($data['name']) ? trim(sanitize_text_field($data['name'])) : '';
    $services = isset($data['services']) && is_array($data['services']) ? array_map('absint', $data['services']) : array();

    if (empty($name)) {
        wp_send_json_error(array('message' => 'Staff name is required.'));
    }

    if (empty($services)) {
        wp_send_json_error(array('message' => 'Please assign at least one service to the staff member.'));
    }

    $post_data = array(
        'ID' => $post_id,
        'post_title' => $name,
    );

    $result = wp_update_post($post_data);

    if ($result) {
        update_post_meta($post_id, 'staff_email', sanitize_email($data['email']));
        update_post_meta($post_id, 'staff_phone', sanitize_text_field($data['phone']));
        update_post_meta($post_id, 'staff_status', sanitize_text_field($data['status']));
        update_post_meta($post_id, 'staff_role', sanitize_text_field($data['role']));
        // services update
        $old_services = get_post_meta($post_id, 'staff_services', true) ?: array();
        $services = isset($data['services']) && is_array($data['services']) ? array_map('absint', $data['services']) : array();
        
        if (isset($data['avatar']) && !empty($data['avatar'])) {
            update_post_meta($post_id, 'staff_avatar', esc_url_raw($data['avatar']));
        }
        if (isset($data['avatar_id']) && !empty($data['avatar_id'])) {
            update_post_meta($post_id, 'staff_avatar_id', intval($data['avatar_id']));
            set_post_thumbnail($post_id, intval($data['avatar_id']));
        }

        // Persist services
        update_post_meta($post_id, 'staff_services', $services);

        // Sync removals from assigned_staff
        $removed = array_diff($old_services, $services);
        foreach ($removed as $sid) {
            $assigned = get_post_meta($sid, 'assigned_staff', true) ?: array();
            $assigned = array_values(array_filter($assigned, function($v) use ($post_id){ return intval($v) !== intval($post_id); }));
            update_post_meta($sid, 'assigned_staff', $assigned);
        }
        // Sync additions to assigned_staff
        $added = array_diff($services, $old_services);
        foreach ($added as $sid) {
            $assigned = get_post_meta($sid, 'assigned_staff', true) ?: array();
            if (!in_array($post_id, $assigned)) {
                $assigned[] = $post_id;
                update_post_meta($sid, 'assigned_staff', $assigned);
            }
        }

        wp_send_json_success(array('message' => 'Staff member updated successfully'));
    } else {
        wp_send_json_error('Failed to update staff member');
    }
}

// Delete staff function
function elite_cuts_delete_staff($data) {
    $post_id = intval($data['id']);
    
    if (wp_delete_post($post_id, true)) {
        wp_send_json_success(array('message' => 'Staff member deleted successfully'));
    } else {
        wp_send_json_error('Failed to delete staff member');
    }
}

// Save staff schedule with basic conflict validation against existing bookings
function elite_cuts_save_staff_schedule($data) {
    $staff_id = isset($data['staff_id']) ? intval($data['staff_id']) : 0;
    $date = isset($data['date']) ? sanitize_text_field($data['date']) : '';
    $start = isset($data['start']) ? sanitize_text_field($data['start']) : '';
    $end = isset($data['end']) ? sanitize_text_field($data['end']) : '';

    if (!$staff_id || empty($date) || empty($start) || empty($end)) {
        wp_send_json_error('Missing required fields');
    }

    // validate date and time formats (basic)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) wp_send_json_error('Invalid date format');
    if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) wp_send_json_error('Invalid time format');

    // Build DateTimes in site timezone
    $tz = wp_timezone();
    try {
        $start_dt = new DateTime($date . ' ' . $start, $tz);
        $end_dt = new DateTime($date . ' ' . $end, $tz);
    } catch (Exception $e) {
        wp_send_json_error('Invalid date/time');
    }

    if ($end_dt <= $start_dt) wp_send_json_error('End time must be after start time');

    // Query existing bookings for this staff on the same date range
    $args = array(
        'post_type' => 'service_booking',
        'post_status' => array('publish','pending','draft'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => '_staff_id', 'value' => $staff_id, 'compare' => '='),
            array('key' => '_preferred_date', 'value' => $date, 'compare' => '=')
        )
    );
    $bookings = get_posts($args);

    foreach ($bookings as $b) {
        $b_date = get_post_meta($b->ID, '_preferred_date', true);
        $b_time = get_post_meta($b->ID, '_preferred_time', true);
        if (empty($b_date) || empty($b_time)) continue;
        try {
            $b_start = new DateTime($b_date . ' ' . $b_time, $tz);
        } catch (Exception $e) { continue; }

        // determine booking duration (minutes)
        $service_id = get_post_meta($b->ID, '_service_id', true);
        $raw_dur = '';
        if ($service_id) {
            $raw_dur = get_post_meta($service_id, '_service_duration', true);
            if (empty($raw_dur)) $raw_dur = get_post_meta($service_id, 'service_duration', true);
        }
        if (empty($raw_dur)) $raw_dur = get_post_meta($b->ID, '_service_duration', true);
        // fallback to 60 minutes
        $minutes = 60;
        if (!empty($raw_dur)) {
            if (is_numeric($raw_dur)) $minutes = intval($raw_dur);
            elseif (strpos($raw_dur, ':') !== false) {
                $p = explode(':', $raw_dur);
                $minutes = intval($p[0]) * 60 + intval($p[1] ?? 0);
            } else {
                if (preg_match('/(\d+)\s*hour/i', $raw_dur, $m)) $minutes = intval($m[1]) * 60;
                elseif (preg_match('/(\d+)\s*min/i', $raw_dur, $m)) $minutes = intval($m[1]);
            }
        }

        $b_end = clone $b_start;
        $b_end->modify('+' . intval($minutes) . ' minutes');

        // Check overlap: [start_dt,end_dt) intersects [b_start,b_end)
        if (($start_dt < $b_end) && ($b_start < $end_dt)) {
            wp_send_json_error(array('message' => 'Schedule conflicts with existing bookings', 'conflict_booking_id' => $b->ID));
        }
    }

    // Persist the schedule into staff meta as an array of entries
    $meta = get_post_meta($staff_id, 'staff_schedules', true) ?: array();
    // append this schedule (simple structure)
    $meta[] = array('date' => $date, 'start' => $start, 'end' => $end);
    update_post_meta($staff_id, 'staff_schedules', $meta);

    wp_send_json_success(array('message' => 'Schedule assigned successfully'));
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

// Optional: Front-end shortcode gated by permissions
function elite_cuts_manage_staff_shortcode() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }
    
    ob_start();
    elite_cuts_manage_staff_page();
    return ob_get_clean();
}
// Register an admin-only shortcode under a different tag to avoid clashing with the frontend shortcode
add_shortcode('manage_staff_admin', 'elite_cuts_manage_staff_shortcode');
?>