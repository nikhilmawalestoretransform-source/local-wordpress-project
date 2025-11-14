<?php

/**
 * Employee Management - Admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'emp_management_admin_menu');

function emp_management_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Admin Emp Manage',
        'Admin Emp Manage',
        'manage_options',
        'emp-management',
        'emp_management_admin_page'
    );
}

// AJAX handlers for employee management
add_action('wp_ajax_get_employees_admin', 'ajax_get_employees_admin');
add_action('wp_ajax_add_emp', 'ajax_add_emp');
add_action('wp_ajax_update_emp', 'ajax_update_emp');
add_action('wp_ajax_delete_emp', 'ajax_delete_emp');

function emp_management_admin_page() {
    // Create tables if they don't exist
    create_emp_management_tables();
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    ?>
    <div class="wrap">
        <h1>👨‍💼 Employee Management</h1>
        
        <style>
            .emp-tabs {

                border: 2px solid;
             }
            .emp-management-container {
                margin: 20px 0;
            }
            .emp-tabs {
                display: flex;
                margin-bottom: 20px;
                background: #f0f0f1;
                border-radius: 8px;
                padding: 5px;
            }
            .emp-tab-btn {
                flex: 1;
                padding: 12px 20px;
                border: none;
                background: transparent;
                color: #2c3338;
                cursor: pointer;
                border-radius: 6px;
                transition: all 0.3s ease;
                font-weight: 600;
                font-size: 15px;
            }
            .emp-tab-btn.active {
                background: #0073aa;
                color: white;
                box-shadow: 0 2px 5px rgba(40,167,69,0.3);
            }
            .emp-tab-content {
                display: none;
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .emp-tab-content.active {
                display: block;
            }
            .emp-form {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
            }
            .emp-form input,
            .emp-form select {
                margin: 5px;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-width: 250px;
                font-size: 14px;
            }
            .emp-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .emp-table th,
            .emp-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            .emp-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .emp-table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .emp-table tr:hover {
                background-color: #e9ecef;
            }
            .status-active { 
                color: #28a745; 
                font-weight: bold; 
            }
            .status-inactive { 
                color: #dc3545; 
                font-weight: bold; 
            }
            .btn { 
                padding: 8px 15px; 
                margin: 2px; 
                cursor: pointer; 
                border: none; 
                border-radius: 4px; 
                font-size: 14px;
                transition: all 0.3s ease;
            }
            .btn-primary { 
                background: #0073aa; 
                color: white; 
            }
            .btn-primary:hover {
                background: #005a87;
            }
            .btn-warning { 
                background: #ffb900; 
                color: black; 
            }
            .btn-warning:hover {
                background: #e6a800;
            }
            .btn-danger { 
                background: #dc3232; 
                color: white; 
            }
            .btn-danger:hover {
                background: #c12c2c;
            }
            .btn-secondary { 
                background: #6c757d; 
                color: white; 
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .form-actions {
                margin-top: 15px;
            }
            .loading {
                opacity: 0.6;
                pointer-events: none;
            }
            .error-message {
                color: #dc3545;
                font-size: 12px;
                margin-top: 5px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #333;
            }
            /* Pagination Styles */
            .pagination {
                margin: 20px 0;
                text-align: center;
            }
            .pagination button {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                padding: 8px 12px;
                margin: 0 2px;
                cursor: pointer;
                border-radius: 4px;
                font-size: 14px;
            }
            .pagination button:hover:not(:disabled) {
                background: #e9ecef;
            }
            .pagination button.active {
                background: #007cba;
                color: white;
                border-color: #007cba;
            }
            .pagination button:disabled {
                background: #f8f9fa;
                color: #6c757d;
                cursor: not-allowed;
            }
            .pagination-info {
                text-align: center;
                margin: 10px 0;
                color: #6c757d;
                font-size: 14px;
            }
        </style>

        <div class="emp-management-container">
            <!-- Employee Management Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab-btn active" onclick="showEmpTab('add-emp-tab', this)">➕ Add Employee</button>
                <button class="emp-tab-btn" onclick="showEmpTab('list-emps-tab', this)">📋 List Employees</button>
            </div>

            <!-- Add Employee Tab -->
            <div id="add-emp-tab" class="emp-tab-content active">
                <div class="emp-form">
                    <h3>➕ Add New Employee</h3>
                    <form id="emp-form">
                        <input type="hidden" name="action" value="add_emp">
                        <input type="hidden" name="emp_id" id="emp_id" value="">
                        
                        <div class="form-group">
                            <label for="emp_name">Employee Name *</label>
                            <input type="text" name="emp_name" id="emp_name" placeholder="Enter employee name" required>
                            <div id="emp_name_error" class="error-message" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emp_email">Email *</label>
                            <input type="email" name="emp_email" id="emp_email" placeholder="Enter employee email" required>
                            <div id="emp_email_error" class="error-message" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emp_position">Position *</label>
                            <input type="text" name="emp_position" id="emp_position" placeholder="Enter employee position" required>
                            <div id="emp_position_error" class="error-message" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emp_status">Status *</label>
                            <select name="emp_status" id="emp_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="emp_submit_btn" onclick="saveEmployee()">➕ Add Employee</button>
                            <button type="button" class="btn btn-secondary" onclick="resetEmpForm()" id="emp_cancel_btn" style="display:none;">❌ Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Employees Tab -->
            <div id="list-emps-tab" class="emp-tab-content">
                <h3>📋 Employee List</h3>
                <div id="emp-table-container">
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                        <p>Loading employees...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                console.log('Employee Management loaded');
                // Load employees when page loads
                loadEmployees(currentPage);
            });

            function showEmpTab(tabName, element) {
                // Hide all tab contents
                document.querySelectorAll('.emp-tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all tab buttons
                document.querySelectorAll('.emp-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected tab and activate button
                document.getElementById(tabName).classList.add('active');
                element.classList.add('active');
                
                // If switching to list tab, reload employees
                if (tabName === 'list-emps-tab') {
                    loadEmployees(currentPage);
                }
            }

            function loadEmployees(page = 1) {
                currentPage = page;
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_employees_admin',
                        nonce: nonce,
                        page: page
                    },
                    beforeSend: function() {
                        const container = document.getElementById('emp-table-container');
                        if (container) {
                            container.innerHTML = '<div style="text-align: center; padding: 20px;">Loading employees...</div>';
                        }
                    },
                    success: function(response) {
                        console.log('AJAX Success - Full Response:', response);
                        
                        if (response && response.success) {
                            console.log('Employees Data:', response.data);
                            displayEmployees(response.data.employees, response.data.pagination);
                        } else {
                            console.error('Server returned error:', response);
                            let errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                            showAlert('Error: ' + errorMsg, 'error');
                            displayEmployees([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response Text:', xhr.responseText);
                        
                        let errorMsg = 'Failed to load employees. Please try again.';
                        showAlert(errorMsg, 'error');
                        displayEmployees([], null);
                    }
                });
            }

            function displayEmployees(employees, pagination) {
                const container = document.getElementById('emp-table-container');
                if (!container) return;
                
                console.log('Displaying employees:', employees);
                
                if (!employees || employees.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6c757d;">No employees found. Add your first employee in the "Add Employee" tab.</div>';
                    return;
                }
                
                let html = `
                    <table class="emp-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                employees.forEach(emp => {
                    // Escape special characters for JavaScript
                    const escapedName = emp.emp_name ? emp.emp_name.replace(/'/g, "\\'").replace(/"/g, '\\"') : '';
                    const escapedEmail = emp.emp_email ? emp.emp_email.replace(/'/g, "\\'").replace(/"/g, '\\"') : '';
                    const escapedPosition = emp.emp_position ? emp.emp_position.replace(/'/g, "\\'").replace(/"/g, '\\"') : '';
                    
                    html += `
                        <tr>
                            <td>${emp.id}</td>
                            <td>${emp.emp_name}</td>
                            <td>${emp.emp_email}</td>
                            <td>${emp.emp_position}</td>
                            <td class="status-${emp.emp_status}">${emp.emp_status ? emp.emp_status.charAt(0).toUpperCase() + emp.emp_status.slice(1) : 'N/A'}</td>
                            <td>${formatDate(emp.created_at)}</td>
                            <td>${formatDate(emp.updated_at)}</td>
                            <td>
                                <button class="btn btn-warning" onclick="editEmployee(${emp.id}, '${escapedName}', '${escapedEmail}', '${escapedPosition}', '${emp.emp_status}')">✏️ Edit</button>
                                <button class="btn btn-danger" onclick="deleteEmployee(${emp.id})">🗑️ Delete</button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;

                // Add pagination info
                if (pagination) {
                    const startItem = ((pagination.current_page - 1) * pagination.per_page) + 1;
                    const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
                    
                    html += `
                        <div class="pagination-info">
                            Showing ${startItem} to ${endItem} of ${pagination.total_items} employees
                        </div>
                        ${generatePagination(pagination)}
                    `;
                }
                
                html += `
                        <div style="margin-top: 15px; color: #666; font-size: 12px;">
                            Total: ${pagination ? pagination.total_items : employees.length} employee(s)
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
            }

            function generatePagination(pagination) {
                if (!pagination || pagination.total_pages <= 1) return '';
                
                let html = '<div class="pagination">';
                
                // Previous button
                html += `<button onclick="loadEmployees(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>‹ Previous</button>`;
                
                // Page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);
                
                // Adjust start page if we're near the end
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                // First page
                if (startPage > 1) {
                    html += `<button onclick="loadEmployees(1)">1</button>`;
                    if (startPage > 2) html += `<button disabled>...</button>`;
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    html += `<button onclick="loadEmployees(${i})" ${i === pagination.current_page ? 'class="active"' : ''}>${i}</button>`;
                }
                
                // Last page
                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) html += `<button disabled>...</button>`;
                    html += `<button onclick="loadEmployees(${pagination.total_pages})">${pagination.total_pages}</button>`;
                }
                
                // Next button
                html += `<button onclick="loadEmployees(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next ›</button>`;
                
                html += '</div>';
                return html;
            }

            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                } catch (e) {
                    return dateString;
                }
            }

            function saveEmployee() {
                const form = document.getElementById('emp-form');
                const submitBtn = document.getElementById('emp_submit_btn');
                const nameError = document.getElementById('emp_name_error');
                const emailError = document.getElementById('emp_email_error');
                const positionError = document.getElementById('emp_position_error');
                
                // Clear previous errors
                nameError.style.display = 'none';
                emailError.style.display = 'none';
                positionError.style.display = 'none';
                
                // Get form values
                const empId = document.getElementById('emp_id').value;
                const empName = document.getElementById('emp_name').value.trim();
                const empEmail = document.getElementById('emp_email').value.trim();
                const empPosition = document.getElementById('emp_position').value.trim();
                const empStatus = document.getElementById('emp_status').value;
                
                // Validate
                let isValid = true;
                if (!empName) {
                    nameError.textContent = 'Employee name is required';
                    nameError.style.display = 'block';
                    isValid = false;
                }
                if (!empEmail) {
                    emailError.textContent = 'Email is required';
                    emailError.style.display = 'block';
                    isValid = false;
                }
                if (!empPosition) {
                    positionError.textContent = 'Position is required';
                    positionError.style.display = 'block';
                    isValid = false;
                }
                
                if (!isValid) return;

                // Set loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Processing...';
                form.classList.add('loading');

                // Prepare data
                const formData = new FormData();
                formData.append('nonce', nonce);
                formData.append('emp_name', empName);
                formData.append('emp_email', empEmail);
                formData.append('emp_position', empPosition);
                formData.append('emp_status', empStatus);
                
                // Determine action
                const action = empId ? 'update_emp' : 'add_emp';
                formData.append('action', action);
                
                if (empId) {
                    formData.append('emp_id', empId);
                }

                console.log('Saving employee:', { empId, empName, empEmail, empPosition, empStatus, action });

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        submitBtn.disabled = false;
                        form.classList.remove('loading');
                        
                        console.log('Save Response:', response);
                        
                        if (response && response.success) {
                            showAlert(response.data, 'success');
                            resetEmpForm();
                            // Reload the employees list
                            loadEmployees(currentPage);
                            // Switch to list tab after successful save
                            showEmpTab('list-emps-tab', document.querySelector('.emp-tab-btn:nth-child(2)'));
                        } else {
                            let errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                            if (errorMsg.includes('already exists')) {
                                emailError.textContent = errorMsg;
                                emailError.style.display = 'block';
                            }
                            showAlert('Error: ' + errorMsg, 'error');
                            submitBtn.textContent = empId ? '🔄 Update Employee' : '➕ Add Employee';
                        }
                    },
                    error: function(xhr, status, error) {
                        submitBtn.disabled = false;
                        form.classList.remove('loading');
                        submitBtn.textContent = empId ? '🔄 Update Employee' : '➕ Add Employee';
                        console.error('Save Error:', error);
                        console.error('Status:', status);
                        console.error('Response Text:', xhr.responseText);
                        showAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editEmployee(id, name, email, position, status) {
                // Switch to add employee tab for editing
                showEmpTab('add-emp-tab', document.querySelector('.emp-tab-btn:nth-child(1)'));
                
                // Populate form with existing data
                document.getElementById('emp_id').value = id;
                document.getElementById('emp_name').value = name;
                document.getElementById('emp_email').value = email;
                document.getElementById('emp_position').value = position;
                document.getElementById('emp_status').value = status;
                document.getElementById('emp_submit_btn').textContent = '🔄 Update Employee';
                document.getElementById('emp_cancel_btn').style.display = 'inline-block';
                
                // Clear any errors
                document.getElementById('emp_name_error').style.display = 'none';
                document.getElementById('emp_email_error').style.display = 'none';
                document.getElementById('emp_position_error').style.display = 'none';
                
                // Update form title
                document.querySelector('#add-emp-tab h3').textContent = '✏️ Edit Employee';
            }

            function resetEmpForm() {
                document.getElementById('emp-form').reset();
                document.getElementById('emp_id').value = '';
                document.getElementById('emp_submit_btn').textContent = '➕ Add Employee';
                document.getElementById('emp_cancel_btn').style.display = 'none';
                document.getElementById('emp_name_error').style.display = 'none';
                document.getElementById('emp_email_error').style.display = 'none';
                document.getElementById('emp_position_error').style.display = 'none';
                
                // Reset form title
                document.querySelector('#add-emp-tab h3').textContent = '➕ Add New Employee';
            }

            function deleteEmployee(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'delete_emp',
                                emp_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                if (response && response.success) {
                                    showAlert(response.data, 'success');
                                    loadEmployees(currentPage);
                                } else {
                                    let errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                                    showAlert('Error: ' + errorMsg, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                showAlert('Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            }

            function showAlert(message, type = 'success') {
                const title = type === 'success' ? 'Success!' : 'Error!';
                const icon = type === 'success' ? 'success' : 'error';
                
                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    confirmButtonColor: type === 'success' ? '#28a745' : '#dc3545',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: true
                });
            }

            // === EMAIL VALIDATION ===
            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            const originalSaveEmployee = saveEmployee;
            saveEmployee = function() {
                const email = document.getElementById('emp_email').value.trim();
                const emailError = document.getElementById('emp_email_error');
                
                if (email && !validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.style.display = 'block';
                    return false;
                }
                
                emailError.style.display = 'none';
                return originalSaveEmployee();
            };

            document.getElementById('emp_email').addEventListener('blur', function() {
                const email = this.value.trim();
                const emailError = document.getElementById('emp_email_error');
                if (email && !validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            });
            // === END EMAIL VALIDATION ===
        </script>
    </div>
    <?php
}

// UPDATED AJAX Handler with Pagination
function ajax_get_employees_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security verification failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 10; // Number of items per page
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    // Get paginated employees
    $employees = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        ORDER BY emp_name ASC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    // Return JSON response with pagination info
    wp_send_json_success([
        'employees' => $employees,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ]
    ]);
}

function ajax_add_emp() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security verification failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    $log_table = $wpdb->prefix . 'admin_emp_stock_management_logs';
    
    $emp_name = sanitize_text_field($_POST['emp_name']);
    $emp_email = sanitize_email($_POST['emp_email']);
    $emp_position = sanitize_text_field($_POST['emp_position']);
    $emp_status = sanitize_text_field($_POST['emp_status']);
    $user_id = get_current_user_id();
    
    // Validate input
    if (empty($emp_name) || empty($emp_email) || empty($emp_position)) {
        wp_send_json_error('All fields are required');
    }
    
    // Check if employee email already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE emp_email = %s", $emp_email
    ));
    
    if ($existing) {
        wp_send_json_error('Employee email already exists');
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'emp_name' => $emp_name,
            'emp_email' => $emp_email,
            'emp_position' => $emp_position,
            'emp_status' => $emp_status
        ),
        array('%s', '%s', '%s', '%s')
    );

    if ($result !== false) {
        $emp_id = $wpdb->insert_id;
        
        // Log the action
        $wpdb->insert(
            $log_table,
            array(
                'emp_id' => $emp_id,
                'action' => 'created',
                'new_value' => $emp_name . ' (' . $emp_email . ') - ' . $emp_position . ' (Status: ' . $emp_status . ')',
                'user_id' => $user_id,
                'user_ip' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
        
        wp_send_json_success('Employee added successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

function ajax_update_emp() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security verification failed');
    }

    if (!isset($_POST['emp_id'])) {
        wp_send_json_error('Missing employee ID');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    $log_table = $wpdb->prefix . 'admin_emp_stock_management_logs';
    
    $emp_id = intval($_POST['emp_id']);
    $emp_name = sanitize_text_field($_POST['emp_name']);
    $emp_email = sanitize_email($_POST['emp_email']);
    $emp_position = sanitize_text_field($_POST['emp_position']);
    $emp_status = sanitize_text_field($_POST['emp_status']);
    $user_id = get_current_user_id();
    
    // Validate input
    if (empty($emp_name) || empty($emp_email) || empty($emp_position)) {
        wp_send_json_error('All fields are required');
    }
    
    // Get old values for logging
    $old_emp = $wpdb->get_row($wpdb->prepare(
        "SELECT emp_name, emp_email, emp_position, emp_status FROM $table_name WHERE id = %d", $emp_id
    ));
    
    if (!$old_emp) {
        wp_send_json_error('Employee not found');
    }
    
    // Check if employee email already exists (excluding current one)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE emp_email = %s AND id != %d", $emp_email, $emp_id
    ));
    
    if ($existing) {
        wp_send_json_error('Employee email already exists');
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'emp_name' => $emp_name,
            'emp_email' => $emp_email,
            'emp_position' => $emp_position,
            'emp_status' => $emp_status
        ),
        array('id' => $emp_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );

    if ($result !== false) {
        // Log the action
        $wpdb->insert(
            $log_table,
            array(
                'emp_id' => $emp_id,
                'action' => 'updated',
                'old_value' => $old_emp->emp_name . ' (' . $old_emp->emp_email . ') - ' . $old_emp->emp_position . ' (Status: ' . $old_emp->emp_status . ')',
                'new_value' => $emp_name . ' (' . $emp_email . ') - ' . $emp_position . ' (Status: ' . $emp_status . ')',
                'user_id' => $user_id,
                'user_ip' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        wp_send_json_success('Employee updated successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

function ajax_delete_emp() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security verification failed');
    }

    if (!isset($_POST['emp_id'])) {
        wp_send_json_error('Missing employee ID');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    $log_table = $wpdb->prefix . 'admin_emp_stock_management_logs';
    
    $emp_id = intval($_POST['emp_id']);
    $user_id = get_current_user_id();
    
    // Get employee info for logging
    $emp = $wpdb->get_row($wpdb->prepare(
        "SELECT emp_name, emp_email, emp_position, emp_status FROM $table_name WHERE id = %d", $emp_id
    ));
    
    if (!$emp) {
        wp_send_json_error('Employee not found');
    }
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $emp_id),
        array('%d')
    );

    if ($result !== false) {
        // Log the deletion
        $wpdb->insert(
            $log_table,
            array(
                'emp_id' => $emp_id,
                'action' => 'deleted',
                'old_value' => $emp->emp_name . ' (' . $emp->emp_email . ') - ' . $emp->emp_position . ' (Status: ' . $emp->emp_status . ')',
                'user_id' => $user_id,
                'user_ip' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
        
        wp_send_json_success('Employee deleted successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

// Create database tables for employee management
function create_emp_management_tables() {
    global $wpdb;
    
    $emp_table = $wpdb->prefix . 'admin_emp_stock_management';
    $emp_logs_table = $wpdb->prefix . 'admin_emp_stock_management_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Employees table
    $sql1 = "CREATE TABLE $emp_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        emp_name varchar(100) NOT NULL,
        emp_email varchar(100) NOT NULL,
        emp_position varchar(100) NOT NULL,
        emp_status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY emp_email (emp_email)
    ) $charset_collate;";
    
    // Employee logs table
    $sql2 = "CREATE TABLE $emp_logs_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        emp_id mediumint(9),
        action varchar(50) NOT NULL,
        old_value text,
        new_value text,
        user_id bigint(20),
        user_ip varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY emp_id (emp_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Helper function
function get_active_employees_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE emp_status = 'active'");
    return $count ?: 0;
}
?>