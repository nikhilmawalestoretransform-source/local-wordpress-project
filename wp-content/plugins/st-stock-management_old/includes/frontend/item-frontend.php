<?php

class STStockManagementFrontendItems {
    
    public static function display_frontend_items() {
        global $wpdb;
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Items</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="itemFrontendTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="add-item-tab" data-bs-toggle="tab" data-bs-target="#add-item" type="button" role="tab">Add Item</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="list-items-tab" data-bs-toggle="tab" data-bs-target="#list-items" type="button" role="tab">List Items</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="itemFrontendTabsContent">
                    <div class="tab-pane fade show active" id="add-item" role="tabpanel">
                        <form id="frontend-item-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="asset_type" class="form-label">Asset Type</label>
                                        <select class="form-control" id="asset_type" name="asset_type" required>
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="brand_model" class="form-label">Brand/Model</label>
                                        <input type="text" class="form-control" id="brand_model" name="brand_model" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="serial_number" class="form-label">Serial Number</label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_purchased" class="form-label">Date Purchased</label>
                                        <input type="date" class="form-control" id="date_purchased" name="date_purchased">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="warranty_expiry_date" class="form-label">Warranty Expiry Date</label>
                                        <input type="date" class="form-control" id="warranty_expiry_date" name="warranty_expiry_date">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                            </div>
                            
                            <input type="hidden" id="item_id" name="item_id" value="">
                            <button type="submit" class="btn btn-primary" id="save-item-btn">Add Item</button>
                        </form>
                    </div>
                    
                    <div class="tab-pane fade" id="list-items" role="tabpanel">
                        <div class="table-responsive">
                            <table id="frontend-items-table" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Asset Type</th>
                                        <th>Brand/Model</th>
                                        <th>Serial Number</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items = $wpdb->get_results("
                                        SELECT i.*, a.asset_name 
                                        FROM {$wpdb->prefix}item_stock_management i 
                                        LEFT JOIN {$wpdb->prefix}admin_item_stock_management a ON i.asset_type = a.id 
                                        ORDER BY i.created_date DESC
                                    ");
                                    foreach ($items as $item) {
                                        ?>
                                        <tr id="item-<?php echo $item->id; ?>">
                                            <td><?php echo $item->id; ?></td>
                                            <td><?php echo esc_html($item->asset_name); ?></td>
                                            <td><?php echo esc_html($item->brand_model); ?></td>
                                            <td><?php echo esc_html($item->serial_number); ?></td>
                                            <td><?php echo $item->quantity; ?></td>
                                            <td><?php echo number_format($item->price, 2); ?></td>
                                            <td><span class="badge bg-<?php echo self::get_status_badge($item->status); ?>"><?php echo ucfirst($item->status); ?></span></td>
                                            <td><?php echo esc_html($item->location); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-item" data-item='<?php echo json_encode($item); ?>'>Edit</button>
                                                <button class="btn btn-sm btn-danger delete-item" data-id="<?php echo $item->id; ?>">Delete</button>
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
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#frontend-items-table').DataTable();
            
            $('#frontend-item-form').on('submit', function(e) {
                e.preventDefault();
                
                var itemId = $('#item_id').val();
                var action = itemId ? 'edit_item' : 'add_item';
                
                var formData = $(this).serialize();
                formData += '&action_type=' + action + '&nonce=' + st_stock_ajax.nonce;
                
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
            
            $('.edit-item').on('click', function() {
                var item = $(this).data('item');
                
                $('#item_id').val(item.id);
                $('#asset_type').val(item.asset_type);
                $('#brand_model').val(item.brand_model);
                $('#serial_number').val(item.serial_number);
                $('#quantity').val(item.quantity);
                $('#price').val(item.price);
                $('#status').val(item.status);
                $('#location').val(item.location);
                $('#date_purchased').val(item.date_purchased);
                $('#warranty_expiry_date').val(item.warranty_expiry_date);
                $('#remarks').val(item.remarks);
                
                $('#save-item-btn').text('Update Item');
                $('#add-item-tab').tab('show');
            });
            
            $('.delete-item').on('click', function() {
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
                            action_type: 'delete_item',
                            item_id: id,
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
    
    private static function get_status_badge($status) {
        switch ($status) {
            case 'active': return 'success';
            case 'inactive': return 'secondary';
            case 'maintenance': return 'warning';
            default: return 'secondary';
        }
    }
    
    public static function add_item() {
        global $wpdb;
        
        $serial_number = sanitize_text_field($_POST['serial_number']);
        
        // Check if serial number already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}item_stock_management WHERE serial_number = %s",
            $serial_number
        ));
        
        if ($existing > 0) {
            wp_send_json_error(array('message' => 'Serial number already exists!'));
            return;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'item_stock_management',
            array(
                'asset_type' => intval($_POST['asset_type']),
                'brand_model' => sanitize_text_field($_POST['brand_model']),
                'serial_number' => $serial_number,
                'quantity' => intval($_POST['quantity']),
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
                'location' => sanitize_text_field($_POST['location']),
                'date_purchased' => sanitize_text_field($_POST['date_purchased']),
                'warranty_expiry_date' => sanitize_text_field($_POST['warranty_expiry_date']),
                'remarks' => sanitize_textarea_field($_POST['remarks']),
                'created_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Item added successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add item.'));
        }
    }
    
    public static function edit_item() {
        global $wpdb;
        
        $item_id = intval($_POST['item_id']);
        $serial_number = sanitize_text_field($_POST['serial_number']);
        
        // Check if serial number already exists for other items
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}item_stock_management WHERE serial_number = %s AND id != %d",
            $serial_number, $item_id
        ));
        
        if ($existing > 0) {
            wp_send_json_error(array('message' => 'Serial number already exists!'));
            return;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'item_stock_management',
            array(
                'asset_type' => intval($_POST['asset_type']),
                'brand_model' => sanitize_text_field($_POST['brand_model']),
                'serial_number' => $serial_number,
                'quantity' => intval($_POST['quantity']),
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
                'location' => sanitize_text_field($_POST['location']),
                'date_purchased' => sanitize_text_field($_POST['date_purchased']),
                'warranty_expiry_date' => sanitize_text_field($_POST['warranty_expiry_date']),
                'remarks' => sanitize_textarea_field($_POST['remarks'])
            ),
            array('id' => $item_id),
            array('%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Item updated successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update item.'));
        }
    }
    
    public static function delete_item() {
        global $wpdb;
        
        $item_id = intval($_POST['item_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'item_stock_management',
            array('id' => $item_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Item deleted successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete item.'));
        }
    }
}
?>