<?php

class STStockManagementDatabase {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            "admin_item_stock_management" => "
                CREATE TABLE {$wpdb->prefix}admin_item_stock_management (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    asset_name varchar(255) NOT NULL,
                    status varchar(20) DEFAULT 'active',
                    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_date datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;
            ",
            "item_stock_management" => "
                CREATE TABLE {$wpdb->prefix}item_stock_management (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    asset_type mediumint(9) NOT NULL,
                    brand_model varchar(255) NOT NULL,
                    serial_number varchar(255) UNIQUE NOT NULL,
                    quantity int DEFAULT 1,
                    price decimal(10,2),
                    status varchar(20) DEFAULT 'active',
                    location varchar(255),
                    date_purchased date,
                    warranty_expiry_date date,
                    remarks text,
                    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_date datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;
            ",
            "admin_emp_stock_management" => "
                CREATE TABLE {$wpdb->prefix}admin_emp_stock_management (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    emp_name varchar(255) NOT NULL,
                    email varchar(255),
                    position varchar(255),
                    status varchar(20) DEFAULT 'active',
                    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_date datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;
            ",
            "emp_stock_management" => "
                CREATE TABLE {$wpdb->prefix}emp_stock_management (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    emp_id mediumint(9) NOT NULL,
                    asset_type mediumint(9) NOT NULL,
                    brand_model varchar(255) NOT NULL,
                    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_date datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;
            "
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            dbDelta($sql);
        }
    }
}
?>