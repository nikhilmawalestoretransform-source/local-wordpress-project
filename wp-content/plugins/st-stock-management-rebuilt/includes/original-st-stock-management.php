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
 * Plugin Name:       Stock Management
 * Plugin URI:        https://storetransform.com
 * Description:       Plugin can Manage stock of IT company assets
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

    // SQL to create the member management table
    // $member_table_sql = "CREATE TABLE IF NOT EXISTS $member_table (
    //     id INT NOT NULL AUTO_INCREMENT,
    //     member_name VARCHAR(255) NOT NULL,
    //     asset_company VARCHAR(255) NOT NULL,
    //     asset_name VARCHAR(255) NOT NULL,
    //     asset_model VARCHAR(255) NOT NULL,
    //     asset_price VARCHAR(255) NOT NULL,
    //     asset_assigned_date DATE NOT NULL,
    //     PRIMARY KEY (id)
    // ) $charset_collate;";


    // Execute the SQL queries
    
   // dbDelta($member_table_sql);

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

//     $employees_table = $wpdb->prefix . 'stock_employees';
// $employees_sql = "CREATE TABLE IF NOT EXISTS $employees_table (
//     id int(11) NOT NULL AUTO_INCREMENT,
//     employee_name varchar(255) NOT NULL,
//     employee_id varchar(100) NOT NULL UNIQUE,
//     department varchar(255) DEFAULT NULL,
//     designation varchar(255) DEFAULT NULL,
//     email varchar(255) DEFAULT NULL,
//     phone varchar(20) DEFAULT NULL,
//     status enum('Active','Inactive') DEFAULT 'Active',
//     created_at timestamp DEFAULT CURRENT_TIMESTAMP,
//     updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     PRIMARY KEY (id)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// 2️⃣ Asset assignments table WITHOUT foreign keys (dbDelta doesn't handle them well)
// $assignments_table = $wpdb->prefix . 'asset_assignments';
// $assignments_sql = "CREATE TABLE IF NOT EXISTS $assignments_table (
//     id int(11) NOT NULL AUTO_INCREMENT,
//     asset_id int(11) NOT NULL,
//     employee_id int(11) NOT NULL,
//     assigned_by varchar(255) DEFAULT NULL,
//     assigned_date date NOT NULL,
//     expected_return_date date DEFAULT NULL,
//     actual_return_date date DEFAULT NULL,
//     assignment_status enum('Assigned','Returned','Lost','Damaged') DEFAULT 'Assigned',
//     condition_at_assignment enum('Excellent','Good','Fair','Poor') DEFAULT 'Good',
//     condition_at_return enum('Excellent','Good','Fair','Poor') DEFAULT NULL,
//     assignment_notes text DEFAULT NULL,
//     return_notes text DEFAULT NULL,
//     created_at timestamp DEFAULT CURRENT_TIMESTAMP,
//     updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     PRIMARY KEY (id)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// 3️⃣ Run dbDelta
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
    
    // Member queries table
    // $sql1 = "CREATE TABLE $table_name (
    //     id int(11) NOT NULL AUTO_INCREMENT,
    //     user_id int(11) NOT NULL,
    //     employee_id int(11) NOT NULL,
    //     query text NOT NULL,
    //     priority enum('low','medium','high','urgent') DEFAULT 'medium',
    //     category varchar(100) DEFAULT 'general',
    //     status enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
    //     admin_response text DEFAULT NULL,
    //     resolved_by int(11) DEFAULT NULL,
    //     created_at datetime DEFAULT CURRENT_TIMESTAMP,
    //     updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    //     PRIMARY KEY (id)
    // ) $charset_collate;";
    
    // Admin notifications table
    // $sql2 = "CREATE TABLE $notifications_table (
    //     id int(11) NOT NULL AUTO_INCREMENT,
    //     type varchar(50) NOT NULL,
    //     title varchar(255) NOT NULL,
    //     message text NOT NULL,
    //     query_id int(11) DEFAULT NULL,
    //     is_read tinyint(1) DEFAULT 0,
    //     created_at datetime DEFAULT CURRENT_TIMESTAMP,
    //     PRIMARY KEY (id)
    // ) $charset_collate;";
    
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
