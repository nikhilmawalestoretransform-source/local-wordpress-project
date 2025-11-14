<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class St_Stock_Management_Activator {
    public static function activate() {
        $db = new St_Stock_Management_DB();
        $db->create_tables();
        // any other activation tasks
    }
}