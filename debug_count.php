<?php
session_start();
require_once "class/db_connect.php";

if (!isset($_SESSION["user_id"])) {
    die("Not logged in");
}

$user_id = $_SESSION["user_id"];

echo "<h1>Debug Info</h1>";
echo "User ID: " . $user_id . "<br>";
echo "Current Date (PHP): " . date('Y-m-d') . "<br><br>";

// 1. Check Global Expired Count
$sqlGlobal = "SELECT COUNT(*) as count FROM appliances a JOIN warranty w ON a.appliance_id = w.appliance_id WHERE w.warranty_expiry < CURDATE()";
$resGlobal = $conn->query($sqlGlobal);
$rowGlobal = $resGlobal->fetch_assoc();
echo "<b>Global Expired Count (All Users):</b> " . $rowGlobal['count'] . "<br>";

// 2. Check User Expired Count (What Dashboard uses)
$sqlUser = "SELECT COUNT(*) as count FROM appliances a JOIN warranty w ON a.appliance_id = w.appliance_id WHERE a.user_id = ? AND w.warranty_expiry < CURDATE()";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$rowUser = $resUser->fetch_assoc();
echo "<b>User Expired Count (Only Logged In):</b> " . $rowUser['count'] . "<br>";

// 3. List the actual expired items for this user
echo "<h3>Expired Items for User $user_id:</h3>";
$sqlList = "SELECT a.appliance_name, w.warranty_expiry FROM appliances a JOIN warranty w ON a.appliance_id = w.appliance_id WHERE a.user_id = ? AND w.warranty_expiry < CURDATE()";
$stmtList = $conn->prepare($sqlList);
$stmtList->bind_param("i", $user_id);
$stmtList->execute();
$resList = $stmtList->get_result();

if ($resList->num_rows > 0) {
    echo "<ul>";
    while ($row = $resList->fetch_assoc()) {
        echo "<li>" . $row['appliance_name'] . " (Expires: " . $row['warranty_expiry'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "No expired items found for this user.<br>";
}
?>
