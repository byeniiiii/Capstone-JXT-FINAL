<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>JXT Tailoring - Payment Receipt</title>
    <link rel="icon" type="image/png" href="../image/logo.png">
=======
    <title>JX Tailoring - Payment Receipt</title>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .receipt {
            background-color: white;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-logo {
            max-width: 120px;
            margin-bottom: 8px;
        }
        .receipt-title {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .receipt-subtitle {
            font-size: 1.25rem;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .receipt-address {
            font-size: 0.875rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .receipt-separator {
            border-bottom: 2px solid #000;
            width: 100%;
            margin: 15px 0;
        }
        .receipt-id, .receipt-date {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .receipt-customer-info {
            margin: 20px 0;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }
        .receipt-customer-info p {
            margin: 5px 0;
            font-size: 0.875rem;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .receipt-table th, .receipt-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            font-size: 0.875rem;
        }
        .receipt-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .receipt-table td:nth-child(2) {
            text-align: left;
        }
        .receipt-table td:nth-child(3),
        .receipt-table td:nth-child(4) {
            text-align: right;
        }
        .receipt-computation {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #ccc;
            font-size: 0.875rem;
        }
        .payment-details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #000;
        }
        .receipt-total-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
        }
        .receipt-signatures {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }
        .receipt-signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 8px;
            height: 40px;
        }
        .receipt-footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }
        .receipt-disclaimer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
        }
        .receipt-disclaimer p:nth-child(2) {
            font-weight: bold;
            font-style: italic;
        }
        .receipt-screenshot {
            max-width: 100%;
            max-height: 300px;
            margin: 15px auto;
            display: block;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        @media print {
            .receipt {
                box-shadow: none;
                padding: 0;
            }
            .receipt-action {
                display: none;
            }
        }
        @media (max-width: 576px) {
            .receipt-title {
                font-size: 1.5rem;
            }
            .receipt-subtitle {
                font-size: 1rem;
            }
            .receipt-table th, .receipt-table td {
                font-size: 0.75rem;
                padding: 5px;
            }
            .receipt-signatures {
                flex-direction: column;
                gap: 20px;
            }
            .receipt-signature-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <?php
        // Parse query parameters with sanitization
        $payment_id = isset($_GET['payment_id']) ? htmlspecialchars($_GET['payment_id']) : '';
        $order_id = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : '';
        $customer_name = isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : 'N/A';
        $address = isset($_GET['address']) ? htmlspecialchars($_GET['address']) : 'N/A';
        $phone_number = isset($_GET['phone_number']) ? htmlspecialchars($_GET['phone_number']) : 'N/A';
        $order_type = isset($_GET['order_type']) ? htmlspecialchars($_GET['order_type']) : 'N/A';
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
        $total_amount = isset($_GET['total_amount']) ? floatval($_GET['total_amount']) : 0;
        $payment_method = isset($_GET['payment_method']) ? htmlspecialchars($_GET['payment_method']) : 'N/A';
        $transaction_reference = isset($_GET['transaction_reference']) ? htmlspecialchars($_GET['transaction_reference']) : '';
        $formatted_date = isset($_GET['formatted_date']) ? htmlspecialchars($_GET['formatted_date']) : date('Y-m-d');
        $screenshot_path = isset($_GET['screenshot_path']) ? htmlspecialchars($_GET['screenshot_path']) : '';
        ?>

        <div class="receipt-header">
<<<<<<< HEAD
            <img src="../image/logo.png" alt="JXT Tailoring Logo" class="receipt-logo img-fluid">
            <h1 class="receipt-title">JXT Tailoring</h1>
=======
            <img src="../image/logo.png" alt="JX Tailoring Logo" class="receipt-logo img-fluid">
            <h1 class="receipt-title">JX Tailoring</h1>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
            <p class="receipt-address">Purok Iba, Palinpinon, Valencia, Negros Oriental<br>Phone: (032) 123-4567<br>TIN: 629-067-859-00000<br>VAT Registered</p>
            <div class="receipt-separator"></div>
            <h2 class="receipt-subtitle">OFFICIAL RECEIPT</h2>
            <div class="receipt-id">Receipt No: <?php echo sprintf('OR-%06d', $payment_id); ?></div>
            <div class="receipt-date">Date: <?php echo $formatted_date; ?></div>
        </div>

        <div class="receipt-customer-info">
            <p><strong>Sold To:</strong> <?php echo $customer_name; ?></p>
            <p><strong>Address:</strong> <?php echo $address; ?></p>
            <p><strong>TIN:</strong> N/A</p>
            <p><strong>Contact:</strong> <?php echo $phone_number; ?></p>
        </div>

        <div class="receipt-details">
            <table class="receipt-table table table-bordered">
                <thead>
                    <tr>
                        <th>Qty</th>
                        <th>Description</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <?php echo ucfirst($order_type); ?> Services<br>
                            Order #<?php echo $order_id; ?><br>
                            <?php echo ($amount >= $total_amount) ? 'Full Payment' : 'Downpayment'; ?>
                        </td>
                        <td>₱<?php echo number_format($amount, 2); ?></td>
                        <td>₱<?php echo number_format($amount, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="receipt-computation">
                <div class="receipt-row">
                    <div class="receipt-label">VATable Sales:</div>
                    <div class="receipt-value">₱<?php echo number_format($amount / 1.12, 2); ?></div>
                </div>
                <div class="receipt-row">
                    <div class="receipt-label">VAT Amount (12%):</div>
                    <div class="receipt-value">₱<?php echo number_format($amount - ($amount / 1.12), 2); ?></div>
                </div>
                <div class="receipt-row receipt-total-row">
                    <div class="receipt-label">Total Amount:</div>
                    <div class="receipt-value receipt-total">₱<?php echo number_format($amount, 2); ?></div>
                </div>

                <div class="payment-details">
                    <div class="receipt-row">
                        <div class="receipt-label">Payment Method:</div>
                        <div class="receipt-value"><?php echo ucfirst($payment_method); ?></div>
                    </div>
                    <?php if (!empty($transaction_reference)): ?>
                    <div class="receipt-row">
                        <div class="receipt-label">Reference No:</div>
                        <div class="receipt-value"><?php echo $transaction_reference; ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="receipt-row">
                        <div class="receipt-label">Total Order Amount:</div>
                        <div class="receipt-value">₱<?php echo number_format($total_amount, 2); ?></div>
                    </div>
                    <div class="receipt-row">
                        <div class="receipt-label">Balance Due:</div>
                        <div class="receipt-value">₱<?php echo number_format($total_amount - $amount, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($screenshot_path) && file_exists($screenshot_path)): ?>
        <div class="receipt-screenshot-container">
            <p class="text-center fw-bold">Transaction Screenshot</p>
            <img src="<?php echo $screenshot_path; ?>" alt="Payment Screenshot" class="receipt-screenshot img-fluid">
        </div>
        <?php endif; ?>

        <div class="receipt-footer">
            <div class="receipt-signatures">
                <div class="receipt-signature-box">
                    <div class="signature-line"></div>
                    <p>Cashier's Signature</p>
                </div>
                <div class="receipt-signature-box">
                    <div class="signature-line"></div>
                    <p>Customer's Signature</p>
                </div>
            </div>
            <div class="receipt-disclaimer">
                <p>"This serves as an Official Receipt"</p>
                <p>THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAX</p>
                <p>Keep this receipt for future reference</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (optional, only if needed for interactivity) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            window.print();
            setTimeout(() => window.close(), 500);
        };
    </script>
</body>
</html>