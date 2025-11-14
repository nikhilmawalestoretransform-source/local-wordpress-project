<?php

class STStockManagementFrontendEmp {
    
    public static function display_frontend_emp() {
        global $wpdb;
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Employee Asset Assignments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="frontend-emp-table" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Assigned Assets</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $employees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}admin_emp_stock_management ORDER BY created_date DESC");
                            foreach ($employees as $emp) {
                                $assigned_assets = $wpdb->get_results($wpdb->prepare(
                                    "SELECT ea.*, a.asset_name, i.brand_model 
                                     FROM {$wpdb->prefix}emp_stock_management ea
                                     LEFT JOIN {$wpdb->prefix}admin_item_stock_management a ON ea.asset_type = a.id
                                     LEFT JOIN {$wpdb->prefix}item_stock_management i ON ea.brand_model = i.brand_model
                                     WHERE ea.emp_id = %d",
                                    $emp->id
                                ));
                                ?>
                                <tr id="emp-<?php echo $emp->id; ?>">
                                    <td><?php echo $emp->id; ?></td>
                                    <td><?php echo esc_html($emp->emp_name); ?></td>
                                    <td><?php echo esc_html($emp->email); ?></td>
                                    <td><?php echo esc_html($emp->position); ?></td>
                                    <td><span class="badge bg-<?php echo $emp->status == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($emp->status); ?></span></td>
                                    <td>
                                        <?php
                                        if ($assigned_assets) {
                                            foreach ($assigned_assets as $asset) {
                                                echo '<span class="badge bg-info me-1">' . esc_html($asset->asset_name) . ' - ' . esc_html($asset->brand_model) . '</span>';
                                            }
                                        } else {
                                            echo 'No assets assigned';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary assign-asset" data-emp-id="<?php echo $emp->id; ?>" data-emp-name="<?php echo esc_attr($emp->emp_name); ?>">Assign Asset</button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Assign Asset Modal -->
        <div class="modal fade" id="assignAssetModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Assets to <span id="modal-emp-name"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="assign-asset-form">
                            <input type="hidden" id="assign_emp_id" name="emp_id">
                            <div class="row" id="asset-row-template">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Asset Type</label>
                                        <select class="form-control asset-type" name="asset_type[]" required>
                                            <option value="">Select Asset Type</option>
                                            <?php
                                            $asset_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}admin_item_stock_management WHERE status = 'active'");
                                            foreach ($asset_types as $type) {
                                                echo '<option value="' . $type->id . '">' . esc_html($type->asset_name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Brand/Model</label>
                                        <select class="form-control brand-model" name="brand_model[]" required>
                                            <option value="">Select Brand/Model</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-success w-100 add-asset-row">+</button>
                                    </div>
                                </div>
                            </div>
                            <div id="additional-rows"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="save-assignments">Save Assignments</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#frontend-emp-table').DataTable();
            
            var rowCount = 1;
            
            $('.assign-asset').on('click', function() {
                var empId = $(this).data('emp-id');
                var empName = $(this).data('emp-name');
                
                $('#assign_emp_id').val(empId);
                $('#modal-emp-name').text(empName);
                $('#assignAssetModal').modal('show');
                
                // Reset form
                $('#additional-rows').empty();
                $('.asset-type').val('');
                $('.brand-model').empty().append('<option value="">Select Brand/Model</option>');
            });
            
            $(document).on('change', '.asset-type', function() {
                var assetType = $(this).val();
                var brandModelSelect = $(this).closest('.row').find('.brand-model');
                
                if (assetType) {
                    $.post(st_stock_ajax.ajax_url, {
                        action_type: 'get_brand_models',
                        asset_type: assetType,
                        nonce: st_stock_ajax.nonce
                    }, function(response) {
                        if (response.success) {
                            brandModelSelect.empty().append('<option value="">Select Brand/Model</option>');
                            $.each(response.data, function(index, item) {
                                brandModelSelect.append('<option value="' + item.brand_model + '">' + item.brand_model + '</option>');
                            });
                        }
                    });
                } else {
                    brandModelSelect.empty().append('<option value="">Select Brand/Model</option>');
                }
            });
            
            $('.add-asset-row').on('click', function() {
                rowCount++;
                var newRow = $('#asset-row-template').clone().removeAttr('id');
                newRow.find('.add-asset-row').removeClass('btn-success add-asset-row').addClass('btn-danger remove-asset-row').text('−');
                $('#additional-rows').append(newRow);
            });
            
            $(document).on('click', '.remove-asset-row', function() {
                if ($('.row').length > 1) {
                    $(this).closest('.row').remove();
                }
            });
            
            $('#save-assignments').on('click', function() {
                var formData = $('#assign-asset-form').serialize();
                formData += '&action_type=assign_asset_to_emp&nonce=' + st_stock_ajax.nonce;
                
                $.post(st_stock_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message
                        }).then(() => {
                            $('#assignAssetModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.data.message
                        });
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public static function get_brand_models() {
        global $wpdb;
        
        $asset_type = intval($_POST['asset_type']);
        
        $brand_models = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT brand_model 
             FROM {$wpdb->prefix}item_stock_management 
             WHERE asset_type = %d AND status = 'active' 
             ORDER BY brand_model",
            $asset_type
        ));
        
        wp_send_json_success($brand_models);
    }
    
    public static function assign_asset() {
        global $wpdb;
        
        $emp_id = intval($_POST['emp_id']);
        $asset_types = $_POST['asset_type'];
        $brand_models = $_POST['brand_model'];
        
        // Delete existing assignments
        $wpdb->delete(
            $wpdb->prefix . 'emp_stock_management',
            array('emp_id' => $emp_id),
            array('%d')
        );
        
        // Insert new assignments
        $success = true;
        for ($i = 0; $i < count($asset_types); $i++) {
            if (!empty($asset_types[$i]) && !empty($brand_models[$i])) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'emp_stock_management',
                    array(
                        'emp_id' => $emp_id,
                        'asset_type' => intval($asset_types[$i]),
                        'brand_model' => sanitize_text_field($brand_models[$i]),
                        'created_date' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s')
                );
                
                if (!$result) {
                    $success = false;
                }
            }
        }
        
        if ($success) {
            wp_send_json_success(array('message' => 'Assets assigned successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to assign some assets.'));
        }
    }
}
?>