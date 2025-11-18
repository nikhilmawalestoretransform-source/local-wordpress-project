jQuery(document).ready(function($) {
    'use strict';

    let itemsChart = null;
    let priceChart = null;

    // Initialize
    loadItems();

    // Add item form submission
    $('#custom-crud-add-form').on('submit', function(e) {
        e.preventDefault();
        addItem();
    });

    // Update item button
    $('#update-item-btn').on('click', function() {
        updateItem();
    });

    // Load items and statistics
    function loadItems() {
        $.ajax({
            url: custom_crud_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_crud_get_items',
                nonce: custom_crud_ajax.nonce
            },
            beforeSend: function() {
                $('#custom-crud-items-list').html(
                    '<tr><td colspan="8" class="text-center text-muted py-5">' +
                    '<div class="spinner-border text-primary me-2" role="status"></div>' +
                    'Loading your items...' +
                    '</td></tr>'
                );
            },
            success: function(response) {
                if (response.success) {
                    displayItems(response.data);
                    updateStatistics(response.data);
                    updateCharts(response.data);
                    updateRecentActivity(response.data);
                } else {
                    showAlert('Error loading items: ' + response.data, 'error');
                }
            },
            error: function() {
                showAlert('Failed to load items. Please try again.', 'error');
            }
        });
    }

    // Update statistics
    function updateStatistics(items) {
        const totalItems = items.length;
        const activeItems = items.filter(item => item.item_status === 'active').length;
        const outOfStock = items.filter(item => item.item_status === 'out_of_stock').length;
        const totalValue = items.reduce((sum, item) => sum + (parseFloat(item.item_price) * parseInt(item.item_quantity)), 0);

        $('#total-items').text(totalItems);
        $('#active-items').text(activeItems);
        $('#out-of-stock').text(outOfStock);
        $('#total-value').text('$' + totalValue.toFixed(2));
        $('#items-count').text(totalItems + ' item' + (totalItems !== 1 ? 's' : ''));
    }

    // Update charts
    function updateCharts(items) {
        updateStatusChart(items);
        updatePriceChart(items);
    }

    function updateStatusChart(items) {
        const ctx = document.getElementById('statusChart').getContext('2d');
        
        const statusCounts = {
            active: items.filter(item => item.item_status === 'active').length,
            inactive: items.filter(item => item.item_status === 'inactive').length,
            out_of_stock: items.filter(item => item.item_status === 'out_of_stock').length
        };

        if (itemsChart) {
            itemsChart.destroy();
        }

        itemsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Out of Stock'],
                datasets: [{
                    data: [statusCounts.active, statusCounts.inactive, statusCounts.out_of_stock],
                    backgroundColor: [
                        '#4facfe',
                        '#a8a8a8',
                        '#ff6b6b'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '70%'
            }
        });
    }

    function updatePriceChart(items) {
        const ctx = document.getElementById('priceChart').getContext('2d');
        
        const prices = items.map(item => parseFloat(item.item_price)).filter(price => price > 0);
        
        if (priceChart) {
            priceChart.destroy();
        }

        priceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['$0-10', '$10-50', '$50-100', '$100+'],
                datasets: [{
                    label: 'Number of Items',
                    data: [
                        prices.filter(p => p <= 10).length,
                        prices.filter(p => p > 10 && p <= 50).length,
                        prices.filter(p => p > 50 && p <= 100).length,
                        prices.filter(p => p > 100).length
                    ],
                    backgroundColor: '#667eea',
                    borderColor: '#764ba2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Update recent activity
    function updateRecentActivity(items) {
        const recentItems = items.slice(0, 3);
        const activityHtml = $('#recent-activity');
        
        if (recentItems.length === 0) {
            activityHtml.html('<div class="activity-item text-center text-muted"><small>No recent activity</small></div>');
            return;
        }

        let html = '';
        recentItems.forEach(item => {
            const timeAgo = getTimeAgo(item.created_at);
            html += `
                <div class="activity-item mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${escapeHtml(item.item_name)}</strong>
                            <div class="small text-muted">Added ${timeAgo}</div>
                        </div>
                        <span class="badge ${getStatusBadgeClass(item.item_status)}">${formatStatus(item.item_status)}</span>
                    </div>
                </div>
            `;
        });
        
        activityHtml.html(html);
    }

    // Display items in table
    function displayItems(items) {
        if (items.length === 0) {
            $('#custom-crud-items-list').html(
                '<tr><td colspan="8" class="text-center text-muted py-5">' +
                '<i class="fas fa-inbox fa-2x mb-3 d-block"></i>' +
                '<h5>No items found</h5>' +
                '<p class="mb-0">Add your first item using the form above</p>' +
                '</td></tr>'
            );
            return;
        }

        let html = '';
        items.forEach(function(item) {
            html += `
                <tr class="fade-in-up">
                    <td><strong>#${item.id}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cube me-2 text-primary"></i>
                            <div>
                                <strong>${escapeHtml(item.item_name)}</strong>
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(item.item_description || '<em class="text-muted">No description</em>')}</td>
                    <td>
                        <span class="badge bg-light text-dark">
                            $${parseFloat(item.item_price).toFixed(2)}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            ${item.item_quantity}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge ${getStatusBadgeClass(item.item_status)}">
                            ${formatStatus(item.item_status)}
                        </span>
                    </td>
                    <td><small class="text-muted">${formatDate(item.created_at)}</small></td>
                    <td class="action-buttons">
                        <button class="btn btn-warning btn-sm edit-btn" data-id="${item.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#custom-crud-items-list').html(html);

        // Attach event listeners to action buttons
        $('.edit-btn').on('click', function() {
            const itemId = $(this).data('id');
            editItem(itemId);
        });

        $('.delete-btn').on('click', function() {
            const itemId = $(this).data('id');
            deleteItemWithConfirmation(itemId);
        });
    }

    // Add new item
    function addItem() {
        const formData = $('#custom-crud-add-form').serialize();
        
        $.ajax({
            url: custom_crud_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=custom_crud_add_item&nonce=' + custom_crud_ajax.nonce,
            beforeSend: function() {
                $('#custom-crud-add-form button[type="submit"]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i>Adding...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('🎉 ' + response.data.message, 'success');
                    $('#custom-crud-add-form')[0].reset();
                    loadItems();
                } else {
                    showAlert('❌ ' + response.data, 'error');
                }
            },
            error: function() {
                showAlert('❌ Failed to add item. Please try again.', 'error');
            },
            complete: function() {
                $('#custom-crud-add-form button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-plus-circle me-1"></i>Add Item');
            }
        });
    }

    // Edit item - load data into modal
    function editItem(itemId) {
        $.ajax({
            url: custom_crud_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_crud_get_item',
                item_id: itemId,
                nonce: custom_crud_ajax.nonce
            },
            beforeSend: function() {
                $('#update-item-btn').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i>Loading...');
            },
            success: function(response) {
                if (response.success) {
                    const item = response.data;
                    $('#edit_item_id').val(item.id);
                    $('#edit_item_name').val(item.item_name);
                    $('#edit_item_description').val(item.item_description);
                    $('#edit_item_price').val(item.item_price);
                    $('#edit_item_quantity').val(item.item_quantity);
                    $('#edit_item_status').val(item.item_status);
                    
                    $('#editItemModal').modal('show');
                } else {
                    showAlert('❌ Error loading item: ' + response.data, 'error');
                }
            },
            error: function() {
                showAlert('❌ Failed to load item. Please try again.', 'error');
            },
            complete: function() {
                $('#update-item-btn').prop('disabled', false)
                    .html('<i class="fas fa-save me-1"></i>Update Item');
            }
        });
    }

    // Update item
    function updateItem() {
        const formData = $('#custom-crud-edit-form').serialize();
        
        $.ajax({
            url: custom_crud_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=custom_crud_update_item&nonce=' + custom_crud_ajax.nonce,
            beforeSend: function() {
                $('#update-item-btn').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('✅ ' + response.data, 'success');
                    $('#editItemModal').modal('hide');
                    loadItems();
                } else {
                    showAlert('❌ ' + response.data, 'error');
                }
            },
            error: function() {
                showAlert('❌ Failed to update item. Please try again.', 'error');
            },
            complete: function() {
                $('#update-item-btn').prop('disabled', false)
                    .html('<i class="fas fa-save me-1"></i>Update Item');
            }
        });
    }

    // Delete item with confirmation
    function deleteItemWithConfirmation(itemId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            backdrop: 'rgba(0,0,0,0.8)'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteItem(itemId);
            }
        });
    }

    // Delete item
    function deleteItem(itemId) {
        $.ajax({
            url: custom_crud_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_crud_delete_item',
                item_id: itemId,
                nonce: custom_crud_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert('✅ ' + response.data, 'success');
                    loadItems();
                } else {
                    showAlert('❌ ' + response.data, 'error');
                }
            },
            error: function() {
                showAlert('❌ Failed to delete item. Please try again.', 'error');
            }
        });
    }

    // Utility functions
    function showAlert(message, type = 'info') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatStatus(status) {
        const statusMap = {
            'active': 'Active',
            'inactive': 'Inactive',
            'out_of_stock': 'Out of Stock'
        };
        return statusMap[status] || status;
    }

    function getStatusBadgeClass(status) {
        const classMap = {
            'active': 'status-active',
            'inactive': 'status-inactive',
            'out_of_stock': 'status-out_of_stock'
        };
        return classMap[status] || 'status-inactive';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return formatDate(dateString);
    }

    // Quick action functions
    function exportData() {
        showAlert('Export feature coming soon!', 'info');
    }

    function showHelp() {
        Swal.fire({
            title: 'Need Help?',
            html: `
                <div class="text-start">
                    <h6>Quick Tips:</h6>
                    <ul>
                        <li>Use the form to add new items</li>
                        <li>Click edit icon to modify items</li>
                        <li>Use status to track item availability</li>
                        <li>Charts update automatically</li>
                    </ul>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Got it!'
        });
    }

    // Reset edit form when modal is hidden
    $('#editItemModal').on('hidden.bs.modal', function() {
        $('#custom-crud-edit-form')[0].reset();
    });

    // Auto-refresh every 30 seconds
    setInterval(loadItems, 30000);
});