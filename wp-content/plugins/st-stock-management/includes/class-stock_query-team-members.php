<?php
/**
 * Member Support System
 * Add this file to your plugin's includes folder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create support tables on plugin activation
register_activation_hook(__FILE__, 'create_member_support_tables');

function create_member_support_tables() {
    global $wpdb;
    
    $queries_table = $wpdb->prefix . 'member_queries';
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Member queries table
    $sql1 = "CREATE TABLE IF NOT EXISTS $queries_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        employee_id int(11) NOT NULL,
        query text NOT NULL,
        priority enum('low','medium','high','urgent') DEFAULT 'medium',
        category varchar(100) DEFAULT 'general',
        status enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
        admin_response text DEFAULT NULL,
        resolved_by int(11) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Admin notifications table
    $sql2 = "CREATE TABLE IF NOT EXISTS $notifications_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        type varchar(50) NOT NULL,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        query_id int(11) DEFAULT NULL,
        is_read tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Member Support Form Shortcode
add_shortcode('member_support', 'member_support_shortcode');

function member_support_shortcode($atts) {
    // Handle form submission
    if (isset($_POST['action']) && $_POST['action'] === 'submit_query') {
        handle_member_query_submission();
    }
    
    ob_start();
    ?>
    <div id="member-support-container">
        <style>
            #member-support-container {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                color: white;
            }
            
            .support-header {
                text-align: center;
                margin-bottom: 30px;
                background: rgba(255,255,255,0.1);
                padding: 20px;
                border-radius: 10px;
                backdrop-filter: blur(10px);
            }
            
            .support-header h2 {
                margin: 0;
                font-size: 2.2em;
                background: linear-gradient(45deg, #f093fb, #f5576c);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            
            .form-container {
                background: rgba(255,255,255,0.1);
                padding: 30px;
                border-radius: 15px;
                backdrop-filter: blur(10px);
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #FFD700;
                font-size: 1.1em;
            }
            
            .form-group select,
            .form-group textarea,
            .form-group input {
                width: 100%;
                padding: 8px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 8px;
                background-color: rgba(0,0,0,0.4);
                color: #ffffff;
                font-size: 14px;
                transition: all 0.3s ease;
                box-sizing: border-box;
            }
            
            .form-group select:focus,
            .form-group textarea:focus,
            .form-group input:focus {
                outline: none;
                border-color: #FFD700;
                box-shadow: 0 0 10px rgba(255,215,0,0.3);
            }
            
            .form-group textarea {
                resize: vertical;
                min-height: 120px;
            }
            
            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .priority-high { border-left: 4px solid #e74c3c !important; }
            .priority-urgent { border-left: 4px solid #c0392b !important; animation: pulse 2s infinite; }
            .priority-medium { border-left: 4px solid #f39c12 !important; }
            .priority-low { border-left: 4px solid #27ae60 !important; }
            
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(192, 57, 43, 0); }
                100% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0); }
            }
            
            .btn {
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 1.1em;
                transition: all 0.3s ease;
                margin: 10px 0;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                background: linear-gradient(45deg, #f093fb, #f5576c);
                color: white;
                width: 100%;
            }
            
            .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            }
            
            .success-message {
                background: linear-gradient(45deg, #27ae60, #2ecc71);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: bold;
                text-align: center;
            }
            
            .info-box {
                background: rgba(52,152,219,0.2);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 4px solid #3498db;
            }
            
            @media (max-width: 768px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }
                
                #member-support-container {
                    padding: 15px;
                }
                
                .support-header h2 {
                    font-size: 1.8em;
                }
            }
        </style>
        
        <div class="support-header">
            <h2>🛠️ Member Support Center</h2>
            <p>Need help? Submit your query and our team will assist you promptly!</p>
        </div>
        
        <div class="form-container">
            <?php if (isset($_POST['query_submitted'])): ?>
                <div class="success-message">
                    ✅ Your query has been submitted successfully! You will receive a response soon.
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                📌 <strong>Before submitting:</strong> Please check our FAQ section or try restarting the application if it's a technical issue.
            </div>
            
            <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="submit_query">
                <?php wp_nonce_field('member_support_action', 'member_support_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="employee_id">👤 Select Employee *</label>
                        <select id="employee_id" name="employee_id" required>
                            <option value="">Choose Employee</option>
                            <?php echo get_active_employees_options(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">📂 Issue Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="technical">🔧 Technical Issue</option>
                            <option value="account">👤 Account Related</option>
                            <option value="asset">💻 Asset Problem</option>
                            <option value="access">🔐 Access Issue</option>
                            <option value="training">📚 Training Request</option>
                            <option value="general">❓ General Query</option>
                            <option value="complaint">⚠️ Complaint</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="priority">⚡ Priority Level *</label>
                    <select id="priority" name="priority" required>
                        <option value="">Select Priority</option>
                        <option value="low">🟢 Low - General question</option>
                        <option value="medium" selected>🟡 Medium - Standard issue</option>
                        <option value="high">🟠 High - Blocking work</option>
                        <option value="urgent">🔴 Urgent - Critical/Security issue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="query_title">📝 Issue Title *</label>
                    <input type="text" id="query_title" name="query_title" placeholder="Brief description of your issue" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="member_query">📋 Detailed Description *</label>
                    <textarea id="member_query" name="member_query" rows="6" placeholder="Please provide detailed information about your issue, including steps to reproduce if applicable..." required></textarea>
                </div>
                
                <button type="submit" class="btn">🚀 Submit Support Request</button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Handle member query submission
function handle_member_query_submission() {
    if (!isset($_POST['member_support_nonce']) || !wp_verify_nonce($_POST['member_support_nonce'], 'member_support_action')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $queries_table = $wpdb->prefix . 'member_queries';
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    
    $employee_id = intval($_POST['employee_id']);
    $category = sanitize_text_field($_POST['category']);
    $priority = sanitize_text_field($_POST['priority']);
    $query_title = sanitize_text_field($_POST['query_title']);
    $member_query = sanitize_textarea_field($_POST['member_query']);
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Insert query
    $query_id = $wpdb->insert($queries_table, [
        'user_id' => $user_id,
        'employee_id' => $employee_id,
        'query' => $query_title . "\n\n" . $member_query,
        'priority' => $priority,
        'category' => $category,
        'status' => 'pending'
    ]);
    
    if ($query_id) {
        $query_id = $wpdb->insert_id;
        
        // Create admin notification
        $priority_emoji = [
            'low' => '🟢',
            'medium' => '🟡', 
            'high' => '🟠',
            'urgent' => '🔴'
        ];
        
        $category_emoji = [
            'technical' => '🔧',
            'account' => '👤',
            'asset' => '💻',
            'access' => '🔐',
            'training' => '📚',
            'general' => '❓',
            'complaint' => '⚠️'
        ];
        
        $notification_title = ($priority_emoji[$priority] ?? '') . ' New Support Request: ' . $query_title;
        $notification_message = "Category: " . ($category_emoji[$category] ?? '') . " " . ucfirst($category) . "\n";
        $notification_message .= "Priority: " . ucfirst($priority) . "\n";
        $notification_message .= "Employee ID: " . $employee_id . "\n";
        $notification_message .= "Submitted by: " . $current_user->display_name . " (ID: " . $user_id . ")\n\n";
        $notification_message .= "Query: " . substr($member_query, 0, 200) . "...";
        
        $wpdb->insert($notifications_table, [
            'type' => 'member_query',
            'title' => $notification_title,
            'message' => $notification_message,
            'query_id' => $query_id,
            'is_read' => 0
        ]);
        
        // Send admin email for high/urgent priorities
        if (in_array($priority, ['high', 'urgent'])) {
            $admin_email = get_option('admin_email');
            $subject = "[" . strtoupper($priority) . "] New Support Request - " . $query_title;
            $email_message = "A new " . $priority . " priority support request has been submitted.\n\n";
            $email_message .= "Category: " . ucfirst($category) . "\n";
            $email_message .= "Employee ID: " . $employee_id . "\n";
            $email_message .= "Submitted by: " . $current_user->display_name . "\n";
            $email_message .= "Title: " . $query_title . "\n\n";
            $email_message .= "Description:\n" . $member_query . "\n\n";
            $email_message .= "Please log in to the admin panel to respond.";
            
            wp_mail($admin_email, $subject, $email_message);
        }
        
        $_POST['query_submitted'] = true;
    }
}

// Admin Support Management Shortcode
add_shortcode('admin_support_panel', 'admin_support_panel_shortcode');

function admin_support_panel_shortcode($atts) {
    // Check if user has admin capabilities
    if (!current_user_can('manage_options')) {
        return '<p>You do not have permission to access this panel.</p>';
    }
    
    // Handle admin actions
    if (isset($_POST['action'])) {
        handle_admin_support_actions();
    }
    
    ob_start();
    ?>
    <div id="admin-support-panel">
        <style>
            #admin-support-panel {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 1400px;
                margin: 0 auto;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                color: white;
            }
            
            .notifications-box {
                background: linear-gradient(45deg, #ff6b6b, #ee5a24);
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                position: relative;
                overflow: hidden;
            }
            
            .notifications-box::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
                transform: rotate(45deg);
                animation: shine 3s infinite;
            }
            
            @keyframes shine {
                0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
                100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
            }
            
            .notification-item {
                background: rgba(0,0,0,0.3);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 10px;
                border-left: 4px solid #f39c12;
                position: relative;
            }
            
            .notification-item.unread {
                border-left-color: #e74c3c;
                animation: pulse-notification 2s infinite;
            }
            
            @keyframes pulse-notification {
                0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
                70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
                100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
            }
            
            .support-table {
                width: 100%;
                border-collapse: collapse;
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
                overflow: hidden;
                margin-top: 20px;
            }
            
            .support-table th, .support-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid rgba(255,255,255,0.2);
                font-size: 13px;
            }
            
            .support-table th {
                background: rgba(102,126,234,0.3);
                font-weight: 600;
                color: #667eea;
            }
            
            .priority-urgent { background: rgba(231,76,60,0.2) !important; }
            .priority-high { background: rgba(230,126,34,0.2) !important; }
            .priority-medium { background: rgba(241,196,15,0.2) !important; }
            .priority-low { background: rgba(46,204,113,0.2) !important; }
            
            .status-pending { color: #f39c12; }
            .status-in-progress { color: #3498db; }
            .status-resolved { color: #27ae60; }
            .status-closed { color: #95a5a6; }
            
            .btn-small {
                padding: 8px 15px;
                font-size: 12px;
                margin: 2px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .btn-respond { background: #3498db; color: white; }
            .btn-resolve { background: #27ae60; color: white; }
            .btn-close { background: #e74c3c; color: white; }
            
            .response-form {
                background: rgba(0,0,0,0.3);
                padding: 20px;
                border-radius: 10px;
                margin-top: 15px;
                display: none;
            }
            
            .response-form textarea {
                width: 100%;
                padding: 10px;
                border-radius: 5px;
                border: 1px solid rgba(255,255,255,0.3);
                background: rgba(255,255,255,0.1);
                color: white;
                resize: vertical;
                box-sizing: border-box;
            }
        </style>
        
        <!-- Notifications Section -->
        <div class="notifications-box">
            <h3>🔔 Recent Notifications</h3>
            <?php echo render_admin_notifications(); ?>
        </div>
        
        <!-- Support Queries Management -->
        <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; backdrop-filter: blur(10px);">
            <h3>🛠️ Support Queries Management</h3>
            <?php echo render_support_queries_table(); ?>
        </div>
    </div>
    
    <script>
        function showResponseForm(queryId) {
            var form = document.getElementById('response-form-' + queryId);
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        function markAsRead(notificationId) {
            // AJAX call to mark notification as read
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=mark_notification_read&notification_id=' + notificationId);
            
            // Hide the notification
            document.getElementById('notification-' + notificationId).style.display = 'none';
        }
    </script>
    <?php
    return ob_get_clean();
}

// Handle admin support actions
function handle_admin_support_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $queries_table = $wpdb->prefix . 'member_queries';
    
    if ($_POST['action'] === 'respond_query') {
        $query_id = intval($_POST['query_id']);
        $response = sanitize_textarea_field($_POST['admin_response']);
        $status = sanitize_text_field($_POST['new_status']);
        
        $wpdb->update($queries_table, [
            'admin_response' => $response,
            'status' => $status,
            'resolved_by' => get_current_user_id()
        ], ['id' => $query_id]);
        
        echo '<div style="background: #27ae60; padding: 10px; border-radius: 5px; margin: 10px 0;">Response saved successfully!</div>';
    }
}

// Render admin notifications
function render_admin_notifications() {
    global $wpdb;
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    
    $notifications = $wpdb->get_results("
        SELECT * FROM $notifications_table 
        WHERE is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    if (empty($notifications)) {
        return '<p>No new notifications</p>';
    }
    
    $html = '';
    foreach ($notifications as $notification) {
        $html .= '<div id="notification-' . $notification->id . '" class="notification-item unread">';
        $html .= '<h4>' . esc_html($notification->title) . '</h4>';
        $html .= '<p>' . nl2br(esc_html($notification->message)) . '</p>';
        $html .= '<small>Received: ' . date('d/m/Y H:i', strtotime($notification->created_at)) . '</small>';
        $html .= '<button onclick="markAsRead(' . $notification->id . ')" style="float: right; background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 3px;">Mark as Read</button>';
        $html .= '</div>';
    }
    
    return $html;
}

// Render support queries table
function render_support_queries_table() {
    global $wpdb;
    $queries_table = $wpdb->prefix . 'member_queries';
    $employees_table = $wpdb->prefix . 'stock_employees';
    
    $queries = $wpdb->get_results("
        SELECT q.*, e.employee_name, e.employee_id as emp_id, u.display_name as user_name
        FROM $queries_table q
        LEFT JOIN $employees_table e ON q.employee_id = e.id
        LEFT JOIN {$wpdb->users} u ON q.user_id = u.ID
        ORDER BY 
            CASE 
                WHEN q.priority = 'urgent' THEN 1
                WHEN q.priority = 'high' THEN 2
                WHEN q.priority = 'medium' THEN 3
                WHEN q.priority = 'low' THEN 4
            END,
            q.created_at DESC
        LIMIT 50
    ");
    
    if (empty($queries)) {
        return '<p>No support queries found.</p>';
    }
    
    $html = '<table class="support-table">';
    $html .= '<thead><tr>';
    $html .= '<th>Priority</th><th>Category</th><th>Employee</th><th>Query</th><th>Status</th><th>Submitted</th><th>Actions</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($queries as $query) {
        $priority_class = 'priority-' . $query->priority;
        $status_class = 'status-' . str_replace(' ', '-', $query->status);
        
        $html .= '<tr class="' . $priority_class . '">';
        
        // Priority
        $priority_icons = ['urgent' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
        $html .= '<td>' . ($priority_icons[$query->priority] ?? '') . ' ' . ucfirst($query->priority) . '</td>';
        
        // Category
        $category_icons = ['technical' => '🔧', 'account' => '👤', 'asset' => '💻', 'access' => '🔐', 'training' => '📚', 'general' => '❓', 'complaint' => '⚠️'];
        $html .= '<td>' . ($category_icons[$query->category] ?? '') . ' ' . ucfirst($query->category) . '</td>';
        
        // Employee
        $html .= '<td><strong>' . esc_html($query->employee_name) . '</strong><br><small>' . esc_html($query->emp_id) . '</small></td>';
        
        // Query
        $html .= '<td>' . esc_html(substr($query->query, 0, 100)) . '...<br>';
        $html .= '<small>By: ' . esc_html($query->user_name) . '</small></td>';
        
        // Status
        $html .= '<td class="' . $status_class . '">' . ucfirst(str_replace('_', ' ', $query->status)) . '</td>';
        
        // Date
        $html .= '<td>' . date('d/m/Y H:i', strtotime($query->created_at)) . '</td>';
        
        // Actions
        $html .= '<td>';
        if ($query->status !== 'closed') {
            $html .= '<button class="btn-small btn-respond" onclick="showResponseForm(' . $query->id . ')">Respond</button>';
        }
        $html .= '</td>';
        
        $html .= '</tr>';
        
        // Response form
        if ($query->status !== 'closed') {
            $html .= '<tr><td colspan="7">';
            $html .= '<div id="response-form-' . $query->id . '" class="response-form">';
            $html .= '<form method="post">';
            $html .= '<input type="hidden" name="action" value="respond_query">';
            $html .= '<input type="hidden" name="query_id" value="' . $query->id . '">';
            $html .= '<h4>Respond to Query</h4>';
            $html .= '<p><strong>Full Query:</strong><br>' . nl2br(esc_html($query->query)) . '</p>';
            if (!empty($query->admin_response)) {
                $html .= '<p><strong>Previous Response:</strong><br>' . nl2br(esc_html($query->admin_response)) . '</p>';
            }
            $html .= '<textarea name="admin_response" rows="4" placeholder="Type your response here..." required></textarea><br><br>';
            $html .= '<select name="new_status" required>';
            $html .= '<option value="in_progress"' . ($query->status === 'in_progress' ? ' selected' : '') . '>In Progress</option>';
            $html .= '<option value="resolved">Resolved</option>';
            $html .= '<option value="closed">Closed</option>';
            $html .= '</select>';
            $html .= '<button type="submit" class="btn-small btn-resolve">Send Response</button>';
            $html .= '</form>';
            $html .= '</div>';
            $html .= '</td></tr>';
        }
    }
    
    $html .= '</tbody></table>';
    
    return $html;
}

// AJAX handler for marking notifications as read
add_action('wp_ajax_mark_notification_read', 'mark_notification_as_read');

function mark_notification_as_read() {
    global $wpdb;
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    $notification_id = intval($_POST['notification_id']);
    
    $wpdb->update($notifications_table, ['is_read' => 1], ['id' => $notification_id]);
    wp_die();
}

// Admin Notification Display for Dashboard
add_action('wp_dashboard_setup', 'add_support_dashboard_widget');

function add_support_dashboard_widget() {
    wp_add_dashboard_widget(
        'member_support_notifications',
        'Member Support Notifications',
        'display_support_dashboard_widget'
    );
}

function display_support_dashboard_widget() {
    global $wpdb;
    $notifications_table = $wpdb->prefix . 'admin_notifications';
    
    $unread_count = $wpdb->get_var("SELECT COUNT(*) FROM $notifications_table WHERE is_read = 0");
    $recent_notifications = $wpdb->get_results("
        SELECT * FROM $notifications_table 
        WHERE is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    
    echo '<div style="padding: 15px;">';
    echo '<h4>🔔 Unread Notifications: ' . intval($unread_count) . '</h4>';
    
    if (empty($recent_notifications)) {
        echo '<p>No new support requests.</p>';
    } else {
        foreach ($recent_notifications as $notification) {
            echo '<div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #e74c3c; border-radius: 4px;">';
            echo '<strong>' . esc_html($notification->title) . '</strong><br>';
            echo '<small>' . date('d/m/Y H:i', strtotime($notification->created_at)) . '</small>';
            echo '</div>';
        }
    }
    
    echo '<p><a href="?page_id=98" class="button-primary">View All Support Requests</a></p>';
    echo '</div>';
}

?>