<?php
include '../db.php'; // Adjust path to your database connection

if (!isset($_GET['order_id'])) {
    die("No order ID provided.");
}

$order_id = $_GET['order_id'];

// Fetch order details from `orders` table
$order_query = $conn->prepare("
    SELECT o.order_id, c.first_name, c.last_name, o.created_at, 
           o.total_amount, o.downpayment_amount, o.payment_method, 
           o.payment_status, o.order_status
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order_result = $order_query->get_result();
$order = $order_result->fetch_assoc();

// Fetch sublimation details from `sublimation_orders`
$sublimation_query = $conn->prepare("
    SELECT s.printing_type, s.template_id, s.custom_design, s.design_path 
    FROM sublimation_orders s
    WHERE s.order_id = ?
");
$sublimation_query->bind_param("i", $order_id);
$sublimation_query->execute();
$sublimation_result = $sublimation_query->get_result();
$sublimation = $sublimation_result->fetch_assoc();

// Determine design option
if ($sublimation) {
    if ($sublimation['custom_design']) {
        $design_option = "Custom Design (" . htmlspecialchars($sublimation['design_path']) . ")";
    } elseif ($sublimation['template_id']) {
        $design_option = "Template (" . htmlspecialchars($sublimation['template_id']) . ")";
    } else {
        $design_option = "N/A";
    }
} else {
    $design_option = "N/A";
}

// Fetch player details (if applicable)
$players_query = $conn->prepare("
    SELECT player_name, jersey_number, size, include_lower 
    FROM sublimation_players 
    WHERE sublimation_id = (SELECT sublimation_id FROM sublimation_orders WHERE order_id = ?)
");
$players_query->bind_param("i", $order_id);
$players_query->execute();
$players_result = $players_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Order Details</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr><th>Order ID</th><td><?= htmlspecialchars($order['order_id']) ?></td></tr>
                <tr><th>Customer Name</th><td><?= htmlspecialchars($order['first_name'] . " " . $order['last_name']) ?></td></tr>
                <tr><th>Order Date</th><td><?= htmlspecialchars($order['created_at']) ?></td></tr>
                <tr><th>Printing Type</th><td><?= htmlspecialchars($sublimation['printing_type'] ?? 'N/A') ?></td></tr>
                <tr><th>Design Option</th><td><?= $design_option ?></td></tr>
                <tr><th>Total Amount</th><td>₱<?= number_format($order['total_amount'], 2) ?></td></tr>
                <tr><th>Downpayment</th><td>₱<?= number_format($order['downpayment_amount'], 2) ?></td></tr>
                <tr><th>Payment Method</th><td><?= htmlspecialchars($order['payment_method']) ?></td></tr>
                <tr><th>Payment Status</th><td><span class="badge bg-<?= $order['payment_status'] == 'fully_paid' ? 'success' : 'warning' ?>">
                    <?= htmlspecialchars($order['payment_status']) ?></span></td></tr>
                <tr><th>Order Status</th><td><span class="badge bg-primary"><?= htmlspecialchars($order['order_status']) ?></span></td></tr>
            </table>
        </div>
    </div>

    <?php if ($players_result->num_rows > 0): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Player Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Player Name</th>
                            <th>Jersey Number</th>
                            <th>Size</th>
                            <th>Include Lower</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($player = $players_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($player['player_name']) ?></td>
                                <td><?= htmlspecialchars($player['jersey_number']) ?></td>
                                <td><?= htmlspecialchars($player['size']) ?></td>
                                <td><?= $player['include_lower'] ? "Yes" : "No" ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="orders.php" class="btn btn-dark">Back</a>
    </div> <br><br>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
