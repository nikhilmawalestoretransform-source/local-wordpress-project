<?php
/**
 * Front Item Logs Management - Admin (With Pagination & Readable JSON)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'front_item_logs_admin_menu');

function front_item_logs_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Front Item Logs',
        'Front Item Logs',
        'manage_options',
        'front-item-logs-management',
        'front_item_logs_admin_page'
    );
}

// AJAX handler for getting front item logs with pagination
add_action('wp_ajax_get_front_item_logs_admin', 'ajax_get_front_item_logs_admin');

function ajax_get_front_item_logs_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'item_front_logs';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Front item logs table not found');
    }
    
    // Pagination parameters
    $per_page = 10;
    $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
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
    
    wp_send_json_success([
        'logs' => $logs,
        'pagination' => [
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'per_page' => $per_page
        ]
    ]);
}

function front_item_logs_admin_page() {
    wp_enqueue_script('jquery');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ?>
    <div class="wrap">
        <h1>📋 Front Item Logs</h1>
        
        <style>
            .logs-table-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .logs-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .logs-table th,
            .logs-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
                vertical-align: top;
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
            .action-viewed { 
                color: #0073aa; 
                font-weight: bold;
                background-color: #f0f8ff;
                padding: 4px 8px;
                border-radius: 3px;
            }
            
            .no-results {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
            .loading {
                text-align: center;
                padding: 40px;
            }
            
            /* JSON data styling */
            .json-data {
                background: #f8f9fa;
                padding: 8px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                font-size: 11px;
                max-width: 250px;
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
            
            /* Pagination Styles */
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
            .displaying-num {
                color: #646970;
                font-size: 13px;
            }
            .no-data {
                color: #999;
                font-style: italic;
            }
        </style>

        <div class="logs-table-container">
            <h3>📊 Front Item Activity Logs</h3>
            <div id="front-item-logs-table-container">
                <div class="loading">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <p>Loading front item logs...</p>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                loadFrontItemLogs(currentPage);
            });

            function loadFrontItemLogs(page = 1) {
                currentPage = page;
                
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_front_item_logs_admin',
                        nonce: nonce,
                        page: page
                    },
                    success: function(response) {
                        if (response && response.success) {
                            displayFrontItemLogs(response.data.logs, response.data.pagination);
                        } else {
                            displayFrontItemLogs([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        displayFrontItemLogs([], null);
                    }
                });
            }

            function displayFrontItemLogs(logs, pagination) {
                const container = document.getElementById('front-item-logs-table-container');
                
                if (!logs || logs.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                            <h3>No front item logs found</h3>
                            <p>The front item logs table is empty.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = `
                    <div class="tablenav">
                        <div class="tablenav-paging">
                            <span class="displaying-num">
                                Displaying ${((pagination.current_page - 1) * pagination.per_page) + 1} - ${Math.min(pagination.current_page * pagination.per_page, pagination.total_items)} of ${pagination.total_items} items
                            </span>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Item ID</th>
                                    <th>User ID</th>
                                    <th>User Name</th>
                                    <th>Old Data</th>
                                    <th>New Data</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                logs.forEach(log => {
                    const action_display = formatActionDisplay(log.action);
                    
                    html += `
                        <tr>
                            <td>${log.id}</td>
                            <td>
                                <span class="${action_display.class}">
                                    ${action_display.label}
                                </span>
                            </td>
                            <td>${log.item_id}</td>
                            <td>${log.user_id}</td>
                            <td>${escapeHtml(log.user_name)}</td>
                            <td>${formatJsonData(log.old_data)}</td>
                            <td>${formatJsonData(log.new_data)}</td>
                            <td><code>${escapeHtml(log.ip_address)}</code></td>
                            <td class="json-data">${formatUserAgent(log.user_agent)}</td>
                            <td>${formatDateTime(log.created_at)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                `;
                
                // Add pagination if needed
                if (pagination && pagination.total_pages > 1) {
                    html += generatePagination(pagination);
                }
                
                container.innerHTML = html;
            }

            function generatePagination(pagination) {
                let html = '<div class="pagination">';
                
                // Previous button
                if (pagination.current_page > 1) {
                    html += `<a href="javascript:void(0)" onclick="loadFrontItemLogs(${pagination.current_page - 1})">« Previous</a>`;
                }
                
                // Page numbers
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === pagination.current_page) {
                        html += `<span class="current">${i}</span>`;
                    } else {
                        html += `<a href="javascript:void(0)" onclick="loadFrontItemLogs(${i})">${i}</a>`;
                    }
                }
                
                // Next button
                if (pagination.current_page < pagination.total_pages) {
                    html += `<a href="javascript:void(0)" onclick="loadFrontItemLogs(${pagination.current_page + 1})">Next »</a>`;
                }
                
                html += '</div>';
                return html;
            }

            function formatActionDisplay(action) {
                const actionMap = {
                    'create': { label: 'Created', class: 'action-created' },
                    'update': { label: 'Updated', class: 'action-updated' },
                    'delete': { label: 'Deleted', class: 'action-deleted' },
                    'view': { label: 'Viewed', class: 'action-viewed' }
                };
                
                return actionMap[action] || { 
                    label: action.charAt(0).toUpperCase() + action.slice(1), 
                    class: 'action-updated' 
                };
            }

            function formatJsonData(data) {
                if (!data || data === 'null' || data === 'NULL') {
                    return '<span class="no-data">—</span>';
                }
                
                try {
                    const parsed = JSON.parse(data);
                    if (typeof parsed === 'object') {
                        let html = '<div class="json-data">';
                        for (const [key, value] of Object.entries(parsed)) {
                            // Convert underscore to space and capitalize first letter of each word
                            const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            const displayValue = Array.isArray(value) ? JSON.stringify(value) : value;
                            html += `<div class="json-row"><span class="json-key">${formattedKey}:</span> <span class="json-value">${escapeHtml(String(displayValue))}</span></div>`;
                        }
                        html += '</div>';
                        return html;
                    }
                    return `<div class="json-data">${escapeHtml(String(data))}</div>`;
                } catch (e) {
                    return `<div class="json-data">${escapeHtml(String(data))}</div>`;
                }
            }

            function formatUserAgent(userAgent) {
                if (!userAgent || userAgent === 'null' || userAgent === 'NULL') {
                    return 'N/A';
                }
                
                // Truncate long user agent strings
                if (userAgent.length > 100) {
                    return escapeHtml(userAgent.substring(0, 100) + '...');
                }
                
                return escapeHtml(userAgent);
            }

            function formatDateTime(dateTimeString) {
                if (!dateTimeString) return 'N/A';
                try {
                    const date = new Date(dateTimeString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                } catch (e) {
                    return dateTimeString;
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>
    </div>
    <?php
}