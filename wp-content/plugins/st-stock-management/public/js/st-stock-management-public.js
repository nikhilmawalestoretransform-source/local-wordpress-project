(function( $ ) {
	'use strict';

	$(function() {

		/* HANDLE ASSET SUBMIT EVENT */
		$('#stock-management-form').on('submit', function (e) {
	        e.preventDefault(); // Prevent the default form submission

			// Determine if this is an update or a new submission
			var isUpdate = !!$('input[name="id"]').val(); // If `id` input exists, it's an update

			var action = isUpdate ? 'update_stock_entry' : 'submit_stock_form'; // Adjust action name accordingly


			 // Collect form data
		    var formData = {
		        action: action,
		        id: $('input[name="id"]').val(),
		        asset_company: $('input[name="asset_company"]').val(),
		        asset_name: $('input[name="asset_name"]').val(),
		        asset_model: $('input[name="asset_model"]').val(),
		        asset_price: $('input[name="asset_price"]').val(),
		        asset_purchase_date: $('input[name="asset_purchase_date"]').val(),
		        total_quantity: $('input[name="total_quantity"]').val(),
		        item_id: jQuery('select[name="item_id"]').val(),
		    };


	        // Serialize form data
	       // var formData = $(this).serialize();

	        // Display a loading message or spinner (optional)
	        $('#stock-management-form button[type="submit"]').text('Submitting...').prop('disabled', true);

	        // Perform AJAX request
	        $.ajax({
	            url: stockManagementAjax.ajax_url, // AJAX URL from localized script
	            type: 'POST',
	            data: formData,
	            success: function (response) {
	                if (response.success) {
	                    alert(response.data); // Show success message
	                    $('#stock-management-form')[0].reset(); // Reset the form
	                } else {
	                    alert('An error occurred: ' + response.data); // Show error message
	                }
	            },
	            error: function (xhr, status, error) {
	                alert('AJAX error: ' + error); // Handle AJAX error
	            },
	            complete: function () {
	                // Re-enable the submit button and restore the text
	                $('#stock-management-form button[type="submit"]').text('Submit').prop('disabled', false);

	                /* REMOVE EDIT PARAMETER FROM URL IF EXIST START */

						const url = new URL(window.location.href);

						if (url.searchParams.has('edit')) {
						// Remove the `edit` parameter
						url.searchParams.delete('edit');

						// Update the URL without reloading the page
						window.history.replaceState({}, document.title, url.toString());
						}
    
	                /* REMOVE EDIT PARAMETER FROM URL IF EXIST END */
	                location.reload();
	            }
	        });
	    });
		/* HANDLE ASSET SUBMIT EVENT END */

		/* HANDLE EDIT EVENT START */

		$(document).on('click', '.delete-stock-entry', function () {
		    if (!confirm('Are you sure you want to delete this entry?')) {
		        return;
		    }

		    var entryId = $(this).data('id');

		    $.ajax({
		        url: stockManagementAjax.ajax_url,
		        type: 'POST',
		        data: {
		            action: 'delete_stock_entry',
		            id: entryId,
		        },
		        success: function (response) {
		            if (response.success) {
		                alert(response.data);
		                location.reload(); // Reload the page to update the table
		            } else {
		                alert('Error: ' + response.data);
		            }
		        },
		        error: function (xhr, status, error) {
		            alert('AJAX error: ' + error);
		        },
		    });
		});



		/* HANDLE EDIT EVENT EDIT */

	});


	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );
