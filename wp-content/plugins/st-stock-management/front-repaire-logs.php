<?php
/**
 * Repair Front Logs Management - Admin (Simple Data Table View with Pagination)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'repair_front_logs_admin_menu');

function repair_front_logs_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Front Repair Logs',
        'Front Repair Logs',
        'manage_options',
        'repair-front-logs-management',
        'repair_front_logs_admin_page'
    );
}

// AJAX handler for getting repair front logs
add_action('wp_ajax_get_repair_front_logs_admin', 'ajax_get_repair_front_logs_admin');

function ajax_get_repair_front_logs_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'repaire_front_log';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Repair front logs table not found');
    }
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 5; // Number of items per page
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    // Get paginated logs ordered by latest first
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        ORDER BY created_at DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    wp_send_json_success([
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ]
    ]);
}

function repair_front_logs_admin_page() {
    wp_enqueue_script('jquery');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ?>
    <div class="wrap">
        <h1>🔧 Repair Front Logs</h1>
        
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
                padding: 10px;
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
            .no-results {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
            .loading {
                text-align: center;
                padding: 40px;
            }
            .action-create { 
                background-color: #d4edda;
                color: #155724;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-update { 
                background-color: #cce7ff;
                color: #004085;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-delete { 
                background-color: #f8d7da;
                color: #721c24;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-status { 
                background-color: #fff3cd;
                color: #856404;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-complete { 
                background-color: #d1ecf1;
                color: #0c5460;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-cancel { 
                background-color: #f8d7da;
                color: #721c24;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .action-view { 
                background-color: #e2e3e5;
                color: #383d41;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
                display: inline-block;
            }
            .json-data {
                max-width: 200px;
                overflow: hidden;
                cursor: pointer;
                position: relative;
            }
            .json-data.expanded {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #ddd;
                max-width: 400px;
                z-index: 1000;
                position: relative;
            }
            .json-data.expanded .json-content {
                max-height: 300px;
                overflow-y: auto;
            }
            .view-more {
                color: #0073aa;
                cursor: pointer;
                font-size: 11px;
                margin-left: 5px;
                font-weight: bold;
            }
            .key-value-pair {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                margin: 0;
            }
            .data-key {
                font-weight: 600;
                color: #2c3e50;
                display: inline-block;
                min-width: 120px;
            }
            .data-value {
                color: #34495e;
                word-break: break-word;
            }
            .data-value.empty {
                color: #95a5a6;
                font-style: italic;
            }
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
            .json-content {
                max-height: 120px;
                overflow-y: hidden;
            }
            .json-data.expanded .json-content {
                max-height: 300px;
                overflow-y: auto;
            }
            .repair-info {
                /*background: #fff3cd;*/
                padding: 8px;
                border-radius: 4px;
                margin-bottom: 8px;
                border-left: 3px solid #ffc107;
            }
            .asset-info {
                background: #d1ecf1;
                padding: 8px;
                border-radius: 4px;
                border-left: 3px solid #17a2b8;
            }
        </style>

        <div class="logs-table-container">
            <h3>🔧 Repair Request Activity Logs</h3>
            <div id="repair-front-logs-table-container">
                <div class="loading">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <p>Loading repair logs...</p>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                loadRepairFrontLogs(currentPage);
            });

            function loadRepairFrontLogs(page) {
                currentPage = page;
                const container = document.getElementById('repair-front-logs-table-container');
                container.innerHTML = `
                    <div class="loading">
                        <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                        <p>Loading repair logs...</p>
                    </div>
                `;

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_repair_front_logs_admin',
                        nonce: nonce,
                        page: page
                    },
                    success: function(response) {
                        if (response && response.success) {
                            displayRepairFrontLogs(response.data.logs, response.data.pagination);
                        } else {
                            displayRepairFrontLogs([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        displayRepairFrontLogs([], null);
                    }
                });
            }

            function displayRepairFrontLogs(logs, pagination) {
                const container = document.getElementById('repair-front-logs-table-container');
                
                if (!logs || logs.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                            <h3>No repair logs found</h3>
                            <p>The repair logs table is empty.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = `
                    <div style="overflow-x: auto;">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Repair ID</th>
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
                    const actionClass = `action-${log.action}`;
                    
                    html += `
                        <tr>
                            <td>${log.id}</td>
                            <td><span class="${actionClass}">${formatAction(log.action)}</span></td>
                            <td>${log.repair_id}</td>
                            <td>${log.user_id}</td>
                            <td><strong>${escapeHtml(log.user_name)}</strong></td>
                            <td class="json-data" id="old-data-${log.id}">
                                <div class="json-content">
                                    ${formatKeyValueData(log.old_data)}
                                </div>
                                ${shouldShowViewMore(log.old_data) ? '<span class="view-more" onclick="toggleJsonData(\'old-data-' + log.id + '\')">[view]</span>' : ''}
                            </td>
                            <td class="json-data" id="new-data-${log.id}">
                                <div class="json-content">
                                    ${formatKeyValueData(log.new_data)}
                                </div>
                                ${shouldShowViewMore(log.new_data) ? '<span class="view-more" onclick="toggleJsonData(\'new-data-' + log.id + '\')">[view]</span>' : ''}
                            </td>
                            <td><code>${escapeHtml(log.ip_address)}</code></td>
                            <td class="json-data" id="user-agent-${log.id}">
                                <div class="json-content">
                                    ${formatUserAgent(log.user_agent)}
                                </div>
                                ${shouldShowViewMore(log.user_agent) ? '<span class="view-more" onclick="toggleJsonData(\'user-agent-' + log.id + '\')">[view]</span>' : ''}
                            </td>
                            <td>${formatDateTime(log.created_at)}</td>
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
                            Showing ${startItem} to ${endItem} of ${pagination.total_items} log records
                        </div>
                        ${generatePagination(pagination)}
                    `;
                }
                
                html += `
                        <div style="margin-top: 15px; color: #666; font-size: 12px;">
                            Total: ${pagination ? pagination.total_items : logs.length} log record(s)
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                            <strong>Action Legend:</strong>
                            <span class="action-create" style="margin-left: 10px;">Create</span>
                            <span class="action-update" style="margin-left: 10px;">Update</span>
                            <span class="action-delete" style="margin-left: 10px;">Delete</span>
                            <span class="action-status" style="margin-left: 10px;">Status</span>
                            <span class="action-complete" style="margin-left: 10px;">Complete</span>
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
            }

            function generatePagination(pagination) {
                if (!pagination || pagination.total_pages <= 1) return '';
                
                let html = '<div class="pagination">';
                
                // Previous button
                html += `<button onclick="loadRepairFrontLogs(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>‹ Previous</button>`;
                
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
                    html += `<button onclick="loadRepairFrontLogs(1)">1</button>`;
                    if (startPage > 2) html += `<button disabled>...</button>`;
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    html += `<button onclick="loadRepairFrontLogs(${i})" ${i === pagination.current_page ? 'class="active"' : ''}>${i}</button>`;
                }
                
                // Last page
                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) html += `<button disabled>...</button>`;
                    html += `<button onclick="loadRepairFrontLogs(${pagination.total_pages})">${pagination.total_pages}</button>`;
                }
                
                // Next button
                html += `<button onclick="loadRepairFrontLogs(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next ›</button>`;
                
                html += '</div>';
                return html;
            }

            function formatAction(action) {
                if (!action) return 'N/A';
                
                const actionMap = {
                    'create': 'Create Repair',
                    'update': 'Update Repair',
                    'delete': 'Delete Repair',
                    'status': 'Status Update',
                    'complete': 'Complete Repair',
                    'cancel': 'Cancel Repair',
                    'view': 'View Repair'
                };
                
                return actionMap[action] || action.charAt(0).toUpperCase() + action.slice(1);
            }

            function formatKeyValueData(data) {
                if (!data || data === 'null' || data === 'NULL' || data === '[]' || data === '{}') {
                    return '<div class="key-value-pair"><span class="data-value empty">No data</span></div>';
                }
                
                try {
                    const parsed = JSON.parse(data);
                    
                    // If it's a simple string, not an object
                    if (typeof parsed === 'string') {
                        return `<div class="key-value-pair"><span class="data-value">${escapeHtml(parsed)}</span></div>`;
                    }
                    
                    let html = '';
                    
                    // Common field mappings for better display names
                    const fieldLabels = {
                        'asset_type': 'Asset Type',
                        'brand_model': 'Brand Model',
                        'serial_number': 'Serial Number',
                        'quantity': 'Quantity',
                        'price': 'Price',
                        'status': 'Status',
                        'location': 'Location',
                        'date_purchased': 'Date Purchased',
                        'warranty_expiry': 'Warranty Expiry',
                        'remarks': 'Remarks',
                        'repair_date': 'Repair Date',
                        'return_date': 'Return Date',
                        'repair_remarks': 'Repair Remarks',
                        'repair_status': 'Repair Status',
                        'technician_notes': 'Technician Notes',
                        'estimated_cost': 'Estimated Cost',
                        'actual_cost': 'Actual Cost',
                        'assigned_to': 'Assigned To',
                        'priority': 'Priority'
                    };
                    
                    // Check if it's repair information or asset information
                    const isRepairData = parsed.repair_date || parsed.repair_status || parsed.repair_remarks;
                    const isAssetData = parsed.asset_type || parsed.brand_model || parsed.serial_number;
                    
                    if (isRepairData) {
                        html += '<div class="repair-info">';
                        html += '<div class="key-value-pair"><span class="data-key" style="font-weight: bold; color: #856404;">🔧 Repair Information</span></div>';
                    }
                    
                    if (isAssetData && !isRepairData) {
                        html += '<div class="asset-info">';
                        html += '<div class="key-value-pair"><span class="data-key" style="font-weight: bold; color: #0c5460;">💻 Asset Information</span></div>';
                    }
                    
                    // Format each key-value pair
                    Object.keys(parsed).forEach(key => {
                        const label = fieldLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        const value = parsed[key];
                        const displayValue = value ? escapeHtml(String(value)) : '';
                        const valueClass = displayValue ? '' : 'empty';
                        
                        html += `
                            <div class="key-value-pair">
                                <span class="data-key">${label}:</span>
                                <span class="data-value ${valueClass}">${displayValue || 'N/A'}</span>
                            </div>
                        `;
                    });
                    
                    if (isRepairData) {
                        html += '</div>';
                    }
                    if (isAssetData && !isRepairData) {
                        html += '</div>';
                    }
                    
                    return html || '<div class="key-value-pair"><span class="data-value empty">No data available</span></div>';
                    
                } catch (e) {
                    // If not JSON, return as simple text
                    const displayText = String(data);
                    return `<div class="key-value-pair"><span class="data-value">${escapeHtml(displayText)}</span></div>`;
                }
            }

            function formatUserAgent(userAgent) {
                if (!userAgent || userAgent === 'null' || userAgent === 'NULL') {
                    return '<span class="data-value empty">N/A</span>';
                }
                
                // Extract browser and OS information from user agent
                const ua = String(userAgent);
                let browser = 'Unknown Browser';
                let os = 'Unknown OS';
                
                // Browser detection
                if (ua.includes('Chrome')) browser = 'Chrome';
                else if (ua.includes('Firefox')) browser = 'Firefox';
                else if (ua.includes('Safari')) browser = 'Safari';
                else if (ua.includes('Edge')) browser = 'Edge';
                else if (ua.includes('Opera')) browser = 'Opera';
                
                // OS detection
                if (ua.includes('Windows')) os = 'Windows';
                else if (ua.includes('Mac OS')) os = 'macOS';
                else if (ua.includes('Linux')) os = 'Linux';
                else if (ua.includes('Android')) os = 'Android';
                else if (ua.includes('iOS')) os = 'iOS';
                
                return `
                    <div class="key-value-pair">
                        <span class="data-key">Browser:</span>
                        <span class="data-value">${browser}</span>
                    </div>
                    <div class="key-value-pair">
                        <span class="data-key">OS:</span>
                        <span class="data-value">${os}</span>
                    </div>
                    <div class="key-value-pair">
                        <span class="data-key">Full UA:</span>
                        <span class="data-value">${escapeHtml(ua.substring(0, 50))}${ua.length > 50 ? '...' : ''}</span>
                    </div>
                `;
            }

            function shouldShowViewMore(data) {
                if (!data) return false;
                
                try {
                    const parsed = JSON.parse(data);
                    if (typeof parsed === 'object') {
                        return Object.keys(parsed).length > 4; // Show view more if more than 4 fields
                    }
                } catch (e) {
                    // Not JSON
                }
                
                return String(data).length > 100;
            }

            function toggleJsonData(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.classList.toggle('expanded');
                    const viewSpan = element.querySelector('.view-more');
                    if (viewSpan) {
                        viewSpan.textContent = element.classList.contains('expanded') ? '[hide]' : '[view]';
                    }
                }
            }

            function formatDateTime(dateTimeString) {
                if (!dateTimeString) return 'N/A';
                try {
                    const date = new Date(dateTimeString);
                    return date.toLocaleDateString() + '<br>' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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