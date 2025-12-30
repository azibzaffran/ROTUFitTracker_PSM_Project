<?php
session_start();

// Require login
if (empty($_SESSION['email'])) {
    header('Location: ../authentication/index.php');
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - RotuFit Tracker</title>
    <link rel="stylesheet" href="homepage.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?= htmlspecialchars($name) ?>!</h1>
            <p>Choose where you'd like to go:</p>
        </div>

        <?php if ($error_message): ?>
        <div class="error-message">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <div class="options-container">
            <a href="../user_profile/dashboard.php" class="option-card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h2>User Profile</h2>
                <p>View and edit your personal information, health metrics, and settings</p>
            </a>

            <a href="../training_management/training_management.php" class="option-card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <h2>Training Management</h2>
                <p>Manage your workout routines, track progress, and plan your training schedule</p>
            </a>
        </div>

        <div class="logout-section">
            <a href="../authentication/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</body>
</html>
