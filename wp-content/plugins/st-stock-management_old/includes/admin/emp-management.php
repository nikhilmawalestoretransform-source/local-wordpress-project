<?php

class STStockManagementAdminEmp {
    
    public static function display_admin_emp() {
        global $wpdb;
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Manage Employees</h5>
            </div>
            <div class="card-body">
                <form id="admin-emp-form" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="hidden" id="emp_id" name="emp_id" value="">
                            <div class="mb-3">
                                <label for="emp_name" class="form-label">Employee Name</label>
                                <input type="text" class="form-control" id="emp_name" name="emp_name" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="emp_email" name="emp_email">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="emp_position" name="emp_position">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="emp_status" class="form-label">Status</label>
                                <select class="form-control" id="emp_status" name="emp_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100" id="save-emp-btn">Add</button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table id="admin-emp-table" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $employees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}admin_emp_stock_management ORDER BY created_date DESC");
                            foreach ($employees as $emp) {
                                ?>
                                <tr id="emp-<?php echo $emp->id; ?>">
                                    <td><?php echo $emp->id; ?></td>
                                    <td><?php echo esc_html($emp->emp_name); ?></td>
                                    <td><?php echo esc_html($emp->email); ?></td>
                                    <td><?php echo esc_html($emp->position); ?></td>
                                    <td><span class="badge bg-<?php echo $emp->status == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($emp->status); ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($emp->created_date)); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($emp->updated_date)); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-emp" data-id="<?php echo $emp->id; ?>" data-name="<?php echo esc_attr($emp->emp_name); ?>" data-email="<?php echo esc_attr($emp->email); ?>" data-position="<?php echo esc_attr($emp->position); ?>" data-status="<?php echo $emp->status; ?>">Edit</button>
                                        <button class="btn btn-sm btn-danger delete-emp" data-id="<?php echo $emp->id; ?>">Delete</button>
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
            $('#admin-emp-table').DataTable();
            
            $('#admin-emp-form').on('submit', function(e) {
                e.preventDefault();
                
                var empId = $('#emp_id').val();
                var action = empId ? 'edit_admin_emp' : 'add_admin_emp';
                
                var formData = {
                    action_type: action,
                    emp_id: empId,
                    emp_name: $('#emp_name').val(),
                    emp_email: $('#emp_email').val(),
                    emp_position: $('#emp_position').val(),
                    emp_status: $('#emp_status').val(),
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
            
            $('.edit-emp').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var email = $(this).data('email');
                var position = $(this).data('position');
                var status = $(this).data('status');
                
                $('#emp_id').val(id);
                $('#emp_name').val(name);
                $('#emp_email').val(email);
                $('#emp_position').val(position);
                $('#emp_status').val(status);
                $('#save-emp-btn').text('Update');
            });
            
            $('.delete-emp').on('click', function() {
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
                            action_type: 'delete_admin_emp',
                            emp_id: id,
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
    
    public static function add_emp() {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'admin_emp_stock_management',
            array(
                'emp_name' => sanitize_text_field($_POST['emp_name']),
                'email' => sanitize_email($_POST['emp_email']),
                'position' => sanitize_text_field($_POST['emp_position']),
                'status' => sanitize_text_field($_POST['emp_status']),
                'created_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Employee added successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add employee.'));
        }
    }
    
    public static function edit_emp() {
        global $wpdb;
        
        $emp_id = intval($_POST['emp_id']);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'admin_emp_stock_management',
            array(
                'emp_name' => sanitize_text_field($_POST['emp_name']),
                'email' => sanitize_email($_POST['emp_email']),
                'position' => sanitize_text_field($_POST['emp_position']),
                'status' => sanitize_text_field($_POST['emp_status'])
            ),
            array('id' => $emp_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Employee updated successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update employee.'));
        }
    }
    
    public static function delete_emp() {
        global $wpdb;
        
        $emp_id = intval($_POST['emp_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'admin_emp_stock_management',
            array('id' => $emp_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Employee deleted successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete employee.'));
        }
    }
}
?>