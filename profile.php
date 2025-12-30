<?php
session_start();
require_once '../authentication/config.php';

// Require login
if (empty($_SESSION['email'])) {
    header('Location: ../authentication/index.php');
    exit();
}

$email = $conn->real_escape_string($_SESSION['email']);

// Fetch latest user info from DB
$stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // If user not found, force logout
    header('Location: ../authentication/logout.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Profile</title>
    <link rel="stylesheet" href="../authentication/style.css">
</head>
<body>
    <div class="container">
        <div class="form-box active">
            <h2>User Profile</h2>
            <div style="text-align:left; padding:10px 20px;">
                <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
                <p><strong>Member since:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
            </div>

            <div style="margin-top:16px;">
                <p><a href="dashboard.php">← Back to Dashboard</a></p>
                <p><a href="../homepage/index.php">← Back to Homepage</a></p>
                <p><a href="../authentication/logout.php">Logout</a></p>
            </div>
        </div>
    </div>
</body>
</html>
