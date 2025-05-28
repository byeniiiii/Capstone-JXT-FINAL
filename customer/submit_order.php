<?php
include '../db.php';
session_start();

// Make sure we have all required data
if (!isset($_POST['order_id']) || !isset($_POST['total_amount'])) {
    echo "Missing required data";
    exit;
}

// Get order ID and calculated total amount from form
$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
$total_amount = (float)$_POST['total_amount'];

// Get customer ID from session
$customer_id = $_SESSION['customer_id'];

// Process other form data...

// Update order with final calculated total
$update_total_query = "UPDATE orders SET total_amount = ? WHERE order_id = ?";
$stmt = $conn->prepare($update_total_query);
$stmt->bind_param("ds", $total_amount, $order_id);
$stmt->execute();

// Process the rest of the order as usual...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f8f9fa; 
        }
    </style>
</head>
<body>

<!-- Success Modal -->
<div class="modal fade" id="orderSuccessModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="orderSuccessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="orderSuccessModalLabel"><i class="fas fa-check-circle me-2"></i> Order Submitted Successfully!</h5>
      </div>
      <div class="modal-body text-center py-4">
        <div class="mb-4">
          <i class="fas fa-tshirt fa-3x text-success mb-3"></i>
          <h4>Thank you for your order!</h4>
          <p class="mb-0">Your sublimation jerseys order has been submitted.</p>
          <div class="alert alert-primary mb-3 py-2">
            <div class="d-flex align-items-center justify-content-center">
              <div>Order ID: <strong class="fs-5"><?php echo htmlspecialchars($order_id); ?></strong></div>
              <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="copyOrderId()" title="Copy Order ID">
                <i class="fas fa-copy"></i>
              </button>
            </div>
          </div>
          <div class="alert alert-light border">
            <p class="mb-1"><strong>Next steps:</strong></p>
            <ul class="text-start mb-0">
              <li>Our staff will review your order details</li>
              <li>You'll receive a notification when your order is approved</li>
              <li>Your total amount: <strong>₱<?php echo number_format($total_amount, 2); ?></strong></li>
              <li>Payment will be collected once your order is approved</li>
            </ul>
          </div>
        </div>
        <div class="d-flex justify-content-center mt-2">
          <a href="index.php" class="btn btn-primary me-2">
            <i class="fas fa-home me-1"></i> Go to Dashboard
          </a>
          <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-list-alt me-1"></i> View My Orders
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show the success modal automatically
        var orderSuccessModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
        orderSuccessModal.show();
        
        // Prevent back button after submission
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
    });
    
    // Function to copy order ID to clipboard
    function copyOrderId() {
        const orderId = '<?php echo htmlspecialchars($order_id); ?>';
        navigator.clipboard.writeText(orderId).then(function() {
            // Show success tooltip
            const copyBtn = document.querySelector('.btn-outline-primary');
            const originalHTML = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            copyBtn.classList.add('btn-success');
            copyBtn.classList.remove('btn-outline-primary');
            
            // Reset button after 2 seconds
            setTimeout(function() {
                copyBtn.innerHTML = originalHTML;
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-primary');
            }, 2000);
        });
    }
</script>
</body>
</html>
