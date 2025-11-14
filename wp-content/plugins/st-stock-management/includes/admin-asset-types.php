<?php
/**
 * Admin Asset Type Management
 * Add to WordPress admin area
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'stock_management_admin_menu');

function stock_management_admin_menu() {
    add_menu_page(
        'Stock Management',
        'Stock Management',
        'manage_options',
        'stock-management',
        'stock_management_admin_page',
        'dashicons-clipboard',
        30
    );
    
    add_submenu_page(
        'stock-management',
        'Asset Types',
        'Asset Types',
        'manage_options',
        'stock-management-asset-types',
        'asset_types_admin_page'
    );
}

function stock_management_admin_page() {
    ?>
    <div class="wrap">
        <h1>Stock Management System</h1>
        <p>Welcome to the Stock Management System admin area.</p>
        
        <div class="card">
            <h2>Quick Stats</h2>
            <p>Total Items: <?php echo get_total_stock_items(); ?></p>
            <p>Active Asset Types: <?php echo get_active_asset_types_count(); ?></p>
        </div>
    </div>
    <?php
}

function asset_types_admin_page() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    ?>
    <div class="wrap">
        <h1>Asset Types Management</h1>
        
        <style>
            .asset-types-container {
                margin: 20px 0;
            }
            .asset-form {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
            }
            .asset-form input,
            .asset-form select {
                margin: 5px;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-width: 200px;
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
        </style>

        <div class="asset-types-container">
            <!-- Add/Edit Form -->
            <div class="asset-form">
                <h3 id="form-title">➕ Add New Asset Type</h3>
                <form id="asset-type-form">
                    <input type="hidden" name="action" value="add_asset_type">
                    <input type="hidden" name="asset_type_id" id="asset_type_id" value="">
                    
                    <div style="margin-bottom: 15px;">
                        <label for="asset_name" style="display: block; margin-bottom: 5px; font-weight: 600;">Asset Name *</label>
                        <input type="text" name="asset_name" id="asset_name" placeholder="Enter asset type name" required style="width: 300px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="asset_status" style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                        <select name="status" id="asset_status" required style="width: 200px;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="asset_submit_btn">➕ Add Asset Type</button>
                        <button type="button" class="btn btn-secondary" onclick="resetAssetForm()" id="asset_cancel_btn" style="display:none;">❌ Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Asset Types List -->
            <div id="asset-types-list">
                <h3>📋 Existing Asset Types</h3>
                <div id="asset-types-table-container">
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
                        <tbody id="asset-types-tbody">
                            <!-- Asset types will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                loadAssetTypes();
                
                $('#asset-type-form').on('submit', function(e) {
                    e.preventDefault();
                    saveAssetType();
                });
            });

            function loadAssetTypes() {
                jQuery.ajax({
                    url: '<?php echo $ajax_url; ?>',
                    type: 'POST',
                    data: {
                        action: 'get_asset_types_admin',
                        nonce: '<?php echo $nonce; ?>'
                    },
                    beforeSend: function() {
                        $('#asset-types-tbody').html('<tr><td colspan="6" style="text-align: center; padding: 20px;">Loading asset types...</td></tr>');
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
                                    showAlert('Error loading asset types', 'error');
                                    return;
                                }
                            }
                        }
                        
                        if (responseData.success) {
                            displayAssetTypes(responseData.data);
                        } else {
                            showAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function() {
                        showAlert('Failed to load asset types', 'error');
                    }
                });
            }

            function displayAssetTypes(assetTypes) {
                const tbody = document.getElementById('asset-types-tbody');
                
                if (assetTypes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No asset types found. Add your first asset type above.</td></tr>';
                    return;
                }
                
                let html = '';
                assetTypes.forEach(asset => {
                    html += `
                        <tr>
                            <td>${asset.id}</td>
                            <td>${asset.asset_name}</td>
                            <td class="status-${asset.status}">${asset.status.charAt(0).toUpperCase() + asset.status.slice(1)}</td>
                            <td>${formatDate(asset.created_at)}</td>
                            <td>${formatDate(asset.updated_at)}</td>
                            <td>
                                <button class="btn btn-warning" onclick="editAssetType(${asset.id}, '${asset.asset_name}', '${asset.status}')">✏️ Edit</button>
                                <button class="btn btn-danger" onclick="deleteAssetType(${asset.id})">🗑️ Delete</button>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }

            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }

            function saveAssetType() {
                const form = document.getElementById('asset-type-form');
                const submitBtn = document.getElementById('asset_submit_btn');
                
                const formData = new FormData(form);
                formData.append('nonce', '<?php echo $nonce; ?>');
                
                // Determine if it's add or update
                const assetId = document.getElementById('asset_type_id').value;
                const action = assetId ? 'update_asset_type' : 'add_asset_type';
                formData.set('action', action);
                if (assetId) {
                    formData.append('asset_type_id', assetId);
                }

                // Set loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Processing...';
                form.classList.add('loading');

                jQuery.ajax({
                    url: '<?php echo $ajax_url; ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        submitBtn.disabled = false;
                        form.classList.remove('loading');
                        
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    showAlert('Response format error', 'error');
                                    return;
                                }
                            }
                        }
                        
                        if (responseData.success) {
                            showAlert(responseData.data, 'success');
                            resetAssetForm();
                            loadAssetTypes();
                        } else {
                            showAlert('Error: ' + responseData.data, 'error');
                            submitBtn.textContent = assetId ? '🔄 Update Asset Type' : '➕ Add Asset Type';
                        }
                    },
                    error: function() {
                        submitBtn.disabled = false;
                        form.classList.remove('loading');
                        submitBtn.textContent = assetId ? '🔄 Update Asset Type' : '➕ Add Asset Type';
                        showAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editAssetType(id, name, status) {
                document.getElementById('form-title').textContent = '✏️ Edit Asset Type';
                document.getElementById('asset_type_id').value = id;
                document.getElementById('asset_name').value = name;
                document.getElementById('asset_status').value = status;
                document.getElementById('asset_submit_btn').textContent = '🔄 Update Asset Type';
                document.getElementById('asset_cancel_btn').style.display = 'inline-block';
                
                // Scroll to form
                document.querySelector('.asset-form').scrollIntoView({ behavior: 'smooth' });
            }

            function resetAssetForm() {
                document.getElementById('asset-type-form').reset();
                document.getElementById('asset_type_id').value = '';
                document.getElementById('form-title').textContent = '➕ Add New Asset Type';
                document.getElementById('asset_submit_btn').textContent = '➕ Add Asset Type';
                document.getElementById('asset_cancel_btn').style.display = 'none';
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
                            url: '<?php echo $ajax_url; ?>',
                            type: 'POST',
                            data: {
                                action: 'delete_asset_type',
                                asset_type_id: id,
                                nonce: '<?php echo $nonce; ?>'
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
                                            showAlert('Response format error', 'error');
                                            return;
                                        }
                                    }
                                }
                                
                                if (responseData.success) {
                                    showAlert(responseData.data, 'success');
                                    loadAssetTypes();
                                } else {
                                    showAlert('Error: ' + responseData.data, 'error');
                                }
                            },
                            error: function() {
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

// Helper functions
function get_total_stock_items() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0;
}

function get_active_asset_types_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_asset_types';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'") ?: 0;
}