<?php
session_start();
require_once 'config.php';
require_once 'email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email']));
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Delete old tokens for this email
        $conn->query("DELETE FROM password_resets WHERE email='$email'");
        
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $token, $expires_at);
        
        if ($stmt->execute()) {
            // Create reset link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . "://" . $host . "/rotufittracker/authentication/reset_password.php?token=" . $token;
            
            // Send email
            $emailSubject = "Password Reset Request - ROTUFitTracker";
            $emailMessage = getPasswordResetEmailTemplate($user['name'], $resetLink);
            
            // For local testing without email server, show link
            if (sendEmail($email, $emailSubject, $emailMessage, true)) {
                $success = "Password reset instructions have been sent to your email address.";
            } else {
                // Fallback: Display link for local testing
                $success = "Email server not configured. Use this link to reset password: <a href='$resetLink' target='_blank'>Reset Password</a>";
            }
        } else {
            $error = "Error processing request. Please try again.";
        }
    } else {
        // Don't reveal if email exists or not (security best practice)
        $success = "If that email address is registered, password reset instructions have been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ROTUFitTracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .info-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
            text-align: center;
        }
        
        .form-header {
            margin-bottom: 10px;
        }
        
        .form-header h2 {
            margin-bottom: 8px;
        }
        
        .back-link {
            margin-top: 25px;
            text-align: center;
        }
        
        .back-link a {
            color: #4a5f4a;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #2d3e2d;
            text-decoration: underline;
        }
        
        .success-message {
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            color: #155724;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .success-message a {
            color: #0c5460;
            font-weight: 600;
        }
        
        .error-message {
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            color: #721c24;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        input[type="email"] {
            margin-bottom: 20px;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box active">
            <form action="" method="post">
                <div class="form-header">
                    <h2>🔒 Forgot Password?</h2>
                </div>
                
                <p class="info-text">
                    No worries! Enter your email address below and we'll send you a link to reset your password. The link will be valid for 24 hours.
                </p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        ⚠️ <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        ✅ <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                    <input type="email" name="email" placeholder="Enter your email address" required autofocus>
                    <button type="submit">📧 Send Reset Link</button>
                <?php endif; ?>
                
                <div class="back-link">
                    <a href="index.php">← Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
