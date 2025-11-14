<?php
// Prevent duplicate class definition
if (!class_exists('STStockManagementAdminPages')) {

class STStockManagementAdminPages {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Stock Management System',
            'Stock Management',
            'manage_options',
            'st-stock-management',
            array($this, 'admin_dashboard_page'),
            'dashicons-clipboard',
            30
        );
        
        // Submenus
        add_submenu_page(
            'st-stock-management',
            'Item Management',
            'Item Management',
            'manage_options',
            'st-stock-management-items',
            array($this, 'item_management_page')
        );
        
        add_submenu_page(
            'st-stock-management',
            'Employee Management',
            'Employee Management',
            'manage_options',
            'st-stock-management-emp',
            array($this, 'emp_management_page')
        );
        
        add_submenu_page(
            'st-stock-management',
            'Repair Management',
            'Repair Management',
            'manage_options',
            'st-stock-management-repair',
            array($this, 'repair_management_page')
        );
    }
    
    public function admin_dashboard_page() {
        // Check if we're already on this page to prevent duplicates
        if (!defined('ST_STOCK_DASHBOARD_LOADED')) {
            define('ST_STOCK_DASHBOARD_LOADED', true);
            ?>
            <div class="wrap st-stock-management">
                <h1 class="mb-4">Stock Management System</h1>
                <div class="alert alert-info">
                    <h4>Efficiently manage your inventory with advanced features</h4>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Item Management</h5>
                                <p class="card-text">Manage asset types and items</p>
                                <a href="<?php echo admin_url('admin.php?page=st-stock-management-items'); ?>" class="btn btn-light">Go to Items</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Employee Management</h5>
                                <p class="card-text">Manage employees and asset assignments</p>
                                <a href="<?php echo admin_url('admin.php?page=st-stock-management-emp'); ?>" class="btn btn-light">Go to Employees</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Repair Management</h5>
                                <p class="card-text">Manage item repairs</p>
                                <a href="<?php echo admin_url('admin.php?page=st-stock-management-repair'); ?>" class="btn btn-light">Go to Repairs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    public function item_management_page() {
        if (!defined('ST_STOCK_ITEMS_LOADED')) {
            define('ST_STOCK_ITEMS_LOADED', true);
            ?>
            <div class="wrap st-stock-management">
                <h1 class="mb-4">Item Management</h1>
                
                <ul class="nav nav-tabs" id="itemTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admin-assets-tab" data-bs-toggle="tab" data-bs-target="#admin-assets" type="button" role="tab">Asset Types (Admin)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="frontend-items-tab" data-bs-toggle="tab" data-bs-target="#frontend-items" type="button" role="tab">Items (Frontend)</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="itemTabsContent">
                    <div class="tab-pane fade show active" id="admin-assets" role="tabpanel">
                        <?php 
                        if (class_exists('STStockManagementAdminItems')) {
                            STStockManagementAdminItems::display_admin_assets();
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="frontend-items" role="tabpanel">
                        <?php 
                        if (class_exists('STStockManagementFrontendItems')) {
                            STStockManagementFrontendItems::display_frontend_items();
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    public function emp_management_page() {
        if (!defined('ST_STOCK_EMP_LOADED')) {
            define('ST_STOCK_EMP_LOADED', true);
            ?>
            <div class="wrap st-stock-management">
                <h1 class="mb-4">Employee Management</h1>
                
                <ul class="nav nav-tabs" id="empTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admin-emp-tab" data-bs-toggle="tab" data-bs-target="#admin-emp" type="button" role="tab">Employees (Admin)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="frontend-emp-tab" data-bs-toggle="tab" data-bs-target="#frontend-emp" type="button" role="tab">Employee Assignments</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="empTabsContent">
                    <div class="tab-pane fade show active" id="admin-emp" role="tabpanel">
                        <?php 
                        if (class_exists('STStockManagementAdminEmp')) {
                            STStockManagementAdminEmp::display_admin_emp();
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="frontend-emp" role="tabpanel">
                        <?php 
                        if (class_exists('STStockManagementFrontendEmp')) {
                            STStockManagementFrontendEmp::display_frontend_emp();
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    public function repair_management_page() {
        if (!defined('ST_STOCK_REPAIR_LOADED')) {
            define('ST_STOCK_REPAIR_LOADED', true);
            ?>
            <div class="wrap st-stock-management">
                <h1 class="mb-4">Repair Management</h1>
                <div class="alert alert-info">
                    <p>Repair management feature is currently under development.</p>
                </div>
            </div>
            <?php
        }
    }
}

} // End class exists check

// Initialize only if not already loaded
if (class_exists('STStockManagementAdminPages') && !defined('ST_STOCK_ADMIN_PAGES_LOADED')) {
    define('ST_STOCK_ADMIN_PAGES_LOADED', true);
    // Don't auto-instantiate here - let the main plugin handle it
}
?>