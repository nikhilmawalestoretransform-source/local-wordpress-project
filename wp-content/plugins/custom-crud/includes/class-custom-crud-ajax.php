<?php

class Custom_CRUD_Ajax {
    
    public function __construct() {
        // Add AJAX actions
        add_action('wp_ajax_custom_crud_add_item', array($this, 'add_item'));
        add_action('wp_ajax_custom_crud_update_item', array($this, 'update_item'));
        add_action('wp_ajax_custom_crud_delete_item', array($this, 'delete_item'));
        add_action('wp_ajax_custom_crud_get_items', array($this, 'get_items'));
        add_action('wp_ajax_custom_crud_get_item', array($this, 'get_item'));
    }
    
    private function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'custom_crud_nonce')) {
            wp_send_json_error('Security verification failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to perform this action');
        }
    }
    
    public function add_item() {
        $this->verify_nonce();
        
        $database = new Custom_CRUD_Database();
        
        $data = array(
            'item_name' => sanitize_text_field($_POST['item_name']),
            'item_description' => sanitize_textarea_field($_POST['item_description']),
            'item_price' => floatval($_POST['item_price']),
            'item_quantity' => intval($_POST['item_quantity']),
            'item_status' => sanitize_text_field($_POST['item_status'])
        );
        
        $item_id = $database->insert_item($data);
        
        if ($item_id) {
            wp_send_json_success(array(
                'message' => 'Item added successfully!',
                'item_id' => $item_id
            ));
        } else {
            wp_send_json_error('Failed to add item');
        }
    }
    
    public function update_item() {
        $this->verify_nonce();
        
        $database = new Custom_CRUD_Database();
        
        $item_id = intval($_POST['item_id']);
        $data = array(
            'item_name' => sanitize_text_field($_POST['item_name']),
            'item_description' => sanitize_textarea_field($_POST['item_description']),
            'item_price' => floatval($_POST['item_price']),
            'item_quantity' => intval($_POST['item_quantity']),
            'item_status' => sanitize_text_field($_POST['item_status'])
        );
        
        $result = $database->update_item($item_id, $data);
        
        if ($result) {
            wp_send_json_success('Item updated successfully!');
        } else {
            wp_send_json_error('Failed to update item');
        }
    }
    
    public function delete_item() {
        $this->verify_nonce();
        
        $database = new Custom_CRUD_Database();
        
        $item_id = intval($_POST['item_id']);
        $result = $database->delete_item($item_id);
        
        if ($result) {
            wp_send_json_success('Item deleted successfully!');
        } else {
            wp_send_json_error('Failed to delete item');
        }
    }
    
    public function get_items() {
        $this->verify_nonce();
        
        $database = new Custom_CRUD_Database();
        $items = $database->get_items(get_current_user_id());
        
        wp_send_json_success($items);
    }
    
    public function get_item() {
        $this->verify_nonce();
        
        $database = new Custom_CRUD_Database();
        $item_id = intval($_POST['item_id']);
        $item = $database->get_item($item_id);
        
        if ($item && $item->created_by == get_current_user_id()) {
            wp_send_json_success($item);
        } else {
            wp_send_json_error('Item not found');
        }
    }
}