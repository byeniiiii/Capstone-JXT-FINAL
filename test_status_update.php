<?php
include 'db.php';

// Get a sample order that's assigned to a sublimator
$query = "SELECT o.order_id, o.order_status, s.sublimator_id
          FROM orders o
          JOIN sublimation_orders s ON o.order_id = s.order_id
          WHERE s.sublimator_id IS NOT NULL
          AND o.order_type = 'sublimation'
          LIMIT 1";
          
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "No orders found with assigned sublimators.";
    exit;
}

$order = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Status Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Order Status Update</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                Order Details
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> <?php echo $order['order_id']; ?></p>
                <p><strong>Current Status:</strong> <?php echo $order['order_status']; ?></p>
                <p><strong>Sublimator ID:</strong> <?php echo $order['sublimator_id']; ?></p>
                
                <hr>
                
                <h5>Update Status</h5>
                <div class="mb-3">
                    <label for="newStatus" class="form-label">New Status</label>
                    <select id="newStatus" class="form-select">
                        <option value="forward_to_sublimator">Forward to Sublimator</option>
                        <option value="in_process">In Process</option>
                        <option value="printing_done">Printing Done</option>
                        <option value="ready_for_pickup">Ready for Pickup</option>
                    </select>
                </div>
                
                <button id="updateBtn" class="btn btn-primary">Update Status</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Response
            </div>
            <div class="card-body">
                <pre id="response">No response yet</pre>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('updateBtn').addEventListener('click', function() {
            const orderId = '<?php echo $order['order_id']; ?>';
            const newStatus = document.getElementById('newStatus').value;
            const responseArea = document.getElementById('response');
            
            // Show loading
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            responseArea.textContent = 'Sending request...';
            
            fetch('sublimator/update_sublimation_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + encodeURIComponent(orderId) + '&new_status=' + encodeURIComponent(newStatus)
            })
            .then(response => response.text())
            .then(data => {
                responseArea.textContent = data;
                
                // Reset button
                this.disabled = false;
                this.innerHTML = 'Update Status';
                
                // Refresh page after 2 seconds to show updated status
                setTimeout(() => {
                    location.reload();
                }, 2000);
            })
            .catch(error => {
                responseArea.textContent = 'Error: ' + error.message;
                
                // Reset button
                this.disabled = false;
                this.innerHTML = 'Update Status';
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 