<?php
/**
 * Stock Items Management - Admin (With Pagination)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'stock_items_admin_menu');

function stock_items_admin_menu() {
    add_submenu_page(
        'stock-management',
        'Front Items List',
        'Front Items List',
        'manage_options',
        'stock-items-management',
        'stock_items_admin_page'
    );
}

// AJAX handler for getting stock items with pagination
add_action('wp_ajax_get_stock_items_admin', 'ajax_get_stock_items_admin');

function ajax_get_stock_items_admin() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'stock_management_ajax')) {
        wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Stock items table not found');
    }
    
    // Pagination parameters
    $per_page = 10;
    $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    
    // Get items with pagination
    $items = $wpdb->get_results($wpdb->prepare("
        SELECT * 
        FROM $table_name 
        ORDER BY updated_at DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    wp_send_json_success([
        'items' => $items,
        'pagination' => [
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'per_page' => $per_page
        ]
    ]);
}

function stock_items_admin_page() {
    wp_enqueue_script('jquery');
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('stock_management_ajax');
    
    ?>
    <div class="wrap">
        <h1>📦 Stock Items</h1>
        
        <style>
            .stock-table-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .stock-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .stock-table th,
            .stock-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                vertical-align: top;
            }
            .stock-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .stock-table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .stock-table tr:hover {
                background-color: #e9ecef;
            }
            .status-available { 
                color: #28a745; 
                font-weight: bold; 
            }
            .status-out-of-stock { 
                color: #dc3545; 
                font-weight: bold; 
            }
            .status-maintenance { 
                color: #ffc107; 
                font-weight: bold; 
            }
            .status-retired { 
                color: #6c757d; 
                font-weight: bold; 
            }
            .condition-excellent { 
                color: #28a745; 
                font-weight: bold; 
            }
            .condition-good { 
                color: #17a2b8; 
                font-weight: bold; 
            }
            .condition-fair { 
                color: #ffc107; 
                font-weight: bold; 
            }
            .condition-poor { 
                color: #dc3545; 
                font-weight: bold; 
            }
            .no-results {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
            .price {
                font-weight: bold;
                color: #28a745;
            }
            .quantity-high {
                color: #28a745;
                font-weight: bold;
            }
            .quantity-low {
                color: #ffc107;
                font-weight: bold;
            }
            .quantity-zero {
                color: #dc3545;
                font-weight: bold;
            }
            .loading {
                text-align: center;
                padding: 40px;
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
        </style>

        <div class="stock-table-container">
            <h3>📋 All Stock Items</h3>
            <div id="stock-items-table-container">
                <div class="loading">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <p>Loading stock items...</p>
                </div>
            </div>
        </div>

        <script>
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const nonce = '<?php echo $nonce; ?>';
            let currentPage = 1;

            jQuery(document).ready(function($) {
                loadStockItems(currentPage);
            });

            function loadStockItems(page = 1) {
                currentPage = page;
                
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_stock_items_admin',
                        nonce: nonce,
                        page: page
                    },
                    success: function(response) {
                        if (response && response.success) {
                            displayStockItems(response.data.items, response.data.pagination);
                        } else {
                            displayStockItems([], null);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        displayStockItems([], null);
                    }
                });
            }

            function displayStockItems(items, pagination) {
                const container = document.getElementById('stock-items-table-container');
                
                if (!items || items.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                            <h3>No stock items found</h3>
                            <p>The stock items table is empty.</p>
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
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Asset Type</th>
                                    <th>Brand/Model</th>
                                    <th>Serial No</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Purchased</th>
                                    <th>Warranty</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    const quantityClass = getQuantityClass(item.quantity);
                    const statusClass = `status-${item.status}`;
                    const conditionClass = `condition-${item.condition_status}`;
                    
                    html += `
                        <tr>
                            <td>${item.id}</td>
                            <td>${escapeHtml(item.asset_type)}</td>
                            <td>${escapeHtml(item.brand_model)}</td>
                            <td>${escapeHtml(item.serial_number)}</td>
                            <td class="${quantityClass}">${item.quantity}</td>
                            <td class="price">${formatPrice(item.price)}</td>
                            <td class="${statusClass}">${formatStatus(item.status)}</td>
                            <td>${escapeHtml(item.location)}</td>
                            <td>${formatDate(item.date_purchased)}</td>
                            <td>${formatDate(item.warranty_expiry)}</td>
                            <td class="${conditionClass}">${formatCondition(item.condition_status)}</td>
                            <td>${escapeHtml(item.remarks || 'N/A')}</td>
                            <td>${formatDateTime(item.updated_at)}</td>
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
                    html += `<a href="javascript:void(0)" onclick="loadStockItems(${pagination.current_page - 1})">« Previous</a>`;
                }
                
                // Page numbers
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === pagination.current_page) {
                        html += `<span class="current">${i}</span>`;
                    } else {
                        html += `<a href="javascript:void(0)" onclick="loadStockItems(${i})">${i}</a>`;
                    }
                }
                
                // Next button
                if (pagination.current_page < pagination.total_pages) {
                    html += `<a href="javascript:void(0)" onclick="loadStockItems(${pagination.current_page + 1})">Next »</a>`;
                }
                
                html += '</div>';
                return html;
            }

            function getQuantityClass(quantity) {
                if (quantity === 0) return 'quantity-zero';
                if (quantity <= 5) return 'quantity-low';
                return 'quantity-high';
            }

            function formatPrice(price) {
                if (!price) return '₹0.00';
                return '₹' + parseFloat(price).toFixed(2);
            }

            function formatStatus(status) {
                if (!status) return 'N/A';
                return status.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
            }

            function formatCondition(condition) {
                if (!condition) return 'N/A';
                return condition.charAt(0).toUpperCase() + condition.slice(1);
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