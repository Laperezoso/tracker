<?php
session_start();
require_once "class/db_connect.php"; 


if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION["role"] === "user") {
        header("Location: users/dashboard.php");
        exit;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    
    $query = "SELECT * FROM user_accounts WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

       
        if ($user["active"] != 1) {
            $error = "Your account is inactive. Please contact the administrator.";
        }
        
        elseif ($password === $user["password"]) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            
            if ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: users/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No account found with that username!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Appliance Warranty Tracker</title>
    <style>
      
body {
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: url("image/logo.png") no-repeat center center fixed;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    position: relative;
    overflow: hidden;
}


.overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 1;
}


.login-container {
    position: relative;
    z-index: 2;
}

.login-box {
    background: rgba(255, 255, 255, 0.95);
    padding: 40px 50px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    text-align: center;
    width: 360px;
}

.login-box h2 {
    color: #0d47a1;
    margin-bottom: 10px;
}

.login-box h3 {
    color: #333;
    font-weight: 500;
    margin-bottom: 25px;
}

.login-box label {
    display: block;
    text-align: left;
    margin: 10px 0 5px;
    color: #222;
    font-weight: 500;
}

.login-box input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    margin-bottom: 15px;
    transition: 0.3s;
}

.login-box input:focus {
    border-color: #0d47a1;
    outline: none;
    box-shadow: 0 0 5px #0d47a1;
}

.login-box button {
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

.login-box button:hover {
    background-color: #1565c0;
}

.error {
    color: #d32f2f;
    background: #ffebee;
    border: 1px solid #f44336;
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 15px;
}

    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="login-container">
        <div class="login-box">
            <h2>Appliance Service Warranty Tracker</h2>
            <h3>Login</h3>

            <?php if (!empty($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <label>Username</label>
                <input type="text" name="username" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
