<?php
include '../db.php';
session_start();

if (!isset($_GET['order_id'])) {
    echo "Error: Order ID is missing.";
    exit();
}

$order_id = $_GET['order_id'];

// Fetch total_amount from orders to validate later - using prepared statement for security
$order_query = $conn->prepare("SELECT total_amount FROM orders WHERE order_id = ?");
$order_query->bind_param("s", $order_id);
$order_query->execute();
$result = $order_query->get_result();
$order_data = $result->fetch_assoc();
$allowed_total = $order_data['total_amount'];

// Handle template_id from GET parameter if it exists
$selected_template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sublimation Order Form | JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Poppins', sans-serif;
        }
        .order-form-container {
            max-width: 800px;
            margin: 40px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: 600;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
        }
        .btn-primary {
            background-color: #343a40;
            border-color: #343a40;
        }
        .btn-primary:hover {
            background-color: #212529;
            border-color: #212529;
        }
        .form-label {
            font-weight: 500;
        }
        .form-text {
            font-size: 0.8rem;
        }
        .player {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .player:hover {
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        .remove-player {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .remove-player:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        .section-title {
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #343a40;
            border-left: 4px solid #343a40;
            padding-left: 10px;
        }
        .price-display {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>

<div class="container order-form-container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h4 class="mb-0"><i class="fas fa-tshirt me-2"></i>Sublimation Order Form</h4>
            <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
        <div class="card-body p-4">
            <form action="submit_order.php" method="POST" enctype="multipart/form-data" class="needs-validation" onsubmit="return validateTotalAmount()" novalidate>
                
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id); ?>">
                <input type="hidden" id="allowed_total" value="<?= $allowed_total ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="completion_date" class="form-label">Required Completion Date <span class="text-danger">*</span></label>
                        <input type="date" name="completion_date" id="completion_date" class="form-control" 
                               required min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                        <div class="form-text">Please allow at least 3 days for processing.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="printing_type" class="form-label">Printing Type <span class="text-danger">*</span></label>
                        <select name="printing_type" id="printing_type" class="form-select" required>
                            <option value="sublimation">Sublimation</option>
                            <option value="silkscreen">Silkscreen</option>
                        </select>
                        <div class="form-text">Select the type of printing method you prefer.</div>
                    </div>
                </div>
                
                <h5 class="section-title"><i class="fas fa-palette me-2"></i>Design Selection</h5>
                
                <div class="mb-3">
                    <label for="design_option" class="form-label">Design Option <span class="text-danger">*</span></label>
                    <select name="design_option" id="design_option" class="form-select" onchange="toggleDesignOption()" required>
                        <option value="available_template">Available Template</option>
                        <option value="custom_design">Custom Design</option>
                    </select>
                </div>

                <!-- Template Selection -->
                <div id="template_select" class="mb-3">
                    <label for="template_id" class="form-label">Choose Template <span class="text-danger">*</span></label>
                    <select name="template_id" id="template_id" class="form-select" onchange="updateTemplatePrice()" required>
                        <option value="">Select Template</option>
                        <?php
                        $templateQuery = "SELECT template_id, name, price FROM templates";
                        $templateResult = mysqli_query($conn, $templateQuery);
                        while ($row = mysqli_fetch_assoc($templateResult)) {
                            echo "<option value='{$row['template_id']}' data-price='{$row['price']}'>{$row['name']} - ₱{$row['price']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Custom Design -->
                <div id="custom_design_upload" class="mb-3" style="display: none;">
                    <label for="custom_design" class="form-label">Upload Custom Design <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-upload"></i></span>
                        <input type="file" name="custom_design" id="custom_design" class="form-control">
                    </div>
                    <div class="form-text">Accepted formats: .png, .jpg, .jpeg, .pdf (Max: 5MB)</div>
                </div>

                <!-- Price Display -->
                <div class="mb-4">
                    <label for="jersey_price" class="form-label">Jersey Base Price:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" id="jersey_price" class="form-control price-display" value="₱0" readonly>
                    </div>
                </div>

                <h5 class="section-title"><i class="fas fa-users me-2"></i>Player Details</h5>
                
                <div id="players">
                    <div class="player">
                        <button type="button" class="remove-player" onclick="removePlayer(this)">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Player Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="player_name[]" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Jersey Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="number" name="jersey_number[]" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Size <span class="text-danger">*</span></label>
                                <select name="size[]" class="form-select" required>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M" selected>M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                    <option value="XXXL">XXXL</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Include Lower?</label>
                                <select name="include_lower[]" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No" selected>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-secondary mt-2 mb-4" onclick="addPlayer()">
                    <i class="fas fa-plus-circle me-1"></i> Add Another Player
                </button>

                <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                
                <div class="mb-4">
                    <label for="instructions" class="form-label">Special Instructions</label>
                    <textarea name="instructions" id="instructions" class="form-control" rows="3" 
                              placeholder="Any specific requirements or details for your order..."></textarea>
                </div>

                <div class="mb-4">
                    <label for="calculated_total" class="form-label">Total Cost (Auto-calculated):</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                        <input type="text" id="calculated_total" class="form-control price-display fw-bold" value="₱0" readonly>
                    </div>
                    <div class="form-text text-danger" id="total-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Warning: Order exceeds the allowed total of ₱<span id="allowed-total-display"><?= $allowed_total ?></span>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary me-md-2" onclick="resetForm()">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Submit Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap & JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

function toggleDesignOption() {
    const option = document.getElementById("design_option").value;
    document.getElementById("template_select").style.display = option === "available_template" ? "block" : "none";
    document.getElementById("custom_design_upload").style.display = option === "custom_design" ? "block" : "none";
    
    // Reset required attributes
    if (option === "available_template") {
        document.getElementById("template_id").required = true;
        document.getElementById("custom_design").required = false;
    } else {
        document.getElementById("template_id").required = false;
        document.getElementById("custom_design").required = true;
    }
}

function updateTemplatePrice() {
    const selected = document.getElementById("template_id");
    const price = selected.options[selected.selectedIndex].getAttribute("data-price") || 0;
    document.getElementById("jersey_price").value = `₱${price}`;
    calculateTotal(price);
}

function addPlayer() {
    const playerContainer = document.getElementById("players");
    const newPlayer = document.querySelector(".player").cloneNode(true);
    
    // Reset input values
    newPlayer.querySelectorAll("input").forEach(input => input.value = "");
    
    // Reset select elements to default options
    newPlayer.querySelectorAll("select").forEach(select => {
        if (select.name === "size[]") {
            select.value = "M";
        } else if (select.name === "include_lower[]") {
            select.value = "No";
        }
    });
    
    playerContainer.appendChild(newPlayer);
    calculateTotal(); // recalculate on add
    
    // Animate the new player card
    setTimeout(() => {
        newPlayer.style.backgroundColor = '#f0f8ff';
        setTimeout(() => {
            newPlayer.style.backgroundColor = '#f8f9fa';
        }, 500);
    }, 10);
}

function removePlayer(button) {
    const players = document.querySelectorAll(".player");
    if (players.length > 1) {
        const playerToRemove = button.parentElement;
        
        // Animate removal
        playerToRemove.style.opacity = '0.5';
        playerToRemove.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            playerToRemove.remove();
            calculateTotal();
        }, 200);
    } else {
        alert("At least one player is required.");
    }
}

function calculateTotal(forcedPrice = null) {
    const playerCount = document.querySelectorAll(".player").length;
    let price = forcedPrice || document.getElementById("template_id").selectedOptions[0]?.getAttribute("data-price") || 0;
    let total = playerCount * parseFloat(price);
    document.getElementById("calculated_total").value = `₱${total.toFixed(2)}`;
    
    // Check if total exceeds allowed amount and show warning
    const totalAllowed = parseFloat(document.getElementById("allowed_total").value);
    const warningEl = document.getElementById("total-warning");
    
    if (total > totalAllowed) {
        warningEl.style.display = "block";
    } else {
        warningEl.style.display = "none";
    }
    
    return total;
}

function validateTotalAmount() {
    const totalAllowed = parseFloat(document.getElementById("allowed_total").value);
    const calculatedTotal = calculateTotal();
    if (calculatedTotal > totalAllowed) {
        alert(`⚠️ Error: Order exceeds the allowed total of ₱${totalAllowed}.`);
        return false;
    }
    return true;
}

function resetForm() {
    // Confirm before resetting
    if (confirm("Are you sure you want to reset the form? All entered data will be lost.")) {
        // Reset select fields
        document.getElementById("printing_type").selectedIndex = 0;
        document.getElementById("design_option").selectedIndex = 0;
        document.getElementById("template_id").selectedIndex = 0;
        
        // Reset text fields
        document.getElementById("jersey_price").value = "₱0";
        document.getElementById("calculated_total").value = "₱0";
        document.getElementById("instructions").value = "";
        
        // Reset file upload
        if (document.getElementById("custom_design")) {
            document.getElementById("custom_design").value = "";
        }
        
        // Reset to single player
        const players = document.querySelectorAll(".player");
        // Keep first player but reset fields
        for (let i = 1; i < players.length; i++) {
            players[i].remove();
        }
        
        // Reset first player fields
        const firstPlayer = document.querySelector(".player");
        firstPlayer.querySelectorAll("input").forEach(input => input.value = "");
        
        // Reset design option display
        toggleDesignOption();
        
        // Reset validation state
        document.querySelector('form').classList.remove('was-validated');
        
        // Hide warning if visible
        document.getElementById("total-warning").style.display = "none";
    }
}
</script>

</body>
</html>
