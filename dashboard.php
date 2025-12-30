<?php
session_start();
require_once '../authentication/config.php';

// Require login
if (empty($_SESSION['email'])) {
    header('Location: ../authentication/index.php');
    exit();
}

$email = $conn->real_escape_string($_SESSION['email']);

// Fetch user ID first
$user_id_query = $conn->query("SELECT id FROM users WHERE email='$email' LIMIT 1");
$user_id_row = $user_id_query->fetch_assoc();
$user_id = $user_id_row['id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $height = $_POST['height'] ? floatval($_POST['height']) : null;
    $weight = $_POST['weight'] ? floatval($_POST['weight']) : null;
    $profile_picture = null;
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_dir = 'uploads/';
            
            // Create uploads directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                $old_pic_query = $conn->query("SELECT profile_picture FROM users WHERE email='$email'");
                $old_pic = $old_pic_query->fetch_assoc()['profile_picture'];
                if ($old_pic && file_exists($upload_dir . $old_pic)) {
                    unlink($upload_dir . $old_pic);
                }
                $profile_picture = $new_filename;
            }
        }
    }
    
    // Update profile with or without picture
    if ($profile_picture) {
        $stmt = $conn->prepare("UPDATE users SET height = ?, weight = ?, profile_picture = ? WHERE email = ?");
        $stmt->bind_param('ddss', $height, $weight, $profile_picture, $email);
    } else {
        $stmt = $conn->prepare("UPDATE users SET height = ?, weight = ? WHERE email = ?");
        $stmt->bind_param('dds', $height, $weight, $email);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile.";
    }
    
    header('Location: dashboard.php');
    exit();
}

// Fetch user info
$stmt = $conn->prepare("SELECT id, name, email, role, height, weight, profile_picture FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set default created_at if not exists
if (!isset($user['created_at'])) {
    $user['created_at'] = date('Y-m-d H:i:s');
}

if (!$user) {
    header('Location: ../authentication/logout.php');
    exit();
}

// Map roles to display names
$roleDisplay = [
    'cadet officer' => 'CADET OFFICER',
    'chief instructor' => 'CHIEF INSTRUCTOR',
    'officer' => 'OFFICER',
    'trainer' => 'TRAINER'
];
$displayRole = $roleDisplay[strtolower($user['role'])] ?? strtoupper($user['role']);

// Calculate BMI if height and weight are available
$bmi = null;
if ($user['height'] && $user['weight']) {
    $heightInMeters = $user['height'] / 100;
    $bmi = round($user['weight'] / ($heightInMeters * $heightInMeters), 1);
}

// Get initials for avatar
$nameParts = explode(' ', $user['name']);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - ROTUFitTracker</title>
    <link rel="stylesheet" href="dashboard.css?v=<?= time() ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-top">
                <div class="logo-section">
                    <img src="ROTUFitTrackerLogo.png" alt="ROTUFitTracker Logo" class="system-logo">
                    <div class="logo-text">
                        <h1>User Profile</h1>
                        <p>A System Monitoring Reserve Officer Training Unit(ROTU) Training Progress</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../homepage/index.php" class="btn btn-secondary">← Back to Home</a>
                    <a href="../authentication/logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    ✗ <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Dashboard Content -->
        <div class="main-info-section">
            <!-- User Info Card -->
            <div class="user-info-card">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_picture']) && file_exists('uploads/' . $user['profile_picture'])): ?>
                        <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h2><?= htmlspecialchars($user['name']) ?></h2>
                    <span class="role-badge"><?= htmlspecialchars($displayRole) ?></span>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Member Since</span>
                            <span class="info-value"><?= date('F d, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Height</span>
                            <span class="info-value"><?= $user['height'] ? number_format($user['height'], 1) . ' cm' : 'Not set' ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Weight</span>
                            <span class="info-value"><?= $user['weight'] ? number_format($user['weight'], 1) . ' kg' : 'Not set' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-actions-row">
                <!-- Health Stats -->
                <div class="info-section">
                    <h3>💪 Health Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value"><?= $bmi ?? '--' ?></div>
                            <div class="stat-label">BMI</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?= $user['height'] ? number_format($user['height'], 0) : '--' ?></div>
                            <div class="stat-label">Height (cm)</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?= $user['weight'] ? number_format($user['weight'], 1) : '--' ?></div>
                            <div class="stat-label">Weight (kg)</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?= ceil((time() - strtotime($user['created_at'])) / 86400) ?></div>
                            <div class="stat-label">Days Active</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="info-section">
                    <h3>⚡ Quick Actions</h3>
                    <div class="quick-actions">
                        <?php if (in_array(strtolower($user['role']), ['chief instructor', 'officer'])): ?>
                            <a href="../training_management/training_management.php" class="action-link">
                                <div class="action-icon">📋</div>
                                <div class="action-text">
                                    <h4>Training Management</h4>
                                    <p>Create and manage training schedules</p>
                                </div>
                            </a>
                        <?php endif; ?>
                        <?php if (strtolower($user['role']) == 'cadet officer'): ?>
                            <a href="../ROLES/<?= strtolower($user['role']) ?>.php" class="action-link">
                                <div class="action-icon">🎯</div>
                                <div class="action-text">
                                    <h4>My Workouts</h4>
                                    <p>View and log your workouts</p>
                                </div>
                            </a>
                        <?php endif; ?>
                        <a href="#edit-profile" class="action-link" onclick="document.getElementById('edit-profile').scrollIntoView({behavior: 'smooth'});">
                            <div class="action-icon">✏️</div>
                            <div class="action-text">
                                <h4>Edit Profile</h4>
                                <p>Update your personal information</p>
                            </div>
                        </a>
                        <a href="profile.php" class="action-link">
                            <div class="action-icon">👤</div>
                            <div class="action-text">
                                <h4>Simple Profile</h4>
                                <p>View basic profile information</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Edit Profile Section -->
            <div class="card edit-profile-section" id="edit-profile">
                <div class="card-header">
                    <h3>✏️ Edit Profile Information</h3>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                        <small style="color: #666; font-size: 12px;">Upload JPG, PNG, or GIF (Max 5MB)</small>
                        <?php if (!empty($user['profile_picture'])): ?>
                            <div style="margin-top: 10px;">
                                <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Current Profile Picture" style="max-width: 100px; border-radius: 6px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" disabled>
                            <small style="color: #666; font-size: 12px;">Contact administrator to change name</small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small style="color: #666; font-size: 12px;">Contact administrator to change email</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" id="height" name="height" class="form-control" 
                                   value="<?= $user['height'] ?? '' ?>" 
                                   step="0.1" min="50" max="300" 
                                   placeholder="Enter height in centimeters">
                        </div>
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" class="form-control" 
                                   value="<?= $user['weight'] ?? '' ?>" 
                                   step="0.1" min="20" max="300" 
                                   placeholder="Enter weight in kilograms">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" class="form-control" value="<?= htmlspecialchars($displayRole) ?>" disabled>
                        <small style="color: #666; font-size: 12px;">Contact administrator to change role</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
