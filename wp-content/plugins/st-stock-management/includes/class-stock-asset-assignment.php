<?php
/**
 * Asset Assignment Management System
 * Add this file to your plugin's includes folder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create database tables on plugin activation
// register_activation_hook(__FILE__, 'create_assignment_tables');

// function create_assignment_tables() {
//     global $wpdb;
    
    
// }

// Register assignment shortcode
add_shortcode('asset_assignment', 'asset_assignment_shortcode');

function asset_assignment_shortcode($atts) {
    // Handle form submissions
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_employee') {
            add_employee();
        }
        if ($_POST['action'] === 'assign_asset') {
            assign_asset_to_employee();
        }
        if ($_POST['action'] === 'return_asset') {
            return_asset_from_employee();
        }
        if ($_POST['action'] === 'update_employee') {
            update_employee();
        }
    }

    ob_start();
    ?>
    <div id="asset-assignment-container">
        <style>
            #asset-assignment-container {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 1400px;
                margin: 0 auto;
                padding: 20px;
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                color: white;
            }
            
            .assignment-header {
                text-align: center;
                margin-bottom: 30px;
                background: rgba(255,255,255,0.1);
                padding: 20px;
                border-radius: 10px;
                backdrop-filter: blur(10px);
            }
            
            .assignment-header h2 {
                margin: 0;
                font-size: 2.5em;
                background: linear-gradient(45deg, #3498db, #2980b9);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            
            .assignment-tabs {
                display: flex;
                margin-bottom: 20px;
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
                padding: 5px;
                flex-wrap: wrap;
            }
            
            .tab-btn {
                flex: 1;
                padding: 12px 15px;
                border: none;
                background: transparent;
                color: white;
                cursor: pointer;
                border-radius: 8px;
                transition: all 0.3s ease;
                font-weight: 600;
                min-width: 150px;
                margin: 2px;
            }
            
            .tab-btn.active {
                background: linear-gradient(45deg, #3498db, #2980b9);
                color: white;
                box-shadow: 0 4px 15px rgba(52,152,219,0.4);
            }
            
            .tab-content {
                display: none;
                background: rgba(255,255,255,0.1);
                padding: 25px;
                border-radius: 10px;
                backdrop-filter: blur(10px);
            }
            
            .tab-content.active {
                display: block;
            }
            
            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .form-group {
                display: flex;
                flex-direction: column;
            }

            .form-group label {
                margin-bottom: 8px;
                font-weight: 600;
                color: #FFD700;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 12px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 8px;
                background-color: rgba(0,0,0,0.4);
                color: #ffffff;
                font-size: 14px;
                line-height: 1.5; /* Add this line */
                height: auto;     /* Ensure it grows with padding */
                transition: all 0.3s ease;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
            }

            /* Focus styles */
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                outline: none;
                border-color: #FFD700;
                box-shadow: 0 0 10px rgba(255,215,0,0.3);
            }

            /* Placeholder styling for inputs and textareas */
            .form-group input::placeholder,
            .form-group textarea::placeholder {
                color: rgba(255,255,255,0.7);
            }

            /* Placeholder styling for select */
            .form-group select option:first-child {
                color: rgba(255,255,255,0.7);
            }

            /* Optional: ensure dropdown arrow is visible */
            .form-group select {
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
            }
            
            .btn {
                padding: 12px 25px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s ease;
                margin: 5px;
                text-decoration: none;
                display: inline-block;
                text-align: center;
            }
            
            .btn-primary {
                background: linear-gradient(45deg, #3498db, #2980b9);
                color: white;
            }
            
            .btn-success {
                background: linear-gradient(45deg, #27ae60, #2ecc71);
                color: white;
            }
            
            .btn-warning {
                background: linear-gradient(45deg, #f39c12, #e67e22);
                color: white;
            }
            
            .btn-danger {
                background: linear-gradient(45deg, #e74c3c, #c0392b);
                color: white;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            
            .assignment-table {
                width: 100%;
                border-collapse: collapse;
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
                overflow: hidden;
                margin-top: 20px;
            }
            
            .assignment-table th, .assignment-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid rgba(255,255,255,0.2);
                font-size: 13px;
            }
            
            .assignment-table th {
                background: rgba(52,152,219,0.3);
                font-weight: 600;
                color: #3498db;
            }
            
            .assignment-table tr:hover {
                background: rgba(255,255,255,0.1);
            }
            
            .status-assigned { color: #2ecc71; font-weight: bold; }
            .status-returned { color: #95a5a6; font-weight: bold; }
            .status-lost { color: #e74c3c; font-weight: bold; }
            .status-damaged { color: #f39c12; font-weight: bold; }
            
            .condition-excellent { color: #2ecc71; }
            .condition-good { color: #3498db; }
            .condition-fair { color: #f39c12; }
            .condition-poor { color: #e74c3c; }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 25px;
            }
            
            .stat-card {
                background: rgba(255,255,255,0.1);
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                backdrop-filter: blur(10px);
            }
            
            .stat-number {
                font-size: 2.5em;
                font-weight: bold;
                margin-bottom: 10px;
            }
            
            .stat-label {
                color: #bdc3c7;
                font-size: 0.9em;
            }
            
            .search-box {
                margin-bottom: 20px;
                padding: 15px;
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
            }
            
            .search-box input {
                width: 100%;
                max-width: 300px;
                padding: 10px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 8px;
                background: rgba(255,255,255,0.1);
                color: white;
            }
            
            @media (max-width: 768px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }
                
                .assignment-table {
                    font-size: 11px;
                }
                
                .tab-btn {
                    padding: 8px 10px;
                    font-size: 12px;
                    min-width: 120px;
                }
                
                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
        </style>

        <div class="assignment-header">
            <h2>👥 Asset Assignment Management</h2>
            <p>Track and manage asset assignments to employees</p>
        </div>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #2ecc71;"><?php echo get_assignment_stats('total_assigned'); ?></div>
                <div class="stat-label">Currently Assigned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #3498db;"><?php echo get_assignment_stats('total_employees'); ?></div>
                <div class="stat-label">Active Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f39c12;"><?php echo get_assignment_stats('overdue_returns'); ?></div>
                <div class="stat-label">Overdue Returns</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #95a5a6;"><?php echo get_assignment_stats('total_returned'); ?></div>
                <div class="stat-label">Total Returned</div>
            </div>
        </div>

        <div class="assignment-tabs">
            <button class="tab-btn active" onclick="showTab('assign-asset')">🏷️ Assign Asset</button>
            <button class="tab-btn" onclick="showTab('return-asset')">🔄 Return Asset</button>
            <button class="tab-btn" onclick="showTab('view-assignments')">📋 View Assignments</button>
            <button class="tab-btn" onclick="showTab('manage-employees')">👤 Manage Employees</button>
            <button class="tab-btn" onclick="showTab('add-employee')">➕ Add Employee</button>
        </div>

        <!-- Assign Asset Tab -->
        <div id="assign-asset" class="tab-content active">
            <h3>🏷️ Assign Asset to Employee</h3>
            <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="assign_asset">
                <?php wp_nonce_field('asset_assignment_action', 'asset_assignment_nonce'); ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="asset_id">🖥️ Select Asset *</label>
                        <select id="asset_id" name="asset_id" required>
                            <option value="">Choose Asset</option>
                            <?php echo get_available_assets_options(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_id">👤 Select Employee *</label>
                        <select id="employee_id" name="employee_id" required>
                            <option value="">Choose Employee</option>
                            <?php echo get_active_employees_options(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_by">👨‍💼 Assigned By</label>
                        <input type="text" id="assigned_by" name="assigned_by" placeholder="Who is assigning this asset?" value="<?php echo wp_get_current_user()->display_name; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_date">📅 Assignment Date *</label>
                        <input type="date" id="assigned_date" name="assigned_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_return_date">📅 Expected Return Date</label>
                        <input type="date" id="expected_return_date" name="expected_return_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="condition_at_assignment">⚡ Condition at Assignment *</label>
                        <select id="condition_at_assignment" name="condition_at_assignment" required>
                            <option value="">Select Condition</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good" selected>Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_notes">💭 Assignment Notes</label>
                        <textarea id="assignment_notes" name="assignment_notes" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">✅ Assign Asset</button>
            </form>
        </div>

        <!-- Return Asset Tab -->
        <div id="return-asset" class="tab-content">
            <h3>🔄 Return Asset</h3>
            <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="return_asset">
                <?php wp_nonce_field('asset_assignment_action', 'asset_assignment_nonce'); ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="assignment_id">🏷️ Select Assignment *</label>
                        <select id="assignment_id" name="assignment_id" required onchange="loadAssignmentDetails(this.value)">
                            <option value="">Choose Assignment to Return</option>
                            <?php echo get_active_assignments_options(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="actual_return_date">📅 Return Date *</label>
                        <input type="date" id="actual_return_date" name="actual_return_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="condition_at_return">⚡ Condition at Return *</label>
                        <select id="condition_at_return" name="condition_at_return" required>
                            <option value="">Select Condition</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_status">📊 Return Status *</label>
                        <select id="assignment_status" name="assignment_status" required>
                            <option value="Returned">Returned</option>
                            <option value="Lost">Lost</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="return_notes">💭 Return Notes</label>
                        <textarea id="return_notes" name="return_notes" rows="3" placeholder="Condition details, damage notes, etc..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning">🔄 Process Return</button>
            </form>
        </div>

        <!-- View Assignments Tab -->
        <div id="view-assignments" class="tab-content">
            <h3>📋 Asset Assignments</h3>
            <div class="search-box">
                <input type="text" id="assignment-search" placeholder="🔍 Search assignments..." onkeyup="filterAssignments()">
            </div>
            <?php echo render_assignments_table(); ?>
        </div>

        <!-- Manage Employees Tab -->
        <div id="manage-employees" class="tab-content">
            <h3>👤 Employee Management</h3>
            <div class="search-box">
                <input type="text" id="employee-search" placeholder="🔍 Search employees..." onkeyup="filterEmployees()">
            </div>
            <?php echo render_employees_table(); ?>
        </div>

        <!-- Add Employee Tab -->
        <div id="add-employee" class="tab-content">
            <h3>➕ Add New Employee</h3>
            <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="add_employee">
                <?php wp_nonce_field('asset_assignment_action', 'asset_assignment_nonce'); ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="employee_name">👤 Employee Name *</label>
                        <input type="text" id="employee_name" name="employee_name" placeholder="Full Name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_id">🆔 Employee ID *</label>
                        <input type="text" id="employee_id" name="employee_id" placeholder="Unique Employee ID" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">🏢 Department</label>
                        <select id="department" name="department">
                            <option value="">Select Department</option>
                            <option value="IT">IT</option>
                            <option value="HR">HR</option>
                            <option value="Finance">Finance</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Operations">Operations</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="designation">💼 Designation</label>
                        <input type="text" id="designation" name="designation" placeholder="Job Title">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">📧 Email</label>
                        <input type="email" id="email" name="email" placeholder="employee@company.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">📱 Phone</label>
                        <input type="tel" id="phone" name="phone" placeholder="+91 9876543210">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">➕ Add Employee</button>
            </form>
        </div>

        <script>
            function showTab(tabName) {
                var tabContents = document.getElementsByClassName('tab-content');
                for (var i = 0; i < tabContents.length; i++) {
                    tabContents[i].classList.remove('active');
                }
                var tabBtns = document.getElementsByClassName('tab-btn');
                for (var i = 0; i < tabBtns.length; i++) {
                    tabBtns[i].classList.remove('active');
                }
                document.getElementById(tabName).classList.add('active');
                event.target.classList.add('active');
            }

            function filterAssignments() {
                var input = document.getElementById('assignment-search');
                var filter = input.value.toLowerCase();
                var table = document.querySelector('.assignment-table');
                var rows = table.getElementsByTagName('tr');

                for (var i = 1; i < rows.length; i++) {
                    var cells = rows[i].getElementsByTagName('td');
                    var found = false;
                    for (var j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                    rows[i].style.display = found ? '' : 'none';
                }
            }

            function filterEmployees() {
                var input = document.getElementById('employee-search');
                var filter = input.value.toLowerCase();
                var table = document.querySelector('#manage-employees .assignment-table');
                var rows = table.getElementsByTagName('tr');

                for (var i = 1; i < rows.length; i++) {
                    var cells = rows[i].getElementsByTagName('td');
                    var found = false;
                    for (var j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                    rows[i].style.display = found ? '' : 'none';
                }
            }

            function editEmployee(id) {
                alert('Edit Employee functionality - ID: ' + id + '\nThis will open an edit form.');
            }

            function viewEmployeeAssets(employeeId) {
                alert('View Assets for Employee ID: ' + employeeId + '\nThis will show all assigned assets.');
            }

            function extendAssignment(assignmentId) {
                var newDate = prompt('Enter new expected return date (YYYY-MM-DD):');
                if (newDate) {
                    alert('Assignment ' + assignmentId + ' extended until ' + newDate);
                }
            }
        </script>
    </div>
    <?php
    return ob_get_clean();
}

// Function to add employee
function add_employee() {
    if (!isset($_POST['asset_assignment_nonce']) || !wp_verify_nonce($_POST['asset_assignment_nonce'], 'asset_assignment_action')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_employees';

    $result = $wpdb->insert(
        $table_name,
        array(
            'employee_name' => sanitize_text_field($_POST['employee_name']),
            'employee_id' => sanitize_text_field($_POST['employee_id']),
            'department' => sanitize_text_field($_POST['department']),
            'designation' => sanitize_text_field($_POST['designation']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'status' => 'Active'
        )
    );

    // Success or error message using transient/session
    if ($result) {
        set_transient('asset_assignment_message', 'Employee added successfully!', 30);
    } else {
        set_transient('asset_assignment_message', 'Error adding employee: ' . esc_html($wpdb->last_error), 30);
    }

    // Redirect to the same page to avoid form resubmission
    wp_redirect(esc_url(remove_query_arg('action', $_SERVER['REQUEST_URI'])));
    exit;
}


// Function to assign asset to employee
function assign_asset_to_employee() {
    if (!isset($_POST['asset_assignment_nonce']) || !wp_verify_nonce($_POST['asset_assignment_nonce'], 'asset_assignment_action')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';

    // Check if asset is already assigned and not returned
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $assignments_table WHERE asset_id = %d AND assignment_status = 'Assigned'",
        intval($_POST['asset_id'])
    ));

    if ($existing) {
        echo '<div class="notice notice-error"><p>This asset is already assigned to someone else!</p></div>';
        return;
    }

    $result = $wpdb->insert(
        $assignments_table,
        array(
            'asset_id' => intval($_POST['asset_id']),
            'employee_id' => intval($_POST['employee_id']),
            'assigned_by' => sanitize_text_field($_POST['assigned_by']),
            'assigned_date' => sanitize_text_field($_POST['assigned_date']),
            'expected_return_date' => !empty($_POST['expected_return_date']) ? sanitize_text_field($_POST['expected_return_date']) : null,
            'condition_at_assignment' => sanitize_text_field($_POST['condition_at_assignment']),
            'assignment_notes' => sanitize_textarea_field($_POST['assignment_notes']),
            'assignment_status' => 'Assigned'
        )
    );

    if ($result) {
        // Update asset status to assigned
        $wpdb->update(
            $assets_table,
            array('status' => 'Assigned'),
            array('id' => intval($_POST['asset_id']))
        );
        
        echo '<div class="notice notice-success"><p>Asset assigned successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error assigning asset: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Function to return asset
function return_asset_from_employee() {
    if (!isset($_POST['asset_assignment_nonce']) || !wp_verify_nonce($_POST['asset_assignment_nonce'], 'asset_assignment_action')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';

    // Get assignment details
    $assignment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $assignments_table WHERE id = %d",
        intval($_POST['assignment_id'])
    ));

    if (!$assignment) {
        echo '<div class="notice notice-error"><p>Assignment not found!</p></div>';
        return;
    }

    $result = $wpdb->update(
        $assignments_table,
        array(
            'actual_return_date' => sanitize_text_field($_POST['actual_return_date']),
            'condition_at_return' => sanitize_text_field($_POST['condition_at_return']),
            'assignment_status' => sanitize_text_field($_POST['assignment_status']),
            'return_notes' => sanitize_textarea_field($_POST['return_notes'])
        ),
        array('id' => intval($_POST['assignment_id']))
    );

    if ($result !== false) {
        // Update asset status
        $new_status = ($_POST['assignment_status'] === 'Returned') ? 'Active' : $_POST['assignment_status'];
        $wpdb->update(
            $assets_table,
            array('status' => $new_status),
            array('id' => $assignment->asset_id)
        );
        
        echo '<div class="notice notice-success"><p>Asset return processed successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error processing return: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Function to get available assets for assignment
function get_available_assets_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    
    $assets = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE status IN ('Active', 'Inactive') ORDER BY asset_type, brand_model"
    );
    
    $options = '';
    foreach ($assets as $asset) {
        $options .= '<option value="' . esc_attr($asset->id) . '">';
        $options .= esc_html($asset->asset_type) . ' - ' . esc_html($asset->brand_model);
        $options .= ' (SN: ' . esc_html($asset->serial_number) . ')';
        $options .= '</option>';
    }
    
    return $options;
}

// Function to get active employees for dropdown
function get_active_employees_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_employees';
    
    $employees = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE status = 'Active' ORDER BY employee_name"
    );
    
    $options = '';
    foreach ($employees as $employee) {
        $options .= '<option value="' . esc_attr($employee->id) . '">';
        $options .= esc_html($employee->employee_name) . ' (' . esc_html($employee->employee_id) . ')';
        if (!empty($employee->department)) {
            $options .= ' - ' . esc_html($employee->department);
        }
        $options .= '</option>';
    }
    
    return $options;
}

// Function to get active assignments for return
function get_active_assignments_options() {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    $assignments = $wpdb->get_results("
        SELECT a.id, a.assigned_date, s.asset_type, s.brand_model, s.serial_number, e.employee_name, e.employee_id
        FROM $assignments_table a
        JOIN $assets_table s ON a.asset_id = s.id
        JOIN $employees_table e ON a.employee_id = e.id
        WHERE a.assignment_status = 'Assigned'
        ORDER BY a.assigned_date DESC
    ");
    
    $options = '';
    foreach ($assignments as $assignment) {
        $options .= '<option value="' . esc_attr($assignment->id) . '">';
        $options .= esc_html($assignment->asset_type) . ' - ' . esc_html($assignment->brand_model);
        $options .= ' → ' . esc_html($assignment->employee_name);
        $options .= ' (Assigned: ' . esc_html(date('d/m/Y', strtotime($assignment->assigned_date))) . ')';
        $options .= '</option>';
    }
    
    return $options;
}

// Function to render assignments table
function render_assignments_table() {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    $assignments = $wpdb->get_results("
        SELECT a.*, s.asset_type, s.brand_model, s.serial_number, e.employee_name, e.employee_id, e.department
        FROM $assignments_table a
        JOIN $assets_table s ON a.asset_id = s.id
        JOIN $employees_table e ON a.employee_id = e.id
        ORDER BY a.created_at DESC
        LIMIT 100
    ");
    
    if (empty($assignments)) {
        return '<p>No assignments found. Start assigning assets to employees!</p>';
    }
    
    $html = '<table class="assignment-table">';
    $html .= '<thead><tr>';
    $html .= '<th>Asset</th><th>Employee</th><th>Assigned Date</th><th>Expected Return</th>';
    $html .= '<th>Status</th><th>Condition</th><th>Actions</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($assignments as $assignment) {
        $status_class = 'status-' . strtolower(str_replace(' ', '-', $assignment->assignment_status));
        $condition_class = 'condition-' . strtolower($assignment->condition_at_assignment);
        
        $is_overdue = false;
        if ($assignment->assignment_status === 'Assigned' && !empty($assignment->expected_return_date)) {
            $is_overdue = strtotime($assignment->expected_return_date) < time();
        }
        
        $html .= '<tr' . ($is_overdue ? ' style="background-color: rgba(231,76,60,0.1);"' : '') . '>';
        $html .= '<td><strong>' . esc_html($assignment->asset_type) . '</strong><br>';
        $html .= '<small>' . esc_html($assignment->brand_model) . ' (SN: ' . esc_html($assignment->serial_number) . ')</small></td>';
        
        $html .= '<td><strong>' . esc_html($assignment->employee_name) . '</strong><br>';
        $html .= '<small>' . esc_html($assignment->employee_id);
        if (!empty($assignment->department)) {
            $html .= ' - ' . esc_html($assignment->department);
        }
        $html .= '</small></td>';
        
        $html .= '<td>' . esc_html(date('d/m/Y', strtotime($assignment->assigned_date))) . '</td>';
        
        $html .= '<td>';
        if (!empty($assignment->expected_return_date)) {
            $html .= esc_html(date('d/m/Y', strtotime($assignment->expected_return_date)));
            if ($is_overdue) {
                $html .= '<br><small style="color: #e74c3c;">⚠️ Overdue</small>';
            }
        } else {
            $html .= '-';
        }
        $html .= '</td>';
        
        $html .= '<td class="' . $status_class . '">' . esc_html($assignment->assignment_status);
        if (!empty($assignment->actual_return_date)) {
            $html .= '<br><small>Returned: ' . esc_html(date('d/m/Y', strtotime($assignment->actual_return_date))) . '</small>';
        }
        $html .= '</td>';
        
        $html .= '<td class="' . $condition_class . '">' . esc_html($assignment->condition_at_assignment);
        if (!empty($assignment->condition_at_return)) {
            $html .= ' → ' . esc_html($assignment->condition_at_return);
        }
        $html .= '</td>';
        
        $html .= '<td>';
        if ($assignment->assignment_status === 'Assigned') {
            $html .= '<button class="btn btn-warning" onclick="extendAssignment(' . $assignment->id . ')" title="Extend Assignment">📅</button>';
        }
        $html .= '<button class="btn btn-primary" onclick="viewAssignmentDetails(' . $assignment->id . ')" title="View Details">👁️</button>';
        $html .= '</td>';
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    return $html;
}

// Function to render employees table
function render_employees_table() {
    global $wpdb;
    $employees_table = $wpdb->prefix . 'stock_employees';
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    
    $employees = $wpdb->get_results("
        SELECT e.*, 
               COUNT(a.id) as total_assignments,
               COUNT(CASE WHEN a.assignment_status = 'Assigned' THEN 1 END) as current_assignments
        FROM $employees_table e
        LEFT JOIN $assignments_table a ON e.id = a.employee_id
        GROUP BY e.id
        ORDER BY e.employee_name
    ");
    
    if (empty($employees)) {
        return '<p>No employees found. Add some employees to get started!</p>';
    }
    
    $html = '<table class="assignment-table">';
    $html .= '<thead><tr>';
    $html .= '<th>Employee</th><th>Department</th><th>Contact</th><th>Current Assets</th><th>Total Assignments</th><th>Actions</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($employees as $employee) {
        $status_class = $employee->status === 'Active' ? 'status-assigned' : 'status-returned';
        
        $html .= '<tr>';
        $html .= '<td><strong>' . esc_html($employee->employee_name) . '</strong><br>';
        $html .= '<small>ID: ' . esc_html($employee->employee_id) . '</small><br>';
        $html .= '<span class="' . $status_class . '">' . esc_html($employee->status) . '</span></td>';
        
        $html .= '<td>' . esc_html($employee->department ?: '-') . '<br>';
        $html .= '<small>' . esc_html($employee->designation ?: '-') . '</small></td>';
        
        $html .= '<td>';
        if (!empty($employee->email)) {
            $html .= '📧 ' . esc_html($employee->email) . '<br>';
        }
        if (!empty($employee->phone)) {
            $html .= '📱 ' . esc_html($employee->phone);
        }
        if (empty($employee->email) && empty($employee->phone)) {
            $html .= '-';
        }
        $html .= '</td>';
        
        $html .= '<td><span style="color: #2ecc71; font-weight: bold; font-size: 1.2em;">' . intval($employee->current_assignments) . '</span></td>';
        $html .= '<td>' . intval($employee->total_assignments) . '</td>';
        
        $html .= '<td>';
        $html .= '<button class="btn btn-primary" onclick="viewEmployeeAssets(' . $employee->id . ')" title="View Assets">👁️</button>';
        $html .= '<button class="btn btn-warning" onclick="editEmployee(' . $employee->id . ')" title="Edit Employee">✏️</button>';
        $html .= '</td>';
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    return $html;
}

// Function to get assignment statistics
function get_assignment_stats($type) {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    switch ($type) {
        case 'total_assigned':
            return $wpdb->get_var("SELECT COUNT(*) FROM $assignments_table WHERE assignment_status = 'Assigned'");
            
        case 'total_employees':
            return $wpdb->get_var("SELECT COUNT(*) FROM $employees_table WHERE status = 'Active'");
            
        case 'overdue_returns':
            return $wpdb->get_var("
                SELECT COUNT(*) FROM $assignments_table 
                WHERE assignment_status = 'Assigned' 
                AND expected_return_date IS NOT NULL 
                AND expected_return_date < CURDATE()
            ");
            
        case 'total_returned':
            return $wpdb->get_var("SELECT COUNT(*) FROM $assignments_table WHERE assignment_status = 'Returned'");
            
        default:
            return 0;
    }
}

// Update employee function
function update_employee() {
    if (!isset($_POST['asset_assignment_nonce']) || !wp_verify_nonce($_POST['asset_assignment_nonce'], 'asset_assignment_action')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_employees';

    $result = $wpdb->update(
        $table_name,
        array(
            'employee_name' => sanitize_text_field($_POST['employee_name']),
            'department' => sanitize_text_field($_POST['department']),
            'designation' => sanitize_text_field($_POST['designation']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'status' => sanitize_text_field($_POST['status'])
        ),
        array('id' => intval($_POST['employee_id']))
    );

    if ($result !== false) {
        echo '<div class="notice notice-success"><p>Employee updated successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error updating employee: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

/**
 * Additional shortcode for employee asset history
 */
add_shortcode('employee_asset_history', 'employee_asset_history_shortcode');

function employee_asset_history_shortcode($atts) {
    $atts = shortcode_atts(array(
        'employee_id' => 0
    ), $atts);
    
    if (empty($atts['employee_id'])) {
        return '<p>Please provide employee ID parameter.</p>';
    }
    
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    $employee = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $employees_table WHERE id = %d",
        intval($atts['employee_id'])
    ));
    
    if (!$employee) {
        return '<p>Employee not found.</p>';
    }
    
    $assignments = $wpdb->get_results($wpdb->prepare("
        SELECT a.*, s.asset_type, s.brand_model, s.serial_number
        FROM $assignments_table a
        JOIN $assets_table s ON a.asset_id = s.id
        WHERE a.employee_id = %d
        ORDER BY a.assigned_date DESC
    ", intval($atts['employee_id'])));
    
    ob_start();
    ?>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <h3>📋 Asset History for <?php echo esc_html($employee->employee_name); ?></h3>
        <p><strong>Employee ID:</strong> <?php echo esc_html($employee->employee_id); ?></p>
        <p><strong>Department:</strong> <?php echo esc_html($employee->department ?: 'N/A'); ?></p>
        
        <?php if (empty($assignments)): ?>
            <p>No assets have been assigned to this employee yet.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #e9ecef;">
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Asset</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Assigned Date</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Return Date</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Status</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                <strong><?php echo esc_html($assignment->asset_type); ?></strong><br>
                                <small><?php echo esc_html($assignment->brand_model); ?> (<?php echo esc_html($assignment->serial_number); ?>)</small>
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><?php echo date('d/m/Y', strtotime($assignment->assigned_date)); ?></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                <?php 
                                if (!empty($assignment->actual_return_date)) {
                                    echo date('d/m/Y', strtotime($assignment->actual_return_date));
                                } else {
                                    echo 'Not Returned';
                                }
                                ?>
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                <span style="color: <?php echo $assignment->assignment_status === 'Assigned' ? '#28a745' : '#6c757d'; ?>; font-weight: bold;">
                                    <?php echo esc_html($assignment->assignment_status); ?>
                                </span>
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                <?php 
                                $start_date = strtotime($assignment->assigned_date);
                                $end_date = !empty($assignment->actual_return_date) ? strtotime($assignment->actual_return_date) : time();
                                $days = floor(($end_date - $start_date) / (60 * 60 * 24));
                                echo $days . ' days';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Asset assignment report shortcode
 */
add_shortcode('asset_assignment_report', 'asset_assignment_report_shortcode');

function asset_assignment_report_shortcode($atts) {
    $atts = shortcode_atts(array(
        'period' => 'all', // all, month, year
        'department' => ''
    ), $atts);
    
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'asset_assignments';
    $assets_table = $wpdb->prefix . 'stock_management';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    $where_clause = '1=1';
    
    if ($atts['period'] === 'month') {
        $where_clause .= " AND a.assigned_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($atts['period'] === 'year') {
        $where_clause .= " AND a.assigned_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
    
    if (!empty($atts['department'])) {
        $where_clause .= $wpdb->prepare(" AND e.department = %s", $atts['department']);
    }
    
    $summary = $wpdb->get_results("
        SELECT 
            e.department,
            COUNT(*) as total_assignments,
            COUNT(CASE WHEN a.assignment_status = 'Assigned' THEN 1 END) as currently_assigned,
            COUNT(CASE WHEN a.assignment_status = 'Returned' THEN 1 END) as returned,
            COUNT(CASE WHEN a.assignment_status = 'Lost' THEN 1 END) as lost,
            COUNT(CASE WHEN a.assignment_status = 'Damaged' THEN 1 END) as damaged
        FROM $assignments_table a
        JOIN $employees_table e ON a.employee_id = e.id
        WHERE $where_clause
        GROUP BY e.department
        ORDER BY total_assignments DESC
    ");
    
    ob_start();
    ?>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <h3>📊 Asset Assignment Report</h3>
        <p><strong>Period:</strong> <?php echo ucfirst($atts['period']); ?></p>
        <?php if (!empty($atts['department'])): ?>
            <p><strong>Department:</strong> <?php echo esc_html($atts['department']); ?></p>
        <?php endif; ?>
        
        <?php if (empty($summary)): ?>
            <p>No assignment data found for the specified criteria.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #e9ecef;">
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Department</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Total</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Currently Assigned</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Returned</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Lost</th>
                        <th style="padding: 12px; border: 1px solid #dee2e6;">Damaged</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong><?php echo esc_html($row->department ?: 'No Department'); ?></strong></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center;"><?php echo intval($row->total_assignments); ?></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center; color: #28a745;"><?php echo intval($row->currently_assigned); ?></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center; color: #6c757d;"><?php echo intval($row->returned); ?></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center; color: #dc3545;"><?php echo intval($row->lost); ?></td>
                            <td style="padding: 12px; border: 1px solid #dee2e6; text-align: center; color: #ffc107;"><?php echo intval($row->damaged); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

?>