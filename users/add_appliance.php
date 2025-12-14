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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["appliance_name"]);
    $brand = trim($_POST["brand"]);
    $model = trim($_POST["model"]);
    $purchase = $_POST["purchase_date"];
    $expiry = $_POST["warranty_expiry"];
    $user_id = $_SESSION["user_id"];
    $status = "Broken"; 

    $current_date = date("Y-m-d");

    
    if ($purchase > $current_date) {
        $message = " Purchase date cannot be in the future.";
    } elseif ($expiry < $current_date) {
        $message = " Warranty already expired. Please check the expiry date.";
    } else {
       
        $sql = "INSERT INTO appliances (user_id, appliance_name, brand, model, status)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $user_id, $name, $brand, $model, $status);

if ($stmt->execute()) {
    // Get the last inserted appliance_id
    $appliance_id = $stmt->insert_id;

    // Insert warranty info
    $warranty_sql = "INSERT INTO warranty (appliance_id, purchase_date, warranty_expiry) VALUES (?, ?, ?)";
    $warranty_stmt = $conn->prepare($warranty_sql);
    $warranty_stmt->bind_param("iss", $appliance_id, $purchase, $expiry);
    $warranty_stmt->execute();

    $message = "Appliance added successfully! (Default Status: Broken)";
} else {
    $message = "Failed to add appliance.";
}

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Appliance</title>
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
            text-align: center;
            color: #2196F3;
         }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label { 
            font-weight: bold;

        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 8px;
            margin: 6px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #2196F3;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover { background-color: #1976D2; }
        p {
            text-align: center;
            font-weight: bold;
            color: <?php echo (strpos($message, '✅') !== false) ? 'green' : ((strpos($message, '⚠️') !== false) ? '#c68900' : 'red'); ?>;
        }
        a {
            text-decoration: none;
            color: #2196F3;
            font-weight: bold;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="form-container">
        <a href="dashboard.php">← Back to Dashboard</a>
    <h2>Add Appliance</h2>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Appliance Name:</label><br>
        <input type="text" name="appliance_name" required><br><br>

        <label>Brand:</label><br>
        <input type="text" name="brand" required><br><br>

        <label>Model:</label><br>
        <input type="text" name="model" required><br><br>

        <label>Purchase Date:</label><br>
        <input type="date" name="purchase_date" required><br><br>

        <label>Warranty Expiry:</label><br>
        <input type="date" name="warranty_expiry" required><br><br>

        <input type="submit" value="Add Appliance">
    </form>
    </div>
</body>
</html>
