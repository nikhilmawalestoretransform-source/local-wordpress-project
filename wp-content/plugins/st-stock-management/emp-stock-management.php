<?php
/**
 * Employee Stock Management System
 * Add this to your plugin file
 */

if (!defined('ABSPATH')) {
    exit;
}

// AJAX handlers for Employee Management
add_action('wp_ajax_get_employees', 'ajax_get_employees');
add_action('wp_ajax_get_asset_types_emp', 'ajax_get_asset_types_emp');
add_action('wp_ajax_get_brand_models_emp', 'ajax_get_brand_models_emp');
add_action('wp_ajax_assign_assets_to_emp', 'ajax_assign_assets_to_emp');
add_action('wp_ajax_get_emp_assigned_assets', 'ajax_get_emp_assigned_assets');
add_action('wp_ajax_remove_emp_asset', 'ajax_remove_emp_asset');

/**
 * Create employee stock management table
 */
function create_emp_stock_management_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'emp_stock_management';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        emp_id mediumint(9) NOT NULL,
        emp_name varchar(200) NOT NULL,
        asset_type varchar(100) NOT NULL,
        brand_model varchar(200) NOT NULL,
        serial_number varchar(100) NULL,
        assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(50) DEFAULT 'Assigned',
        PRIMARY KEY (id),
        KEY emp_id (emp_id),
        KEY asset_type (asset_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create employee master table
 */
function create_admin_emp_stock_management_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_emp_stock_management';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        emp_name varchar(200) NOT NULL,
        emp_id varchar(50) NOT NULL,
        department varchar(100) NOT NULL,
        designation varchar(100) NOT NULL,
        email varchar(200) NULL,
        phone varchar(20) NULL,
        status varchar(50) DEFAULT 'Active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY emp_id (emp_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insert sample data if table is empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($count == 0) {
        $sample_data = array(
            array('John Doe', 'EMP001', 'IT', 'Software Engineer', 'john.doe@company.com', '123-456-7890'),
            array('Jane Smith', 'EMP002', 'HR', 'HR Manager', 'jane.smith@company.com', '123-456-7891'),
            array('Mike Johnson', 'EMP003', 'Finance', 'Accountant', 'mike.johnson@company.com', '123-456-7892'),
            array('Sarah Wilson', 'EMP004', 'IT', 'System Admin', 'sarah.wilson@company.com', '123-456-7893'),
            array('David Brown', 'EMP005', 'Operations', 'Operations Manager', 'david.brown@company.com', '123-456-7894')
        );
        
        foreach ($sample_data as $data) {
            $wpdb->insert(
                $table_name,
                array(
                    'emp_name' => $data[0],
                    'emp_id' => $data[1],
                    'department' => $data[2],
                    'designation' => $data[3],
                    'email' => $data[4],
                    'phone' => $data[5]
                )
            );
        }
    }
}

// Register table creation on plugin activation
register_activation_hook(__FILE__, 'create_emp_stock_management_table');
register_activation_hook(__FILE__, 'create_admin_emp_stock_management_table');

/**
 * AJAX: Get employees list
 */
function ajax_get_employees() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'admin_emp_stock_management';
        
        $employees = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'Active' ORDER BY emp_name");
        
        echo json_encode(array('success' => true, 'data' => $employees));
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}

/**
 * AJAX: Get asset types from admin_emp_stock_management
 */
function ajax_get_asset_types_emp() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'admin_emp_stock_management';
        
        // For demo purposes, return predefined asset types
        // In real scenario, you might have these in a separate table
        $asset_types = array('Laptop', 'Printer', 'Phone', 'Monitor', 'Tablet');
        
        echo json_encode(array('success' => true, 'data' => $asset_types));
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}

/**
 * AJAX: Get brand models based on asset type
 */
function ajax_get_brand_models_emp() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['asset_type'])) {
            echo json_encode(array('success' => false, 'data' => 'Asset type is required'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';
        $asset_type = sanitize_text_field($_POST['asset_type']);
        
        $brand_models = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT brand_model, serial_number FROM $table_name WHERE asset_type = %s AND status = 'Active'",
            $asset_type
        ));
        
        echo json_encode(array('success' => true, 'data' => $brand_models));
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}

/**
 * AJAX: Assign assets to employee
 */
function ajax_assign_assets_to_emp() {
    if (ob_get_length()) ob_clean();
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
        $emp_table = $wpdb->prefix . 'admin_emp_stock_management';
        $assign_table = $wpdb->prefix . 'emp_stock_management';
        
        // Get employee details
        $emp = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $emp_table WHERE id = %d",
            intval($_POST['emp_id'])
        ));
        
        if (!$emp) {
            echo json_encode(array('success' => false, 'data' => 'Employee not found'));
            exit;
        }

        $assets = json_decode(stripslashes($_POST['assets']), true);
        $success_count = 0;
        
        foreach ($assets as $asset) {
            $result = $wpdb->insert(
                $assign_table,
                array(
                    'emp_id' => $emp->id,
                    'emp_name' => $emp->emp_name,
                    'asset_type' => sanitize_text_field($asset['asset_type']),
                    'brand_model' => sanitize_text_field($asset['brand_model']),
                    'serial_number' => sanitize_text_field($asset['serial_number'])
                )
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        echo json_encode(array('success' => true, 'data' => "Successfully assigned $success_count asset(s) to $emp->emp_name"));
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}

/**
 * AJAX: Get employee assigned assets
 */
function ajax_get_emp_assigned_assets() {
    if (ob_get_length()) ob_clean();
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
        $table_name = $wpdb->prefix . 'emp_stock_management';
        
        $assets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE emp_id = %d ORDER BY assigned_date DESC",
            intval($_POST['emp_id'])
        ));
        
        echo json_encode(array('success' => true, 'data' => $assets));
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}

/**
 * AJAX: Remove employee asset
 */
function ajax_remove_emp_asset() {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
            echo json_encode(array('success' => false, 'data' => 'Security verification failed'));
            exit;
        }

        if (!isset($_POST['assignment_id'])) {
            echo json_encode(array('success' => false, 'data' => 'Assignment ID is required'));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'emp_stock_management';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => intval($_POST['assignment_id']))
        );
        
        if ($result) {
            echo json_encode(array('success' => true, 'data' => 'Asset removed successfully'));
        } else {
            echo json_encode(array('success' => false, 'data' => 'Failed to remove asset'));
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'data' => 'Error: ' . $e->getMessage()));
        exit;
    }
}