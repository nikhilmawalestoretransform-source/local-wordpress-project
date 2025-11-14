<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://storetransform.com
 * @since             1.0.0
 * @package           St_Stock_Management
 *
 * @wordpress-plugin
 * Plugin Name:       ST Stock Management
 * Plugin URI:        https://storetransform.com
 * Description:       Plugin can Manage stock of IT company assets repaire.
 * Version:           1.0.0
 * Author:            storetransform
 * Author URI:        https://storetransform.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       st-stock-management
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ST_STOCK_MANAGEMENT_VERSION', '1.0.0' );

$thumb_img = plugin_dir_url(__FILE__) . 'assets/images/banner-image.webp';

define( 'ST_STOCK_MANAGEMENT_THUMB', $thumb_img );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-st-stock-management-activator.php
 */
function activate_st_stock_management() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-management-activator.php';
	St_Stock_Management_Activator::activate();

    /* CREATE DB TABLES IF NOT EXISTS */
    global $wpdb;

    // Include the required file for dbDelta function
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $stock_table = $wpdb->prefix . 'st_stock_management';
   // $member_table = $wpdb->prefix . 'st_member_management';
    $items_table = $wpdb->prefix . 'st_stock_items_name';

    $items_manage = "CREATE TABLE $items_table ( 
        id INT NOT NULL AUTO_INCREMENT,
        item_type VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($items_manage);

    // SQL to create the stock management table
    $stock_table_sql = "CREATE TABLE IF NOT EXISTS $stock_table (
        id INT NOT NULL AUTO_INCREMENT,
        item_id INT DEFAULT NULL,
        asset_company VARCHAR(255) NOT NULL,
        asset_model VARCHAR(255) NOT NULL,
        asset_price VARCHAR(255) NOT NULL,
        asset_purchase_date DATE NOT NULL,
        total_quantity INT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($stock_table_sql);

  

    $table_name = $wpdb->prefix . 'stock_management';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        asset_type varchar(100) NOT NULL,
        brand_model varchar(100) NOT NULL,
        serial_number varchar(100) NOT NULL,
        quantity int(11) NOT NULL DEFAULT 1,
        price decimal(10,2) NOT NULL DEFAULT 0.00,
        status varchar(50) NOT NULL,
        location varchar(100) NOT NULL,
        date_purchased date NOT NULL,
        warranty_expiry date,
        condition_status varchar(50) NOT NULL,
        remarks text,
        custom_fields longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);


require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//dbDelta($employees_sql);
//dbDelta($assignments_sql);

// 4️⃣ Add foreign keys separately
$wpdb->query(
    "ALTER TABLE $assignments_table
     ADD CONSTRAINT fk_asset_id FOREIGN KEY (asset_id) REFERENCES {$wpdb->prefix}stock_management(id) ON DELETE CASCADE"
);

$wpdb->query(
    "ALTER TABLE $assignments_table
     ADD CONSTRAINT fk_employee_id FOREIGN KEY (employee_id) REFERENCES $employees_table(id) ON DELETE CASCADE"
);


    $table_name = $wpdb->prefix . 'member_queries';
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    //dbDelta($sql1);
    //dbDelta($sql2);

}




/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-st-stock-management-deactivator.php
 */
function deactivate_st_stock_management() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-management-deactivator.php';
	St_Stock_Management_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_st_stock_management' );
register_deactivation_hook( __FILE__, 'deactivate_st_stock_management' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-management.php';

/* CREATE SHORTCODE FOR ADD STOCK ENTRY */
//require plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-management-shortcode.php';

/* CREATE SHORTCODE FOR ADMIN REPORT ABOUT SPENT */
//require plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-spent-shortcode.php';

/* MEMBER MANAGEMENT START */
//require plugin_dir_path( __FILE__ ) . 'includes/class-st-stock-usermanage-shortcode.php';

/* MEMBER MANAGEMENT START */
require plugin_dir_path( __FILE__ ) . 'class-stock_management-shortcode.php';

//require_once plugin_dir_path(__FILE__) . 'includes/class-stock-asset-assignment.php';

//require_once plugin_dir_path(__FILE__) . 'includes/class-stock_query-team-members.php';

$comp_logo = plugins_url('includes/mycompany.png', dirname(__FILE__));
define( 'ST_STOCK_MANAGEMENT_CMPIMG', $comp_logo );
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_st_stock_management() {

	$plugin = new St_Stock_Management();
	$plugin->run();

}


run_st_stock_management();



// Include admin functionality
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin-asset-types.php';
    require_once plugin_dir_path(__FILE__) . 'asset-log-management.php';
    require_once plugin_dir_path(__FILE__) . 'admin-emp-management.php';
    require_once plugin_dir_path(__FILE__) . 'emp-log-management.php';
    require_once plugin_dir_path(__FILE__) . 'admin-stock_items-list.php';
    require_once plugin_dir_path(__FILE__) . 'front-Item-logs.php';
    require_once plugin_dir_path(__FILE__) . 'admin-repaire-list.php';
    require_once plugin_dir_path(__FILE__) . 'front-repaire-logs.php';
    require_once plugin_dir_path(__FILE__) . 'front-emp-asset-assign-log.php';

    
    
}



class StockManagementTableCreator {
    
    private $tables = [];
    
    public function __construct() {
        $this->init_tables();
    }
    
    private function init_tables() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        
        $this->tables = [
            $table_prefix . 'admin_emp_stock_management' => "
                CREATE TABLE `{$table_prefix}admin_emp_stock_management` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `emp_name` varchar(100) NOT NULL,
                    `emp_email` varchar(100) NOT NULL,
                    `emp_position` varchar(100) NOT NULL,
                    `emp_status` varchar(20) DEFAULT 'active',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `emp_email` (`emp_email`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'admin_emp_stock_management_logs' => "
                CREATE TABLE `{$table_prefix}admin_emp_stock_management_logs` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `emp_id` mediumint(9) DEFAULT NULL,
                    `action` varchar(50) NOT NULL,
                    `old_value` text DEFAULT NULL,
                    `new_value` text DEFAULT NULL,
                    `user_id` bigint(20) DEFAULT NULL,
                    `user_ip` varchar(45) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `emp_id` (`emp_id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'admin_item_stock_management' => "
                CREATE TABLE `{$table_prefix}admin_item_stock_management` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `asset_name` varchar(100) NOT NULL,
                    `status` varchar(20) DEFAULT 'active',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `asset_name` (`asset_name`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'admin_item_stock_management_logs' => "
                CREATE TABLE `{$table_prefix}admin_item_stock_management_logs` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `asset_type_id` mediumint(9) DEFAULT NULL,
                    `action` varchar(50) NOT NULL,
                    `old_value` text DEFAULT NULL,
                    `new_value` text DEFAULT NULL,
                    `user_id` bigint(20) DEFAULT NULL,
                    `user_ip` varchar(45) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `asset_type_id` (`asset_type_id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'admin_stock_management_log' => "
                CREATE TABLE `{$table_prefix}admin_stock_management_log` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `action` varchar(50) NOT NULL,
                    `item_id` mediumint(9) NOT NULL,
                    `user_id` mediumint(9) NOT NULL,
                    `user_name` varchar(100) NOT NULL,
                    `old_data` text DEFAULT NULL,
                    `new_data` text DEFAULT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text NOT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `item_id` (`item_id`),
                    KEY `user_id` (`user_id`),
                    KEY `action` (`action`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'asset_assign_front_log' => "
                CREATE TABLE `{$table_prefix}asset_assign_front_log` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `action` varchar(50) NOT NULL,
                    `assign_id` mediumint(9) NOT NULL,
                    `emp_id` mediumint(9) NOT NULL,
                    `user_id` mediumint(9) NOT NULL,
                    `user_name` varchar(100) NOT NULL,
                    `asset_data` text NOT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text NOT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `assign_id` (`assign_id`),
                    KEY `emp_id` (`emp_id`),
                    KEY `user_id` (`user_id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'emp_asset_assign_table' => "
                CREATE TABLE `{$table_prefix}emp_asset_assign_table` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `emp_id` mediumint(9) NOT NULL,
                    `asset_type` varchar(100) NOT NULL,
                    `brand_model` varchar(200) NOT NULL,
                    `assigned_date` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `emp_id` (`emp_id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'emp_stock_management' => "
                CREATE TABLE `{$table_prefix}emp_stock_management` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `emp_name` varchar(100) NOT NULL,
                    `emp_email` varchar(100) NOT NULL,
                    `emp_position` varchar(100) NOT NULL,
                    `emp_status` varchar(20) DEFAULT 'active',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `emp_email` (`emp_email`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'item_front_logs' => "
                CREATE TABLE `{$table_prefix}item_front_logs` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `action` varchar(50) NOT NULL,
                    `item_id` mediumint(9) NOT NULL,
                    `user_id` mediumint(9) NOT NULL,
                    `user_name` varchar(100) NOT NULL,
                    `old_data` text DEFAULT NULL,
                    `new_data` text DEFAULT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text NOT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `item_id` (`item_id`),
                    KEY `user_id` (`user_id`),
                    KEY `action` (`action`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'repaire_front_log' => "
                CREATE TABLE `{$table_prefix}repaire_front_log` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `action` varchar(50) NOT NULL,
                    `repair_id` mediumint(9) NOT NULL,
                    `user_id` mediumint(9) NOT NULL,
                    `user_name` varchar(100) NOT NULL,
                    `old_data` text DEFAULT NULL,
                    `new_data` text DEFAULT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text NOT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `repair_id` (`repair_id`),
                    KEY `user_id` (`user_id`),
                    KEY `action` (`action`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'repaire_stock_management' => "
                CREATE TABLE `{$table_prefix}repaire_stock_management` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `serial_number` varchar(100) NOT NULL,
                    `asset_type` varchar(100) NOT NULL,
                    `brand_model` varchar(200) NOT NULL,
                    `repair_remarks` text NOT NULL,
                    `repair_date` date NOT NULL,
                    `return_date` date DEFAULT NULL,
                    `status` varchar(50) DEFAULT 'Under Repair',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `serial_number` (`serial_number`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'stock_management' => "
                CREATE TABLE `{$table_prefix}stock_management` (
                    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                    `asset_type` varchar(100) NOT NULL,
                    `brand_model` varchar(100) NOT NULL,
                    `serial_number` varchar(100) NOT NULL,
                    `quantity` int(11) NOT NULL DEFAULT 1,
                    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
                    `status` varchar(50) NOT NULL,
                    `location` varchar(100) NOT NULL,
                    `date_purchased` date NOT NULL,
                    `warranty_expiry` date DEFAULT NULL,
                    `condition_status` varchar(50) NOT NULL,
                    `remarks` text DEFAULT NULL,
                    `custom_fields` longtext DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'st_stock_items_name' => "
                CREATE TABLE `{$table_prefix}st_stock_items_name` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `item_type` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`)
                ) $charset_collate;
            ",
            
            $table_prefix . 'st_stock_management' => "
                CREATE TABLE `{$table_prefix}st_stock_management` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `item_id` int(11) DEFAULT NULL,
                    `asset_company` varchar(255) NOT NULL,
                    `asset_model` varchar(255) NOT NULL,
                    `asset_price` varchar(255) NOT NULL,
                    `asset_purchase_date` date NOT NULL,
                    `total_quantity` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) $charset_collate;
            "
        ];
    }
    
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = [];
        foreach ($this->tables as $table_name => $sql) {
            if (!$this->table_exists($table_name)) {
                $result = dbDelta($sql);
                $results[$table_name] = $result;
            } else {
                $results[$table_name] = 'Table already exists';
            }
        }
        
        return $results;
    }
    
    public function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    public function add_table($table_name, $sql) {
        $this->tables[$table_name] = $sql;
    }
    
    public function remove_table($table_name) {
        unset($this->tables[$table_name]);
    }
    
    public function get_tables() {
        return array_keys($this->tables);
    }
    
    public function get_table_count() {
        return count($this->tables);
    }
}

// Usage in your plugin
function initialize_stock_management_tables() {
    $table_creator = new StockManagementTableCreator();
    $results = $table_creator->create_tables();
    
    // Optional: Log results for debugging
    error_log('Stock Management Tables Creation Results: ' . print_r($results, true));
    
    return $results;
}

// Register activation hook
register_activation_hook(__FILE__, 'initialize_stock_management_tables');

// You can also call it directly if needed
function check_and_create_tables_manually() {
    return initialize_stock_management_tables();
}