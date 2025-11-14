<?php 

/**
 * Stock Management Chart Plugin
 */
class Stock_Spent
{
    private static $instance_count = 0;
    
    function __construct()
    {        
        add_shortcode( 'spent_stock_report', [$this, 'spent_stock_report'] );
        
        // Enqueue scripts in footer
        add_action('wp_footer', [$this, 'enqueue_scripts_charts']);
        
        // Add admin styles
        add_action('wp_head', [$this, 'add_admin_styles']);
    }

    /**
     * Add styles in head
     */
    public function add_admin_styles() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'spent_stock_report')) {
            echo $this->get_css_styles();
        }
    }

    /**
     * Enqueue Chart.js script
     */
    public function enqueue_scripts_charts() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'spent_stock_report')) {
            ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
            <script>
            // Ensure Chart is available globally
            if (typeof window.Chart === 'undefined' && typeof Chart !== 'undefined') {
                window.Chart = Chart;
            }
            </script>
            <?php
        }
    }

    /**
     * CSS Styles
     */
    private function get_css_styles() {
        return '
        <style type="text/css">
        .stock-management-container {
            max-width: 1200px !important;
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .stock-management-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4CAF50;
        }

        .stock-management-header h2 {
            font-size: 28px;
            color: #333;
            margin: 0;
            font-weight: 600;
        }

        .stock-management-content {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            align-items: flex-start;
        }

        .chart-container {
            flex: 1;
            min-width: 350px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }

        .chart-canvas {
            max-width: 100%;
            height: auto;
        }

        .super-total {
            font-size: 20px;
            font-weight: bold;
            color: #4CAF50;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }

        .table-container {
            flex: 1;
            min-width: 350px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: -10px;
            padding: 10px;
        }

        #stock-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        #stock-table thead th {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        #stock-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        #stock-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        #stock-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        #stock-table tfoot th {
            background: #f8f9fa;
            padding: 15px 10px;
            font-weight: bold;
            border-top: 2px solid #4CAF50;
            font-size: 15px;
        }

        #stock-table tfoot th:last-child {
            color: #4CAF50;
            font-size: 16px;
        }

        .loading-message, .error-message {
            text-align: center;
            padding: 20px;
            margin: 10px 0;
            border-radius: 6px;
        }

        .loading-message {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        @media (max-width: 768px) {
            .stock-management-container {
                margin: 10px;
                padding: 15px;
            }
            
            .stock-management-content {
                flex-direction: column;
                gap: 20px;
            }

            .chart-container,
            .table-container {
                min-width: auto;
            }

            .chart-wrapper {
                height: 300px;
            }

            #stock-table {
                font-size: 12px;
            }

            #stock-table thead th,
            #stock-table tbody td,
            #stock-table tfoot th {
                padding: 8px 6px;
            }
        }
        </style>';
    }

    /**
     * Format number to Indian Rupee format
     */
    public function formatToInr($number)
    {
        $number = (float)$number;
        $explrestunits = "";
        $num = explode('.', $number);
        $integerPart = $num[0];
        $decimalPart = isset($num[1]) ? $num[1] : '00';

        if (strlen($integerPart) > 3) {
            $lastthree = substr($integerPart, -3);
            $restunits = substr($integerPart, 0, -3);
            $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
            $expunit = str_split($restunits, 2);
            for ($i = 0; $i < sizeof($expunit); $i++) {
                if ($i == 0) {
                    $explrestunits .= (int)$expunit[$i] . ",";
                } else {
                    $explrestunits .= $expunit[$i] . ",";
                }
            }
            $thecash = $explrestunits . $lastthree;
        } else {
            $thecash = $integerPart;
        }

        // Fix decimal part
        if (strlen($decimalPart) == 1) {
            $decimalPart .= '0';
        } elseif (strlen($decimalPart) > 2) {
            $decimalPart = substr($decimalPart, 0, 2);
        }

        return '₹ ' . $thecash . '.' . $decimalPart;
    }

    /**
     * Main shortcode function
     */
    function spent_stock_report(){

        // Check if the user is logged in
        if (!is_user_logged_in()) {
            return '<div class="error-message">You must be logged in to view this page.</div>';
        }

        // Check if the user has admin capabilities
        if (!current_user_can('administrator')) {
            return '<div class="error-message">You do not have the necessary rights to view this page.</div>';
        }
    
        global $wpdb;
        $stock_table = $wpdb->prefix . 'st_stock_management';
        $item_table  = $wpdb->prefix . 'st_stock_items_name';
        
        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$stock_table'") != $stock_table) {
            return '<div class="error-message">Stock management table not found.</div>';
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$item_table'") != $item_table) {
            return '<div class="error-message">Stock items table not found.</div>';
        }
        
        // Get data with error handling
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                COALESCE(items.item_type, 'Unknown Item') AS asset_name, 
                COALESCE(stock.asset_price, 0) AS asset_price, 
                COALESCE(stock.total_quantity, 0) AS total_quantity
            FROM %i AS stock
            LEFT JOIN %i AS items ON stock.item_id = items.id
            WHERE stock.asset_price > 0 AND stock.total_quantity > 0
        ", $stock_table, $item_table), ARRAY_A);

        if (empty($results)) {
            return '<div class="error-message">No data available to display the chart. Please check if you have stock items with valid prices and quantities.</div>';
        }

        // Prepare data for Chart.js
        $asset_names = [];
        $asset_prices = [];
        $hover_tooltips = [];
        $super_total = 0;

        foreach ($results as $row) {
            $asset_name = !empty($row['asset_name']) ? esc_js($row['asset_name']) : 'Unknown Item';
            $asset_price = (float)$row['asset_price'];
            $total_quantity = (int)$row['total_quantity'];

            if ($asset_price <= 0 || $total_quantity <= 0) continue;

            $total_value = $total_quantity * $asset_price;
            $super_total += $total_value;

            $asset_names[] = $asset_name;
            $asset_prices[] = $total_value;
            $hover_tooltips[] = sprintf("Price: ₹%.2f | Qty: %d | Total: ₹%.2f", $asset_price, $total_quantity, $total_value);
        }

        if (empty($asset_names)) {
            return '<div class="error-message">No valid data found for chart display.</div>';
        }

        // Generate unique ID for this instance
        self::$instance_count++;
        $chart_id = 'stockChart_' . self::$instance_count;

        ob_start();
        ?>

        <div class="stock-management-container">
            <div class="stock-management-header">
                <h2>📊 Stock Management Overview</h2>
            </div>
            <div class="stock-management-content">

                <div class="chart-container">
                    <div class="chart-wrapper">
                        <canvas id="<?php echo $chart_id; ?>" class="chart-canvas"></canvas>
                    </div>
                    
                    <div class="loading-message" id="loading_<?php echo $chart_id; ?>">
                        Loading chart... Please wait.
                    </div>
                    
                    <div class="super-total">
                        💰 Super Total: <?php echo $this->formatToInr($super_total); ?>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-wrapper">
                        <table id="stock-table">
                            <thead>
                                <tr>
                                    <th>📦 Asset Name</th>
                                    <th>💵 Price</th>
                                    <th>📊 Total Quantity</th>
                                    <th>💰 Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): 
                                    $total_value = (float)$row['asset_price'] * (int)$row['total_quantity'];
                                    if ($total_value <= 0) continue;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($row['asset_name']); ?></td>
                                        <td><?php echo $this->formatToInr($row['asset_price']); ?></td>
                                        <td><?php echo number_format($row['total_quantity']); ?></td>
                                        <td><?php echo $this->formatToInr($total_value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">🎯 Super Total:</th>
                                    <th><?php echo $this->formatToInr($super_total); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        (function() {
            'use strict';
            
            const chartId = '<?php echo $chart_id; ?>';
            const loadingElement = document.getElementById('loading_' + chartId);
            
            function hideLoading() {
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
            }
            
            function showError(message) {
                hideLoading();
                const canvas = document.getElementById(chartId);
                if (canvas && canvas.parentNode) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '❌ ' + message;
                    canvas.parentNode.insertBefore(errorDiv, canvas);
                    canvas.style.display = 'none';
                }
            }
            
            function initChart() {
                try {
                    // Check if Chart.js is loaded
                    if (typeof Chart === 'undefined') {
                        console.log('Chart.js not loaded yet, retrying...');
                        setTimeout(initChart, 500);
                        return;
                    }
                    
                    const canvas = document.getElementById(chartId);
                    if (!canvas) {
                        showError('Canvas element not found');
                        return;
                    }
                    
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        showError('Could not get canvas context');
                        return;
                    }

                    // Chart data
                    const chartData = {
                        labels: <?php echo json_encode($asset_names); ?>,
                        datasets: [{
                            label: 'Stock Value Distribution',
                            data: <?php echo json_encode($asset_prices); ?>,
                            backgroundColor: [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384', 
                                '#36A2EB', '#FFCE56'
                            ].slice(0, <?php echo count($asset_names); ?>),
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverBorderWidth: 4,
                            hoverOffset: 10
                        }]
                    };

                    const tooltipData = <?php echo json_encode($hover_tooltips); ?>;

                    // Chart configuration
                    const config = {
                        type: 'pie',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Stock Value Distribution',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    padding: {
                                        top: 10,
                                        bottom: 30
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = tooltipData[context.dataIndex] || '';
                                            return label;
                                        }
                                    },
                                    backgroundColor: 'rgba(0,0,0,0.8)',
                                    titleColor: 'white',
                                    bodyColor: 'white',
                                    borderColor: 'rgba(255,255,255,0.3)',
                                    borderWidth: 1,
                                    cornerRadius: 6,
                                    displayColors: true
                                },
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            },
                            animation: {
                                animateRotate: true,
                                animateScale: true,
                                duration: 1500,
                                easing: 'easeInOutQuart'
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            }
                        }
                    };

                    // Create chart
                    const chart = new Chart(ctx, config);
                    
                    hideLoading();
                    console.log('Chart created successfully for:', chartId);
                    
                } catch (error) {
                    console.error('Error creating chart:', error);
                    showError('Failed to create chart: ' + error.message);
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(initChart, 100);
                });
            } else {
                setTimeout(initChart, 100);
            }
        })();
        </script>

        <?php
        return ob_get_clean();
    }
}

// Initialize the class
new Stock_Spent();