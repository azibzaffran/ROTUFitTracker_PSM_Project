<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$token = '';
$validToken = false;

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Verify token
    $stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    
    if ($reset) {
        // Check if token is expired
        if (strtotime($reset['expires_at']) < time()) {
            $error = "This password reset link has expired. Please request a new one.";
        } elseif ($reset['used'] == 1) {
            $error = "This password reset link has already been used. Please request a new one.";
        } else {
            $validToken = true;
        }
    } else {
        $error = "Invalid password reset link.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $conn->real_escape_string($_POST['token']);
    
    // Validate passwords
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Get email from token
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        
        if ($reset) {
            $email = $reset['email'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param('ss', $hashedPassword, $email);
            
            if ($stmt->execute()) {
                // Mark token as used
                $conn->query("UPDATE password_resets SET used = 1 WHERE token = '$token'");
                
                $success = "Password reset successful! You can now login with your new password.";
                $_SESSION['success'] = "Password reset successful! Please login with your new password.";
                
                // Redirect to login after 3 seconds
                header("refresh:3;url=index.php");
            } else {
                $error = "Error updating password. Please try again.";
            }
        } else {
            $error = "Invalid or expired token.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ROTUFitTracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .password-requirements {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 13px;
            color: #666;
        }
        .password-requirements ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }
        .password-requirements li {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box active">
            <?php if ($success): ?>
                <h2>✓ Success!</h2>
                <p class="success-message"><?= $success ?></p>
                <p style="text-align: center; color: #666;">
                    Redirecting to login page...
                </p>
            <?php elseif (!$validToken): ?>
                <h2>Invalid Link</h2>
                <p class="error-message"><?= $error ?></p>
                <p style="margin-top: 20px; text-align: center;">
                    <a href="forgot_password.php">Request a new password reset link</a>
                </p>
                <p style="margin-top: 10px; text-align: center;">
                    <a href="index.php">← Back to Login</a>
                </p>
            <?php else: ?>
                <form action="" method="post">
                    <h2>Reset Password</h2>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        Enter your new password below.
                    </p>
                    
                    <?php if ($error): ?>
                        <p class="error-message"><?= $error ?></p>
                    <?php endif; ?>
                    
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 6 characters long</li>
                            <li>Both passwords must match</li>
                        </ul>
                    </div>
                    
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="password" name="password" placeholder="New Password" required minlength="6" autofocus>
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
                    <button type="submit">Reset Password</button>
                    
                    <p style="margin-top: 20px;">
                        <a href="index.php">← Back to Login</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
