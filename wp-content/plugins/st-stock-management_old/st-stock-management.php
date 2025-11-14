<?php
/**
 * Plugin Name: ST Stock Management
 * Description: Inventory, employee, and repair management system with Bootstrap, DataTables, AJAX, and SweetAlert2.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// --- Database creation on plugin activation ---
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $tables = [
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}admin_item_stock_management (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            asset_name VARCHAR(255) NOT NULL,
            status TINYINT DEFAULT 1,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset",
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}admin_emp_stock_management (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            emp_name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            position VARCHAR(255),
            status TINYINT DEFAULT 1,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset",
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}item_stock_management (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            asset_type BIGINT NOT NULL,
            brand_model VARCHAR(255),
            serial_number VARCHAR(255) NOT NULL UNIQUE,
            quantity INT DEFAULT 1,
            price DECIMAL(10,2),
            status TINYINT DEFAULT 1,
            location VARCHAR(255),
            date_purchased DATE,
            warranty_expiry DATE,
            remarks TEXT,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset",
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emp_stock_management (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            emp_name BIGINT NOT NULL,
            asset_type BIGINT NOT NULL,
            brand_model VARCHAR(255),
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset",
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}stsm_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            action_type VARCHAR(50),
            table_name VARCHAR(100),
            record_id BIGINT,
            details TEXT,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($tables as $sql) { dbDelta($sql); }
});

// --- Enqueue scripts & styles ---
add_action('admin_enqueue_scripts', 'stsm_assets');
add_action('wp_enqueue_scripts', 'stsm_assets');

function stsm_assets() {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);

    wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);

    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

    wp_enqueue_script('stsm-script', plugin_dir_url(__FILE__) . 'stsm-script.js', ['jquery'], null, true);

    wp_localize_script('stsm-script', 'stsm_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
}

// --- Admin Menu ---
add_action('admin_menu', function() {
    add_menu_page(
        'Stock Management',
        'Stock Management',
        'manage_options',
        'st-stock-management',
        'stsm_admin_page',
        'dashicons-archive',
        6
    );
});

// --- Admin Page ---
function stsm_admin_page() {
    ?>
    <div class="wrap container mt-3">
        <h1>Stock Management System</h1>
        <p>Efficiently manage your inventory with advanced features</p>

        <ul class="nav nav-tabs" id="stsmTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="items-tab" data-bs-toggle="tab" href="#items" role="tab">Item Management</a></li>
            <li class="nav-item"><a class="nav-link" id="employees-tab" data-bs-toggle="tab" href="#employees" role="tab">Employee Management</a></li>
            <li class="nav-item"><a class="nav-link" id="repairs-tab" data-bs-toggle="tab" href="#repairs" role="tab">Repair Management</a></li>
        </ul>

        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="items"><?php stsm_items_tab(); ?></div>
            <div class="tab-pane fade" id="employees"><?php stsm_employees_tab(); ?></div>
            <div class="tab-pane fade" id="repairs"><p>Repair management coming soon.</p></div>
        </div>
    </div>
    <?php
}

// --- Items Tab ---
function stsm_items_tab() {
    global $wpdb;
    $items = $wpdb->get_results("
        SELECT i.*, a.asset_name 
        FROM {$wpdb->prefix}item_stock_management i 
        LEFT JOIN {$wpdb->prefix}admin_item_stock_management a ON i.asset_type=a.id 
        ORDER BY i.id DESC
    ");
    ?>
    <button class="btn btn-primary mb-2" id="stsm-add-item">Add Item</button>
    <table class="table table-bordered" id="stsm-items-table">
        <thead>
            <tr>
                <th>ID</th><th>Asset Type</th><th>Brand/Model</th><th>Serial</th>
                <th>Qty</th><th>Price</th><th>Status</th><th>Location</th>
                <th>Purchased</th><th>Warranty</th><th>Remarks</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($items as $i): ?>
            <tr>
                <td><?= $i->id ?></td>
                <td><?= esc_html($i->asset_name) ?></td>
                <td><?= esc_html($i->brand_model) ?></td>
                <td><?= esc_html($i->serial_number) ?></td>
                <td><?= $i->quantity ?></td>
                <td><?= $i->price ?></td>
                <td><?= $i->status? 'Active':'Inactive' ?></td>
                <td><?= esc_html($i->location) ?></td>
                <td><?= esc_html($i->date_purchased) ?></td>
                <td><?= esc_html($i->warranty_expiry) ?></td>
                <td><?= esc_html($i->remarks) ?></td>
                <td>
                    <button class="btn btn-warning btn-sm stsm-edit-item" data-id="<?= $i->id ?>">Edit</button>
                    <button class="btn btn-danger btn-sm stsm-delete-item" data-id="<?= $i->id ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>
    jQuery(document).ready(function($){ $('#stsm-items-table').DataTable(); });
    </script>
    <?php
}

// --- Employees Tab ---
function stsm_employees_tab() {
    global $wpdb;
    $emps = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}admin_emp_stock_management ORDER BY id DESC");
    ?>
    <table class="table table-bordered" id="stsm-employees-table">
        <thead><tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($emps as $e): ?>
            <tr>
                <td><?= $e->id ?></td>
                <td><?= esc_html($e->emp_name) ?></td>
                <td><?= esc_html($e->email) ?></td>
                <td><?= esc_html($e->position) ?></td>
                <td><?= $e->status? 'Active':'Inactive' ?></td>
                <td><button class="btn btn-primary btn-sm stsm-assign" data-id="<?= $e->id ?>">Assign</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>jQuery(document).ready(function($){ $('#stsm-employees-table').DataTable(); });</script>
    <?php
}

// --- AJAX Handlers ---
add_action('wp_ajax_stsm_delete_item', function(){
    global $wpdb;
    $id = intval($_POST['id']);
    $table = $wpdb->prefix.'item_stock_management';
    $wpdb->delete($table,['id'=>$id]);
    $wpdb->insert($wpdb->prefix.'stsm_logs',['action_type'=>'delete','table_name'=>$table,'record_id'=>$id,'details'=>'Item deleted']);
    wp_send_json_success();
});

add_action('wp_ajax_stsm_delete_employee', function(){
    global $wpdb;
    $id = intval($_POST['id']);
    $table = $wpdb->prefix.'admin_emp_stock_management';
    $wpdb->delete($table,['id'=>$id]);
    $wpdb->insert($wpdb->prefix.'stsm_logs',['action_type'=>'delete','table_name'=>$table,'record_id'=>$id,'details'=>'Employee deleted']);
    wp_send_json_success();
});
