<?php
require_once "class/db_connect.php";

$sql = "ALTER TABLE user_accounts 
        ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'default_avatar.png'";

if ($conn->query($sql) === TRUE) {
    echo "Column 'profile_pic' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
