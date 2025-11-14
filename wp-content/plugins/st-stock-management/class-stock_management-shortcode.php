<?php
/**
 * Stock Management Shortcode 
 * Add this to your plugin file
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Isolate AJAX calls from other plugins/themes
if (defined('DOING_AJAX') && DOING_AJAX) {
    if (isset($_POST['action']) && strpos($_POST['action'], 'stock_') !== false) {
        // Remove all plugins that might interfere
        remove_all_actions('init');
        remove_all_actions('wp_head');
        remove_all_actions('wp_footer');
        remove_all_actions('admin_init');
        
        // Clear any existing output
        if (ob_get_length()) {
            ob_clean();
        }
    }
}

// Create database tables on plugin activation
register_activation_hook(__FILE__, 'create_stock_management_table');
register_activation_hook(__FILE__, 'create_emp_asset_assign_table');
register_activation_hook(__FILE__, 'create_repaire_stock_management_table');

// Create log tables on plugin activation
register_activation_hook(__FILE__, 'create_front_log_tables');

// Register shortcode
add_shortcode('stock_management', 'stock_management_shortcode');

// AJAX handlers for logged-in users
add_action('wp_ajax_add_stock_item', 'ajax_add_stock_item');
add_action('wp_ajax_update_stock_item', 'ajax_update_stock_item');
add_action('wp_ajax_delete_stock_item', 'ajax_delete_stock_item');
add_action('wp_ajax_add_custom_field', 'ajax_add_custom_field');
add_action('wp_ajax_delete_custom_field', 'ajax_delete_custom_field');
add_action('wp_ajax_get_stock_items', 'ajax_get_stock_items');
add_action('wp_ajax_get_emp_list', 'ajax_get_emp_list');
add_action('wp_ajax_get_asset_types', 'ajax_get_asset_types');
add_action('wp_ajax_get_brand_models', 'ajax_get_brand_models');
add_action('wp_ajax_assign_assets_to_emp', 'ajax_assign_assets_to_emp');
add_action('wp_ajax_get_brands_by_asset', 'ajax_get_brands_by_asset');
add_action('wp_ajax_get_assigned_assets', 'ajax_get_assigned_assets');
add_action('wp_ajax_check_serial_number', 'ajax_check_serial_number');
add_action('wp_ajax_get_serial_numbers', 'ajax_get_serial_numbers');
add_action('wp_ajax_add_repair_item', 'ajax_add_repair_item');
add_action('wp_ajax_get_repair_items', 'ajax_get_repair_items');
add_action('wp_ajax_update_repair_item', 'ajax_update_repair_item');
add_action('wp_ajax_delete_repair_item', 'ajax_delete_repair_item');

// AJAX handlers for non-logged-in users
add_action('wp_ajax_nopriv_add_stock_item', 'ajax_add_stock_item');
add_action('wp_ajax_nopriv_update_stock_item', 'ajax_update_stock_item');
add_action('wp_ajax_nopriv_delete_stock_item', 'ajax_delete_stock_item');
add_action('wp_ajax_nopriv_add_custom_field', 'ajax_add_custom_field');
add_action('wp_ajax_nopriv_delete_custom_field', 'ajax_delete_custom_field');
add_action('wp_ajax_nopriv_get_stock_items', 'ajax_get_stock_items');
add_action('wp_ajax_nopriv_get_emp_list', 'ajax_get_emp_list');
add_action('wp_ajax_nopriv_get_asset_types', 'ajax_get_asset_types');
add_action('wp_ajax_nopriv_get_brand_models', 'ajax_get_brand_models');
add_action('wp_ajax_nopriv_assign_assets_to_emp', 'ajax_assign_assets_to_emp');
add_action('wp_ajax_nopriv_get_brands_by_asset', 'ajax_get_brands_by_asset');
add_action('wp_ajax_nopriv_get_assigned_assets', 'ajax_get_assigned_assets');
add_action('wp_ajax_nopriv_check_serial_number', 'ajax_check_serial_number');
add_action('wp_ajax_nopriv_get_serial_numbers', 'ajax_get_serial_numbers');
add_action('wp_ajax_nopriv_add_repair_item', 'ajax_add_repair_item');
add_action('wp_ajax_nopriv_get_repair_items', 'ajax_get_repair_items');
add_action('wp_ajax_nopriv_update_repair_item', 'ajax_update_repair_item');
add_action('wp_ajax_nopriv_delete_repair_item', 'ajax_delete_repair_item');



/**
 * Create stock management table if needed (runs on plugin activation)
 */
function create_stock_management_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        asset_type varchar(100) NOT NULL,
        brand_model varchar(200) NOT NULL,
        serial_number varchar(100) NOT NULL UNIQUE,
        quantity int(11) DEFAULT 1,
        price decimal(10,2) NOT NULL,
        status varchar(50) NOT NULL,
        location varchar(100) NOT NULL,
        date_purchased date NOT NULL,
        warranty_expiry date NULL,
        remarks text NULL,
        custom_fields text NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY serial_number (serial_number)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create employee asset assignment table
 */
function create_emp_asset_assign_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'emp_asset_assign_table';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        emp_id mediumint(9) NOT NULL,
        asset_type varchar(100) NOT NULL,
        brand_model varchar(200) NOT NULL,
        assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY emp_id (emp_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create repair management table
 */
function create_repaire_stock_management_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'repaire_stock_management';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        serial_number varchar(100) NOT NULL,
        asset_type varchar(100) NOT NULL,
        brand_model varchar(200) NOT NULL,
        repair_remarks text NOT NULL,
        repair_date date NOT NULL,
        return_date date NULL,
        status varchar(50) DEFAULT 'Under Repair',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY serial_number (serial_number)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'stock_management_enqueue_scripts');
function stock_management_enqueue_scripts() {
    // CSS
    wp_enqueue_style('stock-management-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0.0');
}

/**
 * Common Logging Functions
 */

/**
 * Get user IP address
 */
function get_user_ip_address() {
    $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Get current user data for logging
 */
function get_current_user_log_data() {
    $user_id = get_current_user_id();
    $user_name = 'Guest';
    
    if ($user_id) {
        $user = wp_get_current_user();
        $user_name = $user->display_name ?: $user->user_login;
    }
    
    return [
        'user_id' => $user_id,
        'user_name' => $user_name,
        'ip_address' => get_user_ip_address(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
}

/**
 * Log item management actions
 */
function log_item_action($action, $item_id, $old_data = null, $new_data = null) {
    global $wpdb;
    
    $user_data = get_current_user_log_data();
    
    $wpdb->insert(
        $wpdb->prefix . 'item_front_logs',
        [
            'action' => $action,
            'item_id' => $item_id,
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['user_name'],
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'ip_address' => $user_data['ip_address'],
            'user_agent' => $user_data['user_agent']
        ],
        ['%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
    );
    
    return $wpdb->insert_id;
}

/**
 * Log asset assignment actions
 */
function log_asset_assign_action($action, $assign_id, $emp_id, $asset_data) {
    global $wpdb;
    
    $user_data = get_current_user_log_data();
    
    $wpdb->insert(
        $wpdb->prefix . 'asset_assign_front_log',
        [
            'action' => $action,
            'assign_id' => $assign_id,
            'emp_id' => $emp_id,
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['user_name'],
            'asset_data' => json_encode($asset_data),
            'ip_address' => $user_data['ip_address'],
            'user_agent' => $user_data['user_agent']
        ],
        ['%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s']
    );
    
    return $wpdb->insert_id;
}

/**
 * Log repair management actions
 */
function log_repair_action($action, $repair_id, $old_data = null, $new_data = null) {
    global $wpdb;
    
    $user_data = get_current_user_log_data();
    
    $wpdb->insert(
        $wpdb->prefix . 'repaire_front_log',
        [
            'action' => $action,
            'repair_id' => $repair_id,
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['user_name'],
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'ip_address' => $user_data['ip_address'],
            'user_agent' => $user_data['user_agent']
        ],
        ['%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
    );
    
    return $wpdb->insert_id;
}

function stock_management_shortcode($atts) {
    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');
    
    // Add SweetAlert2
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
    
    // ADD DATATABLES
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
    wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6');
    
    // Get AJAX URL and nonce
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ob_start();
    ?>
    <div id="stock-management-container">
        

        <div class="stock-header">
            <h2>🏢 Stock Management System</h2>
            <p>Efficiently manage your inventory with advanced features</p>
        </div>

        <div class="stock-tabs">
            <button class="tab-btn active" onclick="showMainTab('item-tab', this)">📦 Item</button>
            <button class="tab-btn" onclick="showMainTab('employee-tab', this)">👨‍💼 Employee</button>
            <button class="tab-btn" onclick="showMainTab('repaire-tab', this)">🔧 Repaire</button>
        </div>

        <!-- Item Tab -->
        <div id="item-tab" class="tab-content active">
            <h3>📦 Item Management</h3>
            
            <!-- Item Sub Tabs -->
            <div class="item-sub-tabs">
                <button class="item-sub-tab-btn active" onclick="showItemSubTab('add-item-tab', this)">➕ Add Item</button>
                <button class="item-sub-tab-btn" onclick="showItemSubTab('view-list-items-tab', this)">📊 View Grouped Items</button>
                <button class="item-sub-tab-btn" onclick="showItemSubTab('list-items-tab', this)">📋 Edit List Items</button>
                
            </div>

            <!-- Add Item Sub Tab -->
            <div id="add-item-tab" class="item-sub-content active">
                <div class="add-item-section">
                    <form id="add-stock-form">
                        <input type="hidden" name="action" value="add_stock_item">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <?php 
                                    global $wpdb;
                                    $table_name = $wpdb->prefix . 'admin_item_stock_management';

                                    // Fetch all active asset types, ordered alphabetically
                                    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'active' ORDER BY asset_name ASC");
                                    ?>

                                    <label for="asset_type">🏷️ Asset Type *</label>
                                    <select id="asset_type" name="asset_type" required>
                                        <option value="">Select Asset Type</option>
                                        <?php 
                                        if ( !empty($results) ) {
                                            foreach ( $results as $row ) {
                                                echo '<option value="' . esc_attr($row->asset_name) . '">' . esc_html($row->asset_name) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No Asset Types Found</option>';
                                        }
                                        ?>
                                    </select>

                            </div>

                            <div class="form-group">
                                <label for="brand_model">🏭 Brand/Model *</label>
                                <input type="text" id="brand_model" name="brand_model" placeholder="e.g., Dell Inspiron 15" required>
                            </div>
                            <div class="form-group">
                                <label for="serial_number">🔢 Serial Number *</label>
                                <input type="text" id="serial_number" name="serial_number" placeholder="e.g., ABC123456" required onblur="checkSerialNumber('add')">
                                <div class="serial-error" id="serial_error_add"></div>
                            </div>
                            <div class="form-group">
                                <label for="quantity">📦 Quantity</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" required disabled>
                            </div>

                            <div class="form-group">
                                <label for="price">₹ Price *</label>
                                <input type="number" id="price" name="price" placeholder="Enter price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="status">📊 Status *</label>
                                <select id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="In Stock">In Stock</option>
    
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="location">📍 Location *</label>
                                <select id="location" name="location" required>
                                    <option value="">Select Location</option>
                                    <option value="4th Floor">4th Floor</option>
                                    <option value="6th Floor">6th Floor</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="date_purchased">📅 Date Purchased *</label>
                                <input type="date" id="date_purchased" name="date_purchased" required>
                            </div>

                            <div class="form-group">
                                <label for="warranty_expiry">🛡️ Warranty Expiry</label>
                                <input type="date" id="warranty_expiry" name="warranty_expiry">
                            </div>

                            <div class="form-group">
                                <label for="remarks">💭 Remarks</label>
                                <textarea id="remarks" name="remarks" rows="3" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="add_submit_button">✅ Add Stock Item</button>
                        <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
                    </form>
                </div>
            </div>

            <!-- Edit Item Sub Tab -->
            <div id="edit-item-tab" class="item-sub-content">
                <div class="edit-form-container">
                    <h4>✏️ Edit Existing Item</h4>
                    <form id="edit-stock-form">
                        <input type="hidden" name="action" value="update_stock_item">
                        <input type="hidden" name="item_id" id="edit_item_id" value="">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <?php 
                                    global $wpdb;
                                    $table_name = $wpdb->prefix . 'admin_item_stock_management';

                                    // Fetch all active asset types, ordered alphabetically
                                    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'active' ORDER BY asset_name ASC");
                                    ?>

                                    <label for="edit_asset_type">🏷️ Asset Type *</label>
                                    <select id="edit_asset_type" name="asset_type" required>
                                        <option value="">Select Asset Type</option>
                                        <?php 
                                        if ( !empty($results) ) {
                                            foreach ( $results as $row ) {
                                                echo '<option value="' . esc_attr($row->asset_name) . '">' . esc_html($row->asset_name) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No Asset Types Found</option>';
                                        }
                                        ?>
                                    </select>

                            </div>

                            <div class="form-group">
                                <label for="edit_brand_model">🏭 Brand/Model *</label>
                                <input type="text" id="edit_brand_model" name="brand_model" placeholder="e.g., Dell Inspiron 15" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_serial_number">🔢 Serial Number *</label>
                                <input type="text" id="edit_serial_number" name="serial_number" placeholder="e.g., ABC123456" required onblur="checkSerialNumber('edit')">
                                <div class="serial-error" id="serial_error_edit"></div>
                            </div>
                            <div class="form-group">
                                <label for="edit_quantity">📦 Quantity</label>
                                <input type="number" id="edit_quantity" name="quantity" value="1" min="1" required disabled>
                            </div>

                            <div class="form-group">
                                <label for="edit_price">₹ Price *</label>
                                <input type="number" id="edit_price" name="price" placeholder="Enter price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_status">📊 Status *</label>
                                <select id="edit_status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="In Stock">In Stock</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_location">📍 Location *</label>
                                <select id="edit_location" name="location" required>
                                    <option value="">Select Location</option>
                                    <option value="4th Floor">4th Floor</option>
                                    <option value="6th Floor">6th Floor</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="edit_date_purchased">📅 Date Purchased *</label>
                                <input type="date" id="edit_date_purchased" name="date_purchased" required>
                            </div>

                            <div class="form-group">
                                <label for="edit_warranty_expiry">🛡️ Warranty Expiry</label>
                                <input type="date" id="edit_warranty_expiry" name="warranty_expiry">
                            </div>

                            <div class="form-group">
                                <label for="edit_remarks">💭 Remarks</label>
                                <textarea id="edit_remarks" name="remarks" rows="3" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="edit_submit_button">🔁 Update Stock Item</button>
                        <button type="button" class="btn btn-secondary" onclick="resetEditForm()">↩️ Cancel Edit</button>
                    </form>
                </div>
            </div>

            <!-- List Items Sub Tab -->
            <div id="list-items-tab" class="item-sub-content">
                <div class="items-list-section">
                    <h4>📋 Items List</h4>
                    
                    <!-- Search Box -->
                    <div class="search-container" style="margin-bottom: 20px;">
                        <input type="text" id="item-search" placeholder="🔍 Search items..." style="
                            padding: 10px 15px;
                            border: 2px solid #ddd;
                            border-radius: 25px;
                            width: 300px;
                            max-width: 100%;
                            font-size: 14px;
                            outline: none;
                            transition: all 0.3s ease;
                        " onkeyup="filterItems()">
                    </div>
                    
                    <div id="stock-items-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                        <div style="text-align: center; padding: 40px;">
                            <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                            <p>Loading items...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View List Items Sub Tab (NEW) -->
            <div id="view-list-items-tab" class="item-sub-content">
                <div class="items-list-section">
                    <h4>📊 Grouped Items Summary</h4>
                    
                    <!-- Search Box for Grouped Items -->
                    <div class="search-container" style="margin-bottom: 20px;">
                        <input type="text" id="grouped-item-search" placeholder="🔍 Search grouped items..." style="
                            padding: 10px 15px;
                            border: 2px solid #ddd;
                            border-radius: 25px;
                            width: 300px;
                            max-width: 100%;
                            font-size: 14px;
                            outline: none;
                            transition: all 0.3s ease;
                        " onkeyup="filterGroupedItems()">
                    </div>
                    
                    <div id="grouped-stock-items-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                        <div style="text-align: center; padding: 40px;">
                            <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                            <p>Loading grouped items...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Close Item Tab -->

        <!-- Employee Tab -->

        <!-- Employee Tab -->
        <div id="employee-tab" class="tab-content">
            <h3>👨‍💼 Employee Management</h3>
            <div id="emp-list-container">
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <p>Loading employees...</p>
                </div>
            </div>
        </div>

        <!-- Repaire Tab -->
        <div id="repaire-tab" class="tab-content">
            <h3>🔧 Repair Management</h3>
            
            <!-- Repair Sub Tabs -->
            <div class="repair-sub-tabs">
                <button class="repair-sub-tab-btn active" onclick="showRepairSubTab('add-repair-tab', this)">➕ Add Repair</button>
                <button class="repair-sub-tab-btn" onclick="showRepairSubTab('list-repairs-tab', this)">📋 List Repairs</button>
            </div>

            <!-- Add Repair Sub Tab -->
            <div id="add-repair-tab" class="repair-sub-content active">
                <div class="add-item-section">
                    <form id="add-repair-form">
                        <input type="hidden" name="action" value="add_repair_item">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="repair_serial_number">🔢 Serial Number *</label>
                                <select id="repair_serial_number" name="serial_number" required onchange="loadAssetDetails()">
                                    <option value="">Select Serial Number</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="repair_asset_type">🏷️ Asset Type</label>
                                <input type="text" id="repair_asset_type" name="asset_type" readonly>
                            </div>

                            <div class="form-group">
                                <label for="repair_brand_model">🏭 Brand/Model</label>
                                <input type="text" id="repair_brand_model" name="brand_model" readonly>
                            </div>

                            <div class="form-group">
                                <label for="repair_date">🔧 Repair Date *</label>
                                <input type="date" id="repair_date" name="repair_date" required>
                            </div>

                            <div class="form-group">
                                <label for="return_date">📅 Expected Return Date</label>
                                <input type="date" id="return_date" name="return_date">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="repair_remarks">💭 Repair Remarks *</label>
                                <textarea id="repair_remarks" name="repair_remarks" rows="4" placeholder="Describe the repair issue..." required></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="add_repair_submit_button">✅ Add Repair Record</button>
                        <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
                    </form>
                </div>
            </div>

            <!-- Edit Repair Sub Tab -->
            <div id="edit-repair-tab" class="repair-sub-content">
                <div class="edit-form-container">
                    <h4>✏️ Edit Repair Record</h4>
                    <form id="edit-repair-form">
                        <input type="hidden" name="action" value="update_repair_item">
                        <input type="hidden" name="repair_id" id="edit_repair_id" value="">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_repair_serial_number">🔢 Serial Number</label>
                                <input type="text" id="edit_repair_serial_number" name="serial_number" readonly>
                            </div>

                            <div class="form-group">
                                <label for="edit_repair_asset_type">🏷️ Asset Type</label>
                                <input type="text" id="edit_repair_asset_type" name="asset_type" readonly>
                            </div>

                            <div class="form-group">
                                <label for="edit_repair_brand_model">🏭 Brand/Model</label>
                                <input type="text" id="edit_repair_brand_model" name="brand_model" readonly>
                            </div>

                            <div class="form-group">
                                <label for="edit_repair_date">🔧 Repair Date *</label>
                                <input type="date" id="edit_repair_date" name="repair_date" required>
                            </div>

                            <div class="form-group">
                                <label for="edit_return_date">📅 Expected Return Date</label>
                                <input type="date" id="edit_return_date" name="return_date">
                            </div>

                            <div class="form-group">
                                <label for="edit_repair_status">📊 Status</label>
                                <select id="edit_repair_status" name="status" required>
                                    <option value="Under Repair">Under Repair</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Waiting for Parts">Waiting for Parts</option>
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="edit_repair_remarks">💭 Repair Remarks *</label>
                                <textarea id="edit_repair_remarks" name="repair_remarks" rows="4" required></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="edit_repair_submit_button">🔁 Update Repair Record</button>
                        <button type="button" class="btn btn-secondary" onclick="resetRepairEditForm()">↩️ Cancel Edit</button>
                    </form>
                </div>
            </div>

            <!-- List Repairs Sub Tab -->
            <div id="list-repairs-tab" class="repair-sub-content">
                <div class="items-list-section">
                    <h4>📋 Repair Records</h4>
                    <div id="repair-items-container">
                        <div style="text-align: center; padding: 40px;">
                            <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                            <p>Loading repair records...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Assets Popup -->
        <div id="assign-assets-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: white;">Assign Assets to Employee</h3>
                    <button type="button" onclick="closeAssignPopup()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">×</button>
                </div>
                
                <div class="assign-assets-container">
                    <input type="hidden" id="assign_emp_id">
                    <div class="form-group">
                        <label for="assign_emp_name">👨‍💼 Employee Name</label>
                       <!--  <select id="assign_emp_name" class="assign-emp-select" required>
                            <option value="">Select Employee</option>
                        </select> -->

                        <span id="assign_emp_name" id="assign_emp_name" class="assign-emp-select" style="font-weight: bold;font-size: 18px;padding: 12px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);color: white;border: 2px solid #5a67d8;border-radius: 8px;display: inline-block;min-width: 250px;box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);margin: 5px 0;"></span>
                    </div>
                    
                    <div id="assets-container">
                        <!-- Asset rows will be dynamically added here -->
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button type="button" class="btn btn-primary" onclick="addAssetRow()">➕ Add Asset</button>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button type="button" class="btn btn-primary" onclick="assignAssetsToEmp()">✅ Assign Assets</button>
                        <button type="button" class="btn btn-secondary" onclick="closeAssignPopup()">❌ Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Global variables
            const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
            const nonce = '<?php echo esc_js($nonce); ?>';
            let stockItems = [];
            let repairItems = [];
            let dataTable = null;
            let empDataTable = null;
            let repairDataTable = null;
            let currentEmpId = null;

            // NEW: Filter items function
            function filterItems() {
                const searchTerm = document.getElementById('item-search').value.toLowerCase();
                
                // Try multiple selectors
                const table = document.getElementById('stock-items-table') || 
                              document.querySelector('.stock-table');
                
                if (!table) return;
                
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
            }
            // NEW: Filter grouped items function
            function filterGroupedItems() {
                const searchTerm = document.getElementById('grouped-item-search').value.toLowerCase();
                const table = document.getElementById('grouped-stock-items-table');
                
                if (!table) return;
                
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
            }

            // NEW: Load grouped stock items
            function loadGroupedStockItems() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_grouped_stock_items',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('grouped-stock-items-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading grouped items...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('grouped-stock-items-container').innerHTML = responseData.data.html;
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Failed to load grouped items. Please try again.', 'error');
                    }
                });
            }


            jQuery(document).ready(function($) {
                // Load stock items on page load
                loadStockItems();
                // Destroy existing DataTable instance before reinitializing

                // Handle form submissions
                $('#add-stock-form').on('submit', function(e) {
                    e.preventDefault();
                    saveStockItem('add');
                });
                
                $('#edit-stock-form').on('submit', function(e) {
                    e.preventDefault();
                    saveStockItem('edit');
                });

                // Load employee list when employee tab is shown
                $('.tab-btn').on('click', function() {
                    if ($(this).text().includes('Employee')) {
                        loadEmpList();
                    }
                    if ($(this).text().includes('Repaire')) {
                        loadRepairItems();
                        loadSerialNumbers();
                    }
                });

                // Handle repair form submissions
                $('#add-repair-form').on('submit', function(e) {
                    e.preventDefault();
                    saveRepairItem('add');
                });
                
                $('#edit-repair-form').on('submit', function(e) {
                    e.preventDefault();
                    saveRepairItem('edit');
                });

                // Set current date for repair date
                const today = new Date().toISOString().split('T')[0];
                $('#repair_date').val(today);
                $('#edit_repair_date').val(today);
            });

            function showMainTab(tabName, el) {
                // Hide all main tab contents
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all main tab buttons
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected main tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');

                // Load specific data based on tab
                if (tabName === 'employee-tab') {
                    loadEmpList();
                }
                if (tabName === 'repaire-tab') {
                    loadRepairItems();
                    loadSerialNumbers();
                }
            }

            function showItemSubTab(tabName, el) {
                // Hide all item sub tab contents
                document.querySelectorAll('.item-sub-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all item sub tab buttons
                document.querySelectorAll('.item-sub-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected item sub tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');
                
                // If switching to list tab, reload items
                if (tabName === 'list-items-tab') {
                    loadStockItems();
                }
                // If switching to grouped items tab, load grouped items
                if (tabName === 'view-list-items-tab') {
                    loadGroupedStockItems();
                }
            }

            function showRepairSubTab(tabName, el) {
                // Hide all repair sub tab contents
                document.querySelectorAll('.repair-sub-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all repair sub tab buttons
                document.querySelectorAll('.repair-sub-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected repair sub tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');
                
                // If switching to list tab, reload repair items
                if (tabName === 'list-repairs-tab') {
                    loadRepairItems();
                }
                if (tabName === 'add-repair-tab') {
                    loadSerialNumbers();
                }
            }

            function showSweetAlert(message, type = 'success') {
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

            function setLoading(formType, loading) {
                const submitBtn = document.getElementById(formType + '_submit_button');
                const form = document.getElementById(formType + '-stock-form');
                
                if (loading) {
                    submitBtn.textContent = '⏳ Processing...';
                    submitBtn.disabled = true;
                    if (form) form.classList.add('loading');
                } else {
                    if (formType === 'add') {
                        submitBtn.textContent = '✅ Add Stock Item';
                    } else if (formType === 'edit') {
                        submitBtn.textContent = '🔁 Update Stock Item';
                    } else if (formType === 'add_repair') {
                        submitBtn.textContent = '✅ Add Repair Record';
                    } else if (formType === 'edit_repair') {
                        submitBtn.textContent = '🔁 Update Repair Record';
                    }
                    submitBtn.disabled = false;
                    if (form) form.classList.remove('loading');
                }
            }

            function setRepairLoading(formType, loading) {
                const submitBtn = document.getElementById(formType + '_submit_button');
                const form = document.getElementById(formType + '-repair-form');
                
                if (loading) {
                    submitBtn.textContent = '⏳ Processing...';
                    submitBtn.disabled = true;
                    if (form) form.classList.add('loading');
                } else {
                    if (formType === 'add_repair') {
                        submitBtn.textContent = '✅ Add Repair Record';
                    } else if (formType === 'edit_repair') {
                        submitBtn.textContent = '🔁 Update Repair Record';
                    }
                    submitBtn.disabled = false;
                    if (form) form.classList.remove('loading');
                }
            }

           // Unique Serial Number Validation with auto-hide
            function checkSerialNumber(formType) {
                const serialNumberField = document.getElementById(formType === 'add' ? 'serial_number' : 'edit_serial_number');
                const errorField = document.getElementById(`serial_error_${formType}`);
                const submitButton = document.getElementById(`${formType}_submit_button`);
                const serialNumber = serialNumberField.value.trim();

                // Clear any existing timeout
                if (window.serialCheckTimeout) {
                    clearTimeout(window.serialCheckTimeout);
                }

                // Apply consistent styling
                errorField.style.cssText = `
                    display: block;
                    padding: 10px 12px;
                    border-radius: 6px;
                    margin-top: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    text-align: center;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                `;

                if (!serialNumber) {
                    errorField.style.display = 'none';
                    submitButton.disabled = false;
                    return;
                }

                // Show loading state
                errorField.textContent = '⏳ Checking serial number...';
                errorField.style.display = 'block';
                errorField.style.color = '#856404';
                errorField.style.backgroundColor = '#fff3cd';
                errorField.style.border = '1px solid #ffeaa7';
                submitButton.disabled = true;

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'check_serial_number',
                        serial_number: serialNumber,
                        form_type: formType,
                        item_id: formType === 'edit' ? document.getElementById('edit_item_id').value : 0,
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            }
                        }
                        
                        if (responseData.success) {
                            if (responseData.data.exists) {
                                errorField.textContent = '❌ Serial number already exists!';
                                errorField.style.color = '#721c24';
                                errorField.style.backgroundColor = '#f8d7da';
                                errorField.style.border = '1px solid #f5c6cb';
                                submitButton.disabled = true;
                            } else {
                                errorField.textContent = '✅ Serial number available';
                                errorField.style.color = '#155724';
                                errorField.style.backgroundColor = '#d4edda';
                                errorField.style.border = '1px solid #c3e6cb';
                                submitButton.disabled = false;
                            }
                        } else {
                            errorField.textContent = '❌ Error checking serial number';
                            errorField.style.color = '#721c24';
                            errorField.style.backgroundColor = '#f8d7da';
                            errorField.style.border = '1px solid #f5c6cb';
                            submitButton.disabled = true;
                        }

                        // Auto-hide after 3 seconds (changed from 1 to 3 seconds for better UX)
                        window.serialCheckTimeout = setTimeout(() => {
                            errorField.style.opacity = '0';
                            errorField.style.transform = 'translateY(-10px)';
                            setTimeout(() => {
                                errorField.style.display = 'none';
                                errorField.style.opacity = '1';
                                errorField.style.transform = 'translateY(0)';
                            }, 300);
                        }, 3000);

                    },
                    error: function() {
                        errorField.textContent = '❌ Network error - Please try again';
                        errorField.style.color = '#721c24';
                        errorField.style.backgroundColor = '#f8d7da';
                        errorField.style.border = '1px solid #f5c6cb';
                        submitButton.disabled = true;

                        // Auto-hide after 3 seconds
                        window.serialCheckTimeout = setTimeout(() => {
                            errorField.style.opacity = '0';
                            errorField.style.transform = 'translateY(-10px)';
                            setTimeout(() => {
                                errorField.style.display = 'none';
                                errorField.style.opacity = '1';
                                errorField.style.transform = 'translateY(0)';
                            }, 300);
                        }, 3000);
                    }
                });
            }
            // Load serial numbers for repair form
            function loadSerialNumbers() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_serial_numbers',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            }
                        }
                        
                        const select = document.getElementById('repair_serial_number');
                        if (responseData.success) {
                            select.innerHTML = '<option value="">Select Serial Number</option>';
                            responseData.data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.serial_number;
                                option.textContent = `${item.serial_number}`;
                                option.setAttribute('data-asset-type', item.asset_type);
                                option.setAttribute('data-brand-model', item.brand_model);
                                select.appendChild(option);
                            });
                        } else {
                            select.innerHTML = '<option value="">Error loading serial numbers</option>';
                        }
                    },
                    error: function() {
                        const select = document.getElementById('repair_serial_number');
                        select.innerHTML = '<option value="">Error loading serial numbers</option>';
                    }
                });
            }

            // Load asset details when serial number is selected
            function loadAssetDetails() {
                const select = document.getElementById('repair_serial_number');
                const selectedOption = select.options[select.selectedIndex];
                
                if (selectedOption.value) {
                    document.getElementById('repair_asset_type').value = selectedOption.getAttribute('data-asset-type');
                    document.getElementById('repair_brand_model').value = selectedOption.getAttribute('data-brand-model');
                } else {
                    document.getElementById('repair_asset_type').value = '';
                    document.getElementById('repair_brand_model').value = '';
                }
            }

            // Stock Management Functions
            function loadStockItems() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_stock_items',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('stock-items-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading items...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('stock-items-container').innerHTML = responseData.data.html;
                            stockItems = responseData.data.items || [];
                            
                            // FIX: Destroy existing instance before reinitializing
                            if (dataTable) {
                                dataTable.destroy();
                                dataTable = null;
                            }
                            
                            // FIX: Initialize with delay
                            setTimeout(function() {
                                initializeDataTable();
                            }, 1000);
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        const responseText = xhr.responseText;
                        const jsonMatch = responseText.match(/\{.*\}/s);
                        if (jsonMatch) {
                            try {
                                const responseData = JSON.parse(jsonMatch[0]);
                                if (responseData.success) {
                                    document.getElementById('stock-items-container').innerHTML = responseData.data.html;
                                    stockItems = responseData.data.items || [];
                                    
                                    // FIX: Apply same fix for error case
                                    if (dataTable) {
                                        dataTable.destroy();
                                        dataTable = null;
                                    }
                                    
                                    setTimeout(function() {
                                        initializeDataTable();
                                    }, 1000);
                                    return;
                                }
                            } catch (e) {}
                        }
                        showSweetAlert('Failed to load items. Please try again.', 'error');
                    }
                });
            }

            function initializeEmpDataTable() {
                // Check if table exists
                const empTable = document.getElementById('emp-table');
                if (!empTable) {
                    console.log('Employee table not found');
                    return;
                }
                
                // Check if DataTable is already initialized
                if (jQuery.fn.DataTable.isDataTable('#emp-table')) {
                    empDataTable = jQuery('#emp-table').DataTable();
                    return;
                }
                
                // Destroy existing instance if it exists
                if (empDataTable) {
                    empDataTable.destroy();
                    empDataTable = null;
                }
                
                empDataTable = jQuery('#emp-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            function saveStockItem(formType) {
                const form = document.getElementById(formType + '-stock-form');
                const formData = new FormData(form);
                formData.append('nonce', nonce);

                setLoading(formType, true);

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        setLoading(formType, false);
                        
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            form.reset();
                            loadStockItems();
                            
                            if (formType === 'edit') {
                                showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));
                                resetEditForm();
                            }
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        setLoading(formType, false);
                        const responseText = xhr.responseText;
                        const jsonMatch = responseText.match(/\{.*\}/s);
                        if (jsonMatch) {
                            try {
                                const responseData = JSON.parse(jsonMatch[0]);
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    form.reset();
                                    loadStockItems();
                                    if (formType === 'edit') {
                                        showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));
                                        resetEditForm();
                                    }
                                    return;
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                    return;
                                }
                            } catch (e) {}
                        }
                        showSweetAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editItem(id) {
                const item = stockItems.find(item => parseInt(item.id) === parseInt(id));
                if (!item) {
                    showSweetAlert('Item not found.', 'error');
                    return;
                }

                showItemSubTab('edit-item-tab', document.querySelector('.item-sub-tab-btn:nth-child(3)'));

                // FIX: Use correct element IDs for edit form
                document.getElementById('edit_asset_type').value = item.asset_type || '';
                document.getElementById('edit_brand_model').value = item.brand_model || '';
                document.getElementById('edit_serial_number').value = item.serial_number || '';
                document.getElementById('edit_quantity').value = item.quantity || '1';
                document.getElementById('edit_price').value = item.price || '';
                document.getElementById('edit_status').value = item.status || '';
                document.getElementById('edit_location').value = item.location || '';
                document.getElementById('edit_date_purchased').value = item.date_purchased || '';
                document.getElementById('edit_warranty_expiry').value = item.warranty_expiry || '';
                document.getElementById('edit_remarks').value = item.remarks || '';
                document.getElementById('edit_item_id').value = item.id;

                // Clear serial number validation
                document.getElementById('serial_error_edit').style.display = 'none';
                document.getElementById('edit_submit_button').disabled = false;
            }

            function resetEditForm() {
                document.getElementById('edit-stock-form').reset();
                document.getElementById('edit_item_id').value = '';
                document.getElementById('serial_error_edit').style.display = 'none';
                document.getElementById('edit_submit_button').disabled = false;
                showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(3)'));
            }

            function deleteItem(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_stock_item',
                                item_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                let responseData = response;
                                if (typeof response === 'string') {
                                    const jsonMatch = response.match(/\{.*\}/s);
                                    if (jsonMatch) {
                                        try {
                                            responseData = JSON.parse(jsonMatch[0]);
                                        } catch (e) {
                                            showSweetAlert('Response format error', 'error');
                                            return;
                                        }
                                    } else {
                                        showSweetAlert('Invalid response format', 'error');
                                        return;
                                    }
                                }
                                
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    loadStockItems();
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                const responseText = xhr.responseText;
                                const jsonMatch = responseText.match(/\{.*\}/s);
                                if (jsonMatch) {
                                    try {
                                        const responseData = JSON.parse(jsonMatch[0]);
                                        if (responseData.success) {
                                            showSweetAlert(responseData.data, 'success');
                                            loadStockItems();
                                            return;
                                        } else {
                                            showSweetAlert('Error: ' + responseData.data, 'error');
                                            return;
                                        }
                                    } catch (e) {}
                                }
                                showSweetAlert('Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            }

            // Repair Management Functions
            function loadRepairItems() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_repair_items',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('repair-items-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading repair records...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('repair-items-container').innerHTML = responseData.data.html;
                            repairItems = responseData.data.items || [];
                            initializeRepairDataTable();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Failed to load repair records. Please try again.', 'error');
                    }
                });
            }

            function initializeRepairDataTable() {
                if (repairDataTable) {
                    repairDataTable.destroy();
                }
                
                repairDataTable = jQuery('#repair-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            function saveRepairItem(formType) {
                const form = document.getElementById(formType + '-repair-form');
                const formData = new FormData(form);
                formData.append('nonce', nonce);

                setRepairLoading(formType + '_repair', true);

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        setRepairLoading(formType + '_repair', false);
                        
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            form.reset();
                            loadRepairItems();
                            loadSerialNumbers();
                            
                            if (formType === 'edit') {
                                showRepairSubTab('list-repairs-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));
                                resetRepairEditForm();
                            }

                            // Reset date to today
                            const today = new Date().toISOString().split('T')[0];
                            if (formType === 'add') {
                                document.getElementById('repair_date').value = today;
                            }
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        setRepairLoading(formType + '_repair', false);
                        showSweetAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editRepairItem(id) {
                const item = repairItems.find(item => parseInt(item.id) === parseInt(id));
                if (!item) {
                    showSweetAlert('Repair record not found.', 'error');
                    return;
                }

                showRepairSubTab('edit-repair-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));

                document.getElementById('edit_repair_serial_number').value = item.serial_number || '';
                document.getElementById('edit_repair_asset_type').value = item.asset_type || '';
                document.getElementById('edit_repair_brand_model').value = item.brand_model || '';
                document.getElementById('edit_repair_date').value = item.repair_date || '';
                document.getElementById('edit_return_date').value = item.return_date || '';
                document.getElementById('edit_repair_status').value = item.status || '';
                document.getElementById('edit_repair_remarks').value = item.repair_remarks || '';
                document.getElementById('edit_repair_id').value = item.id;
            }

            function resetRepairEditForm() {
                document.getElementById('edit-repair-form').reset();
                document.getElementById('edit_repair_id').value = '';
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('edit_repair_date').value = today;
                showRepairSubTab('list-repairs-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));
            }

            function deleteRepairItem(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_repair_item',
                                repair_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                let responseData = response;
                                if (typeof response === 'string') {
                                    const jsonMatch = response.match(/\{.*\}/s);
                                    if (jsonMatch) {
                                        try {
                                            responseData = JSON.parse(jsonMatch[0]);
                                        } catch (e) {
                                            showSweetAlert('Response format error', 'error');
                                            return;
                                        }
                                    } else {
                                        showSweetAlert('Invalid response format', 'error');
                                        return;
                                    }
                                }
                                
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    loadRepairItems();
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                showSweetAlert('Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            }

            // Employee Management Functions (keep existing)
            function loadEmpList() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_emp_list',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('emp-list-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading employees...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('emp-list-container').innerHTML = responseData.data.html;
                            initializeEmpDataTable();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Failed to load employees. Please try again.', 'error');
                    }
                });
            }

            function initializeEmpDataTable() {
                if (empDataTable) {
                    empDataTable.destroy();
                }
                
                empDataTable = jQuery('#emp-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            // Asset Assignment Functions (keep existing)
            function openAssignPopup(empId, empName) {
                currentEmpId = empId;
                document.getElementById('assign_emp_id').value = empId;
                
                // Set the employee name directly in the span
                document.getElementById('assign_emp_name').textContent = empName;
                
                // Load previously assigned assets
                loadAssignedAssets(empId);
                
                document.getElementById('assign-assets-popup').style.display = 'flex';
            }

            function closeAssignPopup() {
                document.getElementById('assign-assets-popup').style.display = 'none';
                // Reset assets container
                document.getElementById('assets-container').innerHTML = '';
                currentEmpId = null;
            }

           function loadEmployeeDropdown() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_emp_list',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            const span = document.getElementById('assign_emp_name');
                            
                            responseData.data.items.forEach(emp => {
                                // Append each employee name to the span
                                // You can choose how you want to display multiple names
                                // Option 1: Show all names separated by commas
                                if (span.textContent === '') {
                                    span.textContent = emp.emp_name;
                                } else {
                                    span.textContent += ', ' + emp.emp_name;
                                }
                                
                                // Option 2: Show as list (if you prefer bullet points)
                                // span.innerHTML += '<div>• ' + emp.emp_name + '</div>';
                                
                                // Option 3: Show only the first employee (if that's what you need)
                                // if (span.textContent === '') {
                                //     span.textContent = emp.emp_name;
                                // }
                            });
                        }
                    }
                });
            }

            function loadAssignedAssets(empId) {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_assigned_assets',
                        emp_id: empId,
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            } else {
                                console.error('Invalid response format');
                                return;
                            }
                        }
                        
                        const assetsContainer = document.getElementById('assets-container');
                        assetsContainer.innerHTML = '';
                        
                        if (responseData.success && responseData.data.length > 0) {
                            // Load previously assigned assets
                            responseData.data.forEach((asset, index) => {
                                addAssetRow(asset.asset_type, asset.brand_model);
                            });
                        } else {
                            // Add one empty row if no assets assigned
                            addAssetRow();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading assigned assets:', error);
                        // Add one empty row on error
                        addAssetRow();
                    }
                });
            }

            function addAssetRow(assetType = '', brandModel = '') {
                const container = document.getElementById('assets-container');
                const index = container.children.length;
                
                const row = document.createElement('div');
                row.className = 'asset-row';
                row.setAttribute('data-index', index);
                row.innerHTML = `
                    <div class="form-group">
                        <label>🏷️ Asset Type</label>
                        <select class="asset-type-select" data-index="${index}" required onchange="loadBrandModels(this)">
                            <option value="">Select Asset Type</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🏭 Brand/Model</label>
                        <select class="brand-model-select" data-index="${index}" required>
                            <option value="">Select Brand/Model</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-danger" onclick="removeAssetRow(${index})">🗑️ Remove</button>
                    </div>
                `;
                
                container.appendChild(row);
                
                // Load asset types for the new row
                loadAssetTypesForRow(index, assetType, brandModel);
            }

            function loadAssetTypesForRow(index, selectedAssetType = '', selectedBrandModel = '') {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_asset_types',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            const select = document.querySelector(`.asset-type-select[data-index="${index}"]`);
                            select.innerHTML = '<option value="">Select Asset Type</option>';
                            
                            responseData.data.forEach(assetType => {
                                const option = document.createElement('option');
                                option.value = assetType;
                                option.textContent = assetType;
                                if (assetType === selectedAssetType) {
                                    option.selected = true;
                                }
                                select.appendChild(option);
                            });
                            
                            // If an asset type was preselected, load its brand models
                            if (selectedAssetType) {
                                loadBrandModelsForRow(index, selectedAssetType, selectedBrandModel);
                            }
                        }
                    }
                });
            }

            function loadBrandModels(selectElement) {
                const index = selectElement.getAttribute('data-index');
                const assetType = selectElement.value;
                
                if (!assetType) {
                    const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                    brandModelSelect.innerHTML = '<option value="">Select Brand/Model</option>';
                    return;
                }

                loadBrandModelsForRow(index, assetType);
            }

            function loadBrandModelsForRow(index, assetType, selectedBrandModel = '') {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_brands_by_asset',
                        asset_type: assetType,
                        nonce: nonce
                    },
                    success: function(response) {
                        const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                        
                        if (typeof response === 'string') {
                            brandModelSelect.innerHTML = response;
                            
                            // If a brand model was preselected, select it
                            if (selectedBrandModel) {
                                setTimeout(() => {
                                    brandModelSelect.value = selectedBrandModel;
                                }, 100);
                            }
                        } else {
                            brandModelSelect.innerHTML = '<option value="">Error loading data</option>';
                        }
                    },
                    error: function() {
                        const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                        brandModelSelect.innerHTML = '<option value="">Error loading data</option>';
                    }
                });
            }

            function removeAssetRow(index) {
                const row = document.querySelector(`.asset-row[data-index="${index}"]`);
                if (row) {
                    row.remove();
                    
                    // Reindex remaining rows
                    const rows = document.querySelectorAll('.asset-row');
                    rows.forEach((row, newIndex) => {
                        row.setAttribute('data-index', newIndex);
                        const assetSelect = row.querySelector('.asset-type-select');
                        const brandSelect = row.querySelector('.brand-model-select');
                        assetSelect.setAttribute('data-index', newIndex);
                        brandSelect.setAttribute('data-index', newIndex);
                        
                        // Update remove button onclick
                        const removeBtn = row.querySelector('.btn-danger');
                        removeBtn.setAttribute('onclick', `removeAssetRow(${newIndex})`);
                    });
                }
            }

            function assignAssetsToEmp() {
                const empId = document.getElementById('assign_emp_id').value;
                const assetRows = document.querySelectorAll('.asset-row');
                const assets = [];

                // FIX: Remove validation for at least one asset
                // Allow empty assignment (removing all assets from employee)
                let isValid = true;
                assetRows.forEach(row => {
                    const assetType = row.querySelector('.asset-type-select').value;
                    const brandModel = row.querySelector('.brand-model-select').value;
                    
                    // Only add if both fields are filled
                    if (assetType && brandModel) {
                        assets.push({
                            asset_type: assetType,
                            brand_model: brandModel
                        });
                    } else if (assetType || brandModel) {
                        // If only one field is filled, show error
                        isValid = false;
                        showSweetAlert('Please fill all asset fields or remove incomplete rows.', 'error');
                        return;
                    }
                });

                // FIX: Allow empty assets array (no validation for at least one asset)
                // This allows removing all assets from an employee

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'assign_assets_to_emp',
                        emp_id: empId,
                        assets: JSON.stringify(assets),
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            closeAssignPopup();
                            loadEmpList();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Server error. Please try again.', 'error');
                    }

                });
            }


        </script>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX function to check unique serial number
function ajax_check_serial_number() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['serial_number'])) {
            echo json_encode(array('success' => false, 'data' => 'Serial number is required'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        $serial_number = sanitize_text_field($_POST['serial_number']);
        $form_type = sanitize_text_field($_POST['form_type']);
        $item_id = intval($_POST['item_id']);

        // Check if serial number exists
        if ($form_type === 'edit') {
            // For edit, exclude current item
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE serial_number = %s AND id != %d",
                $serial_number,
                $item_id
            );
        } else {
            // For add, check all records
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE serial_number = %s",
                $serial_number
            );
        }

        $count = $wpdb->get_var($query);

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'exists' => $count > 0
            )
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to get serial numbers for repair form
function ajax_get_serial_numbers() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        // Get all serial numbers with asset details
        $results = $wpdb->get_results("
            SELECT serial_number, asset_type, brand_model 
            FROM $table_name 
            WHERE status = 'Active' 
            ORDER BY serial_number ASC
        ");

        echo json_encode(array(
            'success' => true,
            'data' => $results
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to add repair item
function ajax_add_repair_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'repaire_stock_management';

        // Get asset details from stock_management table
        $stock_table = $wpdb->prefix . 'stock_management';
        $serial_number = sanitize_text_field($_POST['serial_number']);
        
        $asset_details = $wpdb->get_row($wpdb->prepare(
            "SELECT asset_type, brand_model FROM $stock_table WHERE serial_number = %s",
            $serial_number
        ));

        if (!$asset_details) {
            echo json_encode(array('success' => false, 'data' => 'Invalid serial number'));
            exit;
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'serial_number' => $serial_number,
                'asset_type' => $asset_details->asset_type,
                'brand_model' => $asset_details->brand_model,
                'repair_remarks' => sanitize_textarea_field($_POST['repair_remarks']),
                'repair_date' => sanitize_text_field($_POST['repair_date']),
                'return_date' => sanitize_text_field($_POST['return_date']),
                'status' => 'Under Repair'
            )
        );

        if ($result) {
            // Log the repair action
            log_repair_action('add', $wpdb->insert_id, null, array(
                'serial_number' => $serial_number,
                'asset_type' => $asset_details->asset_type,
                'brand_model' => $asset_details->brand_model,
                'repair_remarks' => sanitize_textarea_field($_POST['repair_remarks']),
                'repair_date' => sanitize_text_field($_POST['repair_date']),
                'return_date' => sanitize_text_field($_POST['return_date']),
                'status' => 'Under Repair'
            ));
            
            echo json_encode(array('success' => true, 'data' => 'Repair record added successfully!'));
        } else {
            echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to get repair items
function ajax_get_repair_items() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'repaire_stock_management';

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        if (empty($results)) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<p>No repair records found.</p>',
                    'items' => array()
                )
            ));
            exit;
        }

        $html = '<table id="repair-table" class="stock-table" style="width:100%">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>Serial Number</th>';
        $html .= '<th>Asset Type</th>';
        $html .= '<th>Brand/Model</th>';
        $html .= '<th>Repair Date</th>';
        $html .= '<th>Return Date</th>';
        $html .= '<th>Status</th>';
        $html .= '<th>Remarks</th>';
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($results as $row) {
            $status_class = 'status-' . strtolower(str_replace(' ', '-', $row->status));

            $html .= '<tr>';
            $html .= '<td>' . intval($row->id) . '</td>';
            $html .= '<td>' . esc_html($row->serial_number) . '</td>';
            $html .= '<td>' . esc_html($row->asset_type) . '</td>';
            $html .= '<td>' . esc_html($row->brand_model) . '</td>';
            $html .= '<td>' . esc_html($row->repair_date) . '</td>';
            $html .= '<td>' . esc_html($row->return_date) . '</td>';
            $html .= '<td class="' . esc_attr($status_class) . '">' . esc_html($row->status) . '</td>';
            $html .= '<td>' . esc_html(wp_trim_words($row->repair_remarks, 10)) . '</td>';
            $html .= '<td style="display:flex;">';
            $html .= '<button class="btn btn-warning" onclick="editRepairItem(' . intval($row->id) . ')">✏️ Edit</button> ';
            $html .= '<button class="btn btn-danger" onclick="deleteRepairItem(' . intval($row->id) . ')">🗑️ Delete</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $html,
                'items' => $results
            )
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to update repair item
function ajax_update_repair_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['repair_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Missing repair ID'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'repaire_stock_management';

        $repair_id = intval($_POST['repair_id']);
        
        // Get old data before update
        $old_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", 
            $repair_id
        ), ARRAY_A);

        $result = $wpdb->update(
            $table_name,
            array(
                'repair_remarks' => sanitize_textarea_field($_POST['repair_remarks']),
                'repair_date' => sanitize_text_field($_POST['repair_date']),
                'return_date' => sanitize_text_field($_POST['return_date']),
                'status' => sanitize_text_field($_POST['status'])
            ),
            array('id' => $repair_id)
        );

        if ($result !== false) {
            // Get new data after update
            $new_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d", 
                $repair_id
            ), ARRAY_A);
            
            // Log the repair action
            log_repair_action('edit', $repair_id, $old_data, $new_data);
            
            echo json_encode(array('success' => true, 'data' => 'Repair record updated successfully!'));
        } else {
            echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to delete repair item
function ajax_delete_repair_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['repair_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Missing repair ID'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'repaire_stock_management';

        $repair_id = intval($_POST['repair_id']);
        
        // Get data before deletion
        $old_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", 
            $repair_id
        ), ARRAY_A);

        $result = $wpdb->delete(
            $table_name,
            array('id' => $repair_id)
        );

        if ($result) {
            // Log the repair action
            log_repair_action('delete', $repair_id, $old_data, null);
            
            echo json_encode(array('success' => true, 'data' => 'Repair record deleted successfully!'));
        } else {
            echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to get stock items
function ajax_get_stock_items() {
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    if ($is_ajax && isset($_POST['action']) && $_POST['action'] === 'get_stock_items') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
    }
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            create_stock_management_table();
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
       
        if (empty($results)) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<p>No stock items found. Add some items to get started!</p>',
                    'items' => array()
                )
            ));
            exit;
        }

        $html = '<table class="stock-table" style="width:100%">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>Asset Type</th><th>Brand/Model</th><th>Serial Number</th>';
        $html .= '<th>Quantity</th><th>Price</th>';
        $html .= '<th>Status</th><th>Location</th>';
        $html .= '<th>Date Purchased</th>';
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($results as $row) {
            $status_class = 'status-' . strtolower($row->status);

            $html .= '<tr>';
            $html .= '<td>' . intval($row->id) . '</td>';
            $html .= '<td>' . esc_html($row->asset_type) . '</td>';
            $html .= '<td>' . esc_html($row->brand_model) . '</td>';
            $html .= '<td>' . esc_html($row->serial_number) . '</td>';
            $html .= '<td>' . intval($row->quantity) . '</td>';
            $html .= '<td>₹' . number_format((float) $row->price, 2) . '</td>';
            $html .= '<td class="' . esc_attr($status_class) . '">' . esc_html($row->status) . '</td>';
            $html .= '<td>' . esc_html($row->location) . '</td>';
            $html .= '<td>' . esc_html($row->date_purchased) . '</td>';
            $html .= '<td style="display:flex;">';
            $html .= '<button class="btn btn-warning" onclick="editItem(' . intval($row->id) . ')">✏️ Edit</button> ';
            $html .= '<button class="btn btn-danger" onclick="deleteItem(' . intval($row->id) . ')">🗑️ Delete</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $html,
                'items' => $results
            )
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to get employee list
function ajax_get_emp_list() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'admin_emp_stock_management';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<p>Employee table not found. Please contact administrator.</p>',
                    'items' => array()
                )
            ));
            exit;
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

        if (empty($results)) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<p>No employees found.</p>',
                    'items' => array()
                )
            ));
            exit;
        }

        $html = '<table id="emp-table" class="stock-table" style="width:100%">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>Name</th>';
        $html .= '<th>Email</th>';
        $html .= '<th>Position</th>';
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($results as $row) {
            $html .= '<tr>';
            $html .= '<td>' . intval($row->id) . '</td>';
            $html .= '<td>' . esc_html($row->emp_name) . '</td>';
            $html .= '<td>' . esc_html($row->emp_email) . '</td>';
            $html .= '<td>' . esc_html($row->emp_position) . '</td>';
            $html .= '<td>';
            $html .= '<button class="btn btn-primary" onclick="openAssignPopup(' . intval($row->id) . ', \'' . esc_js($row->emp_name) . '\')">📋 Assign Assets</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $html,
                'items' => $results
            )
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to get asset types from admin_item_stock_management table
function ajax_get_asset_types() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'admin_item_stock_management';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo json_encode(array('success' => false, 'data' => 'Asset types table not found'));
            exit;
        }

        // Get active asset types
        $asset_types = $wpdb->get_col("SELECT DISTINCT asset_name FROM $table_name WHERE status = 'active' ORDER BY asset_name ASC");

        echo json_encode(array(
            'success' => true,
            'data' => $asset_types
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to get brand models based on asset type from stock_management table
function ajax_get_brands_by_asset() {
    while (ob_get_level()) { ob_end_clean(); }
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo '<option value="">Security error</option>';
            exit;
        }

        if (!isset($_POST['asset_type'])) {
            echo '<option value="">Asset type required</option>';
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        $asset_type = sanitize_text_field($_POST['asset_type']);
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<option value="">Table not found</option>';
            exit;
        }

        $brand_models = $wpdb->get_col($wpdb->prepare(
            "SELECT  CONCAT(brand_model, ' (', serial_number, ')') as display_text FROM $table_name WHERE asset_type = %s AND brand_model IS NOT NULL AND brand_model != '' ORDER BY brand_model ASC",
            $asset_type
        ));
  

        if (empty($brand_models)) {
            echo '<option value="">No brands found</option>';
            exit;
        }

        $html = '<option value="">Select Brand/Model</option>';
        foreach ($brand_models as $brand_model) {
            $html .= '<option value="' . esc_attr($brand_model) . '">' . esc_html($brand_model) . '</option>';
        }
        
        echo $html;
        exit;

    } catch (Exception $e) {
        echo '<option value="">Error loading data</option>';
        exit;
    }
}

// AJAX function to get assigned assets for an employee
function ajax_get_assigned_assets() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['emp_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Employee ID is required'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'emp_asset_assign_table';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo json_encode(array('success' => true, 'data' => array()));
            exit;
        }

        $emp_id = intval($_POST['emp_id']);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE emp_id = %d ORDER BY id ASC",
            $emp_id
        ));

        echo json_encode(array(
            'success' => true,
            'data' => $results
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}

// AJAX function to assign assets to employee
function ajax_assign_assets_to_emp() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['emp_id']) || !isset($_POST['assets'])) {
            echo json_encode(array('success' => false, 'data' => 'Employee ID and assets are required'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'emp_asset_assign_table';

        // Check if table exists, create if not
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            create_emp_asset_assign_table();
        }

        $emp_id = intval($_POST['emp_id']);
        $assets = json_decode(stripslashes($_POST['assets']), true);

        // Get old assigned assets before update
        $old_assets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE emp_id = %d ORDER BY id ASC",
            $emp_id
        ), ARRAY_A);

        // First, delete existing assignments for this employee
        $wpdb->delete($table_name, array('emp_id' => $emp_id));

        $success_count = 0;
        $new_assignments = array();
        
        foreach ($assets as $asset) {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'emp_id' => $emp_id,
                    'asset_type' => sanitize_text_field($asset['asset_type']),
                    'brand_model' => sanitize_text_field($asset['brand_model'])
                )
            );
            
            if ($result) {
                $success_count++;
                $new_assignments[] = array(
                    'assign_id' => $wpdb->insert_id,
                    'asset_type' => sanitize_text_field($asset['asset_type']),
                    'brand_model' => sanitize_text_field($asset['brand_model'])
                );
            }
        }

        if ($success_count > 0) {
            // Log asset assignment action
            foreach ($new_assignments as $assignment) {
                log_asset_assign_action('assign', $assignment['assign_id'], $emp_id, array(
                    'asset_type' => $assignment['asset_type'],
                    'brand_model' => $assignment['brand_model']
                ));
            }
            
            echo json_encode(array('success' => true, 'data' => "Successfully assigned $success_count asset(s) to employee."));
        } else {
            // Log removal of all assets
            foreach ($old_assets as $old_asset) {
                log_asset_assign_action('remove', $old_asset['id'], $emp_id, array(
                    'asset_type' => $old_asset['asset_type'],
                    'brand_model' => $old_asset['brand_model']
                ));
            }
            
            echo json_encode(array('success' => true, 'data' => "All assets have been removed from this employee."));
        }
        exit;

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to add stock item
function ajax_add_stock_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        $result = $wpdb->insert(
            $table_name,
            array(
                'asset_type' => sanitize_text_field($_POST['asset_type']),
                'brand_model' => sanitize_text_field($_POST['brand_model']),
                'serial_number' => sanitize_text_field($_POST['serial_number']),
                'quantity' => 1,
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
                'location' => sanitize_text_field($_POST['location']),
                'date_purchased' => sanitize_text_field($_POST['date_purchased']),
                'warranty_expiry' => sanitize_text_field($_POST['warranty_expiry']),
                'remarks' => sanitize_textarea_field($_POST['remarks']),
                'custom_fields' => ''
            )
        );

        if ($result) {
            // Log the item action
            log_item_action('add', $wpdb->insert_id, null, array(
                'asset_type' => sanitize_text_field($_POST['asset_type']),
                'brand_model' => sanitize_text_field($_POST['brand_model']),
                'serial_number' => sanitize_text_field($_POST['serial_number']),
                'quantity' => 1,
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
                'location' => sanitize_text_field($_POST['location']),
                'date_purchased' => sanitize_text_field($_POST['date_purchased']),
                'warranty_expiry' => sanitize_text_field($_POST['warranty_expiry']),
                'remarks' => sanitize_textarea_field($_POST['remarks'])
            ));
            
            echo json_encode(array('success' => true, 'data' => 'Stock item added successfully!'));
        } else {
            // Check if it's a duplicate serial number error
            if (strpos($wpdb->last_error, 'Duplicate entry') !== false && strpos($wpdb->last_error, 'serial_number') !== false) {
                echo json_encode(array('success' => false, 'data' => 'Serial number already exists! Please use a unique serial number.'));
            } else {
                echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
            }
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to update stock item
function ajax_update_stock_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['item_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Missing item ID'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        $item_id = intval($_POST['item_id']);
        
        // Get old data before update
        $old_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", 
            $item_id
        ), ARRAY_A);

        $result = $wpdb->update(
            $table_name,
            array(
                'asset_type' => sanitize_text_field($_POST['asset_type']),
                'brand_model' => sanitize_text_field($_POST['brand_model']),
                'serial_number' => sanitize_text_field($_POST['serial_number']),
                'quantity' => 1,
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
                'location' => sanitize_text_field($_POST['location']),
                'date_purchased' => sanitize_text_field($_POST['date_purchased']),
                'warranty_expiry' => sanitize_text_field($_POST['warranty_expiry']),
                'remarks' => sanitize_textarea_field($_POST['remarks'])
            ),
            array('id' => $item_id)
        );

        if ($result !== false) {
            // Get new data after update
            $new_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d", 
                $item_id
            ), ARRAY_A);
            
            // Log the item action
            log_item_action('edit', $item_id, $old_data, $new_data);
            
            echo json_encode(array('success' => true, 'data' => 'Stock item updated successfully!'));
        } else {
            // Check if it's a duplicate serial number error
            if (strpos($wpdb->last_error, 'Duplicate entry') !== false && strpos($wpdb->last_error, 'serial_number') !== false) {
                echo json_encode(array('success' => false, 'data' => 'Serial number already exists! Please use a unique serial number.'));
            } else {
                echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
            }
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// AJAX function to delete stock item
function ajax_delete_stock_item() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['item_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Missing item ID'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        $item_id = intval($_POST['item_id']);
        
        // Get data before deletion
        $old_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", 
            $item_id
        ), ARRAY_A);

        $result = $wpdb->delete(
            $table_name,
            array('id' => $item_id)
        );

        if ($result) {
            // Log the item action
            log_item_action('delete', $item_id, $old_data, null);
            
            echo json_encode(array('success' => true, 'data' => 'Stock item deleted successfully!'));
        } else {
            echo json_encode(array('success' => false, 'data' => 'Database error: ' . $wpdb->last_error));
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Server error: ' . $e->getMessage()));
        exit;
    }
}

// Simplified AJAX functions for custom fields
function ajax_add_custom_field() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'data' => 'Custom fields feature coming soon'));
    exit;
}

function ajax_delete_custom_field() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'data' => 'Custom fields feature coming soon'));
    exit;
}



add_action('wp_ajax_get_grouped_stock_items', 'ajax_get_grouped_stock_items');
add_action('wp_ajax_nopriv_get_grouped_stock_items', 'ajax_get_grouped_stock_items');


// NEW AJAX FUNCTION: Get grouped stock items
function ajax_get_grouped_stock_items() {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            create_stock_management_table();
        }

        // Group by asset_type and brand_model, calculate totals
        $results = $wpdb->get_results("
            SELECT 
                asset_type,
                brand_model,
                COUNT(*) as total_quantity,
                SUM(price) as total_price,
                GROUP_CONCAT(id) as item_ids
            FROM $table_name 
            GROUP BY asset_type, brand_model 
            ORDER BY asset_type, brand_model
        ");
       
        if (empty($results)) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<p>No stock items found. Add some items to get started!</p>',
                    'items' => array()
                )
            ));
            exit;
        }

        $html = '<table id="grouped-stock-items-table" class="stock-table" style="width:100%">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>Asset Type</th>';
        $html .= '<th>Brand/Model</th>';
        $html .= '<th>Total Quantity</th>';
        $html .= '<th>Total Price</th>';
        $html .= '</tr></thead><tbody>';

        $counter = 1;
        foreach ($results as $row) {
            $html .= '<tr>';
            $html .= '<td>' . intval($counter) . '</td>';
            $html .= '<td>' . esc_html($row->asset_type) . '</td>';
            $html .= '<td>' . esc_html($row->brand_model) . '</td>';
            $html .= '<td>' . intval($row->total_quantity) . '</td>';
            $html .= '<td>₹' . number_format((float) $row->total_price, 2) . '</td>';
            $html .= '</tr>';
            $counter++;
        }

        $html .= '</tbody></table>';

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $html,
                'items' => $results
            )
        ));
        exit;

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'data' => 'Database error: ' . $e->getMessage()
        ));
        exit;
    }
}