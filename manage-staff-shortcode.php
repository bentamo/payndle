<?php
/**
 * Staff Management Shortcode
 * Provides a front-end interface for managing staff members via shortcode [manage_staff]
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Helper function to get current business ID
 */
function get_current_business_id() {
    $current_user_id = get_current_user_id();
    $current_business_id = 0;
    
    $user_business = get_posts([
        'post_type' => 'payndle_business',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_business_owner_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($user_business)) {
        $current_business_id = $user_business[0]->ID;
    }
    
    return $current_business_id;
}

// Renderer for the shared staff form. Kept inside this file so shortcode is the single source of truth.
if (!function_exists('payndle_render_staff_form')) {
    function payndle_render_staff_form() {
        // Fetch published services to populate dropdown
        $services = get_posts(array(
            'post_type' => 'service',
            'post_status' => 'publish',
            'numberposts' => -1
        ));

        ob_start();
        ?>
        <!-- Shared Staff Form -->
        <div id="staff-modal" class="elite-modal" style="display: none;">
            <div class="elite-modal-content">
                <div class="elite-modal-header">
                    <h3 id="staff-modal-title"><?php _e('Add New Staff', 'payndle'); ?></h3>
                    <span class="elite-close staff-close">&times;</span>
                </div>
                <div class="elite-modal-body payndle-staff-container">
                    <form id="staff-form" class="payndle-staff-form" novalidate>
                        <input type="hidden" id="staff-id" value="">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-name"><?php _e('Full Name', 'payndle'); ?></label>
                                <input type="text" id="staff-name" class="elite-input" required>
                            </div>
                            <div class="form-group">
                                <label for="staff-service-dropdown"><?php _e('Services', 'payndle'); ?></label>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <select id="staff-service-dropdown" class="elite-select" style="flex:1;">
                                        <option value=""><?php echo esc_html_x('Select a service...', 'placeholder', 'payndle'); ?></option>
                                        <?php foreach ($services as $s) : ?>
                                            <option value="<?php echo esc_attr($s->ID); ?>"><?php echo esc_html($s->post_title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" id="staff-service-add" class="button button-secondary" aria-label="<?php _e('Add service', 'payndle'); ?>"><?php _e('Add', 'payndle'); ?></button>
                                </div>
                                <div id="staff-service-tags" style="margin-top:0.5rem; display:flex; flex-wrap:wrap; gap:6px; align-items:center;"></div>
                                <!-- Hidden inputs for selected services will be appended here as <input type="hidden" name="services[]" value="ID"> -->
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-email"><?php _e('Email', 'payndle'); ?></label>
                                <input type="email" id="staff-email" class="elite-input" required>
                            </div>
                            <div class="form-group">
                                <label for="staff-phone"><?php _e('Phone', 'payndle'); ?></label>
                                <input type="text" id="staff-phone" class="elite-input">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-status"><?php _e('Status', 'payndle'); ?></label>
                                <select id="staff-status" class="elite-select">
                                    <option value="active"><?php _e('Active', 'payndle'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'payndle'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="staff-avatar"><?php _e('Profile Photo', 'payndle'); ?></label>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <div id="staff-avatar-preview-wrap" style="display:flex; align-items:center; gap:0.6rem;">
                                        <img id="staff-avatar-preview" src="" alt="" class="avatar-preview" style="display:none; width:54px; height:54px; border-radius:50%; object-fit:cover;" />
                                        <div id="staff-avatar-placeholder" class="avatar-initial" style="width:54px; height:54px; display:flex; align-items:center; justify-content:center;">?</div>
                                    </div>
                                    <input type="hidden" id="staff-avatar-id" value="">
                                    <input type="hidden" id="staff-avatar" value="">
                                    <input type="file" id="staff-avatar-file" accept="image/*" style="display:none;" />
                                    <div style="display:flex; flex-direction:column; gap:0.4rem;">
                                        <button type="button" id="staff-avatar-upload" class="button button-secondary"><?php _e('Upload / Select', 'payndle'); ?></button>
                                        <div class="help-text"><?php _e('Choose an image file to upload as profile photo.', 'payndle'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <!-- Inline form error container (will be filled by JS when validation fails) -->
                            <div id="staff-form-errors" aria-live="polite" style="display:none; margin-bottom:0.75rem;"></div>
                            <button type="button" class="elite-button secondary staff-cancel"><?php _e('Cancel', 'payndle'); ?></button>
                            <button type="submit" class="elite-button primary"><?php _e('Save Staff', 'payndle'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            /* Small adjustments for staff modal (local to staff form) */
                #staff-modal .payndle-staff-container { padding: 16px; }
                #staff-modal .payndle-staff-form input, #staff-modal .payndle-staff-form select { padding: 10px; border-radius: 10px; border:1px solid #e6eaef; }
            /* Service checkbox list styling to ensure visibility inside modal */
            #staff-service-list { box-sizing: border-box; width:100%; min-height:54px; max-height:220px; overflow-y:auto; padding:8px; }
            #staff-service-list label { display:flex !important; align-items:center !important; gap:0.6rem !important; padding:6px 4px !important; border-radius:6px !important; color: #112233 !important; }
            #staff-service-list label:hover { background: rgba(0,0,0,0.03) !important; }
            #staff-service-list input.service-checkbox { width:18px !important; height:18px !important; margin:0 !important; flex:0 0 18px !important; appearance:auto !important; -webkit-appearance:checkbox !important; }
            #staff-service-list label span { color:#112233 !important; display:inline-block !important; font-size:14px !important; line-height:1.2 !important; }
            /* Ensure the container isn't collapsed by other styles */
            #staff-service-list { background: #fff !important; color: #112233 !important; }
            #staff-service-search { box-sizing: border-box; }
            #staff-service-selectall { transform:scale(1); margin:0; }
            /* Compact service tag styles (override inline styles if present) */
            #staff-service-tags { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
            .service-tag { display:inline-flex !important; align-items:center !important; gap:6px !important; padding:4px 6px !important; border-radius:14px !important; background:#eef6ff !important; border:1px solid #cfe6ff !important; font-size:13px !important; color:#0b2540 !important; }
            .service-tag .tag-label { font-size:12px !important; line-height:1 !important; }
            .service-tag .tag-remove { background:transparent !important; border:none !important; padding:0 4px !important; margin:0 !important; font-size:12px !important; color:#556 !important; cursor:pointer !important; }
            .service-tag input[type="hidden"] { display:none !important; }
        </style>
        <?php
        // Ensure Select2 initializes on pages where shortcode is rendered (safe, checks for jQuery and Select2)
        ?>
        <script type="text/javascript">
        (function(){
            try {
                if (typeof jQuery === 'undefined') return;
                jQuery(function($){
                    var $sel = $('#staff-service');
                    if (!$sel.length) return;
                    // If Select2 plugin is loaded, initialize; otherwise, leave native <select>
                    if (typeof $.fn.select2 === 'function') {
                        var placeholder = (typeof staffManager !== 'undefined' && staffManager.select_placeholder) ? staffManager.select_placeholder : '<?php echo esc_js(__('Select services', 'payndle')); ?>';
                        // Avoid double init
                        if (!$sel.data('select2')) {
                            $sel.select2({ placeholder: placeholder, width: 'resolve', allowClear: true });
                        }
                    }
                });
            } catch(e) { console && console.warn && console.warn('Select2 init error', e); }
        })();
        </script>

        <?php
        // Finish rendering the staff form template
        echo ob_get_clean();
    }
}

/**
 * Register the staff management shortcode
 */
function manage_staff_shortcode($atts) {
    // Enqueue necessary styles and scripts
    // corrected filename: staff-management.css exists in assets/css
    wp_enqueue_style('manage-staff-style', plugin_dir_url(__FILE__) . 'assets/css/staff-management.css');
    // Enqueue Select2 from CDN for nicer multi-select UI
    wp_enqueue_style('select2-core', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
    wp_enqueue_script('select2-core', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    // Enqueue the front-end staff management script (existing file) and ensure underscore is available
    wp_enqueue_script('staff-management-script', plugin_dir_url(__FILE__) . 'assets/js/staff-management.js', array('jquery', 'underscore', 'select2-core'), '1.0.0', true);
    // Ensure WP media scripts are available for upload functionality
    if (function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
    }
    
    // Localize script with AJAX URL and nonce - MOVED TO ENQUEUE FUNCTION
    /*
    wp_localize_script('staff-management-script', 'staffManager', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('staff_management_nonce'),
        'rest_url' => rest_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'confirm_delete' => __('Are you sure you want to delete this staff member?', 'payndle'),
    ));
    */

    // Check user capabilities - allow any logged-in user to match the AJAX handler
    if (!is_user_logged_in()) {
        return '<div class="error-message">' . __('You must be logged in to access this page.', 'payndle') . '</div>';
    }

    // If the admin-side renderer exists, reuse it so shortcode UI matches admin UI exactly.
    if (function_exists('elite_cuts_manage_staff_page')) {
        ob_start();
        elite_cuts_manage_staff_page();
        return ob_get_clean();
    }

    ob_start();
    ?>
    <div class="staff-management-container">
        <div class="staff-header">
            <h2><?php _e('Manage Staff', 'payndle'); ?></h2>
            <button id="add-staff-btn" class="button button-primary">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add New Staff', 'payndle'); ?>
            </button>
        </div>

        <!-- Filters and Search -->
        <div class="staff-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <input type="text" id="staff-search" class="regular-text" placeholder="<?php _e('Search staff...', 'payndle'); ?>">
                </div>
                <div class="filter-group">
                    <select id="filter-role" class="regular-text">
                        <option value=""><?php _e('All Roles', 'payndle'); ?></option>
                        <?php
                        $services_for_filter = get_posts(array(
                            'post_type' => 'service',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                            'fields' => 'ids'
                        ));
                        foreach ($services_for_filter as $sid) {
                            $title = get_the_title($sid);
                            echo '<option value="' . esc_attr($sid) . '">' . esc_html($title) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="filter-status" class="regular-text">
                        <option value=""><?php _e('All Status', 'payndle'); ?></option>
                        <option value="active"><?php _e('Active', 'payndle'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'payndle'); ?></option>
                    </select>
                </div>
                <button id="apply-filters" class="button"><?php _e('Apply', 'payndle'); ?></button>
                <button id="reset-filters" class="button"><?php _e('Reset', 'payndle'); ?></button>
            </div>
        </div>

        <!-- Staff Table -->
        <table class="wp-list-table widefat fixed striped staff-table">
            <colgroup>
                <col style="width:20%;" /> <!-- Staff -->
                <col style="width:20%;" /> <!-- Role -->
                <col style="width:30%;" /> <!-- Email/Contact -->
                <col style="width:10%;" /> <!-- Phone/Availability -->
                <col style="width:3%;"  /> <!-- Status -->
                <col style="width:17%;" /> <!-- Actions -->
            </colgroup>
            <thead>
                <tr>
                    <th><?php _e('Name', 'payndle'); ?></th>
                    <th><?php _e('Role', 'payndle'); ?></th>
                    <th><?php _e('Email', 'payndle'); ?></th>
                    <th><?php _e('Phone', 'payndle'); ?></th>
                    <th><?php _e('Status', 'payndle'); ?></th>
                    <th><?php _e('Actions', 'payndle'); ?></th>
                </tr>
            </thead>
            <tbody id="staff-list">
                <!-- Staff list will be loaded via AJAX -->
                <tr>
                    <td colspan="6" class="loading">
                        <span class="spinner is-active"></span> <?php _e('Loading staff...', 'payndle'); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="staff-pagination">
            <div class="tablenav-pages">
                <span class="displaying-num">0 <?php _e('items', 'payndle'); ?></span>
                <span class="pagination-links">
                    <a class="first-page button" href="#"><span class="screen-reader-text"><?php _e('First page', 'payndle'); ?></span><span aria-hidden="true">«</span></a>
                    <a class="prev-page button" href="#"><span class="screen-reader-text"><?php _e('Previous page', 'payndle'); ?></span><span aria-hidden="true">‹</span></a>
                    <span class="screen-reader-text"><?php _e('Current Page', 'payndle'); ?></span>
                    <span id="table-paging" class="paging-input">
                        <span class="tablenav-paging-text">1 <?php _e('of', 'payndle'); ?> <span class="total-pages">1</span></span>
                    </span>
                    <a class="next-page button" href="#"><span class="screen-reader-text"><?php _e('Next page', 'payndle'); ?></span><span aria-hidden="true">›</span></a>
                    <a class="last-page button" href="#"><span class="screen-reader-text"><?php _e('Last page', 'payndle'); ?></span><span aria-hidden="true">»</span></a>
                </span>
            </div>
        </div>
    </div>

    <!-- Shared Staff Form Template (rendered by this file) -->
    <?php payndle_render_staff_form(); ?>
    
    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal" style="display: none;">
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
    <style>
        /* Minimal modal styles for frontend confirm dialog */
        #confirm-modal { position: fixed !important; z-index: 999999 !important; left: 0 !important; top: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; padding: 1rem; }
        #confirm-modal .modal-content { background: #fff; border-radius: 8px; width: 90%; max-width: 440px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; position: relative; }
        #confirm-modal .modal-header, #confirm-modal .modal-footer { padding: 0.9rem 1rem; border-bottom: 1px solid #e6e9ee; display: flex; align-items: center; justify-content: space-between; }
        #confirm-modal .modal-footer { border-bottom: none; border-top: 1px solid #e6e9ee; justify-content: flex-end; gap: .5rem; }
        #confirm-modal .modal-body { padding: 1rem; }
        #confirm-modal .close { cursor: pointer; font-size: 1.2rem; color: #62708a; }
        .button-danger { background: #F44336; color: #fff; border: none; }
        .button-danger:hover { background: #d63a2f; }
    </style>
    
    <!-- Message Container -->
    <div id="message-container"></div>

    <!-- Avatar Upload Modal (present in DOM for JS to control) -->
    <div id="avatar-upload-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Upload Profile Photo', 'payndle'); ?></h3>
                <span class="close avatar-upload-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="avatar-dropzone" class="avatar-dropzone">
                    <div class="dz-instructions"><?php _e('Drag & drop an image here, or click to select a file', 'payndle'); ?></div>
                    <input type="file" id="avatar-drop-input" accept="image/*" style="display:none;" />
                    <div id="avatar-dz-preview" class="avatar-dz-preview" style="display:none;"></div>
                </div>
                <div class="dz-progress" style="display:none;">
                    <div class="dz-progress-bar" style="width:0%"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="avatar-upload-cancel" class="button"><?php _e('Cancel', 'payndle'); ?></button>
                <button type="button" id="avatar-upload-confirm" class="button button-primary" style="display:none;"><?php _e('Upload', 'payndle'); ?></button>
            </div>
        </div>
    </div>
    
    <script type="text/template" id="staff-row-template">
        <tr data-id="<%= id %>">
            <td class="staff-name">
                <% if (avatar && avatar.length) { %>
                    <div class="staff-cell"><img src="<%= avatar %>" alt="<%= name %>" class="avatar" /><div class="staff-meta"><div class="staff-name-text"><%= name %></div></div></div>
                <% } else { %>
                    <div class="staff-cell"><div class="avatar-initial"><%= (name || '').split(' ').map(function(n){ return n.charAt(0); }).join('').toUpperCase().substring(0,2) %></div><div class="staff-meta"><div class="staff-name-text"><%= name %></div></div></div>
                <% } %>
            </td>
            <td class="staff-role"><%= role %></td>
            <td class="staff-email"><%= email %></td>
            <td class="staff-phone"><%= phone %></td>
            <td class="staff-status">
                <span class="status-badge status-<%= status %>">
                    <%= status.charAt(0).toUpperCase() + status.slice(1) %>
                </span>
            </td>
            <td class="staff-actions">
                <button class="button button-small edit-staff" title="<?php _e('Edit', 'payndle'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button class="button button-small delete-staff" title="<?php _e('Delete', 'payndle'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </td>
        </tr>
    </script>
    
    <script>
    (function(){
        function init() {
    // Production: initialization (debug logs removed)

        // Initialize unified upload system
        function initializeUploadSystem() {
            // initialize upload system
            
            const uploadBtn = document.getElementById('staff-avatar-upload');
            const fileInput = document.getElementById('staff-avatar-file');
            
            if (!uploadBtn) {
                // Upload button not present in this context.
                return;
            }
            
            
            // Check if wp.media is available (admin context)
            const hasWpMedia = typeof wp !== 'undefined' && wp.media;
            // check for wp.media availability
            
            if (hasWpMedia) {
                // Use WordPress media library
                uploadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const frame = wp.media({
                        title: 'Select Profile Photo',
                        multiple: false
                    });
                    
                    frame.on('select', function() {
                        const attachment = frame.state().get('selection').first().toJSON();
                        // media selected
                        
                        if (attachment) {
                            updateAvatarPreview(attachment.url, attachment.id);
                        }
                    });
                    
                    frame.open();
                });
            } else {
                // Use file input + REST API
                uploadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (fileInput) fileInput.click();
                });
                if (fileInput) fileInput.addEventListener('change', handleFileUpload);
            }
        }
        
        function updateAvatarPreview(url, id) {
            // update avatar preview (debug logs removed)
            
            // Update hidden fields
            const avatarInput = document.getElementById('staff-avatar');
            const avatarIdInput = document.getElementById('staff-avatar-id');
            
            if (avatarInput) avatarInput.value = url;
            if (avatarIdInput) avatarIdInput.value = id;
            
            // Update preview
            const preview = document.getElementById('staff-avatar-preview');
            const placeholder = document.getElementById('staff-avatar-placeholder');
            
            if (preview && url) {
                preview.src = url;
                preview.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            }
        }
        
        async function handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const uploadBtn = document.getElementById('staff-avatar-upload');
            if (uploadBtn) {
                uploadBtn.textContent = 'Uploading...';
                uploadBtn.disabled = true;
            }
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                
                const response = await fetch('<?php echo rest_url('payndle/v1/upload-avatar'); ?>', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Upload failed: ' + response.status);
                }
                
                const result = await response.json();
                updateAvatarPreview(result.url, result.id);
                showMessage('Upload successful');
                
            } catch (error) {
                showMessage('Upload failed: ' + error.message, 'error');
            } finally {
                if (uploadBtn) {
                    uploadBtn.textContent = '<?php _e('Upload / Select', 'payndle'); ?>';
                    uploadBtn.disabled = false;
                }
                                            <div class="form-group">
                                                <label for="staff-service-search"><?php _e('Services', 'payndle'); ?></label>
                                                <div style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.5rem;">
                                                    <input type="search" id="staff-service-search" class="elite-input" placeholder="<?php echo esc_attr_x('Filter services...', 'placeholder', 'payndle'); ?>" style="flex:1;" />
                                                    <label style="display:flex; align-items:center; gap:0.4rem; margin-left:0.5rem; font-size:0.9rem;"><input type="checkbox" id="staff-service-selectall" /> <?php _e('All', 'payndle'); ?></label>
                                                </div>
                                                <div id="staff-service-list" style="max-height:180px; overflow:auto; padding:6px; border:1px solid #e6eaef; border-radius:8px; background:#fff;">
                                                    <?php if (!empty($services)) : ?>
                                                        <?php foreach ($services as $s) : ?>
                                                            <label style="display:block; margin:4px 0; font-weight:normal;">
                                                                <input type="checkbox" name="services[]" class="service-checkbox" value="<?php echo esc_attr($s->ID); ?>" />
                                                                <span style="margin-left:0.5rem;"><?php echo esc_html($s->post_title); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="help-text"><?php _e('No services available. Create a service first.', 'payndle'); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
        const staffForm = document.getElementById('staff-form');
        const staffList = document.getElementById('staff-list');
        const addStaffBtn = document.getElementById('add-staff-btn');
        const modal = document.getElementById('staff-modal');
        const confirmModal = document.getElementById('confirm-modal');
        const closeBtns = document.querySelectorAll('.elite-close, .staff-cancel, #confirm-cancel');
        const messageContainer = document.getElementById('message-container');
        
    // Elements
    const templateEl = document.getElementById('staff-row-template');
        
        let staffRowTemplate;
        if (templateEl && typeof _ !== 'undefined') {
            try {
                staffRowTemplate = _.template(templateEl.innerHTML);
            } catch (e) {
                // Fallback to null template if underscore compilation fails
                staffRowTemplate = null;
            }
        } else {
            staffRowTemplate = function(data) {
                const avatar = data.avatar || '';
                const initials = (data.name || '').split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0,2) || '??';
                const photoHtml = avatar.length ? `<img src="${avatar}" alt="${data.name}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />` : `<div class="avatar-initial">${initials}</div>`;
                return `<tr data-id="${data.id}">
                    <td class="staff-name"><div class="staff-cell">${photoHtml}<div class="staff-meta"><div class="staff-name-text">${data.name || ''}</div></div></div></td>
                    <td class="staff-role">${data.role || ''}</td>
                    <td class="staff-email">${data.email || ''}</td>
                    <td class="staff-phone">${data.phone || ''}</td>
                    <td class="staff-status">
                        <span class="status-badge status-${data.status || 'active'}">
                            ${(data.status || 'active').charAt(0).toUpperCase() + (data.status || 'active').slice(1)}
                        </span>
                    </td>
                    <td class="staff-actions">
                        <button class="button button-small edit-staff" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="button button-small delete-staff" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>`;
            };
        }

        function showMessage(message, type = 'success') {
            const messageEl = document.createElement('div');
            messageEl.className = `notice notice-${type} is-dismissible`;
            messageEl.innerHTML = `<p>${message}</p>`;
            messageContainer.insertBefore(messageEl, messageContainer.firstChild);
            setTimeout(() => messageEl.remove(), 5000);
        }

        function openModal() { if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }
        function closeModal() { if (modal) { modal.style.display = 'none'; document.body.style.overflow = 'auto'; staffForm.reset(); document.getElementById('staff-id').value = ''; document.getElementById('staff-modal-title').textContent = '<?php _e('Add New Staff', 'payndle'); ?>'; } }

        function renderStaff(list) {
            staffList.innerHTML = '';
            if (!list || list.length === 0) {
                staffList.innerHTML = `<tr><td colspan="6" class="no-staff"><?php _e('No staff members found. Click "Add New Staff" to get started.', 'payndle'); ?></td></tr>`;
                return;
            }
            list.forEach(s => staffList.insertAdjacentHTML('beforeend', staffRowTemplate(s)));
            attachRowHandlers();
        }

        function attachRowHandlers() {
            document.querySelectorAll('.edit-staff').forEach(btn => btn.addEventListener('click', onEditClick));
            document.querySelectorAll('.delete-staff').forEach(btn => btn.addEventListener('click', onDeleteClick));
        }

        function loadStaff(filters = {}) {
            const postData = { action: 'manage_staff_public', nonce: '<?php echo wp_create_nonce('staff_management_nonce'); ?>', action_type: 'get_staff', data: JSON.stringify(filters) };
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: new URLSearchParams(postData) })
            .then(r => r.json())
            .then(resp => { if (resp && resp.success) renderStaff(resp.data.staff || []); else staffList.innerHTML = '<tr><td colspan="6"><?php _e('Could not load staff', 'payndle'); ?></td></tr>'; })
            .catch(() => staffList.innerHTML = '<tr><td colspan="6"><?php _e('Server error loading staff', 'payndle'); ?></td></tr>');
        }

        function onEditClick(e) {
            const tr = e.currentTarget.closest('tr');
            const id = tr.dataset.id;
            // Fetch single staff record using get_staff with id
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: new URLSearchParams({ action: 'manage_staff_public', nonce: '<?php echo wp_create_nonce('staff_management_nonce'); ?>', action_type: 'get_staff', data: JSON.stringify({ id: id }) }) })
            .then(r => r.json()).then(resp => {
                if (!resp || !resp.success) return showMessage('<?php _e('Could not load staff record', 'payndle'); ?>', 'error');
                const item = (resp.data.staff && resp.data.staff[0]) ? resp.data.staff[0] : null;
                if (!item) return;
                document.getElementById('staff-id').value = item.id;
                document.getElementById('staff-name').value = item.name || '';
                document.getElementById('staff-email').value = item.email || '';
                document.getElementById('staff-phone').value = item.phone || '';
                document.getElementById('staff-status').value = item.status || 'active';
                // set service select
                if (item.services && item.services.length) {
                    // Mark all services assigned to this staff as checked (supports both select and checkboxes)
                    const ids = (item.services || []).map(s => String(s.id));
                    const sel = document.getElementById('staff-service');
                    if (sel && sel.tagName && sel.tagName.toLowerCase() === 'select') {
                        for (let i = 0; i < sel.options.length; i++) {
                            const opt = sel.options[i];
                            opt.selected = ids.indexOf(String(opt.value)) !== -1;
                        }
                    } else {
                        // check checkboxes
                        const checkboxes = document.querySelectorAll('input[name="services[]"]');
                        checkboxes.forEach(cb => { cb.checked = ids.indexOf(String(cb.value)) !== -1; });
                    }
                }
                document.getElementById('staff-modal-title').textContent = '<?php _e('Edit Staff', 'payndle'); ?>';
                openModal();
            }).catch(() => showMessage('<?php _e('Server error', 'payndle'); ?>', 'error'));
        }

        function onDeleteClick(e) {
            const tr = e.currentTarget.closest('tr');
            const id = tr.dataset.id;
            const modal = document.getElementById('confirm-modal');
            const msg = document.getElementById('confirm-message');
            const confirmBtn = document.getElementById('confirm-action');
            const cancelBtn = document.getElementById('confirm-cancel');
            const closeBtn = document.querySelector('#confirm-modal .close');

            // If modal elements missing, do not use native confirm; show inline message and abort
            if (!modal || !confirmBtn || !cancelBtn) {
                return showMessage('<?php _e('Confirmation UI is unavailable. Please refresh and try again.', 'payndle'); ?>', 'error');
            }

            if (msg) msg.textContent = '<?php _e('Are you sure you want to delete this staff member?', 'payndle'); ?>';

            // Ensure modal is at document.body level to avoid stacking/overflow issues
            if (modal && modal.parentNode !== document.body) {
                document.body.appendChild(modal);
            }
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            const cleanup = () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                confirmBtn.removeEventListener('click', onConfirm);
                cancelBtn.removeEventListener('click', onCancel);
                if (closeBtn) closeBtn.removeEventListener('click', onCancel);
            };
            const onCancel = () => cleanup();
            const onConfirm = () => { cleanup(); performDelete(id); };

            confirmBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
            if (closeBtn) closeBtn.addEventListener('click', onCancel);
        }

        function performDelete(id) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: new URLSearchParams({ action: 'manage_staff_public', nonce: '<?php echo wp_create_nonce('staff_management_nonce'); ?>', action_type: 'delete_staff', data: JSON.stringify({ id: id }) }) })
            .then(r => r.json()).then(resp => { if (resp && resp.success) { showMessage(resp.message || '<?php _e('Deleted', 'payndle'); ?>'); loadStaff(); } else showMessage(resp.message || '<?php _e('Could not delete', 'payndle'); ?>', 'error'); })
            .catch(() => showMessage('<?php _e('Server error', 'payndle'); ?>', 'error'));
        }

        // Form submit
        if (staffForm) {
            staffForm.addEventListener('submit', function(e){
                e.preventDefault();
                const id = document.getElementById('staff-id').value || null;
                const nameEl = document.getElementById('staff-name');
                const emailEl = document.getElementById('staff-email');
                const phoneEl = document.getElementById('staff-phone');
                const statusEl = document.getElementById('staff-status');
                const name = nameEl ? (nameEl.value || '') : '';
                const email = emailEl ? (emailEl.value || '') : '';
                const phone = phoneEl ? (phoneEl.value || '') : '';
                const status = statusEl ? (statusEl.value || 'active') : 'active';

                // Collect selected services from common possible locations (checkboxes, hidden inputs, or dropdown)
                const services = [];
                // If a dropdown exists (staff-service-dropdown), prefer its selected value(s)
                const dropdown = document.getElementById('staff-service-dropdown');
                if (dropdown && dropdown.tagName && dropdown.tagName.toLowerCase() === 'select') {
                    for (let i = 0; i < dropdown.options.length; i++) {
                        const opt = dropdown.options[i];
                        if (opt.selected && opt.value) services.push(opt.value);
                    }
                }
                // Also consider any inputs named services[] (checkboxes, hidden inputs added by UI)
                document.querySelectorAll('input[name="services[]"]').forEach(function(el){
                    try {
                        if (!el) return;
                        if (el.type && el.type.toLowerCase() === 'checkbox') {
                            if (el.checked && el.value) services.push(el.value);
                        } else {
                            if (el.value) services.push(el.value);
                        }
                    } catch (err) { /* ignore */ }
                });

                // Deduplicate services
                const uniqueServices = Array.from(new Set(services.map(String))).filter(s => s && s.length);

                // Validate required fields: name, email, service
                if (!name.trim() || !email.trim() || uniqueServices.length === 0) {
                    // show the specific message only when fields missing
                    showMessage('Please fill required fields: service, name and email', 'error');
                    // Focus first missing input for convenience
                    if (!name.trim() && nameEl) {
                        try { nameEl.focus(); } catch(e){}
                    } else if (!email.trim() && emailEl) {
                        try { emailEl.focus(); } catch(e){}
                    }
                    return;
                }

                const actionType = id ? 'update_staff' : 'add_staff';
                const postData = { action: 'manage_staff_public', nonce: '<?php echo wp_create_nonce('staff_management_nonce'); ?>', action_type: actionType, data: JSON.stringify({ id: id, name: name, email: email, phone: phone, status: status, services: uniqueServices }) };
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: new URLSearchParams(postData) })
                .then(r => r.json()).then(resp => { if (resp && resp.success) { showMessage(resp.message || '<?php _e('Saved', 'payndle'); ?>'); closeModal(); loadStaff(); } else showMessage(resp.message || '<?php _e('Could not save', 'payndle'); ?>', 'error'); })
                .catch(() => showMessage('<?php _e('Server error', 'payndle'); ?>', 'error'));
            });
        }

        // Bind add button
        if (addStaffBtn) addStaffBtn.addEventListener('click', function(){ document.getElementById('staff-form').reset(); document.getElementById('staff-id').value = ''; document.getElementById('staff-modal-title').textContent = '<?php _e('Add New Staff', 'payndle'); ?>'; openModal(); });

        // Public filters: Role (Service) Apply/Reset
        const roleFilterEl = document.getElementById('filter-role');
        const applyBtn = document.getElementById('apply-filters');
        const resetBtn = document.getElementById('reset-filters');
        if (applyBtn) applyBtn.addEventListener('click', function(){
            const val = roleFilterEl && roleFilterEl.value ? roleFilterEl.value : '';
            const statusEl = document.getElementById('filter-status');
            const searchEl = document.getElementById('staff-search');
            const filters = {};
            if (/^\d+$/.test(val)) { filters.service_id = parseInt(val, 10); }
            if (statusEl && statusEl.value) { filters.status = statusEl.value; }
            if (searchEl && searchEl.value) { filters.search = searchEl.value.trim(); }
            loadStaff(filters);
        });
        if (resetBtn) resetBtn.addEventListener('click', function(){
            if (roleFilterEl) roleFilterEl.value = '';
            const statusEl = document.getElementById('filter-status');
            const searchEl = document.getElementById('staff-search');
            if (statusEl) statusEl.value = '';
            if (searchEl) searchEl.value = '';
            loadStaff({});
        });

        // close handlers
        closeBtns.forEach(b => b.addEventListener('click', closeModal));

        // Initial load with current selection (if any)
        (function(){
            const val = roleFilterEl && roleFilterEl.value ? roleFilterEl.value : '';
            const filters = {};
            if (/^\d+$/.test(val)) { filters.service_id = parseInt(val, 10); }
            loadStaff(filters);
        })();
        
        } // End of init function
        
    // Production: runtime environment checks removed
        
        // Ensure DOM ready before init (don't block on underscore; init will gracefully handle its absence)
        (function whenDOMReady(fn){
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fn);
            } else {
                // call on next tick to let other scripts finish hooking if needed
                setTimeout(fn, 0);
            }
        })(init);
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('manage_staff', 'manage_staff_shortcode');

// Simple test shortcode to verify shortcodes are working
function payndle_test_shortcode() {
    return '<div style="background: red; color: white; padding: 10px;">TEST SHORTCODE IS WORKING! Use [payndle_test] to see this.</div>';
}
add_shortcode('payndle_test', 'payndle_test_shortcode');

/**
 * AJAX handler for staff management
 */
// Use a dedicated public action name to avoid clashing with admin-only handler
add_action('wp_ajax_manage_staff_public', 'handle_staff_ajax');
add_action('wp_ajax_nopriv_manage_staff_public', 'handle_staff_ajax');
function handle_staff_ajax() {
    // Verify nonce
    check_ajax_referer('staff_management_nonce', 'nonce');

    // Check user capabilities
    // Allow any logged-in user to use the shortcode's staff endpoints (nonce still required).
    // If you want stricter control, replace this with a capability check like current_user_can('manage_options').
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to perform this action', 'payndle'));
    }

    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $raw_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '{}';
    // Allow data to be sent as JSON string or as array
    if (is_string($raw_data)) {
        $data = json_decode($raw_data, true);
        if (!is_array($data)) $data = array();
    } elseif (is_array($raw_data)) {
        $data = $raw_data;
    } else {
        $data = array();
    }
    $response = array('success' => false, 'message' => '');

    try {
        switch ($action) {
            case 'get_staff':
                $paged = isset($data['paged']) ? absint($data['paged']) : 1;
                $per_page = isset($data['per_page']) ? absint($data['per_page']) : 20;
                $search = isset($data['search']) ? sanitize_text_field($data['search']) : '';
                $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';
                $service_id = isset($data['service_id']) ? absint($data['service_id']) : 0;
                $role = isset($data['role']) ? sanitize_text_field($data['role']) : '';
                $id = isset($data['id']) ? absint($data['id']) : 0;

                $current_business_id = get_current_business_id();
                if (!$current_business_id) {
                    throw new Exception(__('No business found. Please create a business profile first.', 'payndle'));
                }

                // Get business code
                $business_code = get_post_meta($current_business_id, '_business_code', true);
                if (!$business_code) {
                    throw new Exception(__('Invalid business code. Please contact support.', 'payndle'));
                }

                $meta_query = array(
                    'relation' => 'AND',
                    array(
                        'key' => '_business_code',
                        'value' => $business_code,
                        'compare' => '='
                    )
                );

                // Add status filter if provided
                if (!empty($status)) {
                    $meta_query[] = array(
                        'key' => 'staff_status',
                        'value' => $status,
                        'compare' => '='
                    );
                }

                $args = array(
                    'post_type' => 'staff',
                    'posts_per_page' => $per_page,
                    'paged' => $paged,
                    'post_status' => 'publish',
                    'meta_query' => $meta_query,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_business_id',
                            'value' => $current_business_id,
                            'compare' => '='
                        )
                    )
                );

                // If specific ID is requested, override other parameters
                if (!empty($id)) {
                    $args['p'] = $id;
                    $args['posts_per_page'] = 1;
                }

                $meta_query = array();
                if (!empty($status)) {
                    $meta_query[] = array(
                        'key' => 'staff_status',
                        'value' => $status,
                        'compare' => '='
                    );
                }

                if (!empty($service_id)) {
                    // Add service filter to meta query
                    $meta_query[] = array(
                        'relation' => 'OR',
                        array(
                            'key' => 'staff_services',
                            'value' => '"' . $service_id . '"',
                            'compare' => 'LIKE'
                        ),
                        array(
                            'key' => 'staff_services',
                            'value' => 'i:' . $service_id . ';',
                            'compare' => 'LIKE'
                        )
                    );
                    // Legacy: some installs used staff_role text equal to service title
                    $s_post = get_post($service_id);
                    if ($s_post && !empty($s_post->post_title)) {
                        $meta_query[] = array(
                            'relation' => 'OR',
                            array(
                                'key' => 'staff_services',
                                'value' => serialize(array($service_id)),
                                'compare' => 'LIKE'
                            ),
                            array(
                                'key' => 'staff_role',
                                'value' => $s_post->post_title,
                                'compare' => '='
                            )
                        );
                    }
                }
                // If role string provided (non-numeric), also match legacy staff_role exactly
                if (!empty($role) && !is_numeric($role)) {
                    $meta_query[] = array(
                        'key' => 'staff_role',
                        'value' => $role,
                        'compare' => '='
                    );
                }

                if (!empty($meta_query)) $args['meta_query'] = $meta_query;
                if (!empty($search)) $args['s'] = $search;

                $query = new WP_Query($args);
                $staff = array();
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $post_id = get_the_ID();
                        $services = get_post_meta($post_id, 'staff_services', true) ?: array();
                        $service_items = array();
                        if (!empty($services) && is_array($services)) {
                            foreach ($services as $sid) {
                                $s_post = get_post($sid);
                                if ($s_post) $service_items[] = array('id' => $sid, 'title' => $s_post->post_title);
                            }
                        }

                        $avatar = get_post_meta($post_id, 'staff_avatar', true);
                        $avatar_id = get_post_meta($post_id, 'staff_avatar_id', true);
                        // If avatar URL is missing but an attachment ID exists, resolve it to a URL
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
                                // Do not force-write back to meta here; just return the resolved URL
                            }
                        }
                        // Fallback to featured image if avatar meta missing
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

                        $staff[] = array(
                            'id' => $post_id,
                            'name' => get_the_title(),
                            'email' => get_post_meta($post_id, 'staff_email', true) ?: get_post_meta($post_id, 'email', true),
                            'phone' => get_post_meta($post_id, 'staff_phone', true) ?: get_post_meta($post_id, 'phone', true),
                            'avatar' => $avatar,
                            'avatar_id' => $avatar_id,
                            'status' => get_post_meta($post_id, 'staff_status', true) ?: 'active',
                            'services' => $service_items
                        );
                    }
                    wp_reset_postdata();
                }

                $response = array(
                    'success' => true,
                    'data' => array(
                        'staff' => $staff,
                        'pagination' => array(
                            'current_page' => $paged,
                            'per_page' => $per_page,
                            'total_items' => (int) $query->found_posts,
                            'total_pages' => (int) $query->max_num_pages
                        )
                    )
                );
                break;

            case 'add_staff':
                $current_business_id = get_current_business_id();
                if (!$current_business_id) {
                    throw new Exception(__('No business found. Please create a business profile first.', 'payndle'));
                }

                $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
                $email = isset($data['email']) ? sanitize_email($data['email']) : '';
                $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
                $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'active';
                $services = isset($data['services']) && is_array($data['services']) ? array_map('absint', $data['services']) : array();

                // Server-side required field validation
                if (empty($name)) throw new Exception(__('Name is required', 'payndle'));
                if (empty($email)) throw new Exception(__('Email is required', 'payndle'));
                if (!empty($email) && !is_email($email)) throw new Exception(__('Invalid email address', 'payndle'));
                if (empty($services) || !is_array($services) || count(array_filter($services)) === 0) throw new Exception(__('Please assign at least one service', 'payndle'));

                // Prevent duplicate staff by email within the same business
                if (!empty($email)) {
                    $existing = get_posts(array(
                        'post_type' => 'staff',
                        'post_status' => 'publish',
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key' => 'email',
                                'value' => $email,
                                'compare' => '='
                            ),
                            array(
                                'key' => '_business_id',
                                'value' => $current_business_id,
                                'compare' => '='
                            )
                        ),
                        'fields' => 'ids',
                        'numberposts' => 1
                    ));
                    if (!empty($existing)) {
                        throw new Exception(sprintf(__('A staff member with that email already exists (ID #%d).', 'payndle'), $existing[0]));
                    }
                }

                // Quick guard: prevent near-simultaneous duplicate creates by title within 3 seconds
                $recent = get_posts(array(
                    'post_type' => 'staff',
                    'post_status' => 'publish',
                    'title' => $name,
                    'numberposts' => 1
                ));
                if (!empty($recent)) {
                    $recent_post = get_post($recent[0]);
                    if ($recent_post) {
                        $diff = time() - strtotime($recent_post->post_date_gmt);
                        if ($diff >= 0 && $diff < 3) {
                            throw new Exception(__('Possible duplicate submission detected; please refresh the page and try again.', 'payndle'));
                        }
                    }
                }

                $post_arr = array(
                    'post_title' => $name,
                    'post_type' => 'staff',
                    'post_status' => 'publish'
                );
                $post_id = wp_insert_post($post_arr);
                if (is_wp_error($post_id) || $post_id == 0) throw new Exception(__('Could not create staff', 'payndle'));

                // Get business code
                $business_code = get_post_meta($current_business_id, '_business_code', true);
                if (!$business_code) {
                    throw new Exception(__('Invalid business code. Please contact support.', 'payndle'));
                }

                // Store business code as primary identifier
                update_post_meta($post_id, '_business_code', $business_code);
                
                // Also store business code in legacy table if needed
                global $wpdb;
                $staff_table = $wpdb->prefix . 'staff_members';
                if ($wpdb->get_var("SHOW TABLES LIKE '$staff_table'") === $staff_table) {
                    $wpdb->update(
                        $staff_table,
                        ['business_code' => $business_code],
                        ['id' => $post_id],
                        ['%s'],
                        ['%d']
                    );
                }

                update_post_meta($post_id, 'email', $email);
                update_post_meta($post_id, 'phone', $phone);
                // avatar fields (optional)
                $avatar = isset($data['avatar']) ? esc_url_raw($data['avatar']) : '';
                $avatar_id = isset($data['avatar_id']) ? absint($data['avatar_id']) : 0;
                if ($avatar) update_post_meta($post_id, 'staff_avatar', $avatar);
                if ($avatar_id) update_post_meta($post_id, 'staff_avatar_id', $avatar_id);
                // set post thumbnail if possible
                if ($avatar_id && function_exists('set_post_thumbnail')) {
                    @set_post_thumbnail($post_id, $avatar_id);
                }
                update_post_meta($post_id, 'staff_status', $status);
                update_post_meta($post_id, 'staff_services', $services);

                // Ensure assigned_staff meta on service posts is synchronized
                payndle_sync_assigned_staff($post_id, $services);

                $response = array('success' => true, 'message' => __('Staff added successfully', 'payndle'), 'data' => array('id' => $post_id));
                break;

            case 'update_staff':
                $current_business_id = get_current_business_id();
                if (!$current_business_id) {
                    throw new Exception(__('No business found. Please create a business profile first.', 'payndle'));
                }

                $post_id = isset($data['id']) ? absint($data['id']) : 0;
                if (!$post_id) {
                    throw new Exception(__('Staff id missing', 'payndle'));
                }

                // Get current business code
                $business_code = get_post_meta($current_business_id, '_business_code', true);
                if (!$business_code) {
                    throw new Exception(__('Invalid business code. Please contact support.', 'payndle'));
                }

                // Verify staff belongs to current business
                $staff_business_code = get_post_meta($post_id, '_business_code', true);
                if (!$staff_business_code) {
                    // Check legacy table
                    global $wpdb;
                    $staff_table = $wpdb->prefix . 'staff_members';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$staff_table'") === $staff_table) {
                        $staff_business_code = $wpdb->get_var($wpdb->prepare(
                            "SELECT business_code FROM $staff_table WHERE id = %d",
                            $post_id
                        ));
                    }
                }
                if ($staff_business_code !== $business_code) {
                    throw new Exception(__('Permission denied: This staff member does not belong to your business.', 'payndle'));
                }
                if (!$post_id) throw new Exception(__('Staff id missing', 'payndle'));
                $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
                $email = isset($data['email']) ? sanitize_email($data['email']) : '';
                $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
                $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'active';
                $services = isset($data['services']) && is_array($data['services']) ? array_map('absint', $data['services']) : array();

                // Server-side required field validation for updates
                if (empty($name)) throw new Exception(__('Name is required', 'payndle'));
                if (empty($email)) throw new Exception(__('Email is required', 'payndle'));
                if (!empty($email) && !is_email($email)) throw new Exception(__('Invalid email address', 'payndle'));
                if (empty($services) || !is_array($services) || count(array_filter($services)) === 0) throw new Exception(__('Please assign at least one service', 'payndle'));

                // Prevent updating to an email that belongs to another staff record
                if (!empty($email)) {
                    $existing = get_posts(array(
                        'post_type' => 'staff',
                        'post_status' => 'publish',
                        'meta_key' => 'email',
                        'meta_value' => $email,
                        'fields' => 'ids',
                        'numberposts' => 1
                    ));
                    if (!empty($existing) && intval($existing[0]) !== intval($post_id)) {
                        throw new Exception(sprintf(__('Another staff member is already using that email (ID #%d).', 'payndle'), $existing[0]));
                    }
                }
                $post_arr = array('ID' => $post_id);
                if (!empty($name)) $post_arr['post_title'] = $name;
                wp_update_post($post_arr);

                $old_services = get_post_meta($post_id, 'staff_services', true) ?: array();
                update_post_meta($post_id, 'email', $email);
                update_post_meta($post_id, 'phone', $phone);
                // avatar fields (optional)
                $avatar = isset($data['avatar']) ? esc_url_raw($data['avatar']) : '';
                $avatar_id = isset($data['avatar_id']) ? absint($data['avatar_id']) : 0;
                if ($avatar !== '') update_post_meta($post_id, 'staff_avatar', $avatar);
                if ($avatar_id) update_post_meta($post_id, 'staff_avatar_id', $avatar_id);
                if ($avatar_id && function_exists('set_post_thumbnail')) {
                    @set_post_thumbnail($post_id, $avatar_id);
                }
                update_post_meta($post_id, 'staff_status', $status);
                update_post_meta($post_id, 'staff_services', $services);
                // Ensure assigned_staff meta on services is synchronized globally
                payndle_sync_assigned_staff($post_id, $services);

                $response = array('success' => true, 'message' => __('Staff updated successfully', 'payndle'));
                break;

            case 'delete_staff':
                $current_business_id = get_current_business_id();
                if (!$current_business_id) {
                    throw new Exception(__('No business found. Please create a business profile first.', 'payndle'));
                }

                $post_id = isset($data['id']) ? absint($data['id']) : 0;
                if (!$post_id) {
                    throw new Exception(__('Staff id missing', 'payndle'));
                }

                // Get current business code
                $business_code = get_post_meta($current_business_id, '_business_code', true);
                if (!$business_code) {
                    throw new Exception(__('Invalid business code. Please contact support.', 'payndle'));
                }

                // Verify staff belongs to current business
                $staff_business_code = get_post_meta($post_id, '_business_code', true);
                if (!$staff_business_code) {
                    // Check legacy table
                    global $wpdb;
                    $staff_table = $wpdb->prefix . 'staff_members';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$staff_table'") === $staff_table) {
                        $staff_business_code = $wpdb->get_var($wpdb->prepare(
                            "SELECT business_code FROM $staff_table WHERE id = %d",
                            $post_id
                        ));
                    }
                }
                if ($staff_business_code !== $business_code) {
                    throw new Exception(__('Permission denied: This staff member does not belong to your business.', 'payndle'));
                }
                if (!$post_id) throw new Exception(__('Staff id missing', 'payndle'));
                // Remove the staff from all service assigned_staff arrays
                payndle_sync_assigned_staff($post_id, array());
                // Delete staff post
                wp_delete_post($post_id, true);
                $response = array('success' => true, 'message' => __('Staff deleted successfully', 'payndle'));
                break;

            default:
                throw new Exception(__('Invalid action', 'payndle'));
        }
    } catch (Exception $e) {
        $response = array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }

    wp_send_json($response);
}

// Handle simple avatar uploads via admin-ajax (fallback when wp.media is unavailable)
add_action('wp_ajax_upload_avatar', 'handle_avatar_upload');
add_action('wp_ajax_nopriv_upload_avatar', 'handle_avatar_upload');
function handle_avatar_upload() {
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('=== AVATAR UPLOAD SERVER DEBUG START ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
    }
    
    if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
    if (!function_exists('wp_generate_attachment_metadata')) require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('Nonce received: ' . $nonce);
    }
    if (!wp_verify_nonce($nonce, 'staff_management_nonce')) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('Nonce verification failed');
        }
        wp_send_json_error(__('Invalid nonce', 'payndle'));
    }
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('Nonce verified successfully');
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('File upload error. FILES: ' . print_r($_FILES, true));
        }
        wp_send_json_error(__('No file uploaded or upload error', 'payndle'));
    }
    
    $file = $_FILES['file'];
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('Processing file: ' . $file['name']);
    }
    $overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file, $overrides);
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('wp_handle_upload result: ' . print_r($movefile, true));
    }
    
    if (!$movefile || isset($movefile['error'])) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('wp_handle_upload failed: ' . ($movefile['error'] ?? 'Unknown error'));
        }
        wp_send_json_error($movefile['error'] ?? __('Upload failed', 'payndle'));
    }

    // Insert attachment
    $wp_filetype = wp_check_filetype($movefile['file'], null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name(basename($movefile['file'])),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('Creating attachment with data: ' . print_r($attachment, true));
    }
    $attach_id = wp_insert_attachment($attachment, $movefile['file']);
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('wp_insert_attachment result: ' . $attach_id);
    }
    
    if (is_wp_error($attach_id)) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('wp_insert_attachment failed: ' . $attach_id->get_error_message());
        }
        wp_send_json_error($attach_id->get_error_message());
    }
    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    $response_data = array('id' => $attach_id, 'url' => $movefile['url']);
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('Success response: ' . print_r($response_data, true));
        error_log('=== AVATAR UPLOAD SERVER DEBUG END ===');
    }
    wp_send_json_success($response_data);
}

// REST API endpoint for avatar upload: /wp-json/payndle/v1/upload-avatar
add_action('rest_api_init', function() {
    register_rest_route('payndle/v1', '/upload-avatar', array(
        'methods' => 'POST',
        'callback' => 'payndle_rest_upload_avatar',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});

function payndle_rest_upload_avatar(WP_REST_Request $request) {
    // Accept file from request - support both 'file' field and raw body
    if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
    if (!function_exists('wp_generate_attachment_metadata')) require_once(ABSPATH . 'wp-admin/includes/image.php');

    $files = $request->get_file_params();
    if (empty($files['file'])) {
        return new WP_REST_Response(array('code' => 'no_file', 'message' => __('No file uploaded', 'payndle')), 400);
    }

    $file = $files['file'];
    $overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file, $overrides);
    if (!$movefile || isset($movefile['error'])) {
        return new WP_REST_Response(array('code' => 'upload_error', 'message' => ($movefile['error'] ?? __('Upload failed', 'payndle'))), 500);
    }

    $wp_filetype = wp_check_filetype($movefile['file'], null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name(basename($movefile['file'])),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $movefile['file']);
    if (is_wp_error($attach_id)) {
        return new WP_REST_Response(array('code' => 'attach_error', 'message' => $attach_id->get_error_message()), 500);
    }
    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return new WP_REST_Response(array('id' => $attach_id, 'url' => $movefile['url']), 200);
}

/**
 * Migration function to handle existing staff records
 */
function migrate_staff_business_ids() {
    // Only run once
    if (get_option('staff_business_id_migration_complete')) {
        return;
    }

    // Get all businesses first
    $businesses = get_posts(array(
        'post_type' => 'payndle_business',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));

    if (empty($businesses)) {
        return; // No businesses to migrate
    }

    $staff = get_posts(array(
        'post_type' => 'staff',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => 'business_id',
                'compare' => 'NOT EXISTS'
            )
        )
    ));

    foreach ($staff as $member) {
        $business_id = get_post_meta($member->ID, '_business_id', true);
        if (!$business_id) {
            // Try to find business ID from related services
            $services = get_post_meta($member->ID, 'staff_services', true);
            if (!empty($services) && is_array($services)) {
                foreach ($services as $service_id) {
                    $service_business_id = get_post_meta($service_id, '_business_id', true);
                    if ($service_business_id) {
                        update_post_meta($member->ID, 'business_id', $service_business_id);
                        break;
                    }
                }
            }
        }
    }

    update_option('staff_business_id_migration_complete', true);
}

// Run migration on plugin update or activation
add_action('init', 'migrate_staff_business_ids');


/**
 * Register a simple staff post type for storing staff records
 */
function payndle_register_staff_cpt() {
    $labels = array(
        'name' => __('Staff', 'payndle'),
        'singular_name' => __('Staff', 'payndle'),
        'add_new_item' => __('Add New Staff', 'payndle'),
        'edit_item' => __('Edit Staff', 'payndle'),
    );
    register_post_type('staff', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'capabilities' => array(),
        'map_meta_cap' => true,
    ));
}
add_action('init', 'payndle_register_staff_cpt');

/**
 * Create sample staff for testing (only if none exist)
 */
function payndle_create_sample_staff() {
    // Only create sample data if no staff exists
    $existing_staff = get_posts(array(
        'post_type' => 'staff',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (empty($existing_staff)) {
        // Create a sample staff member
        $staff_id = wp_insert_post(array(
            'post_title' => 'John Doe',
            'post_type' => 'staff',
            'post_status' => 'publish'
        ));
        
        if ($staff_id && !is_wp_error($staff_id)) {
            update_post_meta($staff_id, 'email', 'john.doe@example.com');
            update_post_meta($staff_id, 'phone', '(555) 123-4567');
            update_post_meta($staff_id, 'staff_status', 'active');
            update_post_meta($staff_id, 'staff_services', array());
        }
    }
}
// Hook this to admin_init to create sample data when visiting admin
add_action('admin_init', 'payndle_create_sample_staff');

/**
 * Enqueue admin styles and scripts
 */
function enqueue_staff_management_assets($hook) {
    // Only load on pages that use the shortcode OR on admin pages
    global $post;
    
    $should_load = false;
    
    // Load on admin pages that might use staff management
    if (is_admin() && (strpos($hook, 'staff') !== false || strpos($hook, 'manage') !== false)) {
        $should_load = true;
    }
    
    // Load on frontend pages with the shortcode
    if (!is_admin() && $post && has_shortcode($post->post_content, 'manage_staff')) {
        $should_load = true;
    }
    
    if ($should_load) {
        // Enqueue WordPress media uploader only if user can upload files
        if (function_exists('wp_enqueue_media') && current_user_can('upload_files')) {
            wp_enqueue_media();
        }
        
        // Enqueue custom styles
        wp_enqueue_style('staff-management-style', 
            plugin_dir_url(__FILE__) . 'assets/css/staff-management.css',
            array(),
            '1.0.2'
        );

        // Enqueue custom script
        wp_enqueue_script('staff-management-script',
            plugin_dir_url(__FILE__) . 'assets/js/staff-management.js',
            array('jquery', 'underscore'),
            '1.0.2',
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('staff-management-script', 'staffManager', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('staff_management_nonce'),
            'rest_url' => rest_url(),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'context' => is_admin() ? 'admin' : 'frontend',
            'ajax_action' => 'manage_staff_public',
            'confirm_delete' => __('Are you sure you want to delete this staff member?', 'payndle'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_staff_management_assets');

/**
 * Keep assigned_staff meta on service posts synchronized.
 * For a given staff_id, ensure only the provided $service_ids include that staff in their assigned_staff meta.
 * If $service_ids is empty, remove the staff from all services.
 */
function payndle_sync_assigned_staff($staff_id, $service_ids = array()) {
    $staff_id = absint($staff_id);
    $service_ids = array_map('absint', (array)$service_ids);

    // Get all published services to iterate (could be narrowed, but safe)
    $all_services = get_posts(array('post_type' => 'service', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids'));
    if (empty($all_services)) return;

    foreach ($all_services as $sid) {
        $assigned = get_post_meta($sid, 'assigned_staff', true) ?: array();
        $assigned = array_map('absint', (array)$assigned);
        $has = in_array($staff_id, $assigned, true);
        $should = in_array($sid, $service_ids, true);

        if ($should && !$has) {
            $assigned[] = $staff_id;
            update_post_meta($sid, 'assigned_staff', array_values(array_unique($assigned)));
        } elseif (!$should && $has) {
            $assigned = array_values(array_filter($assigned, function($v) use ($staff_id) { return intval($v) !== intval($staff_id); }));
            update_post_meta($sid, 'assigned_staff', $assigned);
        }
    }
}