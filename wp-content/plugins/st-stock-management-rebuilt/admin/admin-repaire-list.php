<?php
/**
 * Repair Management - Admin (Simple Data Table View with Pagination)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'repair_management_admin_menu');

function repair_management_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Front Repair List',
        'Front Repair List',
        'manage_options',
        'repair-management',
        'repair_management_admin_page'
    );
}

// AJAX handler for getting repair items
add_action('wp_ajax_get_repair_items_admin', 'ajax_get_repair_items_admin');

function ajax_get_repair_items_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'repaire_stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Repair management table not found');
    }
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 10; // Number of items per page
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    // Get paginated repair records ordered by latest first
    $items = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        ORDER BY created_at DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    wp_send_json_success([
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ]
    ]);
}

function repair_management_admin_page() {
    wp_enqueue_script('jquery');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ?>
    <div class="wrap">
        <h1>🔧 Repair Management</h1>
        
        <style>
            .repair-table-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .repair-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .repair-table th,
            .repair-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                vertical-align: top;
            }
            .repair-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .repair-table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .repair-table tr:hover {
                background-color: #e9ecef;
            }
            .status-under-repair { 
                background-color: #fff3cd;
                color: #856404;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .status-completed { 
                background-color: #d4edda;
                color: #155724;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .status-waiting-for-parts { 
                background-color: #ffeaa7;
                color: #856404;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .status-cancelled { 
                background-color: #f8d7da;
                color: #721c24;
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 4px;
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
            .overdue {
                background-color: #f8d7da;
                color: #721c24;
                font-weight: bold;
            }
            .due-soon {
                background-color: #fff3cd;
                color: #856404;
                font-weight: bold;
            }
            .on-time {
                background-color: #d4edda;
                color: #155724;
            }
            .remarks-cell {
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .remarks-cell:hover {
                overflow: visible;
                white-space: normal;
                background: white;
                z-index: 1000;
                position: relative;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        </style>

        <div class="repair-table-container">
            <h3>📋 All Repair Records</h3>
            <div id="repair-items-table-container">
                <div class="loading">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <p>Loading repair records...</p>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                loadRepairItems(currentPage);
            });

            function loadRepairItems(page) {
                currentPage = page;
                const container = document.getElementById('repair-items-table-container');
                container.innerHTML = `
                    <div class="loading">
                        <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                        <p>Loading repair records...</p>
                    </div>
                `;

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_repair_items_admin',
                        nonce: nonce,
                        page: page
                    },
                    success: function(response) {
                        if (response && response.success) {
                            displayRepairItems(response.data.items, response.data.pagination);
                        } else {
                            displayRepairItems([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        displayRepairItems([], null);
                    }
                });
            }

            function displayRepairItems(items, pagination) {
                const container = document.getElementById('repair-items-table-container');
                
                if (!items || items.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <div style="font-size: 48px; margin-bottom: 20px;">🔧</div>
                            <h3>No repair records found</h3>
                            <p>No repair records have been created yet.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = `
                    <div style="overflow-x: auto;">
                        <table class="repair-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Serial Number</th>
                                    <th>Asset Type</th>
                                    <th>Brand/Model</th>
                                    <th>Repair Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Repair Remarks</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    const statusClass = `status-${item.status.toLowerCase().replace(/\s+/g, '-')}`;
                    const returnDateClass = getReturnDateClass(item.return_date, item.status);
                    
                    html += `
                        <tr>
                            <td>${item.id}</td>
                            <td><strong>${escapeHtml(item.serial_number)}</strong></td>
                            <td>${escapeHtml(item.asset_type)}</td>
                            <td>${escapeHtml(item.brand_model)}</td>
                            <td>${formatDate(item.repair_date)}</td>
                            <td class="${returnDateClass}">${formatDate(item.return_date)}</td>
                            <td><span class="${statusClass}">${formatStatus(item.status)}</span></td>
                            <td class="remarks-cell" title="${escapeHtml(item.repair_remarks)}">${escapeHtml(item.repair_remarks || 'N/A')}</td>
                            <td>${formatDateTime(item.created_at)}</td>
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
                            Showing ${startItem} to ${endItem} of ${pagination.total_items} records
                        </div>
                        ${generatePagination(pagination)}
                    `;
                }
                
                html += `
                        <div style="margin-top: 15px; color: #666; font-size: 12px;">
                            Total: ${pagination ? pagination.total_items : items.length} repair record(s)
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                            <strong>Status Legend:</strong>
                            <span class="status-under-repair" style="margin-left: 10px;">Under Repair</span>
                            <span class="status-completed" style="margin-left: 10px;">Completed</span>
                            <span class="status-waiting-for-parts" style="margin-left: 10px;">Waiting for Parts</span>
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
            }

            function generatePagination(pagination) {
                if (!pagination || pagination.total_pages <= 1) return '';
                
                let html = '<div class="pagination">';
                
                // Previous button
                html += `<button onclick="loadRepairItems(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>‹ Previous</button>`;
                
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
                    html += `<button onclick="loadRepairItems(1)">1</button>`;
                    if (startPage > 2) html += `<button disabled>...</button>`;
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    html += `<button onclick="loadRepairItems(${i})" ${i === pagination.current_page ? 'class="active"' : ''}>${i}</button>`;
                }
                
                // Last page
                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) html += `<button disabled>...</button>`;
                    html += `<button onclick="loadRepairItems(${pagination.total_pages})">${pagination.total_pages}</button>`;
                }
                
                // Next button
                html += `<button onclick="loadRepairItems(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next ›</button>`;
                
                html += '</div>';
                return html;
            }

            function getReturnDateClass(returnDate, status) {
                if (status === 'Completed') return 'on-time';
                if (!returnDate || returnDate === '0000-00-00') return '';
                
                const today = new Date();
                const returnDateObj = new Date(returnDate + 'T00:00:00');
                const timeDiff = returnDateObj.getTime() - today.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (daysDiff < 0) return 'overdue';
                if (daysDiff <= 3) return 'due-soon';
                return 'on-time';
            }

            function formatStatus(status) {
                if (!status) return 'N/A';
                return status.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
            }

            function formatDate(dateString) {
                if (!dateString || dateString === '0000-00-00') return 'N/A';
                try {
                    const date = new Date(dateString + 'T00:00:00');
                    return date.toLocaleDateString();
                } catch (e) {
                    return dateString;
                }
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