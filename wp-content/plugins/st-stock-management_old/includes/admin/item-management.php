<?php

class STStockManagementAdminItems {
    
    public static function display_admin_assets() {
        global $wpdb;
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Asset Types</h5>
            </div>
            <div class="card-body">
                <form id="admin-asset-form" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="hidden" id="asset_id" name="asset_id" value="">
                            <div class="mb-3">
                                <label for="asset_name" class="form-label">Asset Name</label>
                                <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="asset_status" class="form-label">Status</label>
                                <select class="form-control" id="asset_status" name="asset_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100" id="save-asset-btn">Add Asset</button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table id="admin-assets-table" class="table table-striped table-bordered">
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
                            <?php
                            $assets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}admin_item_stock_management ORDER BY created_date DESC");
                            foreach ($assets as $asset) {
                                ?>
                                <tr id="asset-<?php echo $asset->id; ?>">
                                    <td><?php echo $asset->id; ?></td>
                                    <td><?php echo esc_html($asset->asset_name); ?></td>
                                    <td><span class="badge bg-<?php echo $asset->status == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($asset->status); ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($asset->created_date)); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($asset->updated_date)); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-asset" data-id="<?php echo $asset->id; ?>" data-name="<?php echo esc_attr($asset->asset_name); ?>" data-status="<?php echo $asset->status; ?>">Edit</button>
                                        <button class="btn btn-sm btn-danger delete-asset" data-id="<?php echo $asset->id; ?>">Delete</button>
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
        
        <script>
        jQuery(document).ready(function($) {
            $('#admin-assets-table').DataTable();
            
            $('#admin-asset-form').on('submit', function(e) {
                e.preventDefault();
                
                var assetId = $('#asset_id').val();
                var action = assetId ? 'edit_admin_asset' : 'add_admin_asset';
                
                var formData = {
                    action_type: action,
                    asset_id: assetId,
                    asset_name: $('#asset_name').val(),
                    asset_status: $('#asset_status').val(),
                    nonce: st_stock_ajax.nonce
                };
                
                $.post(st_stock_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message
                        }).then(() => {
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
            
            $('.edit-asset').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var status = $(this).data('status');
                
                $('#asset_id').val(id);
                $('#asset_name').val(name);
                $('#asset_status').val(status);
                $('#save-asset-btn').text('Update Asset');
            });
            
            $('.delete-asset').on('click', function() {
                var id = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = {
                            action_type: 'delete_admin_asset',
                            asset_id: id,
                            nonce: st_stock_ajax.nonce
                        };
                        
                        $.post(st_stock_ajax.ajax_url, formData, function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.data.message
                                }).then(() => {
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
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public static function add_asset() {
        global $wpdb;
        
        $asset_name = sanitize_text_field($_POST['asset_name']);
        $status = sanitize_text_field($_POST['asset_status']);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'admin_item_stock_management',
            array(
                'asset_name' => $asset_name,
                'status' => $status,
                'created_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Asset added successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add asset.'));
        }
    }
    
    public static function edit_asset() {
        global $wpdb;
        
        $asset_id = intval($_POST['asset_id']);
        $asset_name = sanitize_text_field($_POST['asset_name']);
        $status = sanitize_text_field($_POST['asset_status']);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'admin_item_stock_management',
            array(
                'asset_name' => $asset_name,
                'status' => $status
            ),
            array('id' => $asset_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Asset updated successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update asset.'));
        }
    }
    
    public static function delete_asset() {
        global $wpdb;
        
        $asset_id = intval($_POST['asset_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'admin_item_stock_management',
            array('id' => $asset_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Asset deleted successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete asset.'));
        }
    }
}
?>