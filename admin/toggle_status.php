<?php
session_start();
require_once "../class/db_connect.php"; // ✅ same path as manage_users

// ✅ Only allow admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

// ✅ Get user ID to toggle
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // 🔒 Prevent admin from toggling their own account
    if ($user_id == $_SESSION['user_id']) {
        header("Location: manage_users.php");
        exit;
    }

    
    $stmt = $conn->prepare("SELECT active FROM user_accounts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_status = $row['active'] == 1 ? 0 : 1;

       
        $update = $conn->prepare("UPDATE user_accounts SET active = ? WHERE user_id = ?");
        $update->bind_param("ii", $new_status, $user_id);
        $update->execute();
    }
}


header("Location: manage_users.php");
exit;
?>
