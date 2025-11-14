<?php
// This script creates the plugin structure
$plugin_dir = 'st-stock-management';

$files = [
    'st-stock-management.php' => 'MAIN_PLUGIN_FILE_CONTENT',
    'includes/database.php' => 'DATABASE_FILE_CONTENT',
    'includes/admin/admin-pages.php' => 'ADMIN_PAGES_CONTENT',
    'includes/admin/item-management.php' => 'ITEM_MANAGEMENT_CONTENT',
    'includes/admin/emp-management.php' => 'EMP_MANAGEMENT_CONTENT',
    'includes/frontend/frontend-pages.php' => 'FRONTEND_PAGES_CONTENT',
    'includes/frontend/item-frontend.php' => 'ITEM_FRONTEND_CONTENT',
    'includes/frontend/emp-frontend.php' => 'EMP_FRONTEND_CONTENT',
    'includes/assets/css/admin-style.css' => 'ADMIN_CSS_CONTENT',
    'includes/assets/css/frontend-style.css' => 'FRONTEND_CSS_CONTENT',
    'includes/assets/js/admin-script.js' => 'ADMIN_JS_CONTENT',
    'includes/assets/js/frontend-script.js' => 'FRONTEND_JS_CONTENT',
    'README.txt' => 'README_CONTENT'
];

foreach ($files as $file_path => $content_placeholder) {
    $full_path = $plugin_dir . '/' . $file_path;
    
    // Create directory if needed
    $dir = dirname($full_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Get the actual content from the files above
    $actual_content = get_file_content($file_path);
    
    // Write file
    file_put_contents($full_path, $actual_content);
    echo "Created: $full_path\n";
}

echo "Plugin structure created! Now zip the '$plugin_dir' folder.\n";

function get_file_content($filename) {
    // You would replace this with the actual content from the files above
    return "Content for $filename";
}
?>