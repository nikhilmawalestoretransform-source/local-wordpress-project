            // Global variables
            const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
            const nonce = '<?php echo esc_js($nonce); ?>';
            let stockItems = [];
            let repairItems = [];
            let dataTable = null;
            let empDataTable = null;
            let repairDataTable = null;
            let currentEmpId = null;

            jQuery(document).ready(function($) {
                // Load stock items on page load
                loadStockItems();
                // Destroy existing DataTable instance before reinitializing

                // Handle form submissions
                $('#add-stock-form').on('submit', function(e) {
                    e.preventDefault();
                    saveStockItem('add');
                });
                
                $('#edit-stock-form').on('submit', function(e) {
                    e.preventDefault();
                    saveStockItem('edit');
                });

                // Load employee list when employee tab is shown
                $('.tab-btn').on('click', function() {
                    if ($(this).text().includes('Employee')) {
                        loadEmpList();
                    }
                    if ($(this).text().includes('Repaire')) {
                        loadRepairItems();
                        loadSerialNumbers();
                    }
                });

                // Handle repair form submissions
                $('#add-repair-form').on('submit', function(e) {
                    e.preventDefault();
                    saveRepairItem('add');
                });
                
                $('#edit-repair-form').on('submit', function(e) {
                    e.preventDefault();
                    saveRepairItem('edit');
                });

                // Set current date for repair date
                const today = new Date().toISOString().split('T')[0];
                $('#repair_date').val(today);
                $('#edit_repair_date').val(today);
            });

            function showMainTab(tabName, el) {
                // Hide all main tab contents
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all main tab buttons
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected main tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');

                // Load specific data based on tab
                if (tabName === 'employee-tab') {
                    loadEmpList();
                }
                if (tabName === 'repaire-tab') {
                    loadRepairItems();
                    loadSerialNumbers();
                }
            }

            function showItemSubTab(tabName, el) {
                // Hide all item sub tab contents
                document.querySelectorAll('.item-sub-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all item sub tab buttons
                document.querySelectorAll('.item-sub-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected item sub tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');
                
                // If switching to list tab, reload items
                if (tabName === 'list-items-tab') {
                    loadStockItems();
                }
            }

            function showRepairSubTab(tabName, el) {
                // Hide all repair sub tab contents
                document.querySelectorAll('.repair-sub-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all repair sub tab buttons
                document.querySelectorAll('.repair-sub-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected repair sub tab and activate button
                document.getElementById(tabName).classList.add('active');
                el.classList.add('active');
                
                // If switching to list tab, reload repair items
                if (tabName === 'list-repairs-tab') {
                    loadRepairItems();
                }
                if (tabName === 'add-repair-tab') {
                    loadSerialNumbers();
                }
            }

            function showSweetAlert(message, type = 'success') {
                const title = type === 'success' ? 'Success!' : 'Error!';
                const icon = type === 'success' ? 'success' : 'error';
                
                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    confirmButtonColor: type === 'success' ? '#28a745' : '#dc3545',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: true
                });
            }

            function setLoading(formType, loading) {
                const submitBtn = document.getElementById(formType + '_submit_button');
                const form = document.getElementById(formType + '-stock-form');
                
                if (loading) {
                    submitBtn.textContent = '⏳ Processing...';
                    submitBtn.disabled = true;
                    if (form) form.classList.add('loading');
                } else {
                    if (formType === 'add') {
                        submitBtn.textContent = '✅ Add Stock Item';
                    } else if (formType === 'edit') {
                        submitBtn.textContent = '🔁 Update Stock Item';
                    } else if (formType === 'add_repair') {
                        submitBtn.textContent = '✅ Add Repair Record';
                    } else if (formType === 'edit_repair') {
                        submitBtn.textContent = '🔁 Update Repair Record';
                    }
                    submitBtn.disabled = false;
                    if (form) form.classList.remove('loading');
                }
            }

            function setRepairLoading(formType, loading) {
                const submitBtn = document.getElementById(formType + '_submit_button');
                const form = document.getElementById(formType + '-repair-form');
                
                if (loading) {
                    submitBtn.textContent = '⏳ Processing...';
                    submitBtn.disabled = true;
                    if (form) form.classList.add('loading');
                } else {
                    if (formType === 'add_repair') {
                        submitBtn.textContent = '✅ Add Repair Record';
                    } else if (formType === 'edit_repair') {
                        submitBtn.textContent = '🔁 Update Repair Record';
                    }
                    submitBtn.disabled = false;
                    if (form) form.classList.remove('loading');
                }
            }

            // Unique Serial Number Validation
            function checkSerialNumber(formType) {
                const serialNumberField = document.getElementById(formType === 'add' ? 'serial_number' : 'edit_serial_number');
                const errorField = document.getElementById(`serial_error_${formType}`);
                const submitButton = document.getElementById(`${formType}_submit_button`);
                const serialNumber = serialNumberField.value.trim();

                if (!serialNumber) {
                    errorField.style.display = 'none';
                    submitButton.disabled = false;
                    return;
                }

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'check_serial_number',
                        serial_number: serialNumber,
                        form_type: formType,
                        item_id: formType === 'edit' ? document.getElementById('edit_item_id').value : 0,
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            }
                        }
                        
                        if (responseData.success) {
                            if (responseData.data.exists) {
                                errorField.textContent = '❌ Serial number already exists!';
                                errorField.style.display = 'block';
                                errorField.style.color = '#dc3545';
                                submitButton.disabled = true;
                            } else {
                                errorField.textContent = '✅ Serial number available';
                                errorField.style.display = 'block';
                                errorField.style.color = '#28a745';
                                submitButton.disabled = false;
                            }
                        } else {
                            errorField.textContent = '❌ Error checking serial number';
                            errorField.style.display = 'block';
                            errorField.style.color = '#dc3545';
                            submitButton.disabled = true;
                        }
                    },
                    error: function() {
                        errorField.textContent = '❌ Error checking serial number';
                        errorField.style.display = 'block';
                        errorField.style.color = '#dc3545';
                        submitButton.disabled = true;
                    }
                });
            }

            // Load serial numbers for repair form
            function loadSerialNumbers() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_serial_numbers',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            }
                        }
                        
                        const select = document.getElementById('repair_serial_number');
                        if (responseData.success) {
                            select.innerHTML = '<option value="">Select Serial Number</option>';
                            responseData.data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.serial_number;
                                option.textContent = `${item.serial_number} - ${item.asset_type} - ${item.brand_model}`;
                                option.setAttribute('data-asset-type', item.asset_type);
                                option.setAttribute('data-brand-model', item.brand_model);
                                select.appendChild(option);
                            });
                        } else {
                            select.innerHTML = '<option value="">Error loading serial numbers</option>';
                        }
                    },
                    error: function() {
                        const select = document.getElementById('repair_serial_number');
                        select.innerHTML = '<option value="">Error loading serial numbers</option>';
                    }
                });
            }

            // Load asset details when serial number is selected
            function loadAssetDetails() {
                const select = document.getElementById('repair_serial_number');
                const selectedOption = select.options[select.selectedIndex];
                
                if (selectedOption.value) {
                    document.getElementById('repair_asset_type').value = selectedOption.getAttribute('data-asset-type');
                    document.getElementById('repair_brand_model').value = selectedOption.getAttribute('data-brand-model');
                } else {
                    document.getElementById('repair_asset_type').value = '';
                    document.getElementById('repair_brand_model').value = '';
                }
            }

            // Stock Management Functions
            function loadStockItems() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_stock_items',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('stock-items-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading items...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('stock-items-container').innerHTML = responseData.data.html;
                            stockItems = responseData.data.items || [];
                            
                            // FIX: Destroy existing instance before reinitializing
                            if (dataTable) {
                                dataTable.destroy();
                                dataTable = null;
                            }
                            
                            // FIX: Initialize with delay
                            setTimeout(function() {
                                initializeDataTable();
                            }, 100);
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        const responseText = xhr.responseText;
                        const jsonMatch = responseText.match(/\{.*\}/s);
                        if (jsonMatch) {
                            try {
                                const responseData = JSON.parse(jsonMatch[0]);
                                if (responseData.success) {
                                    document.getElementById('stock-items-container').innerHTML = responseData.data.html;
                                    stockItems = responseData.data.items || [];
                                    
                                    // FIX: Apply same fix for error case
                                    if (dataTable) {
                                        dataTable.destroy();
                                        dataTable = null;
                                    }
                                    
                                    setTimeout(function() {
                                        initializeDataTable();
                                    }, 100);
                                    return;
                                }
                            } catch (e) {}
                        }
                        showSweetAlert('Failed to load items. Please try again.', 'error');
                    }
                });
            }

            function initializeEmpDataTable() {
                // Check if table exists
                const empTable = document.getElementById('emp-table');
                if (!empTable) {
                    console.log('Employee table not found');
                    return;
                }
                
                // Check if DataTable is already initialized
                if (jQuery.fn.DataTable.isDataTable('#emp-table')) {
                    empDataTable = jQuery('#emp-table').DataTable();
                    return;
                }
                
                // Destroy existing instance if it exists
                if (empDataTable) {
                    empDataTable.destroy();
                    empDataTable = null;
                }
                
                empDataTable = jQuery('#emp-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            function saveStockItem(formType) {
                const form = document.getElementById(formType + '-stock-form');
                const formData = new FormData(form);
                formData.append('nonce', nonce);

                setLoading(formType, true);

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        setLoading(formType, false);
                        
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            form.reset();
                            loadStockItems();
                            
                            if (formType === 'edit') {
                                showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));
                                resetEditForm();
                            }
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        setLoading(formType, false);
                        const responseText = xhr.responseText;
                        const jsonMatch = responseText.match(/\{.*\}/s);
                        if (jsonMatch) {
                            try {
                                const responseData = JSON.parse(jsonMatch[0]);
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    form.reset();
                                    loadStockItems();
                                    if (formType === 'edit') {
                                        showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));
                                        resetEditForm();
                                    }
                                    return;
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                    return;
                                }
                            } catch (e) {}
                        }
                        showSweetAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editItem(id) {
                const item = stockItems.find(item => parseInt(item.id) === parseInt(id));
                if (!item) {
                    showSweetAlert('Item not found.', 'error');
                    return;
                }

                showItemSubTab('edit-item-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));

                // FIX: Use correct element IDs for edit form
                document.getElementById('edit_asset_type').value = item.asset_type || '';
                document.getElementById('edit_brand_model').value = item.brand_model || '';
                document.getElementById('edit_serial_number').value = item.serial_number || '';
                document.getElementById('edit_quantity').value = item.quantity || '1';
                document.getElementById('edit_price').value = item.price || '';
                document.getElementById('edit_status').value = item.status || '';
                document.getElementById('edit_location').value = item.location || '';
                document.getElementById('edit_date_purchased').value = item.date_purchased || '';
                document.getElementById('edit_warranty_expiry').value = item.warranty_expiry || '';
                document.getElementById('edit_remarks').value = item.remarks || '';
                document.getElementById('edit_item_id').value = item.id;

                // Clear serial number validation
                document.getElementById('serial_error_edit').style.display = 'none';
                document.getElementById('edit_submit_button').disabled = false;
            }

            function resetEditForm() {
                document.getElementById('edit-stock-form').reset();
                document.getElementById('edit_item_id').value = '';
                document.getElementById('serial_error_edit').style.display = 'none';
                document.getElementById('edit_submit_button').disabled = false;
                showItemSubTab('list-items-tab', document.querySelector('.item-sub-tab-btn:nth-child(2)'));
            }

            function deleteItem(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_stock_item',
                                item_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                let responseData = response;
                                if (typeof response === 'string') {
                                    const jsonMatch = response.match(/\{.*\}/s);
                                    if (jsonMatch) {
                                        try {
                                            responseData = JSON.parse(jsonMatch[0]);
                                        } catch (e) {
                                            showSweetAlert('Response format error', 'error');
                                            return;
                                        }
                                    } else {
                                        showSweetAlert('Invalid response format', 'error');
                                        return;
                                    }
                                }
                                
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    loadStockItems();
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                const responseText = xhr.responseText;
                                const jsonMatch = responseText.match(/\{.*\}/s);
                                if (jsonMatch) {
                                    try {
                                        const responseData = JSON.parse(jsonMatch[0]);
                                        if (responseData.success) {
                                            showSweetAlert(responseData.data, 'success');
                                            loadStockItems();
                                            return;
                                        } else {
                                            showSweetAlert('Error: ' + responseData.data, 'error');
                                            return;
                                        }
                                    } catch (e) {}
                                }
                                showSweetAlert('Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            }

            // Repair Management Functions
            function loadRepairItems() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_repair_items',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('repair-items-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading repair records...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('repair-items-container').innerHTML = responseData.data.html;
                            repairItems = responseData.data.items || [];
                            initializeRepairDataTable();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Failed to load repair records. Please try again.', 'error');
                    }
                });
            }

            function initializeRepairDataTable() {
                if (repairDataTable) {
                    repairDataTable.destroy();
                }
                
                repairDataTable = jQuery('#repair-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            function saveRepairItem(formType) {
                const form = document.getElementById(formType + '-repair-form');
                const formData = new FormData(form);
                formData.append('nonce', nonce);

                setRepairLoading(formType + '_repair', true);

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        setRepairLoading(formType + '_repair', false);
                        
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            form.reset();
                            loadRepairItems();
                            loadSerialNumbers();
                            
                            if (formType === 'edit') {
                                showRepairSubTab('list-repairs-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));
                                resetRepairEditForm();
                            }

                            // Reset date to today
                            const today = new Date().toISOString().split('T')[0];
                            if (formType === 'add') {
                                document.getElementById('repair_date').value = today;
                            }
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        setRepairLoading(formType + '_repair', false);
                        showSweetAlert('Server error. Please try again.', 'error');
                    }
                });
            }

            function editRepairItem(id) {
                const item = repairItems.find(item => parseInt(item.id) === parseInt(id));
                if (!item) {
                    showSweetAlert('Repair record not found.', 'error');
                    return;
                }

                showRepairSubTab('edit-repair-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));

                document.getElementById('edit_repair_serial_number').value = item.serial_number || '';
                document.getElementById('edit_repair_asset_type').value = item.asset_type || '';
                document.getElementById('edit_repair_brand_model').value = item.brand_model || '';
                document.getElementById('edit_repair_date').value = item.repair_date || '';
                document.getElementById('edit_return_date').value = item.return_date || '';
                document.getElementById('edit_repair_status').value = item.status || '';
                document.getElementById('edit_repair_remarks').value = item.repair_remarks || '';
                document.getElementById('edit_repair_id').value = item.id;
            }

            function resetRepairEditForm() {
                document.getElementById('edit-repair-form').reset();
                document.getElementById('edit_repair_id').value = '';
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('edit_repair_date').value = today;
                showRepairSubTab('list-repairs-tab', document.querySelector('.repair-sub-tab-btn:nth-child(2)'));
            }

            function deleteRepairItem(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_repair_item',
                                repair_id: id,
                                nonce: nonce
                            },
                            success: function(response) {
                                let responseData = response;
                                if (typeof response === 'string') {
                                    const jsonMatch = response.match(/\{.*\}/s);
                                    if (jsonMatch) {
                                        try {
                                            responseData = JSON.parse(jsonMatch[0]);
                                        } catch (e) {
                                            showSweetAlert('Response format error', 'error');
                                            return;
                                        }
                                    } else {
                                        showSweetAlert('Invalid response format', 'error');
                                        return;
                                    }
                                }
                                
                                if (responseData.success) {
                                    showSweetAlert(responseData.data, 'success');
                                    loadRepairItems();
                                } else {
                                    showSweetAlert('Error: ' + responseData.data, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                showSweetAlert('Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            }

            // Employee Management Functions (keep existing)
            function loadEmpList() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_emp_list',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        document.getElementById('emp-list-container').innerHTML = 
                            '<div style="text-align: center; padding: 20px;">Loading employees...</div>';
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Data format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            document.getElementById('emp-list-container').innerHTML = responseData.data.html;
                            initializeEmpDataTable();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Failed to load employees. Please try again.', 'error');
                    }
                });
            }

            function initializeEmpDataTable() {
                if (empDataTable) {
                    empDataTable.destroy();
                }
                
                empDataTable = jQuery('#emp-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 25, 50],
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next →",
                            "previous": "← Previous"
                        }
                    },
                    "responsive": true
                });
            }

            // Asset Assignment Functions (keep existing)
            function openAssignPopup(empId, empName) {
                currentEmpId = empId;
                document.getElementById('assign_emp_id').value = empId;
                
                // Load employee dropdown
                loadEmployeeDropdown();
                
                // Set the selected employee
                document.getElementById('assign_emp_name').value = empId;
                
                // Load previously assigned assets
                loadAssignedAssets(empId);
                
                document.getElementById('assign-assets-popup').style.display = 'flex';
            }

            function closeAssignPopup() {
                document.getElementById('assign-assets-popup').style.display = 'none';
                // Reset assets container
                document.getElementById('assets-container').innerHTML = '';
                currentEmpId = null;
            }

            function loadEmployeeDropdown() {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_emp_list',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            const select = document.getElementById('assign_emp_name');
                            select.innerHTML = '<option value="">Select Employee</option>';
                            
                            responseData.data.items.forEach(emp => {
                                const option = document.createElement('option');
                                option.value = emp.id;
                                option.textContent = emp.emp_name;
                                select.appendChild(option);
                            });
                        }
                    }
                });
            }

            function loadAssignedAssets(empId) {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_assigned_assets',
                        emp_id: empId,
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    return;
                                }
                            } else {
                                console.error('Invalid response format');
                                return;
                            }
                        }
                        
                        const assetsContainer = document.getElementById('assets-container');
                        assetsContainer.innerHTML = '';
                        
                        if (responseData.success && responseData.data.length > 0) {
                            // Load previously assigned assets
                            responseData.data.forEach((asset, index) => {
                                addAssetRow(asset.asset_type, asset.brand_model);
                            });
                        } else {
                            // Add one empty row if no assets assigned
                            addAssetRow();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading assigned assets:', error);
                        // Add one empty row on error
                        addAssetRow();
                    }
                });
            }

            function addAssetRow(assetType = '', brandModel = '') {
                const container = document.getElementById('assets-container');
                const index = container.children.length;
                
                const row = document.createElement('div');
                row.className = 'asset-row';
                row.setAttribute('data-index', index);
                row.innerHTML = `
                    <div class="form-group">
                        <label>🏷️ Asset Type</label>
                        <select class="asset-type-select" data-index="${index}" required onchange="loadBrandModels(this)">
                            <option value="">Select Asset Type</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🏭 Brand/Model</label>
                        <select class="brand-model-select" data-index="${index}" required>
                            <option value="">Select Brand/Model</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-danger" onclick="removeAssetRow(${index})">🗑️ Remove</button>
                    </div>
                `;
                
                container.appendChild(row);
                
                // Load asset types for the new row
                loadAssetTypesForRow(index, assetType, brandModel);
            }

            function loadAssetTypesForRow(index, selectedAssetType = '', selectedBrandModel = '') {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_asset_types',
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            const select = document.querySelector(`.asset-type-select[data-index="${index}"]`);
                            select.innerHTML = '<option value="">Select Asset Type</option>';
                            
                            responseData.data.forEach(assetType => {
                                const option = document.createElement('option');
                                option.value = assetType;
                                option.textContent = assetType;
                                if (assetType === selectedAssetType) {
                                    option.selected = true;
                                }
                                select.appendChild(option);
                            });
                            
                            // If an asset type was preselected, load its brand models
                            if (selectedAssetType) {
                                loadBrandModelsForRow(index, selectedAssetType, selectedBrandModel);
                            }
                        }
                    }
                });
            }

            function loadBrandModels(selectElement) {
                const index = selectElement.getAttribute('data-index');
                const assetType = selectElement.value;
                
                if (!assetType) {
                    const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                    brandModelSelect.innerHTML = '<option value="">Select Brand/Model</option>';
                    return;
                }

                loadBrandModelsForRow(index, assetType);
            }

            function loadBrandModelsForRow(index, assetType, selectedBrandModel = '') {
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_brands_by_asset',
                        asset_type: assetType,
                        nonce: nonce
                    },
                    success: function(response) {
                        const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                        
                        if (typeof response === 'string') {
                            brandModelSelect.innerHTML = response;
                            
                            // If a brand model was preselected, select it
                            if (selectedBrandModel) {
                                setTimeout(() => {
                                    brandModelSelect.value = selectedBrandModel;
                                }, 100);
                            }
                        } else {
                            brandModelSelect.innerHTML = '<option value="">Error loading data</option>';
                        }
                    },
                    error: function() {
                        const brandModelSelect = document.querySelector(`.brand-model-select[data-index="${index}"]`);
                        brandModelSelect.innerHTML = '<option value="">Error loading data</option>';
                    }
                });
            }

            function removeAssetRow(index) {
                const row = document.querySelector(`.asset-row[data-index="${index}"]`);
                if (row) {
                    row.remove();
                    
                    // Reindex remaining rows
                    const rows = document.querySelectorAll('.asset-row');
                    rows.forEach((row, newIndex) => {
                        row.setAttribute('data-index', newIndex);
                        const assetSelect = row.querySelector('.asset-type-select');
                        const brandSelect = row.querySelector('.brand-model-select');
                        assetSelect.setAttribute('data-index', newIndex);
                        brandSelect.setAttribute('data-index', newIndex);
                        
                        // Update remove button onclick
                        const removeBtn = row.querySelector('.btn-danger');
                        removeBtn.setAttribute('onclick', `removeAssetRow(${newIndex})`);
                    });
                }
            }

            function assignAssetsToEmp() {
                const empId = document.getElementById('assign_emp_id').value;
                const assetRows = document.querySelectorAll('.asset-row');
                const assets = [];

                // FIX: Remove validation for at least one asset
                // Allow empty assignment (removing all assets from employee)
                let isValid = true;
                assetRows.forEach(row => {
                    const assetType = row.querySelector('.asset-type-select').value;
                    const brandModel = row.querySelector('.brand-model-select').value;
                    
                    // Only add if both fields are filled
                    if (assetType && brandModel) {
                        assets.push({
                            asset_type: assetType,
                            brand_model: brandModel
                        });
                    } else if (assetType || brandModel) {
                        // If only one field is filled, show error
                        isValid = false;
                        showSweetAlert('Please fill all asset fields or remove incomplete rows.', 'error');
                        return;
                    }
                });

                // FIX: Allow empty assets array (no validation for at least one asset)
                // This allows removing all assets from an employee

                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'assign_assets_to_emp',
                        emp_id: empId,
                        assets: JSON.stringify(assets),
                        nonce: nonce
                    },
                    success: function(response) {
                        let responseData = response;
                        if (typeof response === 'string') {
                            const jsonMatch = response.match(/\{.*\}/s);
                            if (jsonMatch) {
                                try {
                                    responseData = JSON.parse(jsonMatch[0]);
                                } catch (e) {
                                    showSweetAlert('Response format error', 'error');
                                    return;
                                }
                            } else {
                                showSweetAlert('Invalid response format', 'error');
                                return;
                            }
                        }
                        
                        if (responseData.success) {
                            showSweetAlert(responseData.data, 'success');
                            closeAssignPopup();
                            loadEmpList();
                        } else {
                            showSweetAlert('Error: ' + responseData.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showSweetAlert('Server error. Please try again.', 'error');
                    }

                });
            }


        