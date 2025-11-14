<?php
// Employee Management File
// This file contains all Employee Management related HTML and JavaScript

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get AJAX URL and nonce
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('stock_management_ajax');
?>

<h3>👨‍💼 Employee Management</h3>
<div id="emp-list-container">
    <div style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
        <p>Loading employees...</p>
    </div>
</div>

<!-- Assign Assets Popup -->
<div id="assign-assets-popup-content" style="display: none;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: white;">Assign Assets to Employee</h3>
            <button type="button" onclick="closeAssignPopup()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">×</button>
        </div>
        
        <div class="assign-assets-container">
            <input type="hidden" id="assign_emp_id">
            <div class="form-group">
                <label for="assign_emp_name">👨‍💼 Employee Name</label>
                <select id="assign_emp_name" class="assign-emp-select" required>
                    <option value="">Select Employee</option>
                    <?php
                    // Fetch employees from admin_emp_stock_management table
                    global $wpdb;
                    $emp_table = $wpdb->prefix . 'admin_emp_stock_management';
                    
                    if ($wpdb->get_var("SHOW TABLES LIKE '$emp_table'") == $emp_table) {
                        $employees = $wpdb->get_results("SELECT * FROM $emp_table ORDER BY emp_name ASC");
                        
                        foreach ($employees as $emp) {
                            echo '<option value="' . esc_attr($emp->id) . '">' . esc_html($emp->emp_name) . '</option>';
                        }
                    } else {
                        echo '<option value="">No employees found</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div id="assets-container">
                <div class="asset-row" data-index="0">
                    <div class="form-group">
                        <label for="asset_type_0">🏷️ Asset Type</label>
                        <select class="asset-type-select" id="asset_type_0" data-index="0" required>
                            <option value="">Select Asset Type</option>
                            <?php
                            // Fetch asset types from admin_item_stock_management table
                            $asset_table = $wpdb->prefix . 'admin_item_stock_management';
                            
                            if ($wpdb->get_var("SHOW TABLES LIKE '$asset_table'") == $asset_table) {
                                $asset_types = $wpdb->get_results("SELECT * FROM $asset_table WHERE status = 'active' ORDER BY asset_name ASC");
                                
                                foreach ($asset_types as $asset) {
                                    echo '<option value="' . esc_attr($emp->id) . '">' . esc_html($asset->asset_name) . '</option>';
                                }
                            } else {
                                echo '<option value="">No asset types found</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand_model_0">🏭 Brand/Model</label>
                        <select class="brand-model-select" id="brand_model_0" data-index="0" required>
                            <option value="">Select Brand/Model</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" onclick="addAssetRow()">➕ Add</button>
                    </div>
                </div>
            </div>
            
            <!-- Previously assigned assets section -->
            <div id="previously-assigned-assets" style="margin-top: 20px; display: none;">
                <h4 style="color: #FFD700; margin-bottom: 15px;">📋 Previously Assigned Assets</h4>
                <div id="assigned-assets-list" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
                    <!-- Previously assigned assets will be loaded here -->
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button type="button" class="btn btn-primary" onclick="assignAssetsToEmp()">✅ Assign Assets</button>
                <button type="button" class="btn btn-secondary" onclick="closeAssignPopup()">❌ Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
// Employee Management JavaScript Functions

// Global variable to track row indices
let assetRowCounter = 1;

function openAssignPopup(empId, empName) {
    document.getElementById('assign_emp_id').value = empId;
    
    // Set popup content
    document.getElementById('assign-assets-popup').innerHTML = document.getElementById('assign-assets-popup-content').innerHTML;
    
    // Set the employee dropdown value
    document.getElementById('assign_emp_name').value = empId;
    
    // Reset the row counter
    assetRowCounter = 1;
    
    // Clear any existing asset rows except the first one
    const assetsContainer = document.getElementById('assets-container');
    const firstRow = assetsContainer.querySelector('.asset-row[data-index="0"]');
    assetsContainer.innerHTML = '';
    assetsContainer.appendChild(firstRow);
    
    // Load previously assigned assets
    loadPreviouslyAssignedAssets(empId);
    
    document.getElementById('assign-assets-popup').style.display = 'flex';
    
    // Re-initialize event listeners for the new popup content
    initializePopupEventListeners();
}

function initializePopupEventListeners() {
    // Add event listeners to all asset type dropdowns
    const assetSelects = document.querySelectorAll('.asset-type-select');
    assetSelects.forEach(select => {
        select.addEventListener('change', function() {
            const index = this.getAttribute('data-index');
            loadBrandModels(this.value, index);
        });
    });
}

function closeAssignPopup() {
    document.getElementById('assign-assets-popup').style.display = 'none';
}

function loadPreviouslyAssignedAssets(empId) {
    jQuery.ajax({
        url: '<?php echo esc_js($ajax_url); ?>',
        type: 'POST',
        data: {
            action: 'get_emp_assigned_assets',
            emp_id: empId,
            nonce: '<?php echo esc_js($nonce); ?>'
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
            
            if (responseData.success && responseData.data.length > 0) {
                const container = document.getElementById('assigned-assets-list');
                const section = document.getElementById('previously-assigned-assets');
                
                let html = '<div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px; font-weight: bold; color: #FFD700;">';
                html += '<div>Asset Type</div>';
                html += '<div>Brand/Model</div>';
                html += '<div>Action</div>';
                html += '</div>';
                
                responseData.data.forEach(asset => {
                    html += `
                    <div class="assigned-asset-item" data-asset-id="${asset.id}" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: center; padding: 10px; background: rgba(255,255,255,0.05); margin-bottom: 5px; border-radius: 5px;">
                        <div>${asset.asset_type}</div>
                        <div>${asset.brand_model}</div>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeAssignedAsset(${asset.id}, ${empId})" style="padding: 5px 10px; font-size: 12px;">🗑️ Remove</button>
                        </div>
                    </div>
                    `;
                });
                
                container.innerHTML = html;
                section.style.display = 'block';
            } else {
                document.getElementById('previously-assigned-assets').style.display = 'none';
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading assigned assets:', error);
            document.getElementById('previously-assigned-assets').style.display = 'none';
        }
    });
}

function removeAssignedAsset(assetId, empId) {
    if (!confirm('Are you sure you want to remove this assigned asset?')) {
        return;
    }
    
    jQuery.ajax({
        url: '<?php echo esc_js($ajax_url); ?>',
        type: 'POST',
        data: {
            action: 'remove_emp_asset',
            asset_id: assetId,
            nonce: '<?php echo esc_js($nonce); ?>'
        },
        success: function(response) {
            let responseData = response;
            if (typeof response === 'string') {
                const jsonMatch = response.match(/\{.*\}/s);
                if (jsonMatch) {
                    try {
                        responseData = JSON.parse(jsonMatch[0]);
                    } catch (e) {
                        showSweetAlert('Error removing asset', 'error');
                        return;
                    }
                } else {
                    showSweetAlert('Invalid response format', 'error');
                    return;
                }
            }
            
            if (responseData.success) {
                showSweetAlert('Asset removed successfully!', 'success');
                // Reload the assigned assets list
                loadPreviouslyAssignedAssets(empId);
            } else {
                showSweetAlert('Error: ' + responseData.data, 'error');
            }
        },
        error: function(xhr, status, error) {
            showSweetAlert('Server error. Please try again.', 'error');
        }
    });
}

function loadBrandModels(assetType, index) {
    console.log('Loading brands for asset type:', assetType, 'Index:', index);
    
    const brandModelSelect = document.getElementById('brand_model_' + index);
    
    if (!assetType) {
        brandModelSelect.innerHTML = '<option value="">Select Brand/Model</option>';
        brandModelSelect.disabled = false;
        return;
    }

    // Show loading
    brandModelSelect.innerHTML = '<option value="">Loading brands...</option>';
    brandModelSelect.disabled = true;

    jQuery.ajax({
        url: '<?php echo esc_js($ajax_url); ?>',
        type: 'POST',
        data: {
            action: 'get_brand_models',
            asset_type: assetType,
            nonce: '<?php echo esc_js($nonce); ?>'
        },
        success: function(response) {
            console.log('Brand models response:', response);
            
            let responseData = response;
            
            if (typeof response === 'object') {
                responseData = response;
            } else if (typeof response === 'string') {
                try {
                    responseData = JSON.parse(response);
                } catch (e) {
                    console.error('Error parsing response:', e);
                    brandModelSelect.innerHTML = '<option value="">Error loading brands</option>';
                    brandModelSelect.disabled = false;
                    return;
                }
            }
            
            if (responseData.success) {
                let optionsHTML = '<option value="">Select Brand/Model</option>';
                
                if (responseData.data && responseData.data.length > 0) {
                    responseData.data.forEach(brandModel => {
                        const safeBrandModel = String(brandModel).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        optionsHTML += `<option value="${safeBrandModel}">${safeBrandModel}</option>`;
                    });
                } else {
                    optionsHTML = '<option value="">No brands found for this asset type</option>';
                }
                
                console.log('Setting dropdown HTML for index', index);
                brandModelSelect.innerHTML = optionsHTML;
                brandModelSelect.disabled = false;
                
                console.log('After update - Options length:', brandModelSelect.options.length);
                
            } else {
                brandModelSelect.innerHTML = '<option value="">Error: ' + (responseData.data || 'Unknown error') + '</option>';
                brandModelSelect.disabled = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading brand models:', error);
            brandModelSelect.innerHTML = '<option value="">Error loading brands</option>';
            brandModelSelect.disabled = false;
        }
    });
}

function addAssetRow() {
    const container = document.getElementById('assets-container');
    const newIndex = assetRowCounter++;
    
    const newRow = document.createElement('div');
    newRow.className = 'asset-row';
    newRow.setAttribute('data-index', newIndex);
    newRow.innerHTML = `
        <div class="form-group">
            <label for="asset_type_${newIndex}">🏷️ Asset Type</label>
            <select class="asset-type-select" id="asset_type_${newIndex}" data-index="${newIndex}" required>
                <option value="">Select Asset Type</option>
                <?php
                // Re-fetch asset types for new rows
                global $wpdb;
                $asset_table = $wpdb->prefix . 'admin_item_stock_management';
                if ($wpdb->get_var("SHOW TABLES LIKE '$asset_table'") == $asset_table) {
                    $asset_types = $wpdb->get_results("SELECT * FROM $asset_table WHERE status = 'active' ORDER BY asset_name ASC");
                    foreach ($asset_types as $asset) {
                        echo '<option value="' . esc_attr($asset->asset_name) . '">' . esc_html($asset->asset_name) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="brand_model_${newIndex}">🏭 Brand/Model</label>
            <select class="brand-model-select" id="brand_model_${newIndex}" data-index="${newIndex}" required>
                <option value="">Select Brand/Model</option>
            </select>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-danger" onclick="removeAssetRow(${newIndex})">🗑️ Remove</button>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Add event listener to the new asset type dropdown
    const newAssetSelect = document.getElementById('asset_type_' + newIndex);
    if (newAssetSelect) {
        newAssetSelect.addEventListener('change', function() {
            const index = this.getAttribute('data-index');
            loadBrandModels(this.value, index);
        });
    }
}

function removeAssetRow(index) {
    const row = document.querySelector(`.asset-row[data-index="${index}"]`);
    if (row) {
        row.remove();
    }
}

function assignAssetsToEmp() {
    const empId = document.getElementById('assign_emp_id').value;
    const assetRows = document.querySelectorAll('.asset-row');
    const assets = [];

    // Validate all rows
    let isValid = true;
    assetRows.forEach(row => {
        const assetType = row.querySelector('.asset-type-select').value;
        const brandModel = row.querySelector('.brand-model-select').value;
        
        if (assetType && brandModel) {
            assets.push({
                asset_type: assetType,
                brand_model: brandModel
            });
        } else if (assetType || brandModel) {
            isValid = false;
            row.style.border = '2px solid #dc3545';
        } else {
            row.style.border = 'none';
        }
    });

    if (!isValid) {
        showSweetAlert('Please fill all asset fields or remove incomplete rows.', 'error');
        return;
    }

    if (assets.length === 0) {
        showSweetAlert('Please add at least one asset.', 'error');
        return;
    }

    jQuery.ajax({
        url: '<?php echo esc_js($ajax_url); ?>',
        type: 'POST',
        data: {
            action: 'assign_assets_to_emp',
            emp_id: empId,
            assets: assets,
            nonce: '<?php echo esc_js($nonce); ?>'
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
                // Clear the form and reload assigned assets
                loadPreviouslyAssignedAssets(empId);
                // Reset the form
                const assetRows = document.querySelectorAll('.asset-row');
                assetRows.forEach((row, index) => {
                    if (index > 0) {
                        row.remove();
                    } else {
                        // Reset first row
                        row.querySelector('.asset-type-select').value = '';
                        row.querySelector('.brand-model-select').innerHTML = '<option value="">Select Brand/Model</option>';
                    }
                });
                assetRowCounter = 1;
            } else {
                showSweetAlert('Error: ' + responseData.data, 'error');
            }
        },
        error: function(xhr, status, error) {
            showSweetAlert('Server error. Please try again.', 'error');
        }
    });
}

// Initialize employee tab when document is ready
jQuery(document).ready(function($) {
    // Load employee list if employee tab is active on page load
    if (document.getElementById('employee-tab') && document.getElementById('employee-tab').classList.contains('active')) {
        loadEmpList();
    }
});
</script>