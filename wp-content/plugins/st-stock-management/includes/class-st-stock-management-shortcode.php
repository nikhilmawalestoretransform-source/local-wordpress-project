<?php 

/**
 * 
 */
class Stock_Manage
{
	
	function __construct()
	{
		
		add_shortcode( 'assets_management', [$this, 'asset_form_loading'] );
		add_shortcode( 'init', [$this, 'render_stock_table'] );
		add_action('wp_ajax_delete_stock_entry', [$this, 'handle_delete_stock_entry']);

        add_shortcode('st_stock_items_form', [$this, 'st_stock_items_shortcode']);

		
		// Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

                // Handle AJAX request
        add_action('wp_ajax_submit_stock_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_submit_stock_form', [$this, 'handle_form_submission']);

        add_action('wp_ajax_update_stock_entry', [$this, 'handle_update_stock_entry']);

        wp_localize_script('stock-management-script', 'stockManagementAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    	]);

		
	}


	    /**
     * Enqueue styles and scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('stock-management-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('stock-management-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
        wp_localize_script('stock-management-script', 'stockManagementAjax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

	public function asset_form_loading(){

		    global $wpdb;

            $items_table = $wpdb->prefix . 'st_stock_items_name';
            $items = $wpdb->get_results("SELECT id, item_type FROM $items_table", ARRAY_A);

		    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
		    $prefill_data = [];

			$as_company = '';
			$as_name = '';
			$as_model = '';
			$as_price = '';
			$as_pn = '';
			$as_tqty = '';
			$as_rqty = '';
			$as_aqty = '';
            $as_item_id = '';

		    if ($edit_id) {
		        $table_name = $wpdb->prefix . 'st_stock_management';
		        $prefill_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id), ARRAY_A);

		        $as_company = esc_attr($prefill_data['asset_company'] ?? '');
		        $as_model = esc_attr($prefill_data['asset_model'] ?? '');
		        $as_price = esc_attr($prefill_data['asset_price'] ?? '');
		        $as_pn = esc_attr($prefill_data['asset_purchase_date'] ?? '');
		        $as_tqty = esc_attr($prefill_data['total_quantity'] ?? '');
                $as_item_id = $prefill_data['item_id'] ?? '';
		    }

		  $image_url = plugins_url('includes/mycompany.png', dirname(__FILE__));


			ob_start();
        ?>
        <h3> STOCK MANAGEMENT </h3>
        <div class="stock-management-form-container">
            <form id="stock-management-form" class="stock-management-form">

            	<?php if ($edit_id): ?>
            		<input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>">
        		<?php endif; ?>

                <label for="item_id">Select Item Type:</label>
                <select id="item_id" name="item_id" required>
                    <option value="">-- Select Item --</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo esc_attr($item['id']); ?>" <?php selected($as_item_id, $item['id']); ?>>
                            <?php echo esc_html($item['item_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>


                <label for="asset_company">Asset Company:</label>
                <input type="text" id="asset_company" name="asset_company" value="<?php echo $as_company; ?>" required>

                <label for="asset_model">Asset Model:</label>
                <input type="text" id="asset_model" name="asset_model" value="<?php echo $as_model; ?>" required>

                <label for="asset_price">Asset Price:</label>
                <input type="text" id="asset_price" name="asset_price" value="<?php echo $as_price; ?>" required>

                <label for="asset_purchase_date">Purchase Date:</label>
                <input type="date" id="asset_purchase_date" name="asset_purchase_date" value="<?php echo $as_pn; ?>" required>

                <label for="total_quantity">Total Quantity:</label>
                <input type="number" id="total_quantity" name="total_quantity" value="<?php echo $as_tqty; ?>" required>

                <button type="submit"><?php echo $edit_id ? 'Update' : 'Submit'; ?></button>
            </form>
        </div>
        <?php
       	echo $this->render_stock_table();
        return ob_get_clean();
	}


	/**
     * Handle form submission via AJAX
     */
    public function handle_form_submission() {
        global $wpdb;

        $stock_table = $wpdb->prefix . 'st_stock_management';
        $data = [
            'item_id' => sanitize_text_field($_POST['item_id']),
            'asset_company' => sanitize_text_field($_POST['asset_company']),
            'asset_model' => sanitize_text_field($_POST['asset_model']),
            'asset_price' => sanitize_text_field($_POST['asset_price']),
            'asset_purchase_date' => sanitize_text_field($_POST['asset_purchase_date']),
            'total_quantity' => intval($_POST['total_quantity']),
        ];

        $wpdb->insert($stock_table, $data);

        wp_send_json_success('Data successfully inserted');
    }

    /* RENDER TABLE AFTER SHORTCODE */

    /* UPDATE FIELDS ACTION START */

    /**
 * Handle AJAX request to update a stock entry.
 */
public function handle_update_stock_entry() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'st_stock_management';

    // Retrieve and sanitize input data
    $id = intval($_POST['id']);
    $asset_company = sanitize_text_field($_POST['asset_company']);
    $asset_model = sanitize_text_field($_POST['asset_model']);
    $asset_price = sanitize_text_field($_POST['asset_price']);
    $asset_purchase_date = sanitize_text_field($_POST['asset_purchase_date']);
    $total_quantity = intval($_POST['total_quantity']);

    // Update the database
    $result = $wpdb->update(
        $table_name,
        [
            'asset_company' => $asset_company,
            'asset_model' => $asset_model,
            'asset_price' => $asset_price,
            'asset_purchase_date' => $asset_purchase_date,
            'total_quantity' => $total_quantity,
        ],
        ['id' => $id],
        [
            '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'
        ],
        ['%d']
    );

    if ($result !== false) {
        wp_send_json_success('Entry updated successfully.');
    } else {
        wp_send_json_error('Failed to update the entry.');
    }
}


    /* UPDATE FIELDS ACTION END */

    /**
 * Render the stock table below the form.
 *
 * @return string
 */
public function render_stock_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'st_stock_management';
    $rows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    // Start table HTML
    $output = '<table class="stock-management-table">';
    $output .= '<thead>
        <tr>
            <th>ID</th>
            <th>Asset Company</th>
            <th>Asset Model</th>
            <th>Asset Price</th>
            <th>Purchase Date</th>
            <th>Total Quantity</th>
            <th>Actions</th>
        </tr>
    </thead>';
    $output .= '<tbody>';

    // Loop through rows and populate table
    foreach ($rows as $row) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($row['id']) . '</td>';
        $output .= '<td>' . esc_html($row['asset_company']) . '</td>';
        $output .= '<td>' . esc_html($row['asset_model']) . '</td>';
        $output .= '<td>' . esc_html($row['asset_price']) . '</td>';
        $output .= '<td>' . esc_html($row['asset_purchase_date']) . '</td>';
        $output .= '<td>' . esc_html($row['total_quantity']) . '</td>';
        $output .= '<td class="two-fields">
            <a href="' . add_query_arg('edit', $row['id'], $_SERVER['REQUEST_URI']) . '"><span class="dashicons dashicons-edit"></span></a>
            <button class="delete-stock-entry" data-id="' . esc_attr($row['id']) . '"><span class="dashicons dashicons-trash"></span></button>
        </td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    return $output;
	}

	public function handle_delete_stock_entry() {
	    global $wpdb;

	    $id = intval($_POST['id']);
	    $table_name = $wpdb->prefix . 'st_stock_management';

	    if ($wpdb->delete($table_name, ['id' => $id])) {
	        wp_send_json_success('Entry deleted successfully.');
	    } else {
	        wp_send_json_error('Failed to delete the entry.');
	    }
	}


    function st_stock_items_shortcode() {
    ob_start();

    global $wpdb;
    $table_name = $wpdb->prefix . 'st_stock_items_name';
    
    // Handle form submission (Add or Update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['st_submit_item'])) {
        $item_type = sanitize_text_field($_POST['item_type']);
        $edit_id = isset($_GET['edit_item']) ? intval($_GET['edit_item']) : 0;

        if (!empty($item_type)) {
            if ($edit_id > 0) {
                // Update existing record
                $wpdb->update($table_name, ['item_type' => $item_type], ['id' => $edit_id]);

            } else {
                // Insert new record
                $wpdb->insert($table_name, ['item_type' => $item_type]);
            }
            // Redirect to remove edit param
            echo "<script>window.location.href='" . esc_url(remove_query_arg(['edit_item'])) . "';</script>";
            exit;
        }
    }

    // Get current item for editing
    $edit_item = null;
    if (isset($_GET['edit_item'])) {
        $edit_id = intval($_GET['edit_item']);
        $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }

    ?>
    <!-- Form -->
    <h3><?php echo $edit_item ? 'Edit' : 'Add'; ?> Stock Item</h3>
    <form method="post">
        <input type="text" name="item_type" value="<?php echo esc_attr($edit_item->item_type ?? ''); ?>" placeholder="Enter Item Type" required>
        <button type="submit" name="st_submit_item"><?php echo $edit_item ? 'Update' : 'Add'; ?> Item</button>
    </form>

    <hr>

    <!-- Table -->
    <h3>Item List</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Item Type</th>
            <th>Actions</th>
        </tr>
        <?php
        $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
        if ($items):
            foreach ($items as $item): ?>
                <tr id="row-<?php echo esc_attr($item->id); ?>">
                    <td><?php echo $item->id; ?></td>
                    <td><?php echo esc_html($item->item_type); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg('edit_item', $item->id)); ?>">Edit</a> |
                        <a href="#" class="delete-item" data-id="<?php echo $item->id; ?>">Delete</a>
                    </td>
                </tr>
        <?php endforeach;
        else: ?>
            <tr><td colspan="3">No items found.</td></tr>
        <?php endif; ?>
    </table>

    <!-- JavaScript -->
    <script>
        document.querySelectorAll('.delete-item').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                if (confirm("Are you sure you want to delete this item?")) {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=delete_st_item&id=' + id)
                    .then(res => res.text())
                    .then(response => {
                        if (response === 'success') {
                            document.getElementById('row-' + id).remove();
                        } else {
                            alert("Failed to delete item.");
                        }
                    });
                }
            });
        });
    </script>

    <?php
    return ob_get_clean();
}




}


new Stock_Manage();


 ?>