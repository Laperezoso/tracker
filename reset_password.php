<?php
session_start();
require_once "class/db_connect.php";

$message = "";
$message_type = "";
$token = $_GET["token"] ?? "";
$valid_token = false;

if (empty($token)) {
    die("Invalid request."); // Or redirect to login
}

// Check Token Validity (Hash match + Expiry)
$token_hash = hash("sha256", $token);
$stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $valid_token = true;
    $user_id = $user['user_id'];
} else {
    $message = "Invalid or expired token. Please request a new one.";
    $message_type = "danger";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    if (!CSRF::check($_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $message_type = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        // Hash and Update
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $conn->prepare("UPDATE user_accounts SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?");
        $update->bind_param("si", $hashed_password, $user_id);
        
        if ($update->execute()) {
            $message = "Password reset successfully! You can now <a href='login.php' style='color:white; text-decoration:underline;'>Login</a>.";
            $message_type = "success";
            $valid_token = false; // Disable form
        } else {
            $message = "Failed to update password.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Warranty Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo" style="background: transparent;">
                <img src="image/Clearbglogo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h2>Reset Password</h2>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Create a new secure password</p>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background-color: var(--<?php echo $message_type; ?>); color: white; padding: 0.75rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center;">
                <?php echo $message; // allow html for login link ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
        <form method="POST" action="">
            <?php echo CSRF::input(); ?>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="New Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full">Reset Password</button>
        </form>
        <?php elseif ($message_type === 'danger'): ?>
             <div style="text-align: center; margin-top: 1.5rem;">
                <a href="forgot_password.php" class="btn btn-secondary w-full">Request New Link</a>
            </div>
        <?php endif; ?>
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
