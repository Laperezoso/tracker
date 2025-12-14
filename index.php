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
    if (!CSRF::check($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed. Please refresh the page.");
    }

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
        
        // Check if password matches (Try Hash first, then Plain Text)
        // 1. Check if it matches as a BCRYPT hash
        if (password_verify($password, $user["password"])) {
             // Valid hash, proceed
             $_SESSION["user_id"] = $user["user_id"];
             $_SESSION["username"] = $user["username"];
             $_SESSION["role"] = $user["role"];
             $_SESSION["profile_pic"] = $user["profile_pic"];
             // ... redirect below
        } 
        // 2. Check if it matches as plain text (Migration logic)
        elseif ($password === $user["password"]) {
            // It matches plain text! Upgrade to Hash.
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE user_accounts SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $newHash, $user["user_id"]);
            $updateStmt->execute();

            // Login check: proceed
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["profile_pic"] = $user["profile_pic"];
        } 
        else {
            // Password incorrect
             $error = "Invalid password!";
        }

        // If session is set (Login successful)
        if (isset($_SESSION["user_id"])) {
             if ($user["role"] === "admin") {
                 header("Location: admin/dashboard.php");
             } else {
                 header("Location: users/dashboard.php");
             }
             exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Appliance Warranty Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo" style="background: transparent;">
                <img src="image/Clearbglogo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h2>Welcome Back</h2>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Sign in to your account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background-color: var(--danger); color: white; padding: 0.75rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo CSRF::input(); ?>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full">Sign In</button>
        </form>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
