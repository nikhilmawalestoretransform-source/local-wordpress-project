<?php

class Custom_CRUD_Installer {
    
    public static function activate() {
        // Load the database class
        require_once CUSTOM_CRUD_PLUGIN_PATH . 'includes/class-custom-crud-database.php';
        
        $database = new Custom_CRUD_Database();
        $database->create_tables();
        
        // Set default options
        add_option('custom_crud_version', CUSTOM_CRUD_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Custom_CRUD_Installer', 'activate'));
register_deactivation_hook(__FILE__, array('Custom_CRUD_Installer', 'deactivate'));