<?php
/**
 * Staff Management Shortcode
 * Provides a front-end interface for managing staff members via shortcode [manage_staff]
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the staff management shortcode
 */
function manage_staff_shortcode($atts) {
    // Enqueue necessary styles and scripts
    wp_enqueue_style('manage-staff-style', plugin_dir_url(__FILE__) . 'assets/css/manage-staff.css');
    wp_enqueue_script('manage-staff-script', plugin_dir_url(__FILE__) . 'assets/js/manage-staff.js', array('jquery'), '1.0.0', true);
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('manage-staff-script', 'staffManager', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('staff_management_nonce'),
        'confirm_delete' => __('Are you sure you want to delete this staff member?', 'payndle'),
    ));

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return '<div class="error-message">' . __('You do not have sufficient permissions to access this page.', 'payndle') . '</div>';
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
                        <option value="barber"><?php _e('Barber', 'payndle'); ?></option>
                        <option value="stylist"><?php _e('Stylist', 'payndle'); ?></option>
                        <option value="receptionist"><?php _e('Receptionist', 'payndle'); ?></option>
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

    <!-- Staff Form Modal -->
    <div id="staff-form-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title"><?php _e('Add New Staff', 'payndle'); ?></h3>
                <span class="close">&times;</span>
            </div>
            <form id="staff-form" class="staff-form">
                <input type="hidden" id="staff-id" name="staff_id" value="">
                
                <div class="form-group">
                    <label for="staff-name"><?php _e('Full Name', 'payndle'); ?> *</label>
                    <input type="text" id="staff-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="staff-role"><?php _e('Role', 'payndle'); ?> *</label>
                    <select id="staff-role" name="role" required>
                        <option value=""><?php _e('Select Role', 'payndle'); ?></option>
                        <option value="barber"><?php _e('Barber', 'payndle'); ?></option>
                        <option value="stylist"><?php _e('Stylist', 'payndle'); ?></option>
                        <option value="receptionist"><?php _e('Receptionist', 'payndle'); ?></option>
                        <option value="manager"><?php _e('Manager', 'payndle'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="staff-email"><?php _e('Email', 'payndle'); ?> *</label>
                    <input type="email" id="staff-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="staff-phone"><?php _e('Phone', 'payndle'); ?></label>
                    <input type="tel" id="staff-phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="staff-status"><?php _e('Status', 'payndle'); ?></label>
                    <select id="staff-status" name="status">
                        <option value="active"><?php _e('Active', 'payndle'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'payndle'); ?></option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-staff-form" class="button"><?php _e('Cancel', 'payndle'); ?></button>
                    <button type="submit" class="button button-primary">
                        <span id="save-button-text"><?php _e('Save Staff', 'payndle'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
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
    
    <!-- Message Container -->
    <div id="message-container"></div>
    
    <script type="text/template" id="staff-row-template">
        <tr data-id="<%= id %>">
            <td class="staff-name"><%= name %></td>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize staff data in localStorage if not exists
        if (!localStorage.getItem('staffData')) {
            const initialStaff = [
                {
                    id: 1,
                    name: 'John Doe',
                    role: 'barber',
                    email: 'john@example.com',
                    phone: '123-456-7890',
                    status: 'active'
                },
                {
                    id: 2,
                    name: 'Jane Smith',
                    role: 'stylist',
                    email: 'jane@example.com',
                    phone: '098-765-4321',
                    status: 'active'
                }
            ];
            localStorage.setItem('staffData', JSON.stringify(initialStaff));
            localStorage.setItem('staffNextId', '3');
        }
        
        // DOM Elements
        const staffForm = document.getElementById('staff-form');
        const staffTable = document.querySelector('.staff-table');
        const staffList = document.getElementById('staff-list');
        const addStaffBtn = document.getElementById('add-staff-btn');
        const modal = document.getElementById('staff-form-modal');
        const confirmModal = document.getElementById('confirm-modal');
        const closeBtns = document.querySelectorAll('.close, #cancel-staff-form, #confirm-cancel');
        const deleteBtns = document.querySelectorAll('.delete-staff');
        const editBtns = document.querySelectorAll('.edit-staff');
        const confirmActionBtn = document.getElementById('confirm-action');
        const messageContainer = document.getElementById('message-container');
        
        // Templates
        const staffRowTemplate = _.template(document.getElementById('staff-row-template').innerHTML);
        
        // Load staff data
        function loadStaff() {
            const staffData = JSON.parse(localStorage.getItem('staffData') || '[]');
            staffList.innerHTML = '';
            
            if (staffData.length === 0) {
                staffList.innerHTML = `
                    <tr>
                        <td colspan="6" class="no-staff">
                            <?php _e('No staff members found. Click "Add New Staff" to get started.', 'payndle'); ?>
                        </td>
                    </tr>`;
                return;
            }
            
            staffData.forEach(staff => {
                staffList.insertAdjacentHTML('beforeend', staffRowTemplate(staff));
            });
            
            // Re-attach event listeners
            attachEventListeners();
        }
        
        // Show modal
        function showModal() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Hide modal
        function hideModal() {
            modal.style.display = 'none';
            confirmModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            staffForm.reset();
            document.getElementById('staff-id').value = '';
            document.getElementById('modal-title').textContent = '<?php _e('Add New Staff', 'payndle'); ?>';
        }
        
        // Show confirmation dialog
        function showConfirm(message, callback) {
            document.getElementById('confirm-message').textContent = message;
            confirmModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            confirmActionBtn.onclick = function() {
                callback();
                hideModal();
            };
        }
        
        // Show message
        function showMessage(message, type = 'success') {
            const messageEl = document.createElement('div');
            messageEl.className = `notice notice-${type} is-dismissible`;
            messageEl.innerHTML = `
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            `;
            
            messageContainer.insertBefore(messageEl, messageContainer.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                messageEl.remove();
            }, 5000);
            
            // Add click handler for dismiss button
            messageEl.querySelector('.notice-dismiss').addEventListener('click', () => {
                messageEl.remove();
            });
        }
        
        // Save staff member
        function saveStaff(staffData) {
            const staff = JSON.parse(localStorage.getItem('staffData') || '[]');
            
            if (staffData.id) {
                // Update existing staff
                const index = staff.findIndex(s => s.id == staffData.id);
                if (index !== -1) {
                    staff[index] = { ...staff[index], ...staffData };
                    showMessage('<?php _e('Staff member updated successfully!', 'payndle'); ?>');
                }
            } else {
                // Add new staff
                const nextId = parseInt(localStorage.getItem('staffNextId') || '1');
                staffData.id = nextId;
                staff.push(staffData);
                localStorage.setItem('staffNextId', (nextId + 1).toString());
                showMessage('<?php _e('Staff member added successfully!', 'payndle'); ?>');
            }
            
            localStorage.setItem('staffData', JSON.stringify(staff));
            loadStaff();
            hideModal();
        }
        
        // Delete staff member
        function deleteStaff(id) {
            const staff = JSON.parse(localStorage.getItem('staffData') || '[]');
            const updatedStaff = staff.filter(s => s.id != id);
            
            localStorage.setItem('staffData', JSON.stringify(updatedStaff));
            showMessage('<?php _e('Staff member deleted successfully!', 'payndle'); ?>');
            loadStaff();
        }
        
        // Attach event listeners
        function attachEventListeners() {
            // Add staff button
            addStaffBtn.addEventListener('click', () => {
                staffForm.reset();
                document.getElementById('staff-id').value = '';
                document.getElementById('modal-title').textContent = '<?php _e('Add New Staff', 'payndle'); ?>';
                showModal();
            });
            
            // Edit staff button
            document.querySelectorAll('.edit-staff').forEach(btn => {
                btn.addEventListener('click', function() {
                    const staffId = this.closest('tr').dataset.id;
                    const staff = JSON.parse(localStorage.getItem('staffData') || '[]');
                    const staffMember = staff.find(s => s.id == staffId);
                    
                    if (staffMember) {
                        Object.keys(staffMember).forEach(key => {
                            const input = document.getElementById(`staff-${key}`);
                            if (input) {
                                input.value = staffMember[key];
                            }
                        });
                        
                        document.getElementById('modal-title').textContent = '<?php _e('Edit Staff', 'payndle'); ?>';
                        showModal();
                    }
                });
            });
            
            // Delete staff button
            document.querySelectorAll('.delete-staff').forEach(btn => {
                btn.addEventListener('click', function() {
                    const staffId = this.closest('tr').dataset.id;
                    const staffName = this.closest('tr').querySelector('.staff-name').textContent;
                    
                    showConfirm(
                        `<?php _e('Are you sure you want to delete', 'payndle'); ?> ${staffName}?`,
                        () => deleteStaff(staffId)
                    );
                });
            });
        }
        
        // Form submission
        staffForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const staffData = {
                id: formData.get('staff_id') || null,
                name: formData.get('name'),
                role: formData.get('role'),
                email: formData.get('email'),
                phone: formData.get('phone') || '',
                status: formData.get('status') || 'active'
            };
            
            saveStaff(staffData);
        });
        
        // Close modals
        closeBtns.forEach(btn => {
            btn.addEventListener('click', hideModal);
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === modal) hideModal();
            if (e.target === confirmModal) hideModal();
        });
        
        // Initialize
        loadStaff();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('manage_staff', 'manage_staff_shortcode');

/**
 * AJAX handler for staff management
 */
add_action('wp_ajax_manage_staff', 'handle_staff_ajax');
function handle_staff_ajax() {
    // Verify nonce
    check_ajax_referer('staff_management_nonce', 'nonce');

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions', 'payndle'));
    }

    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $data = isset($_POST['data']) ? $_POST['data'] : array();
    $response = array('success' => false, 'message' => '');

    try {
        switch ($action) {
            case 'get_staff':
                // Get staff list with pagination
                $paged = isset($data['paged']) ? absint($data['paged']) : 1;
                $per_page = isset($data['per_page']) ? absint($data['per_page']) : 10;
                $search = isset($data['search']) ? sanitize_text_field($data['search']) : '';
                $role = isset($data['role']) ? sanitize_text_field($data['role']) : '';
                $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';

                $args = array(
                    'role__in' => array('administrator', 'editor', 'author', 'contributor'),
                    'number' => $per_page,
                    'paged' => $paged,
                    'meta_query' => array()
                );

                // Add search
                if (!empty($search)) {
                    $args['search'] = '*' . $search . '*';
                    $args['search_columns'] = array('user_login', 'user_email', 'display_name', 'user_nicename');
                }

                // Add role filter
                if (!empty($role)) {
                    $args['role'] = $role;
                }

                // Add status filter
                if (!empty($status)) {
                    $args['meta_query'][] = array(
                        'key' => 'staff_status',
                        'value' => $status,
                        'compare' => '='
                    );
                }

                // Get users
                $user_query = new WP_User_Query($args);
                $staff = array();

                if (!empty($user_query->get_results())) {
                    foreach ($user_query->get_results() as $user) {
                        $staff[] = array(
                            'id' => $user->ID,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'email' => $user->user_email,
                            'phone' => get_user_meta($user->ID, 'phone', true),
                            'role' => !empty($user->roles[0]) ? $user->roles[0] : '',
                            'status' => get_user_meta($user->ID, 'staff_status', true) ?: 'active',
                            'bio' => get_user_meta($user->ID, 'description', true)
                        );
                    }
                }

                $response = array(
                    'success' => true,
                    'data' => array(
                        'staff' => $staff,
                        'pagination' => array(
                            'current_page' => $paged,
                            'per_page' => $per_page,
                            'total_items' => $user_query->total_users,
                            'total_pages' => ceil($user_query->total_users / $per_page)
                        )
                    )
                );
                break;

            case 'add_staff':
                // TODO: Implement add staff logic
                $response = array(
                    'success' => true,
                    'message' => __('Staff added successfully', 'payndle'),
                    'data' => $data
                );
                break;

            case 'update_staff':
                // TODO: Implement update staff logic
                $response = array(
                    'success' => true,
                    'message' => __('Staff updated successfully', 'payndle'),
                    'data' => $data
                );
                break;

            case 'delete_staff':
                // TODO: Implement delete staff logic
                $response = array(
                    'success' => true,
                    'message' => __('Staff deleted successfully', 'payndle')
                );
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

/**
 * Enqueue admin styles and scripts
 */
function enqueue_staff_management_assets($hook) {
    // Only load on pages that use the shortcode
    global $post;
    if (!is_admin() && (has_shortcode($post->post_content, 'manage_staff'))) {
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue custom styles
        wp_enqueue_style('staff-management-style', 
            plugin_dir_url(__FILE__) . 'assets/css/staff-management.css',
            array(),
            '1.0.0'
        );

        // Enqueue custom script
        wp_enqueue_script('staff-management-script',
            plugin_dir_url(__FILE__) . 'assets/js/staff-management.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_staff_management_assets');
