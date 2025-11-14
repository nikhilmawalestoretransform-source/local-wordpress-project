<?php
/**
 * Plugin Name: ST Stock Management (Rebuilt)
 * Plugin URI: https://storetransform.com
 * Description: Rebuilt and reorganized version of ST Stock Management. Presentation may be updated; core functionality preserved.
 * Version: 1.0.0
 * Author: storetransform
 * Text Domain: st-stock-management
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('STSM_PLUGIN_FILE', __FILE__);
define('STSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('STSM_VERSION', '1.0.0');

// Autoload simple class files
require_once STSM_PLUGIN_DIR . 'includes/class-st-stock-management-db.php';
require_once STSM_PLUGIN_DIR . 'includes/class-st-stock-management-activator.php';
require_once STSM_PLUGIN_DIR . 'includes/class-st-stock-management-deactivator.php';
require_once STSM_PLUGIN_DIR . 'includes/class-st-stock-management.php';

register_activation_hook(__FILE__, array('St_Stock_Management_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('St_Stock_Management_Deactivator', 'deactivate'));

// Initialize plugin
function stsm_run_plugin() {
    $plugin = new St_Stock_Management();
    $plugin->run();
}
stsm_run_plugin();