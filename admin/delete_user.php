<?php
session_start();
require_once "../class/db_connect.php"; 


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    
    if ($user_id == $_SESSION['user_id']) {
        header("Location: manage_users.php");
        exit;
    }

    
    $stmt = $conn->prepare("DELETE FROM user_accounts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}


header("Location: manage_users.php");
exit;
?>
