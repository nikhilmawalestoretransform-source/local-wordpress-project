<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class St_Stock_Management {

    public function __construct() {
        // load textdomain
        add_action('init', array($this, 'load_textdomain'));
        // load admin and public components after plugins_loaded
        add_action('plugins_loaded', array($this, 'includes'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('st-stock-management', false, dirname(plugin_basename(__FILE__)) . '/../languages');
    }

    public function includes() {
        // include DB helper already loaded by main bootstrap

        if ( is_admin() ) {
            // Admin pages: include existing admin scripts copied into admin/
            // These files are kept largely as-is to preserve behavior.
            $admin_dir = STSM_PLUGIN_DIR . 'admin/';
            $files = array(
                'admin-stock_items-list.php',
                'admin-emp-management.php',
                'admin-asset-types.php',
                'admin-repaire-list.php',
                'asset-log-management.php',
                'stock-management-employee.php',
                'emp-log-management.php',
                'class-stock_management-shortcode.php',
                'class-stock-management-shortcode.php'
            );
            foreach ( $files as $file ) {
                if ( file_exists( $admin_dir . $file ) ) {
                    require_once $admin_dir . $file;
                }
            }
        } else {
            // public side includes (if any exist in public/)
            $public_dir = STSM_PLUGIN_DIR . 'public/';
            if ( file_exists( $public_dir . 'class-st-stock-management-public.php' ) ) {
                require_once $public_dir . 'class-st-stock-management-public.php';
            }
            // include front-end partials if needed
        }

        // Always include original includes file if present (for compatibility)
        $orig = STSM_PLUGIN_DIR . 'includes/original-st-stock-management.php';
        if ( file_exists( $orig ) ) {
            // Do not require to avoid duplicate declarations; include only if safe.
            // require_once $orig;
        }

        // register admin menu hook
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // enqueue scripts for admin and public
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }

    public function register_admin_menu() {
        add_menu_page('Stock Management', 'Stock Management', 'manage_options', 'st-stock-management', array($this, 'render_admin_dashboard'), 'dashicons-archive', 6);

        add_submenu_page('st-stock-management', 'Asset Types', 'Asset Types', 'manage_options', 'st-stock-asset-types', STSM_PLUGIN_DIR . 'admin/admin-asset-types.php');
        // Note: we keep original admin pages, so use those files via callbacks if available.
    }

    public function render_admin_dashboard() {
        $file = STSM_PLUGIN_DIR . 'admin/admin-stock_items-list.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="wrap"><h2>Stock Management</h2><p>Admin file missing.</p></div>';
        }
    }

    public function enqueue_admin_assets($hook) {
        $css = STSM_PLUGIN_URL . 'assets/css/st-stock-management-admin.css';
        $js = STSM_PLUGIN_URL . 'assets/js/st-stock-management-admin.js';
        if ( file_exists( STSM_PLUGIN_DIR . 'assets/css/st-stock-management-admin.css' ) ) {
            wp_enqueue_style('stsm-admin', $css, array(), STSM_VERSION);
        }
        if ( file_exists( STSM_PLUGIN_DIR . 'assets/js/st-stock-management-admin.js' ) ) {
            wp_enqueue_script('stsm-admin', $js, array('jquery'), STSM_VERSION, true);
        }
    }

    public function enqueue_public_assets() {
        $css = STSM_PLUGIN_URL . 'assets/css/st-stock-management-public.css';
        $js = STSM_PLUGIN_URL . 'assets/js/st-stock-management-public.js';
        if ( file_exists( STSM_PLUGIN_DIR . 'assets/css/st-stock-management-public.css' ) ) {
            wp_enqueue_style('stsm-public', $css, array(), STSM_VERSION);
        }
        if ( file_exists( STSM_PLUGIN_DIR . 'assets/js/st-stock-management-public.js' ) ) {
            wp_enqueue_script('stsm-public', $js, array('jquery'), STSM_VERSION, true);
        }
    }

    public function run() {
        // placeholder - additional boot actions here if needed
    }
}