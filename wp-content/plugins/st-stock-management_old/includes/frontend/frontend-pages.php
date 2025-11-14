<?php

class STStockManagementFrontendPages {
    
    public function __construct() {
        add_shortcode('stock_management', array($this, 'stock_management_shortcode'));
    }
    
    public function stock_management_shortcode($atts) {
        ob_start();
        
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'items';
        ?>
        <div class="st-stock-management-frontend">
            <div class="container-fluid">
                <h1 class="text-center mb-4">Stock Management System</h1>
                <p class="text-center text-muted mb-4">Efficiently manage your inventory with advanced features</p>
                
                <ul class="nav nav-tabs justify-content-center" id="frontendTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'items' ? 'active' : ''; ?>" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab">Item Management</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'employees' ? 'active' : ''; ?>" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">Employee Management</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'repairs' ? 'active' : ''; ?>" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs" type="button" role="tab">Repair Management</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="frontendTabsContent">
                    <div class="tab-pane fade <?php echo $tab === 'items' ? 'show active' : ''; ?>" id="items" role="tabpanel">
                        <?php STStockManagementFrontendItems::display_frontend_items(); ?>
                    </div>
                    <div class="tab-pane fade <?php echo $tab === 'employees' ? 'show active' : ''; ?>" id="employees" role="tabpanel">
                        <?php STStockManagementFrontendEmp::display_frontend_emp(); ?>
                    </div>
                    <div class="tab-pane fade <?php echo $tab === 'repairs' ? 'show active' : ''; ?>" id="repairs" role="tabpanel">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Repair Management</h5>
                                <p class="card-text">This feature is currently under development.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new STStockManagementFrontendPages();
?>