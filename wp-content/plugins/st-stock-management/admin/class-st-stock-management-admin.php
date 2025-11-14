<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://storetransform.com
 * @since      1.0.0
 *
 * @package    St_Stock_Management
 * @subpackage St_Stock_Management/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    St_Stock_Management
 * @subpackage St_Stock_Management/admin
 * @author     storetransform <hr03webindiainc@gmail.com>
 */
class St_Stock_Management_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Add admin menu
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

    }

    /**
     * Add options page to admin menu
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Stock Management', // Page title
            'Stock Management', // Menu title
            'manage_options', // Capability
            'stock-management', // Menu slug
            array($this, 'display_admin_dashboard_widgets'), // Function to display the page
            'dashicons-clipboard', // Icon (you can change this)
            30 // Position
        );

        // Add submenu pages if needed
        // add_submenu_page(
        //  'stock-management', // Parent slug
        //  'Overview', // Page title
        //  'Overview', // Menu title
        //  'manage_options', // Capability
        //  'stock-management', // Menu slug (same as parent for main page)
        //  array($this, 'display_plugin_admin_page') // Function to display the page
        // );

        // Add Assets submenu
        // add_submenu_page(
        //  'stock-management',
        //  'Manage Assets',
        //  'Manage Assets',
        //  'manage_options',
        //  'stock-management-assets',
        //  array($this, 'display_assets_page')
        // );

        // // Reports
        // add_submenu_page(
        //     'stock-management',
        //     'Reports',
        //     'Reports',
        //     'manage_options',
        //     'stock-management-reports',
        //     array($this, 'display_reports_page') // FIXED ✅
        // );


    }

    /**
     * Reports Page
     */
    public function display_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';

    // Handle filters & search
    $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : '';
    $to_date   = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : '';
    $search    = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $where = 'WHERE 1=1';
    if ($from_date && $to_date) {
        $where .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $from_date . " 00:00:00", $to_date . " 23:59:59");
    }
    if ($search) {
        $where .= $wpdb->prepare(" AND (brand_model LIKE %s OR serial_number LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    }

    // Sorting
    $sortable_columns = ['asset_type','brand_model','serial_number','quantity','price','created_at'];
    $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $sortable_columns) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at';
    $order   = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

    // Pagination
    $items_per_page = 10;
    $current_page   = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset         = ($current_page - 1) * $items_per_page;

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");

    // Fetch results with ORDER & LIMIT
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name 
        $where 
        ORDER BY $orderby $order 
        LIMIT %d OFFSET %d
    ", $items_per_page, $offset));

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=stock_report.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Asset Type','Brand/Model','Serial Number','Quantity','Price','Total Value','Date']);

        foreach ($results as $row) {
            $total_value = $row->quantity * $row->price;
            fputcsv($output, [
                $row->asset_type,
                $row->brand_model,
                $row->serial_number,
                $row->quantity,
                $row->price,
                $total_value,
                $row->created_at
            ]);
        }
        fclose($output);
        exit;
    }

    $total_pages = ceil($total_items / $items_per_page);

    // Generate sortable column headers
    function sortable_header($label, $column, $orderby, $order) {
        $current_url = remove_query_arg(['orderby','order']);
        $order_next = ($orderby === $column && $order === 'ASC') ? 'desc' : 'asc';
        $arrow = '';

        if ($orderby === $column) {
            $arrow = $order === 'ASC' ? ' 🔼' : ' 🔽';
        }

        $url = add_query_arg(['orderby' => $column, 'order' => $order_next], $current_url);
        return '<a href="'.esc_url($url).'">'.$label.$arrow.'</a>';
    }
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">📊 Stock Reports</h1>
        <hr class="wp-header-end">

        <!-- Filters -->
        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="stock-management-reports" />
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <label><strong>From:</strong>
                    <input type="date" name="from_date" value="<?php echo esc_attr($from_date); ?>" />
                </label>
                <label><strong>To:</strong>
                    <input type="date" name="to_date" value="<?php echo esc_attr($to_date); ?>" />
                </label>
                <label>
                    <input type="search" name="s" placeholder="Search Brand/Serial..." value="<?php echo esc_attr($search); ?>" style="min-width:220px;" />
                </label>
                <button type="submit" class="button button-primary">Filter</button>
                <a href="?page=stock-management-reports" class="button">Reset</a>
                <a href="?page=stock-management-reports&from_date=<?php echo esc_attr($from_date); ?>&to_date=<?php echo esc_attr($to_date); ?>&s=<?php echo esc_attr($search); ?>&export=csv" class="button button-secondary">⬇ Export CSV</a>
            </div>
        </form>

        <?php if (empty($results)) : ?>
            <div class="notice notice-warning inline"><p>No records found!</p></div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo sortable_header('Asset Type','asset_type',$orderby,$order); ?></th>
                        <th><?php echo sortable_header('Brand/Model','brand_model',$orderby,$order); ?></th>
                        <th><?php echo sortable_header('Serial Number','serial_number',$orderby,$order); ?></th>
                        <th><?php echo sortable_header('Quantity','quantity',$orderby,$order); ?></th>
                        <th><?php echo sortable_header('Price','price',$orderby,$order); ?></th>
                        <th>Total Value (Qty × Price)</th>
                        <th><?php echo sortable_header('Date','created_at',$orderby,$order); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_total = 0;
                    foreach ($results as $row) :
                        $total_value = $row->quantity * $row->price;
                        $grand_total += $total_value;
                        ?>
                        <tr>
                            <td><?php echo esc_html($row->asset_type); ?></td>
                            <td><?php echo esc_html($row->brand_model); ?></td>
                            <td><?php echo esc_html($row->serial_number); ?></td>
                            <td><?php echo intval($row->quantity); ?></td>
                            <td><?php echo number_format((float)$row->price, 2); ?></td>
                            <td><strong><?php echo number_format((float)$total_value, 2); ?></strong></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:#f1f1f1; font-weight:bold;">
                        <td colspan="5" style="text-align:right;">Grand Total Value:</td>
                        <td><?php echo number_format((float)$grand_total, 2); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $base_url = remove_query_arg('paged');
                    if ($from_date) $base_url = add_query_arg('from_date', $from_date, $base_url);
                    if ($to_date) $base_url = add_query_arg('to_date', $to_date, $base_url);
                    if ($search)   $base_url = add_query_arg('s', $search, $base_url);
                    if ($orderby)  $base_url = add_query_arg('orderby', $orderby, $base_url);
                    if ($order)    $base_url = add_query_arg('order', $order, $base_url);

                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%', $base_url),
                        'format'    => '',
                        'prev_text' => '« Prev',
                        'next_text' => 'Next »',
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}


public function display_admin_dashboard_widgets() {
    global $wpdb;
    $stock_table = $wpdb->prefix . 'stock_management';
    $emp_table = $wpdb->prefix . 'admin_emp_stock_management';
    $asset_count_get = $wpdb->prefix . 'admin_item_stock_management';
    
    // Get asset count
    $asset_count = $wpdb->get_var("SELECT COUNT(*) FROM $asset_count_get");
    
    // Get grand total value
    $grand_total = $wpdb->get_var("SELECT SUM(quantity * price) FROM $stock_table");
    
    // Get employee count from admin_emp_stock_management table
    $employee_count = $wpdb->get_var("SELECT COUNT(*) FROM $emp_table");

    // Get additional stats for dashboard
    $total_employees = $employee_count;
    $total_assets = $asset_count;
    $total_inventory_value = $grand_total;

    ?>
    <div class="wrap">
        <!-- Animated Header -->
        <div class="dashboard-header" style="text-align: center; margin-bottom: 40px; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; color: white; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            
            <h1 style="font-size: 2.5rem; margin: 0 0 10px 0; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">🚀 Stock Management Dashboard</h1>
            <p style="font-size: 1.2rem; margin: 0; opacity: 0.9; font-weight: 300;">Welcome to Stock Management System</p>
        </div>

        <!-- Animated Stats Cards -->
        <div class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin: 40px 0;">
            <!-- Employee Count Card -->
            <div class="stat-card animated-card" data-count="<?php echo intval($total_employees); ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3); border: none; position: relative; overflow: hidden; transform: translateY(50px); opacity: 0;">
                <div class="card-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.1); z-index: 1;"></div>
                <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Employees</h3>
                        <p class="count-number" style="font-size: 3rem; margin: 0; font-weight: 800; color: white; line-height: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">0</p>
                        <small style="color: rgba(255,255,255,0.8); font-size: 13px;">👥 Registered in system</small>
                    </div>
                    <div class="card-icon" style="font-size: 4rem; color: rgba(255,255,255,0.3); transition: all 0.3s ease;">👥</div>
                </div>
                <div class="card-wave" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, rgba(255,255,255,0.8), rgba(255,255,255,0.4)); transform: scaleX(0); transform-origin: left;"></div>
            </div>

            <!-- Asset Count Card -->
            <div class="stat-card animated-card" data-count="<?php echo intval($total_assets); ?>" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; border-radius: 15px; box-shadow: 0 15px 35px rgba(245, 87, 108, 0.3); border: none; position: relative; overflow: hidden; transform: translateY(50px); opacity: 0;">
                <div class="card-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.1); z-index: 1;"></div>
                <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Asset Types</h3>
                        <p class="count-number" style="font-size: 3rem; margin: 0; font-weight: 800; color: white; line-height: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">0</p>
                        <small style="color: rgba(255,255,255,0.8); font-size: 13px;">💼 Items in inventory</small>
                    </div>
                    <div class="card-icon" style="font-size: 4rem; color: rgba(255,255,255,0.3); transition: all 0.3s ease;">💼</div>
                </div>
                <div class="card-wave" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, rgba(255,255,255,0.8), rgba(255,255,255,0.4)); transform: scaleX(0); transform-origin: left;"></div>
            </div>

            <!-- Grand Total Card -->
            <div class="stat-card animated-card" data-count="<?php echo intval($total_inventory_value); ?>" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 30px; border-radius: 15px; box-shadow: 0 15px 35px rgba(79, 172, 254, 0.3); border: none; position: relative; overflow: hidden; transform: translateY(50px); opacity: 0;">
                <div class="card-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.1); z-index: 1;"></div>
                <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Inventory Value</h3>
                        <p class="count-number" style="font-size: 3rem; margin: 0; font-weight: 800; color: white; line-height: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">₹0</p>
                        <small style="color: rgba(255,255,255,0.8); font-size: 13px;">💰 Current stock value</small>
                    </div>
                    <div class="card-icon" style="font-size: 4rem; color: rgba(255,255,255,0.3); transition: all 0.3s ease;">💰</div>
                </div>
                <div class="card-wave" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, rgba(255,255,255,0.8), rgba(255,255,255,0.4)); transform: scaleX(0); transform-origin: left;"></div>
            </div>
        </div>
    </div>

    <style>
    .stat-card {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
    }
    
    .stat-card:hover {
        transform: translateY(-10px) scale(1.05) !important;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25) !important;
        z-index: 10;
    }
    
    .stat-card:hover .card-icon {
        transform: scale(1.2);
        opacity: 0.4 !important;
    }
    
    .stat-card:hover .card-wave {
        transform: scaleX(1);
        transition: transform 0.6s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animated-card {
        animation: fadeInUp 0.8s ease forwards;
    }
    
    .animated-card:nth-child(1) {
        animation-delay: 0.1s;
    }
    
    .animated-card:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .animated-card:nth-child(3) {
        animation-delay: 0.3s;
    }
    
    .dashboard-header {
        animation: fadeInUp 0.8s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .stat-card:active {
        animation: pulse 0.3s ease;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Animate counter numbers with easing
        $('.stat-card').each(function(index) {
            var $this = $(this);
            var target = $this.data('count');
            var $number = $this.find('.count-number');
            var current = 0;
            var duration = 2000;
            var startTime = null;
            
            function animateCounter(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = timestamp - startTime;
                var percentage = Math.min(progress / duration, 1);
                
                // Easing function for smooth animation
                var easeOutQuart = 1 - Math.pow(1 - percentage, 4);
                current = Math.floor(target * easeOutQuart);
                
                if ($number.text().includes('₹')) {
                    // Format Indian currency with commas
                    $number.text('₹' + current.toLocaleString('en-IN'));
                } else {
                    $number.text(current.toLocaleString('en-IN'));
                }
                
                if (percentage < 1) {
                    requestAnimationFrame(animateCounter);
                } else {
                    if ($number.text().includes('₹')) {
                        $number.text('₹' + target.toLocaleString('en-IN'));
                    } else {
                        $number.text(target.toLocaleString('en-IN'));
                    }
                }
            }
            
            // Start animation with delay based on card index
            setTimeout(function() {
                requestAnimationFrame(animateCounter);
            }, 300 + (index * 100));
        });

        // Enhanced hover effects
        $('.stat-card').on('mouseenter', function() {
            $(this).css({
                'transform': 'translateY(-10px) scale(1.05)',
                'transition': 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                'z-index': '10'
            });
        }).on('mouseleave', function() {
            $(this).css({
                'transform': 'translateY(0) scale(1)',
                'transition': 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                'z-index': '1'
            });
        });

        // Click animation
        $('.stat-card').on('click', function() {
            var $card = $(this);
            $card.css('transform', 'scale(0.95)');
            setTimeout(function() {
                $card.css('transform', 'scale(1)');
            }, 150);
        });
    });
    </script>
    <?php
}

public function display_plugin_admin_page_welcome() {
?>

    <div class="card">
                        <h3>Shortcodes</h3>
                        
                        <p class="number">[assets_management]</p>
                        <p class="number">[spent_stock_report]</p>                      
                    </div> <?
}

    /**
     * Display the admin page
     */
    public function display_plugin_admin_page1() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <img style="display: none" src="<?php echo ST_STOCK_MANAGEMENT_THUMB; ?>" alt="Plugin Banner" style="max-width: 100%; height: auto; margin-top: 20px;">

            
            <div class="stock-management-dashboard">
                <div class="overview-cards">

                <div class="card">
                        <h3>Shortcodes</h3>
                        
                        <p class="number">[assets_management]</p>
                        <p class="number">[spent_stock_report]</p>                      
                    </div>


                    <div class="card">
                        <h3>Total Assets</h3>
                        <?php 
                        global $wpdb;
                        $total_assets = $wpdb->get_var("SELECT COUNT(*) FROM wp_st_stock_management");
                        ?>
                        <p class="number"><?php echo $total_assets; ?></p>
                    </div>
                    
                    <div class="card">
                        <h3>Assigned Assets</h3>
                        <?php 
                        $assigned_assets = $wpdb->get_var("SELECT COUNT(*) FROM wp_st_stock_management WHERE assigned_quantity > 0");
                        ?>
                        <p class="number"><?php echo $assigned_assets; ?></p>
                    </div>
                    
                    <div class="card">
                        <h3>Available Assets</h3>
                        <?php 
                        $available_assets = $wpdb->get_var("SELECT COUNT(*) FROM wp_st_stock_management WHERE remaining_quantity > 0");
                        ?>
                        <p class="number"><?php echo $available_assets; ?></p>
                    </div>
                </div>

                <div class="recent-activities">
                    <h2>Recent Asset Activities</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Model</th>
                                <th>Total Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php


    global $wpdb;
$stock_table = $wpdb->prefix . 'st_stock_management';
$item_table  = $wpdb->prefix . 'st_stock_items_name';
    
    // Join to get item_type instead of manual asset_name
$recent_assets = $wpdb->get_results("
    SELECT 
        items.item_type AS asset_name, 
        stock.asset_model, 
        stock.total_quantity
    FROM $stock_table AS stock
    LEFT JOIN $item_table AS items ON stock.item_id = items.id
");

                            foreach ($recent_assets as $asset) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($asset->asset_name); ?></td>
                                    <td><?php echo esc_html($asset->asset_model); ?></td>
                                    <td><?php echo esc_html($asset->total_quantity); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .stock-management-dashboard {
                margin-top: 20px;
            }
            .overview-cards {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
            }
            .card {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                flex: 1;
            }
            .card h3 {
                margin: 0 0 10px 0;
                color: #23282d;
            }
            .card .number {
                font-size: 24px;
                font-weight: bold;
                margin: 0;
                color: #2271b1;
            }
            .recent-activities {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .recent-activities h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }

    /**
     * Display the assets management page
     */
    public function display_assets_page() {
        global $wpdb;
    $table_name = $wpdb->prefix . 'st_stock_items_name';

    // Handle form submission (Add or Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asset_name'])) {
        $item_type = sanitize_text_field($_POST['asset_name']);
        $edit_id   = isset($_GET['edit_item']) ? intval($_GET['edit_item']) : 0;

        if (!empty($item_type)) {
            if ($edit_id > 0) {
                // Update existing record
                $wpdb->update($table_name, ['item_type' => $item_type], ['id' => $edit_id]);
            } else {
                // Insert new record
                $wpdb->insert($table_name, ['item_type' => $item_type]);
            }

            echo '<div class="updated"><p>Asset saved successfully!</p></div>';
        }
    }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Add New Asset Form -->
            <div class="asset-form-container">
                <h2>Add New Asset</h2>
                <form id="add-asset-form" method="post">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="asset_name">Asset Name</label></th>
                            <td><input type="text" id="asset_name" name="asset_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="asset_model">Model</label></th>
                            <td><input type="text" id="asset_model" name="asset_model" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="total_quantity">Total Quantity</label></th>
                            <td><input type="number" id="total_quantity" name="total_quantity" min="1" required></td>
                        </tr>
                    </table>
                    <?php submit_button('Add Asset'); ?>
                </form>
            </div>

            <!-- Assets List Table -->
            <div class="asset-list-container">
                <h2>Current Assets</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Model</th>
                            <th>Total Quantity</th>
                            <th>Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        $assets = $wpdb->get_results("SELECT * FROM wp_st_stock_management ORDER BY id DESC");
                        foreach ($assets as $asset) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($asset->asset_company); ?></td>
                                <td><?php echo esc_html($asset->asset_model); ?></td>
                                <td><?php echo esc_html($asset->total_quantity); ?></td>
                                <td><?php echo esc_html($asset->assigned_quantity); ?></td>
                                <td>
                                    <button class="button button-small edit-asset" data-id="<?php echo $asset->id; ?>">Edit</button>
                                    <button class="button button-small button-link-delete delete-asset" data-id="<?php echo $asset->id; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .asset-form-container {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .asset-list-container {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .button-link-delete {
                color: #dc3232;
            }
        </style>
        <?php
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in St_Stock_Management_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The St_Stock_Management_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/st-stock-management-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in St_Stock_Management_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The St_Stock_Management_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/st-stock-management-admin.js', array( 'jquery' ), $this->version, false );

    }

}