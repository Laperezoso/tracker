<?php
session_start();
require_once "../class/db_connect.php";
require_once "../class/appliances.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$applianceObj = new Appliance($conn);
$message = "";

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Location: view_appliances.php");
    exit;
}

$appliance_id = $_GET["id"];
$user_id = $_SESSION["user_id"];

// --- SELECT appliance with warranty info ---
$stmt = $conn->prepare("
    SELECT a.*, w.purchase_date, w.warranty_expiry
    FROM appliances a
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    WHERE a.appliance_id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $appliance_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appliance = $result->fetch_assoc();

if (!$appliance) {
    $message = "❌ Appliance not found or not yours.";
} else {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["appliance_name"]);
    $brand = trim($_POST["brand"]);
    $model = trim($_POST["model"]);
    $purchase = $_POST["purchase_date"];
    $expiry = $_POST["warranty_expiry"];
    $current_date = date("Y-m-d");

    if ($purchase > $current_date) {
        $message = "❌ Purchase date cannot be in the future.";
    } elseif ($expiry < $current_date) {
        $message = "⚠️ Warranty already expired. Please check the expiry date.";
    } else {
        $status = "Pending";

        // --- Update appliances table ---
        $sql_app = "UPDATE appliances 
                    SET appliance_name = ?, brand = ?, model = ?, status = ?
                    WHERE appliance_id = ? AND user_id = ?";
        $stmt_app = $conn->prepare($sql_app);
        $stmt_app->bind_param("ssssii", $name, $brand, $model, $status, $appliance_id, $user_id);
        $stmt_app->execute();  // only execute once

        // --- Check if warranty row exists ---
        $stmt_check = $conn->prepare("SELECT appliance_id FROM warranty WHERE appliance_id = ?");
        $stmt_check->bind_param("i", $appliance_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Update existing warranty
            $sql_w = "UPDATE warranty 
                      SET purchase_date = ?, warranty_expiry = ?
                      WHERE appliance_id = ?";
            $stmt_w = $conn->prepare($sql_w);
            $stmt_w->bind_param("ssi", $purchase, $expiry, $appliance_id);
            $stmt_w->execute();
        } else {
            // Insert new warranty row
            $sql_w_insert = "INSERT INTO warranty (appliance_id, purchase_date, warranty_expiry) VALUES (?, ?, ?)";
            $stmt_w_insert = $conn->prepare($sql_w_insert);
            $stmt_w_insert->bind_param("iss", $appliance_id, $purchase, $expiry);
            $stmt_w_insert->execute();
        }

        $message = "✅ Appliance updated successfully! (Status set to Pending)";

        // Refresh appliance data
        $stmt_refresh = $conn->prepare("
            SELECT a.*, w.purchase_date, w.warranty_expiry
            FROM appliances a
            LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
            WHERE a.appliance_id = ? AND a.user_id = ?
        ");
        $stmt_refresh->bind_param("ii", $appliance_id, $user_id);
        $stmt_refresh->execute();
        $appliance = $stmt_refresh->get_result()->fetch_assoc();
    }
}

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Appliance | Appliance Tracker</title>
    <style>
       
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: url("../image/logo.png") no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

      
        .overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1;
        }

       
        .form-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 50px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #0d47a1;
            margin-bottom: 10px;
        }

        a.back {
            display: inline-block;
            color: #0d47a1;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
        }
        a.back:hover {
            text-decoration: underline;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            margin: 10px 0 5px;
            color: #222;
        }

        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: 0.3s;
        }

        input[type="text"]:focus, input[type="date"]:focus {
            border-color: #0d47a1;
            box-shadow: 0 0 6px #0d47a1;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #0d47a1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #1565c0;
        }

        .message {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .success { color: green; }
        .warning { color: #c68900; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <div class="form-container">
        <a href="view_appliances.php" class="back">← Back to My Appliances</a>
        <h2>Edit Appliance</h2>

        <?php if ($message): ?>
            <p class="message 
                <?php 
                    echo (strpos($message, '✅') !== false) ? 'success' : 
                         ((strpos($message, '⚠️') !== false) ? 'warning' : 'error'); 
                ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($appliance): ?>
        <form method="POST">
            <label>Appliance Name</label>
            <input type="text" name="appliance_name" value="<?php echo htmlspecialchars($appliance['appliance_name']); ?>" required>

            <label>Brand</label>
            <input type="text" name="brand" value="<?php echo htmlspecialchars($appliance['brand']); ?>" required>

            <label>Model</label>
            <input type="text" name="model" value="<?php echo htmlspecialchars($appliance['model']); ?>" required>

            <label>Purchase Date</label>
            <input type="date" name="purchase_date" value="<?php echo htmlspecialchars($appliance['purchase_date']); ?>" required>

            <label>Warranty Expiry</label>
            <input type="date" name="warranty_expiry" value="<?php echo htmlspecialchars($appliance['warranty_expiry']); ?>" required>

            <button type="submit">Update Appliance</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
