<?php 

 class MemberManage
 {
 	
 	function __construct()
 	{
 		add_shortcode("member_with_stock_manage",[$this,'asset_assign_form']);
        add_action('wp_ajax_st_stock_manage_user_asset', [$this, 'st_stock_manage_user_asset_function']);
        add_action('wp_ajax_nopriv_st_stock_manage_user_asset', [$this, 'st_stock_manage_user_asset_function']);

 	}

    function st_stock_manage_user_asset_function() {

        $response = array(
            'status' => false,
            'message' => '',
            'data' => null
        );

        // Validate user email
        if (empty($_POST['user_email'])) {
            $response['message'] = 'User email is required';
            wp_send_json($response);
            die();
        }

        // Validate user ID
        if (empty($_POST['user_id'])) {
            $response['message'] = 'User ID is required';
            wp_send_json($response);
            die();
        }

        // Validate assets array
        if (empty($_POST['assets']) || !is_array($_POST['assets'])) {
            $response['message'] = 'No assets selected / User saved field removed';
            $user_id = intval($_POST['user_id']);
            $this->removeall($user_id);
            wp_send_json($response);
            die();
        }

    

        // Validate each asset
        foreach ($_POST['assets'] as $asset) {

            if (empty($asset['asset_name'])) {
                $response['message'] = 'Asset name is required for all items';
                wp_send_json($response);
                die();
            }

            if (empty($asset['quantity']) || !is_numeric($asset['quantity']) || $asset['quantity'] < 1) {
                $response['message'] = 'Valid quantity is required for ' . $asset['asset_name'];
                wp_send_json($response);
                die();
            }

            // Validate if quantity doesn't exceed remaining quantity
            if ($asset['quantity'] > $asset['remaining_quantity']) {
                $response['message'] = 'Requested quantity exceeds available quantity for ' . $asset['asset_name'];
                wp_send_json($response);
                die();
            }
        }

 

       // print_r($_POST['assets']);

        // If we get here, all validations passed
        try {
            global $wpdb;
            $user_id = intval($_POST['user_id']);
            $current_date = current_time('mysql');

            // Begin transaction
           /// $wpdb->query('START TRANSACTION');

            foreach ($_POST['assets'] as $asset) {
                // Get the asset details from database
                $asset_details = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, remaining_quantity, assigned_quantity, total_quantity 
                    FROM wp_st_stock_management 
                    WHERE id = %d",
                    $asset['asset_id']
                ));

               

                if (!$asset_details) {
                    throw new Exception('Asset not found: ' . $asset['asset_name']);
                }

                $dy_key = 'asset_id_'.$asset['asset_id'];

                $check_data_exist = get_user_meta($user_id, 'st_stock_user_asset', true);

                // echo 'user_id';
                // print_r($user_id);

                // PREAPRE DATA FOR USER META TABLE
                $user_meta_data[$dy_key] = array(
                    'quantity' => $asset['quantity'],
                    'assigned_date' => $current_date
                );

                

                if( !is_serialized( $user_meta_data ) ) {
                    $user_meta_data_daw = maybe_serialize($user_meta_data);
                }

                
                if(empty($check_data_exist)){

                        // INSERT USER META DATA
                        $status = 'fd';
                        $old_quantity = 0;


                        if (!update_user_meta( $user_id, 'st_stock_user_asset', $user_meta_data_daw)) {
                            error_log('Failed to update user meta for key: ' . $st_stock_user_asset);
                        }                       

                       $print_Res = $this->calculating_quantity_update($old_quantity, $asset['quantity'], $asset['asset_id'], $status);

                       $response['message'] = 'Entry Saved!!!';
                        
                        
                }else{

                        
                        // UPDATE USER META DATA
                        $exploded_array = maybe_unserialize($check_data_exist);

                        // echo '<pre>';
                        // print_r($exploded_array);
                        // echo '</pre>';
                        $dy_key = 'asset_id_'.$asset['asset_id'];
                        // WE CAN CHCECK IF DATA APPEND OR EDIT OLD DATA
                        if(array_key_exists($dy_key, $exploded_array)){
                                                      
                            //IF EDIT THE WE MATCH VALUES ARE NEW OR SAME
                            if($exploded_array[$dy_key]['quantity'] == $asset['quantity']){
                                //IF VALUE SAME THEN WE IGNORE
                                $response['message'] = 'Quantity is updated same qty';
                                //wp_send_json($response);
                                //die();

                            }else{
                                //IF VALUE NOT SAME THEN WE UPDATE

                                $status = 'update';
                                $old_quantity = 0;
                                $old_quantity = $exploded_array[$dy_key]['quantity'];
                                $print_Res = $this->calculating_quantity_update($old_quantity,$asset['quantity'], $asset['asset_id'], $status);
                                $exploded_array[$dy_key]['quantity'] = $asset['quantity'];
                                $exploded_array[$dy_key]['assigned_date'] = $current_date;
                                $response['message'] = 'Quantity is updated adjust';
                            }
                        }else{
                            //IF DATA NOT EXIST THEN WE APPEND
                            //echo 'new key';
                            $status = 'append';
                            $old_quantity = 0;
                            $print_Res = $this->calculating_quantity_update($old_quantity,$asset['quantity'], $asset['asset_id'], $status);
                            $exploded_array[$dy_key] = array(
                                'quantity' => $asset['quantity'],
                                'assigned_date' => $current_date
                            );
                            $response['message'] = 'Quantity is updated append';
                        }
                        
                      
                        // echo '<br>';
                        // print_r($print_Res);

                        if( !is_serialized( $exploded_array ) ) {
                            $exploded_array_REentry = maybe_serialize($exploded_array);
                        }
                       //print_r($exploded_array);
                       
                        //UPDATE USER META DATA
                        update_user_meta($user_id, 'st_stock_user_asset', $exploded_array_REentry);
                }

                //die();


                // if ($result === false) {
                //     throw new Exception('Failed to record assignment for ' . $asset['asset_name']);
                // }
            }

            // If we get here, commit the transaction
            // $wpdb->query('COMMIT');

            // $response['status'] = true;
            // $response['message'] = 'Assets successfully assigned to user';
            // $response['data'] = $_POST['assets'];

        } catch (Exception $e) {
            // If anything goes wrong, rollback the transaction
            $wpdb->query('ROLLBACK');
            $response['message'] = $e->getMessage();
        }

        wp_send_json($response);
        die();
    }

    public function removeall($user_id){

        $check_data_exist = get_user_meta($user_id, 'st_stock_user_asset', true);

if(!empty($check_data_exist)){

$exploded_array = maybe_unserialize($check_data_exist);

foreach($exploded_array as $asset_key => $asset_array){

    $asset_id = intval(str_replace('asset_id_', '', $asset_key));
    $old_qty = $asset_array['quantity'];

        global $wpdb;

        $asset_details = $wpdb->get_row($wpdb->prepare(
            "SELECT id, remaining_quantity, assigned_quantity, total_quantity 
            FROM wp_st_stock_management 
            WHERE id = %d",
            $asset_id
        ));

        if (!$asset_details) {
            throw new Exception('Asset not found: ' . $asset['asset_name']);
        }

        $total_quantity = $asset_details->total_quantity;
        $assigned_quantity = $asset_details->assigned_quantity;

        $qty_adjust = $assigned_quantity - $old_qty;

        if(  $total_quantity <= $qty_adjust ){
            echo 'assigned quantity is note more than total quantity';
        }

        $update_status = $wpdb->update(
            'wp_st_stock_management',
            array(
                'assigned_quantity' => $qty_adjust,
            ),
            array('id' => $asset_id)
        );

}

update_user_meta($user_id, 'st_stock_user_asset', '');

}

    }

    public function calculating_quantity_update($old_quantity, $quantity, $asset_id, $status){

        global $wpdb;

        $asset_details = $wpdb->get_row($wpdb->prepare(
            "SELECT id, remaining_quantity, assigned_quantity, total_quantity 
            FROM wp_st_stock_management 
            WHERE id = %d",
            $asset_id
        ));

        if (!$asset_details) {
            throw new Exception('Asset not found: ' . $asset['asset_name']);
        }

        $total_quantity = $asset_details->total_quantity;
        $assigned_quantity = $asset_details->assigned_quantity;

        if(  'update' == $status ){

            // UPDATING IN SAME WITH CAREFULL
            echo 'old qty';
            print_r($old_quantity);

            echo 'asigned qty';
            print_r($assigned_quantity);

            echo 'New qty';
            print_r($quantity);

            //die();
            $qty_adjust = $assigned_quantity - $old_quantity;
            $addition_quantity = $quantity + $qty_adjust;

        }else{

            $addition_quantity = $quantity + $assigned_quantity;

        }

        if(  $total_quantity <= $addition_quantity ){
            echo 'assigned quantity is note more than total quantity';
        }

            //         echo 'dasdad';
            // print_r($addition_quantity);
            // die();

        $update_status = $wpdb->update(
                    'wp_st_stock_management',
                    array(
                        'assigned_quantity' => $addition_quantity,
                    ),
                    array('id' => $asset_id)
                );

        return $addition_quantity;
        
    }

 	public function asset_assign_form(){



// Check if managing assets for a specific user
if (isset($_GET['manageasset']) && !empty($_GET['manageasset'])) {
    $user_id = intval($_GET['manageasset']);
    $user_info = get_userdata($user_id);
}

// Fetch asset data from the wp_st_stock_management table
global $wpdb;
$assets = $wpdb->get_results("SELECT id, asset_name, asset_model, total_quantity, remaining_quantity, assigned_quantity FROM wp_st_stock_management");

 		ob_start();
 		?>
<body>

    

<?php if (!isset($user_info)): ?>
    <!-- List all users -->
    <h3>Asset Assignment Form</h3>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $users = get_users();
            foreach ($users as $user) {
                echo "<tr>
                        <td>{$user->first_name}</td>
                        <td>{$user->last_name}</td>
                        <td>{$user->user_email}</td>
                        <td><a class='edit-button' href='?manageasset={$user->ID}'>Edit</a></td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
<?php else: ?>

	<style type="text/css">
        .form-wrapper{
            max-width:1200px !important ;
        }
	   form {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .repeater-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .repeater-row input[type="text"], 
        .repeater-row select, 
        .repeater-row input[type="number"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            flex: 1;
        }

        .repeater-row input[readonly] {
            background-color: #f9f9f9;
        }

        .repeater-row button {
            padding: 8px 12px;
            font-size: 14px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .repeater-row button:hover {
            background-color: #c82333;
        }

        .add-row {
            margin-top: 10px;
            text-align: right;
        }

        .add-row button {
            background-color: #28a745;
            padding: 10px 15px;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-row button:hover {
            background-color: #218838;
        }

        .submit-button {
            text-align: center;
            margin-top: 20px;
        }

        .submit-button button {
            background-color: #007bff;
            padding: 10px 20px;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-button button:hover {
            background-color: #0056b3;
        }
        input.remaining-quantity.input-qty {
  text-align: center;
  padding: 6px 10px;
  border: 1px solid #d4d4d4;
  max-width: 70px;
}
.status-message{
    text-align: center;
    color: #ff5638;
    font-size: 15px;
}

/* quantity plus minus start */




/* quantity plus minus end */
    </style>
    <!-- Asset management form -->
<div class="form-wrapper">
    <h3>Manage User Assets</h3>
    <input type="hidden" name="useridhide" id="useridhide" value="<?php echo $user_id; ?>">
 <form id="assetForm" action="" method="POST">
    <div>
        <?php $user_email = ($user_info->user_email); 
        
        $count_row = 0;

        $check_data_exist = get_user_meta($user_id, 'st_stock_user_asset', true);

        if($check_data_exist){        
          
        $exploded_array = maybe_unserialize($check_data_exist);
            
            if(count($exploded_array) > 0){
            $count_row = count($exploded_array);
            $array_keys = array_keys($exploded_array);

            }
        }
        ?>

        <label for="user_name">Name of User:</label>
        <input type="text" id="user_name" name="user_name" value="<?php echo $user_email; ?>" readonly>
    </div>

    <!-- Repeater starts -->
    <div id="assetRepeater"> 
            <?php if($count_row){
             for($i = 0; $i < $count_row; $i++){ //echo $array_keys[$i]; ?>
    <div class="repeater-row">

        <select name="asset_name[]" class="asset-name" required>
            <option value="">Select Asset Name</option>
            <?php foreach ($assets as $asset):

                $dy_key = 'asset_id_'.$asset->id;

                if( $dy_key == $array_keys[$i] ){
                        $selected_value = 'selected';
                        $key_quantity = $exploded_array[$dy_key]['quantity'];
                }else{
                    $selected_value = '';
                }
                $merge_name_maodel = esc_html($asset->asset_name) .' '. esc_html($asset->asset_model);
                $rem_qty = $asset->remaining_quantity;
                if($rem_qty == 0){ $rem_qty = $asset->total_quantity; } ?>
                <option value="<?php echo $merge_name_maodel; ?>" 
                        data-totalqty="<?php echo esc_html($asset->total_quantity); ?>" 
                        data-remqty="<?php echo esc_html($rem_qty); ?>"
                        data-asset-id="<?php echo $asset->id; ?>" <?php echo $selected_value; ?>>
                    <?php echo $merge_name_maodel; ?>
                </option>
            <?php endforeach; ?>
        </select>


        <div class="qty-container">
            <button class="qty-btn-minus btn-light" type="button">
                <span class="dashicons dashicons-minus"></span>
            </button>
            <input type="number" value="<?php echo $key_quantity; ?>" min="1" step="1" name="remaining_quantity[]" class="remaining-quantity input-qty" required="">
            <button class="qty-btn-plus btn-light" type="button">
                <span class="dashicons dashicons-plus"></span>
            </button>
        </div>

        <button type="button" class="remove-row">Remove</button>
    </div>
    <?php }
    } ?>
 
    </div>
    <!-- Repeater ends -->

    <div class="add-row">
        <button type="button" id="addRow">Add New Asset</button>
    </div>

    <div class="status-message"><p class="status-message-text"></p></div>
    <div class="submit-button">
        <button type="submit">Submit</button>
    </div>
</form>
</div>
 <script>
                document.addEventListener("DOMContentLoaded", function () {

                    // CLICK EVENT INPUT + AND -

                    // Handle quantity buttons using event delegation
                document.addEventListener('click', function(event) {
                    if (event.target.classList.contains('qty-btn-plus')) {
                        var container = event.target.closest('.qty-container');
                        var quantityInput = container.querySelector('.remaining-quantity');
                        var currentVal = parseInt(quantityInput.value);
                        var maxVal = parseInt(quantityInput.getAttribute('max'));

                        if (currentVal) {
                            quantityInput.value = currentVal + 1;
                        }
                    }

                    if (event.target.classList.contains('qty-btn-minus')) {
                        var container = event.target.closest('.qty-container');
                        var quantityInput = container.querySelector('.remaining-quantity');
                        var currentVal = parseInt(quantityInput.value);

                        if (currentVal > 1) {
                            quantityInput.value = currentVal - 1;
                        }
                    }
                });


                        const repeaterContainer = document.getElementById("assetRepeater");
                        const addRowButton = document.getElementById("addRow");
                        const assetForm = document.getElementById("assetForm");

                        const ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";


                        // Keep track of selected values
                        let selectedValues = new Set();

                        // Function to update disabled options
                        function updateDisabledOptions() {

                           // handle_quantity_click();
                            const allSelects = document.querySelectorAll('.asset-name');
                            selectedValues.clear();
                            
                            // Collect all currently selected values
                            allSelects.forEach(select => {
                                if (select.value) {
                                    selectedValues.add(select.value);
                                }
                            });

                            // Update disabled state for all options
                            allSelects.forEach(select => {
                                Array.from(select.options).forEach(option => {
                                    if (option.value && selectedValues.has(option.value) && option.value !== select.value) {
                                        option.disabled = true;
                                    } else {
                                        option.disabled = false;
                                    }
                                });
                            });
                        }


                        function handle_quantity_click(){

                                    // Handle quantity buttons using event delegation
                                    
                                    var buttonPlus = document.querySelectorAll(".qty-btn-plus");
                    var buttonMinus = document.querySelectorAll(".qty-btn-minus");

                    // Add click event listeners for the "plus" buttons
                    buttonPlus.forEach(function(button) {
                        button.addEventListener("click", function() {
                            
                            var container = this.closest(".qty-container");
                            var quantityInput = container.querySelector(".remaining-quantity");
                            var currentVal = Number(quantityInput.value);
                            var maxVal = Number(quantityInput.getAttribute('max'));

                            if (currentVal) {
                                quantityInput.value = currentVal + 1;
                            }
                        });
                    });

                    // Add click event listeners for the "minus" buttons
                    buttonMinus.forEach(function(button) {
                        button.addEventListener("click", function() {
                            
                            var container = this.closest(".qty-container");
                            var quantityInput = container.querySelector(".remaining-quantity");
                            var amount = Number(quantityInput.value);

                            // Prevent decrementing below 1
                            if (amount > 1) {
                                quantityInput.value = amount - 1;
                            }
                        });
                    });



                        }

                        // Function to handle select change and update max quantity
                        function handleSelectChange(select) {
                            const row = select.closest('.repeater-row');
                            const quantityInput = row.querySelector('.remaining-quantity');
                            const selectedOption = select.options[select.selectedIndex];
                            const remainingQty = selectedOption.getAttribute('data-remqty');                           
                            
                            // Set max attribute and current value if it exceeds max
                            quantityInput.setAttribute('max', remainingQty);
                            if (parseInt(quantityInput.value) > parseInt(remainingQty)) {
                                quantityInput.value = remainingQty;
                            }
                        }

                        // Add new row dynamically
                        addRowButton.addEventListener("click", () => {
                            const newRow = document.createElement("div");
                            newRow.classList.add("repeater-row");
                            newRow.innerHTML = `
                                <select name="asset_name[]" class="asset-name" required>
                                    <option value="">Select Asset Name</option>
                                    <?php foreach ($assets as $asset):
                                        $merge_name_maodel = esc_html($asset->asset_name) .' '. esc_html($asset->asset_model);
                                        $rem_qty = $asset->remaining_quantity;
                                        if($rem_qty == 0){ $rem_qty = $asset->total_quantity; } ?>
                                        <option value="<?php echo $merge_name_maodel; ?>" 
                                                data-totalqty="<?php echo esc_html($asset->total_quantity); ?>" 
                                                data-remqty="<?php echo esc_html($rem_qty); ?>"
                                                data-asset-id="<?php echo $asset->id; ?>">
                                            <?php echo $merge_name_maodel; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="qty-container">
                                    <button class="qty-btn-minus btn-light" type="button">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                    <input type="number" step="1" value="1" min="1" name="remaining_quantity[]" 
                                           class="remaining-quantity input-qty" required>
                                    <button class="qty-btn-plus btn-light" type="button">
                                        <span class="dashicons dashicons-plus"></span>
                                    </button>
                                </div>

                                <button type="button" class="remove-row">Remove</button>
                            `;
                            repeaterContainer.appendChild(newRow);

                            // Add select change event listener
                            const newSelect = newRow.querySelector('.asset-name');
                            newSelect.addEventListener('change', (e) => {
                                handleSelectChange(e.target);
                                updateDisabledOptions();
                            });

                            // Update disabled options immediately after adding new row
                            updateDisabledOptions();

                            // Add remove button functionality
                            newRow.querySelector(".remove-row").addEventListener("click", function () {
                                newRow.remove();
                                updateDisabledOptions(); // Update options when removing a row
                            });

                                              // Get the buttons
                                              handle_quantity_click();

                });


                    // Remove row functionality for existing rows
                    document.querySelectorAll(".remove-row").forEach(button => {
                            button.addEventListener("click", function () {
                                button.parentElement.remove();
                            });
                            //handle_quantity_click();

                        });

                        /* REMAINING QANTTIY MANAGE WHEN SELECT DROPDOWN VALUE CHANGE START */
                        /* REMAINING QANTTIY MANAGE WHEN SELECT DROPDOWN VALUE CHANGE END */

                        // Handle form submission
assetForm.addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent form from submitting normally
    
    const formData = {
        user_email: document.getElementById('user_name').value,
        user_id: document.getElementById('useridhide').value,
        action: 'st_stock_manage_user_asset',
        assets: []
    };

    // Collect data from all repeater rows
    document.querySelectorAll('.repeater-row').forEach(row => {
        const select = row.querySelector('.asset-name');
        const quantityInput = row.querySelector('.remaining-quantity');
        
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            formData.assets.push({
                asset_name: select.value,
                quantity: quantityInput.value,
                asset_id: selectedOption.getAttribute('data-asset-id'),
                total_quantity: selectedOption.getAttribute('data-totalqty'),
                remaining_quantity: selectedOption.getAttribute('data-remqty')
            });
        }
    });

    console.log('Form Data:', formData);

    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: ajaxurl,
        data: formData,
        success: function(msg){
            // console.log(msg);
            document.querySelector('.status-message-text').innerHTML = msg.message;

            setTimeout(function(){
                document.querySelector('.status-message-text').innerHTML = '';
            }, 5000);
        }
    });

    // Optional: Send data to server using AJAX
    // fetch('/your-endpoint', {
    //     method: 'POST',
    //     headers: {
    //         'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify(formData)
    // })
    // .then(response => response.json())
    // .then(data => {
    //     console.log('Success:', data);
    // })
    // .catch((error) => {
    //     console.error('Error:', error);
    // });
});



                    }); 
                    // END OF MAIN DOCUMENT READY FUNCTION




                </script>

<?php endif; ?>

</body>



 		<?php 
 		 return ob_get_clean();
 	}
 }

 new MemberManage();

