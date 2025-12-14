<?php
require_once "class/db_connect.php";

echo "<h2>Starting Password Migration...</h2>";

// Get all users
$query = "SELECT user_id, password, username FROM user_accounts";
$result = $conn->query($query);

$count = 0;

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        $id = $row['user_id'];
        $pass = $row['password'];
        $username = $row['username'];

        // Check if already hashed (Bcrypt hashes start with $2y$ and are 60 chars long)
        if (strlen($pass) == 60 && substr($pass, 0, 4) == '$2y$') {
            echo "<li>User <b>$username</b> (ID: $id): <span style='color:green'>Already hashed.</span></li>";
            continue;
        }

        // Hash the password
        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        // Update DB
        $updateSql = "UPDATE user_accounts SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $hashed, $id);
        
        if ($stmt->execute()) {
            echo "<li>User <b>$username</b> (ID: $id): <span style='color:blue'>Converted to hash.</span></li>";
            $count++;
        } else {
            echo "<li>User <b>$username</b> (ID: $id): <span style='color:red'>Failed to update.</span></li>";
        }
    }
    echo "</ul>";
} else {
    echo "No users found.";
}

echo "<h3>Migration Complete. Updated $count users.</h3>";
echo "<p>Please delete this file after use.</p>";
?>
