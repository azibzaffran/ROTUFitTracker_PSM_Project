<?php
/**
 * Email Helper Function
 * Sends emails using SMTP if configured, otherwise uses PHP mail() as fallback
 */

function sendEmail($to, $subject, $message, $isHtml = true) {
    global $smtp;
    
    // If SMTP is configured, use it
    if (!empty($smtp['host']) && !empty($smtp['username'])) {
        return sendEmailSMTP($to, $subject, $message, $isHtml);
    }
    
    // Fallback to PHP mail() for local testing
    return sendEmailPHP($to, $subject, $message, $isHtml);
}

/**
 * Send email using SMTP
 */
function sendEmailSMTP($to, $subject, $message, $isHtml = true) {
    global $smtp;
    
    // For production, use PHPMailer or similar library
    // This is a basic implementation
    $headers = "From: {$smtp['from_name']} <{$smtp['from_email']}>\r\n";
    $headers .= "Reply-To: {$smtp['from_email']}\r\n";
    
    if ($isHtml) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    // Try to use mail() function
    return mail($to, $subject, $message, $headers);
}

/**
 * Send email using PHP mail() - For local testing
 */
function sendEmailPHP($to, $subject, $message, $isHtml = true) {
    global $smtp;
    
    $headers = "From: {$smtp['from_name']} <{$smtp['from_email']}>\r\n";
    $headers .= "Reply-To: {$smtp['from_email']}\r\n";
    
    if ($isHtml) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate HTML email template for password reset
 */
function getPasswordResetEmailTemplate($userName, $resetLink) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2d3e2d, #4a5f4a); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; }
            .button { display: inline-block; padding: 12px 30px; background: #4a5f4a; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>ROTUFitTracker</h1>
            </div>
            <div class='content'>
                <h2>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($userName) . ",</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #4a5f4a;'>" . htmlspecialchars($resetLink) . "</p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                <p>Best regards,<br>ROTUFitTracker Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
