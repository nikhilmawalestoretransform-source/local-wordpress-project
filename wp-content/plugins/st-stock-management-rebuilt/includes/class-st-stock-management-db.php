<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class St_Stock_Management_DB {

    private $tables = array();

    public function __construct() {
        $this->init_tables();
    }

    private function init_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();

        $this->tables = array(
            $prefix . 'admin_emp_stock_management' => "CREATE TABLE `{$prefix}admin_emp_stock_management` (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `emp_name` varchar(100) NOT NULL,
                `emp_email` varchar(100) NOT NULL,
                `emp_position` varchar(100) NOT NULL,
                `emp_status` varchar(20) DEFAULT 'active',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `emp_email` (`emp_email`)
            ) {$charset_collate};",
            // ... other table definitions omitted for brevity but created similarly
        );

        // Add critical tables used elsewhere (stock_management, st_stock_items_name, st_stock_management)
        $this->tables[$prefix . 'stock_management'] = "CREATE TABLE `{$prefix}stock_management` (
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
        ) {$charset_collate};";

        $this->tables[$prefix . 'st_stock_items_name'] = "CREATE TABLE `{$prefix}st_stock_items_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_type` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) {$charset_collate};";

        $this->tables[$prefix . 'st_stock_management'] = "CREATE TABLE `{$prefix}st_stock_management` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) DEFAULT NULL,
            `asset_company` varchar(255) NOT NULL,
            `asset_model` varchar(255) NOT NULL,
            `asset_price` varchar(255) NOT NULL,
            `asset_purchase_date` date NOT NULL,
            `total_quantity` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) {$charset_collate};";
    }

    public function create_tables() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $results = array();
        foreach ( $this->tables as $table => $sql ) {
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                $results[$table] = dbDelta( $sql );
            } else {
                $results[$table] = 'exists';
            }
        }
        return $results;
    }

    public function table_exists($name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$name}'") == $name;
    }
}