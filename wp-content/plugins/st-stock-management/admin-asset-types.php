<?php
/**
 * Asset Type Management - Admin (Complete with AJAX handlers)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'asset_types_admin_menu');

function asset_types_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Admin Asset Types',
        'Admin Asset Types',
        'manage_options',
        'asset-types-management',
        'asset_types_admin_page'
    );
}

// AJAX handlers for asset type management
add_action('wp_ajax_get_asset_types_admin', 'ajax_get_asset_types_admin');
add_action('wp_ajax_add_asset_type', 'ajax_add_asset_type');
add_action('wp_ajax_update_asset_type', 'ajax_update_asset_type');
add_action('wp_ajax_delete_asset_type', 'ajax_delete_asset_type');

// Logging function for asset management
function log_asset_management_action($asset_type_id, $action, $old_value = null, $new_value = null) {
    global $wpdb;
    
    $logs_table = $wpdb->prefix . 'admin_item_stock_management_logs';
    
    // Check if logs table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") != $logs_table) {
        return false;
    }
    
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    // Convert arrays/objects to JSON for storage
    if (is_array($old_value) || is_object($old_value)) {
        $old_value = json_encode($old_value);
    }
    
    if (is_array($new_value) || is_object($new_value)) {
        $new_value = json_encode($new_value);
    }
    
    $result = $wpdb->insert(
        $logs_table,
        array(
            'asset_type_id' => $asset_type_id,
            'action' => $action,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'user_id' => $user_id,
            'user_ip' => $user_ip
        ),
        array('%d', '%s', '%s', '%s', '%d', '%s')
    );
    
    return $result !== false;
}

function asset_types_admin_page() {
    // Check and create tables if they don't exist
    create_asset_management_tables();
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ?>
    <div class="wrap">
        <h1>📦 Asset Types Management</h1>
        
        <style>
            .asset-tabs {

                border: 2px solid;
             }
            .asset-types-container {
                margin: 20px 0;
            }
            .asset-tabs {
                display: flex;
                margin-bottom: 20px;
                background: #f0f0f1;
                border-radius: 8px;
                padding: 5px;
            }
            .asset-tab-btn {
                font-size: 15px;
                flex: 1;
                padding: 12px 20px;
                border: none;
                background: transparent;
                color: #2c3338;
                cursor: pointer;
                border-radius: 6px;
                transition: all 0.3s ease;
                font-weight: 600;
            }
            .asset-tab-btn.active {
                background: #0073aa;
                color: white;
                box-shadow: 0 2px 5px rgba(0,115,170,0.3);
            }
            .asset-tab-content {
                display: none;
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .asset-tab-content.active {
                display: block;
            }
            .asset-form {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
            }
            .asset-form input,
            .asset-form select {
                margin: 5px;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-width: 250px;
                font-size: 14px;
            }
            .asset-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .asset-table th,
            .asset-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            .asset-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .asset-table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .asset-table tr:hover {
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

        <div class="asset-types-container">
            <!-- Asset Management Tabs -->
            <div class="asset-tabs">
                <button class="asset-tab-btn active" onclick="showAssetTab('add-asset-tab', this)">➕ Add Asset Type</button>
                <button class="asset-tab-btn" onclick="showAssetTab('list-assets-tab', this)">📋 List Asset Types</button>
            </div>

            <!-- Add Asset Type Tab -->
            <div id="add-asset-tab" class="asset-tab-content active">
                <div class="asset-form">
                    <h3>➕ Add New Asset Type</h3>
                    <form id="asset-type-form">
                        <input type="hidden" name="action" value="add_asset_type">
                        <input type="hidden" name="asset_type_id" id="asset_type_id" value="">
                        
                        <div class="form-group">
                            <label for="asset_name">Asset Name *</label>
                            <input type="text" name="asset_name" id="asset_name" placeholder="Enter asset type name" required>
                            <div id="asset_name_error" class="error-message" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="asset_status">Status *</label>
                            <select name="status" id="asset_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="asset_submit_btn" onclick="saveAssetType()">➕ Add Asset Type</button>
                            <button type="button" class="btn btn-secondary" onclick="resetAssetForm()" id="asset_cancel_btn" style="display:none;">❌ Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Asset Types Tab -->
            <div id="list-assets-tab" class="asset-tab-content">
                <h3>📋 Existing Asset Types</h3>
                <div id="asset-types-table-container">
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                        <p>Loading asset types...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                console.log('Asset Types Management loaded');
                // Load asset types when page loads
                loadAssetTypes(currentPage);
            });

            function showAssetTab(tabName, element) {
                // Hide all tab contents
                document.querySelectorAll('.asset-tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all tab buttons
                document.querySelectorAll('.asset-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected tab and activate button
                document.getElementById(tabName).classList.add('active');
                element.classList.add('active');
                
                // If switching to list tab, reload asset types
                if (tabName === 'list-assets-tab') {
                    loadAssetTypes(currentPage);
                }
            }

            function loadAssetTypes(page = 1) {
                currentPage = page;
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_asset_types_admin',
                        nonce: nonce,
                        page: page
                    },
                    beforeSend: function() {
                        const container = document.getElementById('asset-types-table-container');
                        if (container) {
                            container.innerHTML = '<div style="text-align: center; padding: 20px;">Loading asset types...</div>';
                        }
                    },
                    success: function(response) {
                        console.log('AJAX Success - Full Response:', response);
                        
                        if (response && response.success) {
                            console.log('Asset Types Data:', response.data);
                            displayAssetTypes(response.data.asset_types, response.data.pagination);
                        } else {
                            console.error('Server returned error:', response);
                            let errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                            showAlert('Error: ' + errorMsg, 'error');
                            displayAssetTypes([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response Text:', xhr.responseText);
                        
                        let errorMsg = 'Failed to load asset types. Please try again.';
                        showAlert(errorMsg, 'error');
                        displayAssetTypes([], null);
                    }
                });
            }

            function displayAssetTypes(assetTypes, pagination) {
                const container = document.getElementById('asset-types-table-container');
                if (!container) return;
                
                console.log('Displaying asset types:', assetTypes);
                
                if (!assetTypes || assetTypes.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6c757d;">No asset types found. Add your first asset type in the "Add Asset Type" tab.</div>';
                    return;
                }
                
                let html = `
                    <table class="asset-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Asset Name</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                assetTypes.forEach(asset => {
                    // Escape special characters in asset name for JavaScript
                    const escapedAssetName = asset.asset_name ? asset.asset_name.replace(/'/g, "\\'").replace(/"/g, '\\"') : '';
                    
                    html += `
                        <tr>
                            <td>${asset.id}</td>
                            <td>${asset.asset_name}</td>
                            <td class="status-${asset.status}">${asset.status ? asset.status.charAt(0).toUpperCase() + asset.status.slice(1) : 'N/A'}</td>
                            <td>${formatDate(asset.created_at)}</td>
                            <td>${formatDate(asset.updated_at)}</td>
                            <td>
                                <button class="btn btn-warning" onclick="editAssetType(${asset.id}, '${escapedAssetName}', '${asset.status}')">✏️ Edit</button>
                                <button class="btn btn-danger" onclick="deleteAssetType(${asset.id})">🗑️ Delete</button>
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
                            Showing ${startItem} to ${endItem} of ${pagination.total_items} asset types
                        </div>
                        ${generatePagination(pagination)}
                    `;
                }
                
                html += `
                        <div style="margin-top: 15px; color: #666; font-size: 12px;">
                            Total: ${pagination ? pagination.total_items : assetTypes.length} asset type(s)
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
            }

            function generatePagination(pagination) {
                if (!pagination || pagination.total_pages <= 1) return '';
                
                let html = '<div class="pagination">';
                
                // Previous button
                html += `<button onclick="loadAssetTypes(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>‹ Previous</button>`;
                
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
                    html += `<button onclick="loadAssetTypes(1)">1</button>`;
                    if (startPage > 2) html += `<button disabled>...</button>`;
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    html += `<button onclick="loadAssetTypes(${i})" ${i === pagination.current_page ? 'class="active"' : ''}>${i}</button>`;
                }
                
                // Last page
                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) html += `<button disabled>...</button>`;
                    html += `<button onclick="loadAssetTypes(${pagination.total_pages})">${pagination.total_pages}</button>`;
                }
                
                // Next button
                html += `<button onclick="loadAssetTypes(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next ›</button>`;
                
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

            function saveAssetType() {
                const form = document.getElementById('asset-type-form');
                const submitBtn = document.getElementById('asset_submit_btn');
                const errorDiv = document.getElementById('asset_name_error');
                
                // Clear previous errors
                errorDiv.style.display = 'none';
                errorDiv.textContent = '';
                
                // Get form values
                const assetId = document.getElementById('asset_type_id').value;
                const assetName = document.getElementById('asset_name').value.trim();
                const status = document.getElementById('asset_status').value;
                
                // Validate
                if (!assetName) {
                    errorDiv.textContent = 'Asset name is required';
                    errorDiv.style.display = 'block';
                    return;
                }

                // Set loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Processing...';
                form.classList.add('loading');

                // Prepare data
                const formData = new FormData();
                formData.append('nonce', nonce);
                formData.append('asset_name', assetName);
                formData.append('status', status);
                
                // Determine action
                const action = assetId ? 'update_asset_type' : 'add_asset_type';
                formData.append('action', action);
                
                if (assetId) {
                    formData.append('asset_type_id', assetId);
                }

                console.log('Saving asset type:', { assetId, assetName, status, action });

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
                            resetAssetForm();
                            // Reload the asset types list
                            loadAssetTypes(currentPage);
                            // Switch to list tab after successful save
                            showAssetTab('list-assets-tab', document.querySelector('.asset-tab-btn:nth-child(2)'));
                        } else {
                            let errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                            if (errorMsg.includes('already exists')) {
                                errorDiv.textContent = errorMsg;
                                errorDiv.style.display = 'block';
                            }
                            showAlert('Error: ' + errorMsg, 'error');
                            submitBtn.textContent = assetId ? '🔄 Update Asset Type' : '➕ Add Asset Type';
                        }
                    },
                    error: function(xhr, status, error) {
                        submitBtn.disabled = false;
                        form.classList.remove('loading');
                        submitBtn.textContent = assetId ? '🔄 Update Asset Type' : '➕ Add Asset Type';
                        console.error('Save Error:', error);
                        console.error('Status:', status);
                        console.error('Response Text:', xhr.responseText);
                        showAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editAssetType(id, name, status) {
                // Switch to add asset tab for editing
                showAssetTab('add-asset-tab', document.querySelector('.asset-tab-btn:nth-child(1)'));
                
                // Populate form with existing data
                document.getElementById('asset_type_id').value = id;
                document.getElementById('asset_name').value = name;
                document.getElementById('asset_status').value = status;
                document.getElementById('asset_submit_btn').textContent = '🔄 Update Asset Type';
                document.getElementById('asset_cancel_btn').style.display = 'inline-block';
                
                // Clear any errors
                document.getElementById('asset_name_error').style.display = 'none';
                
                // Update form title
                document.querySelector('#add-asset-tab h3').textContent = '✏️ Edit Asset Type';
            }

            function resetAssetForm() {
                document.getElementById('asset-type-form').reset();
                document.getElementById('asset_type_id').value = '';
                document.getElementById('asset_submit_btn').textContent = '➕ Add Asset Type';
                document.getElementById('asset_cancel_btn').style.display = 'none';
                document.getElementById('asset_name_error').style.display = 'none';
                
                // Reset form title
                document.querySelector('#add-asset-tab h3').textContent = '➕ Add New Asset Type';
            }

            function deleteAssetType(id) {
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
                                action: 'delete_asset_type',
                                asset_type_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                if (response && response.success) {
                                    showAlert(response.data, 'success');
                                    loadAssetTypes(currentPage);
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
        </script>
    </div>
    <?php
}

// UPDATED AJAX Handler with Pagination
function ajax_get_asset_types_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Asset types table not found');
    }
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 10; // Number of items per page
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    // Get paginated asset types
    $asset_types = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        ORDER BY id DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    // Return JSON response with pagination info
    wp_send_json_success([
        'asset_types' => $asset_types,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ]
    ]);
}

function ajax_add_asset_type() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Asset types table not found');
    }
    
    $asset_name = sanitize_text_field($_POST['asset_name']);
    $status = sanitize_text_field($_POST['status']);
    
    // Validate input
    if (empty($asset_name)) {
        wp_send_json_error('Asset name is required');
    }
    
    // Check if asset type already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE asset_name = %s", $asset_name
    ));
    
    if ($existing) {
        wp_send_json_error('Asset type already exists');
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'asset_name' => $asset_name,
            'status' => $status
        ),
        array('%s', '%s')
    );

    if ($result !== false) {
        $new_asset_id = $wpdb->insert_id;
        
        // LOG THE ACTION - Added logging without affecting functionality
        $log_data = array(
            'asset_name' => $asset_name,
            'status' => $status
        );
        log_asset_management_action($new_asset_id, 'asset_type_created', null, $log_data);
        
        wp_send_json_success('Asset type added successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

function ajax_update_asset_type() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    if (!isset($_POST['asset_type_id'])) {
        wp_send_json_error('Missing asset type ID');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Asset types table not found');
    }
    
    $asset_type_id = intval($_POST['asset_type_id']);
    $asset_name = sanitize_text_field($_POST['asset_name']);
    $status = sanitize_text_field($_POST['status']);
    
    // Validate input
    if (empty($asset_name)) {
        wp_send_json_error('Asset name is required');
    }
    
    // Get old values for logging
    $old_asset = $wpdb->get_row($wpdb->prepare(
        "SELECT asset_name, status FROM $table_name WHERE id = %d", $asset_type_id
    ));
    
    if (!$old_asset) {
        wp_send_json_error('Asset type not found');
    }
    
    // Check if asset name already exists (excluding current one)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE asset_name = %s AND id != %d", $asset_name, $asset_type_id
    ));
    
    if ($existing) {
        wp_send_json_error('Asset type already exists');
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'asset_name' => $asset_name,
            'status' => $status
        ),
        array('id' => $asset_type_id),
        array('%s', '%s'),
        array('%d')
    );

    if ($result !== false) {
        // LOG THE ACTION - Added logging without affecting functionality
        $old_data = array(
            'asset_name' => $old_asset->asset_name,
            'status' => $old_asset->status
        );
        $new_data = array(
            'asset_name' => $asset_name,
            'status' => $status
        );
        log_asset_management_action($asset_type_id, 'asset_type_updated', $old_data, $new_data);
        
        wp_send_json_success('Asset type updated successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

function ajax_delete_asset_type() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    if (!isset($_POST['asset_type_id'])) {
        wp_send_json_error('Missing asset type ID');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Asset types table not found');
    }
    
    $asset_type_id = intval($_POST['asset_type_id']);
    
    // Get asset info for logging
    $asset = $wpdb->get_row($wpdb->prepare(
        "SELECT asset_name, status FROM $table_name WHERE id = %d", $asset_type_id
    ));
    
    if (!$asset) {
        wp_send_json_error('Asset type not found');
    }
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $asset_type_id),
        array('%d')
    );

    if ($result !== false) {
        // LOG THE ACTION - Added logging without affecting functionality
        $deleted_data = array(
            'asset_name' => $asset->asset_name,
            'status' => $asset->status
        );
        log_asset_management_action($asset_type_id, 'asset_type_deleted', $deleted_data, null);
        
        wp_send_json_success('Asset type deleted successfully!');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

// Create database tables only if they don't exist
function create_asset_management_tables() {
    global $wpdb;
    
    $asset_types_table = $wpdb->prefix . 'admin_item_stock_management';
    $asset_logs_table = $wpdb->prefix . 'admin_item_stock_management_logs';
    
    // Check if main table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$asset_types_table'") == $asset_types_table) {
        // Table exists, no need to create
        return;
    }
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Asset types table
    $sql1 = "CREATE TABLE $asset_types_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        asset_name varchar(100) NOT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY asset_name (asset_name)
    ) $charset_collate;";
    
    // Asset logs table
    $sql2 = "CREATE TABLE $asset_logs_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        asset_type_id mediumint(9),
        action varchar(50) NOT NULL,
        old_value text,
        new_value text,
        user_id bigint(20),
        user_ip varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY asset_type_id (asset_type_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Helper functions
function get_asset_types_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return 0;
    }
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    return $count ?: 0;
}

function get_active_asset_types_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'admin_item_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return 0;
    }
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
    return $count ?: 0;
}
?>