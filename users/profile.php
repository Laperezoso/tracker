<?php
session_start();
require_once "../class/db_connect.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$message = "";
$message_type = ""; // success or danger

// Fetch current user data
$stmt = $conn->prepare("SELECT username, email, profile_pic FROM user_accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle File Upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['profile_pic'])) {
    if (!CSRF::check($_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }
    
    $file = $_FILES['profile_pic'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filetmp = $file['tmp_name'];
    $filesize = $file['size'];
    $fileerror = $file['error'];
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        if ($fileerror === 0) {
            if ($filesize < 2000000) { // 2MB
                $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
                $destination = "../image/" . $new_name;
                
                if (move_uploaded_file($filetmp, $destination)) {
                    // Update DB
                    $updatePic = $conn->prepare("UPDATE user_accounts SET profile_pic = ? WHERE user_id = ?");
                    $updatePic->bind_param("si", $new_name, $user_id);
                    if ($updatePic->execute()) {
                        $message = "Profile picture updated!";
                        $message_type = "success";
                        // Update current user array and session
                        $user['profile_pic'] = $new_name;
                        $_SESSION['profile_pic'] = $new_name;
                    } else {
                        $message = "Database update failed.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Failed to move uploaded file.";
                    $message_type = "danger";
                }
            } else {
                $message = "File is too large (Max 2MB).";
                $message_type = "danger";
            }
        } else {
            $message = "Error uploading file.";
            $message_type = "danger";
        }
    } else {
        $message = "Invalid file type. Allowed: jpg, jpeg, png, gif.";
        $message_type = "danger";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_FILES['profile_pic'])) { // Only run if NOT file upload (avoid double check)
        if (!CSRF::check($_POST['csrf_token'])) {
            die("CSRF validation failed.");
        }
    }
    
    // 1. Update Profile Info
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);

        if (!empty($new_username) && !empty($new_email)) {
            // Check for duplicates (excluding self)
            $check = $conn->prepare("SELECT user_id FROM user_accounts WHERE (username = ? OR email = ?) AND user_id != ?");
            $check->bind_param("ssi", $new_username, $new_email, $user_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "Username or Email already taken by another user.";
                $message_type = "danger";
            } else {
                $update = $conn->prepare("UPDATE user_accounts SET username = ?, email = ? WHERE user_id = ?");
                $update->bind_param("ssi", $new_username, $new_email, $user_id);
                if ($update->execute()) {
                    $message = "Profile updated successfully! (Please re-login if you changed your username)";
                    $message_type = "success";
                    // Update session username just in case
                    $_SESSION["username"] = $new_username;
                    // Refresh data
                    $user['username'] = $new_username;
                    $user['email'] = $new_email;
                } else {
                    $message = "Failed to update profile.";
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Username and Email cannot be empty.";
            $message_type = "danger";
        }
    }

    // 2. Change Password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Get current password hash
        $pwdStmt = $conn->prepare("SELECT password FROM user_accounts WHERE user_id = ?");
        $pwdStmt->bind_param("i", $user_id);
        $pwdStmt->execute();
        $stored_hash = $pwdStmt->get_result()->fetch_assoc()['password'];

        if (password_verify($current_password, $stored_hash)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $updatePwd = $conn->prepare("UPDATE user_accounts SET password = ? WHERE user_id = ?");
                    $updatePwd->bind_param("si", $new_hash, $user_id);
                    if ($updatePwd->execute()) {
                        $message = "Password changed successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to update password.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "New password must be at least 6 characters long.";
                    $message_type = "danger";
                }
            } else {
                $message = "New passwords do not match.";
                $message_type = "danger";
            }
        } else {
            $message = "Incorrect current password.";
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
    <title>My Profile | Warranty Tracker</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand flex items-center gap-2">
        <img src="../image/Clearbglogo.png" alt="Logo" style="height: 40px;">
        Warranty Tracker
    </div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="view_appliances.php" class="nav-link">My Appliances</a>
        <a href="add_appliance.php" class="nav-link">Add Appliance</a>
        <a href="profile.php" class="nav-link active">My Profile</a>
    </div>
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
             <div style="width: 35px; height: 35px; border-radius: 50%; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <img src="../image/<?php echo !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'default_avatar.png'; ?>" 
                     alt="Profile" 
                     style="width: 100%; height: 100%; object-fit: cover;">
             </div>
             <span style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container" style="max-width: 1000px;">
    <div class="flex items-center justify-between mb-4">
        <h2>My Profile</h2>
    </div>

    <?php if (!empty($message)): ?>
        <div class="card mb-4 text-center text-<?php echo $message_type; ?>" style="padding: 1rem; border-left: 4px solid var(--<?php echo $message_type; ?>);">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Profile Picture -->
        <div class="card" style="grid-column: span 2; display: flex; align-items: center; gap: 2rem;">
            <div style="position: relative; width: 100px; height: 100px;">
                <img src="../image/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default_avatar.png'; ?>" 
                     alt="Profile" 
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-color);">
            </div>
            <div style="flex: 1;">
                <h3 class="mb-2">Profile Picture</h3>
                <form method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                    <?php echo CSRF::input(); ?>
                    <input type="file" name="profile_pic" class="form-control" accept="image/*" required style="width: auto;">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">Accepted formats: JPG, PNG. Max size: 2MB.</p>
            </div>
        </div>

        <!-- Update Info -->
        <div class="card">
            <h3 class="mb-4"><i class="fas fa-user-edit"></i> Personal Information</h3>
            <form method="POST">
                <?php echo CSRF::input(); ?>
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <h3 class="mb-4"><i class="fas fa-lock"></i> Change Password</h3>
            <form method="POST">
                <?php echo CSRF::input(); ?>
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>

    </div>
</div>

<footer class="footer">
    <div class="flex justify-center gap-4 mb-4">
        <a href="#" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fab fa-facebook"></i></a>
        <a href="#" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fab fa-twitter"></i></a>
        <a href="mailto:WarrantyTracker@gmail.com" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fas fa-envelope"></i></a>
    </div>
    <p class="text-secondary" style="margin-bottom: 0.5rem;"><strong>Appliance Warranty Tracker</strong> — Making home management easier.</p>
    <p class="text-secondary" style="margin-bottom: 0.5rem;">razelherodias014@gmail.com</p>
    <p class="text-secondary" style="font-size: 0.9rem;">&copy; <?php echo date("Y"); ?> Appliance Service Warranty Tracker</p>
</footer>

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
