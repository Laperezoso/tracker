<?php
session_start();
require_once "../class/db_connect.php";
require_once "../class/appliances.php";


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$applianceObj = new Appliance($conn);
$user_id = $_SESSION["user_id"];


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo "<script>alert('Invalid appliance ID.'); window.location='view_appliances.php';</script>";
    exit;
}

$id = intval($_GET["id"]);


if ($applianceObj->deleteAppliance($id, $user_id)) {
    echo "<script>alert('Appliance deleted successfully.'); window.location='view_appliances.php';</script>";
    exit;
} else {
    echo "<script>alert('Failed to delete appliance.'); window.location='view_appliances.php';</script>";
    exit;
}
?>
