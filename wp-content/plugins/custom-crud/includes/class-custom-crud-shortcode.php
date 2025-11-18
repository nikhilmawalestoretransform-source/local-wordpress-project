<?php

class Custom_CRUD_Shortcode {
    
    public function __construct() {
        add_shortcode('custom_crud', array($this, 'display_crud_interface'));
    }
    
    public function display_crud_interface($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->display_enhanced_login_message();
        }
        
        ob_start();
        ?>
        <div class="custom-crud-container">
            <!-- Enhanced Header -->
            <div class="crud-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-5 fw-bold mb-2">
                                <i class="fas fa-cubes me-3"></i>Custom CRUD Dashboard
                            </h1>
                            <p class="lead mb-0 opacity-75">Manage your inventory with style and efficiency</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="user-info d-inline-block">
                                <i class="fas fa-user-circle me-2"></i>
                                <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>
                                <div class="small opacity-75">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M j, Y'); ?>
                                </div>
                            </div>
                            <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-light btn-sm ms-2">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <!-- Statistics Row -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card stats-total fade-in-up" style="animation-delay: 0.1s">
                            <i class="fas fa-boxes"></i>
                            <div class="stats-number" id="total-items">0</div>
                            <div class="stats-label">Total Items</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card stats-active fade-in-up" style="animation-delay: 0.2s">
                            <i class="fas fa-check-circle"></i>
                            <div class="stats-number" id="active-items">0</div>
                            <div class="stats-label">Active Items</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card stats-value fade-in-up" style="animation-delay: 0.3s">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="stats-number" id="total-value">$0</div>
                            <div class="stats-label">Total Value</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card stats-out-of-stock fade-in-up" style="animation-delay: 0.4s">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="stats-number" id="out-of-stock">0</div>
                            <div class="stats-label">Out of Stock</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Items by Status</h5>
                            <canvas id="statusChart" height="250"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Price Distribution</h5>
                            <canvas id="priceChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Add Item -->
                <div class="row">
                    <!-- Add Item Card -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-plus-circle me-2"></i>Add New Item
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="custom-crud-add-form">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="item_name" class="form-label">
                                                <i class="fas fa-tag me-1"></i>Item Name *
                                            </label>
                                            <input type="text" class="form-control" id="item_name" name="item_name" required 
                                                   placeholder="Enter item name">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="item_price" class="form-label">
                                                <i class="fas fa-dollar-sign me-1"></i>Price ($)
                                            </label>
                                            <input type="number" class="form-control" id="item_price" name="item_price" 
                                                   step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="item_quantity" class="form-label">
                                                <i class="fas fa-boxes me-1"></i>Quantity
                                            </label>
                                            <input type="number" class="form-control" id="item_quantity" name="item_quantity" 
                                                   min="0" placeholder="0">
                                        </div>
                                        <div class="col-12">
                                            <label for="item_status" class="form-label">
                                                <i class="fas fa-info-circle me-1"></i>Status
                                            </label>
                                            <select class="form-select" id="item_status" name="item_status">
                                                <option value="active">🟢 Active</option>
                                                <option value="inactive">⚪ Inactive</option>
                                                <option value="out_of_stock">🔴 Out of Stock</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="item_description" class="form-label">
                                                <i class="fas fa-align-left me-1"></i>Description
                                            </label>
                                            <textarea class="form-control" id="item_description" name="item_description" 
                                                      rows="3" placeholder="Enter item description..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-plus-circle me-1"></i>Add Item
                                            </button>
                                            <button type="reset" class="btn btn-secondary">
                                                <i class="fas fa-undo me-1"></i>Reset Form
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary text-start" onclick="loadItems()">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh Data
                                    </button>
                                    <button class="btn btn-outline-success text-start" onclick="$('#custom-crud-add-form')[0].reset()">
                                        <i class="fas fa-broom me-2"></i>Clear Form
                                    </button>
                                    <button class="btn btn-outline-warning text-start" onclick="exportData()">
                                        <i class="fas fa-download me-2"></i>Export Data
                                    </button>
                                    <button class="btn btn-outline-info text-start" onclick="showHelp()">
                                        <i class="fas fa-question-circle me-2"></i>Get Help
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card mt-4">
                            <div class="card-header bg-dark text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline" id="recent-activity">
                                    <div class="activity-item text-center text-muted">
                                        <small>No recent activity</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list-ul me-2"></i>Your Items
                                    </h5>
                                    <span class="badge bg-light text-dark" id="items-count">0 items</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-hashtag"></i> ID</th>
                                                <th><i class="fas fa-tag"></i> Item Name</th>
                                                <th><i class="fas fa-align-left"></i> Description</th>
                                                <th><i class="fas fa-dollar-sign"></i> Price</th>
                                                <th><i class="fas fa-boxes"></i> Quantity</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
                                                <th><i class="fas fa-calendar"></i> Created</th>
                                                <th><i class="fas fa-cog"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="custom-crud-items-list">
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-5">
                                                    <div class="spinner-border text-primary me-2" role="status"></div>
                                                    Loading your items...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <div class="modal fade" id="editItemModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Edit Item
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="custom-crud-edit-form">
                            <input type="hidden" id="edit_item_id" name="item_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_item_name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="edit_item_price" class="form-label">Price ($)</label>
                                    <input type="number" class="form-control" id="edit_item_price" name="item_price" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label for="edit_item_quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="edit_item_quantity" name="item_quantity" min="0">
                                </div>
                                <div class="col-12">
                                    <label for="edit_item_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_item_status" name="item_status">
                                        <option value="active">🟢 Active</option>
                                        <option value="inactive">⚪ Inactive</option>
                                        <option value="out_of_stock">🔴 Out of Stock</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="edit_item_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_item_description" name="item_description" rows="4"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-warning" id="update-item-btn">
                            <i class="fas fa-save me-1"></i>Update Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function display_enhanced_login_message() {
        ob_start();
        ?>
        <div class="custom-crud-login-message">
            <div class="login-promo-card">
                <div class="promo-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <h2 class="promo-title">Welcome to CRUD Manager</h2>
                <p class="promo-text mb-4">Access your personalized dashboard to manage your inventory efficiently</p>
                
                <ul class="promo-features">
                    <li><i class="fas fa-check-circle"></i> Advanced item management</li>
                    <li><i class="fas fa-chart-line"></i> Real-time statistics</li>
                    <li><i class="fas fa-shield-alt"></i> Secure data handling</li>
                    <li><i class="fas fa-mobile-alt"></i> Mobile-friendly interface</li>
                </ul>
                
                <div class="d-grid gap-2">
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                    </a>
                    <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo wp_registration_url(); ?>" class="btn btn-outline-light">
                            <i class="fas fa-user-plus me-2"></i>Create New Account
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 small opacity-75">
                    <i class="fas fa-info-circle me-1"></i>
                    You need to be logged in to access this section
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}