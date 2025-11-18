<?php

/**
 * Employee Logs Management - With Pagination
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'emp_logs_admin_menu');

function emp_logs_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Admin Emp Logs',
        'Admin Emp Logs',
        'manage_options',
        'emp-logs-management',
        'emp_logs_admin_page'
    );
}

// Function to format JSON data into readable format
function emp_format_json_readable($json_string) {
    if (empty($json_string)) {
        return '<span class="no-data">—</span>';
    }
    
    $data = json_decode($json_string, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        return '<div class="json-data" title="' . esc_attr($json_string) . '">' . 
               esc_html($json_string) . '</div>';
    }
    
    $output = '<div class="json-data">';
    foreach ($data as $key => $value) {
        // Convert underscore to space and capitalize first letter of each word
        $formatted_key = ucwords(str_replace('_', ' ', $key));
        
        // Format the value
        if (is_array($value)) {
            $display_value = json_encode($value);
        } else {
            $display_value = $value;
        }
        
        $output .= '<div class="json-row"><span class="json-key">' . esc_html($formatted_key) . ':</span> <span class="json-value">' . esc_html($display_value) . '</span></div>';
    }
    $output .= '</div>';
    
    return $output;
}

// Function to format action display with proper colors and labels
function emp_format_action_display($action) {
    $action_map = array(
        'created' => array(
            'label' => 'Employee Created',
            'class' => 'action-created'
        ),
        'updated' => array(
            'label' => 'Employee Updated',
            'class' => 'action-updated'
        ),
        'deleted' => array(
            'label' => 'Employee Deleted',
            'class' => 'action-deleted'
        ),
        'emp_created' => array(
            'label' => 'Employee Created',
            'class' => 'action-created'
        ),
        'emp_updated' => array(
            'label' => 'Employee Updated',
            'class' => 'action-updated'
        ),
        'emp_deleted' => array(
            'label' => 'Employee Deleted',
            'class' => 'action-deleted'
        )
    );
    
    if (isset($action_map[$action])) {
        return array(
            'label' => $action_map[$action]['label'],
            'class' => $action_map[$action]['class']
        );
    }
    
    // Default for unknown actions
    return array(
        'label' => ucwords(str_replace('_', ' ', $action)),
        'class' => 'action-updated'
    );
}

function emp_logs_admin_page() {
    global $wpdb;
    
    // Pagination settings
    $per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    ?>
    <div class="wrap">
        <h1>📋 Employee Logs</h1>
        
        <style>
            .logs-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .logs-table th,
            .logs-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
                font-size: 14px;
            }
            .logs-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .logs-table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .logs-table tr:hover {
                background-color: #e9ecef;
            }
            
            /* Action colors */
            .action-created { 
                color: #28a745; 
                font-weight: bold;
                background-color: #f8fff9;
                padding: 4px 8px;
                border-radius: 3px;
            }
            .action-updated { 
                color: #ffc107; 
                font-weight: bold;
                background-color: #fffbf0;
                padding: 4px 8px;
                border-radius: 3px;
            }
            .action-deleted { 
                color: #dc3545; 
                font-weight: bold;
                background-color: #fff5f5;
                padding: 4px 8px;
                border-radius: 3px;
            }
            
            .error-notice {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border: 1px solid #f5c6cb;
                border-radius: 4px;
                margin: 10px 0;
            }
            
            /* JSON data styling */
            .json-data {
                background: #f8f9fa;
                padding: 8px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                max-width: 300px;
                max-height: 120px;
                overflow: auto;
                word-break: break-all;
                border: 1px solid #e9ecef;
            }
            .json-row {
                margin-bottom: 4px;
                padding-bottom: 4px;
                border-bottom: 1px dashed #dee2e6;
            }
            .json-row:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }
            .json-key {
                font-weight: bold;
                color: #21759b;
            }
            .json-value {
                color: #333;
            }
            
            .pagination {
                margin-top: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 5px;
            }
            .pagination a,
            .pagination span {
                padding: 8px 12px;
                border: 1px solid #ddd;
                text-decoration: none;
                color: #0073aa;
                border-radius: 3px;
            }
            .pagination .current {
                background: #0073aa;
                color: white;
                border-color: #0073aa;
            }
            .pagination a:hover {
                background: #f8f9fa;
            }
            .tablenav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 20px 0;
            }
            .no-data {
                color: #999;
                font-style: italic;
            }
        </style>

        <?php
        // Get logs from database
        $table_name = $wpdb->prefix . 'admin_emp_stock_management_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<div class="error-notice">❌ Error: Logs table not found in database.</div>';
            return;
        }
        
        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        // Get logs with pagination
        $logs = $wpdb->get_results($wpdb->prepare("
            SELECT * 
            FROM $table_name 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        // Check for query errors
        if ($wpdb->last_error) {
            echo '<div class="error-notice">❌ Database Error: ' . esc_html($wpdb->last_error) . '</div>';
            return;
        }
        ?>

        <div class="logs-container">
            <?php if (empty($logs)) : ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <p>No logs found in the database.</p>
                </div>
            <?php else : ?>
                <div class="tablenav">
                    <div class="tablenav-paging">
                        <span class="displaying-num">
                            <?php 
                            $start = ($current_page - 1) * $per_page + 1;
                            $end = min($current_page * $per_page, $total_items);
                            echo "Displaying $start - $end of $total_items items";
                            ?>
                        </span>
                    </div>
                </div>
                
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Emp ID</th>
                            <th>Action</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>User ID</th>
                            <th>User IP</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <?php $action_display = emp_format_action_display($log->action); ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td><?php echo esc_html($log->emp_id); ?></td>
                                <td>
                                    <span class="<?php echo $action_display['class']; ?>">
                                        <?php echo $action_display['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo emp_format_json_readable($log->old_value); ?>
                                </td>
                                <td>
                                    <?php echo emp_format_json_readable($log->new_value); ?>
                                </td>
                                <td>
                                    <?php 
                                    $user = get_user_by('id', $log->user_id);
                                    echo $user ? esc_html($user->display_name) : 'User ' . esc_html($log->user_id);
                                    ?>
                                </td>
                                <td><code><?php echo esc_html($log->user_ip); ?></code></td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <div class="pagination">
                        <?php
                        // Previous button
                        if ($current_page > 1) {
                            echo '<a href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '">« Previous</a>';
                        }
                        
                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i == $current_page) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '">' . $i . '</a>';
                            }
                        }
                        
                        // Next button
                        if ($current_page < $total_pages) {
                            echo '<a href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '">Next »</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; text-align: center; color: #6c757d;">
                    <p><strong>Total Records:</strong> <?php echo $total_items; ?> | <strong>Page:</strong> <?php echo $current_page; ?> of <?php echo $total_pages; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}