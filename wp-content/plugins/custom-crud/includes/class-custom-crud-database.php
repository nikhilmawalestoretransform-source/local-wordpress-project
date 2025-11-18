<?php

class Custom_CRUD_Database {
    
    public function __construct() {
        // Constructor can be used for initialization
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            item_name varchar(255) NOT NULL,
            item_description text NULL,
            item_price decimal(10,2) DEFAULT 0.00,
            item_quantity int(11) DEFAULT 0,
            item_status varchar(50) DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_name (item_name),
            KEY item_status (item_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function get_items($user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        if ($user_id) {
            $query = $wpdb->prepare("SELECT * FROM $table_name WHERE created_by = %d ORDER BY created_at DESC", $user_id);
        } else {
            $query = "SELECT * FROM $table_name ORDER BY created_at DESC";
        }
        
        return $wpdb->get_results($query);
    }
    
    public function get_item($item_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id));
    }
    
    public function insert_item($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        $defaults = array(
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public function update_item($item_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($table_name, $data, array('id' => $item_id));
        
        return $result !== false;
    }
    
    public function delete_item($item_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_crud_items';
        
        // Check if user owns the item
        $item = $this->get_item($item_id);
        if ($item && $item->created_by != get_current_user_id()) {
            return false;
        }
        
        $result = $wpdb->delete($table_name, array('id' => $item_id));
        
        return $result !== false;
    }
}