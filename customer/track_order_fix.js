// Script to fix modal initialization in track_order.php

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the orderModal as a Bootstrap modal object
    let orderModalElement = document.getElementById('orderModal');
    let orderModal = new bootstrap.Modal(orderModalElement);

    // Expose the openModal function globally
    window.openModal = function(orderId, totalAmount, downpayment, paymentMethod, paymentStatus, createdAt, completionDate, designOption, printingType, quantity, size, color, instructions) {
        // Update modal title and view details link
        document.getElementById("orderModalLabel").innerHTML = `Order #${orderId}`;
        document.getElementById("view-full-details").href = `view_order.php?id=${orderId}`;
        
        // Show loading state
        document.getElementById("order-summary-content").innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading order details...</p></div>';
        
        // Make sure the modal is shown before the fetch request
        orderModal.show();
        
        // Parse order status from server
        fetch(`get_order_tracking.php?id=${orderId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Order summary section
                let orderSummaryContent = `
                    <div class="summary-section">
                        <div class="summary-row">
                            <div class="summary-label">Order Type</div>
                            <div class="summary-value">${printingType ? 'Sublimation' : 'Tailoring'}</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Order Date</div>
                            <div class="summary-value">${createdAt}</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Expected Delivery</div>
                            <div class="summary-value">${completionDate}</div>
                        </div>
                    </div>
                    
                    <div class="summary-section">
                        <div class="summary-header">Payment Details</div>
                        <div class="summary-row">
                            <div class="summary-label">Payment Method</div>
                            <div class="summary-value">${paymentMethod.toUpperCase()}</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Status</div>
                            <div class="summary-value">
                                <span class="shopee-badge ${paymentStatus.replace('_', '-')}-badge">
                                    ${paymentStatus.replace(/_/g, ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-section price-section">
                        <div class="summary-row">
                            <div class="summary-label">Total Amount</div>
                            <div class="summary-value">₱${totalAmount}</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Downpayment</div>
                            <div class="summary-value">₱${downpayment}</div>
                        </div>
                        <div class="summary-row total-row">
                            <div class="summary-label">Remaining Balance</div>
                            <div class="summary-value">₱${(parseFloat(totalAmount.replace(/,/g, '')) - parseFloat(downpayment.replace(/,/g, ''))).toFixed(2)}</div>
                        </div>
                    </div>`;
                
                document.getElementById("order-summary-content").innerHTML = orderSummaryContent;
                
                // Build the Shopee-style tracking timeline
                const orderStatus = data.status || 'pending_approval';
                
                // Define the steps in the order process
                const steps = [
                    { id: 'pending_approval', label: 'Order Placed', icon: 'shopping-cart' },
                    { id: 'approved', label: 'Order Approved', icon: 'check-circle' },
                    { id: 'in_process', label: 'Processing', icon: 'cog' },
                    { id: 'ready_for_pickup', label: 'Ready for Pickup', icon: 'box' },
                    { id: 'completed', label: 'Completed', icon: 'check-double' }
                ];
                
                // Find current step index
                let currentStepIndex = steps.findIndex(step => 
                    step.id === orderStatus ||
                    (orderStatus === 'processing' && step.id === 'in_process') ||
                    (orderStatus === 'in-process' && step.id === 'in_process')
                );
                
                if (currentStepIndex === -1) currentStepIndex = 0;
                
                // Create shopee-style progress tracker
                let progressHtml = `
                    <div class="shopee-tracking">
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar">
                                <div class="progress-inner" style="width: ${(currentStepIndex / (steps.length - 1)) * 100}%"></div>
                            </div>
                            <div class="progress-steps">`;
                            
                steps.forEach((step, index) => {
                    const isActive = index <= currentStepIndex;
                    progressHtml += `
                        <div class="progress-step ${isActive ? 'active' : ''} ${index === currentStepIndex ? 'current' : ''}" 
                             style="left: ${(index / (steps.length - 1)) * 100}%">
                            <div class="step-icon">
                                <i class="fas fa-${step.icon}"></i>
                            </div>
                            <div class="step-label">${step.label}</div>
                        </div>`;
                });
                
                progressHtml += `
                        </div>
                    </div>
                </div>`;
                
                document.getElementById("order-progress-content").innerHTML = progressHtml;
                
                // Generate the timeline from status history
                let timelineHtml = '';
                if (data.status_history && Array.isArray(data.status_history) && data.status_history.length > 0) {
                    timelineHtml += '<ul class="timeline-list">';
                    
                    // Reverse to show most recent first
                    data.status_history.reverse().forEach((event, index) => {
                        timelineHtml += `
                            <li class="timeline-item">
                                <div class="timeline-point ${index === 0 ? 'active' : ''}"></div>
                                <div class="timeline-content">
                                    <div class="event-time">${new Date(event.timestamp).toLocaleString()}</div>
                                    <div class="event-title">${event.status.replace(/_/g, ' ').toUpperCase()}</div>
                                    <div class="event-message">${event.note || 'Status updated'}</div>
                                </div>
                            </li>`;
                    });
                    
                    timelineHtml += '</ul>';
                } else {
                    timelineHtml = `
                        <div class="timeline-empty text-center py-4">
                            <i class="fas fa-history fa-3x mb-3 text-muted"></i>
                            <p>No detailed tracking history available for this order yet.</p>
                        </div>`;
                }
                
                document.getElementById("order-timeline-content").innerHTML = timelineHtml;
            })
            .catch(error => {
                console.error('Error fetching order tracking data:', error);
                document.getElementById("order-summary-content").innerHTML = 
                    '<div class="alert alert-danger">Error loading order details. Please try again later.</div>';
                document.getElementById("order-progress-content").innerHTML = 
                    '<div class="alert alert-danger">Error loading order progress. Please try again later.</div>';
                document.getElementById("order-timeline-content").innerHTML = 
                    '<div class="alert alert-danger">Error loading timeline data. Please try again later.</div>';
            });
    };
});
