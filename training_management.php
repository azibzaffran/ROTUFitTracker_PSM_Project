<?php
session_start();
require_once '../authentication/config.php';

// Require login
if (empty($_SESSION['email'])) {
    header('Location: ../authentication/index.php');
    exit();
}

$email = $conn->real_escape_string($_SESSION['email']);

// Fetch user info
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: ../authentication/logout.php');
    exit();
}

// Check if user is Chief Instructor or Officer
if (!in_array(strtolower($user['role']), ['chief instructor', 'officer'])) {
    $_SESSION['error_message'] = "Access denied. Only Chief Instructors and Officers can access Training Management.";
    header('Location: ../homepage/index.php');
    exit();
}

// Handle training schedule creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    
    // Insert training schedule
    $stmt = $conn->prepare("INSERT INTO training_schedules (title, start_date, end_date, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $title, $start_date, $end_date, $user['id']);
    
    if ($stmt->execute()) {
        $schedule_id = $conn->insert_id;
        
        // Insert training types for each level
        $levels = ['junior', 'intermediate', 'senior'];
        foreach ($levels as $level) {
            $instructor_id = $_POST[$level . '_instructor'] ? intval($_POST[$level . '_instructor']) : null;
            $location = $conn->real_escape_string($_POST[$level . '_location']);
            
            if ($location) { // Only insert if location is provided
                $stmt = $conn->prepare("INSERT INTO training_types (schedule_id, level, instructor_id, location) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isis', $schedule_id, $level, $instructor_id, $location);
                $stmt->execute();
            }
        }
        
        $_SESSION['success_message'] = "Training schedule created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating training schedule.";
    }
    
    header('Location: training_management.php');
    exit();
}

// Handle training deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM training_schedules WHERE id = ? AND created_by = ?");
    $stmt->bind_param('ii', $delete_id, $user['id']);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Training schedule deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting training schedule.";
    }
    
    header('Location: training_management.php');
    exit();
}

// Fetch all training schedules
$schedules_query = "
    SELECT ts.*, u.name as created_by_name 
    FROM training_schedules ts 
    JOIN users u ON ts.created_by = u.id 
    ORDER BY ts.start_date DESC
";
$schedules_result = $conn->query($schedules_query);

// Fetch all instructors (trainers and officers)
$instructors_query = "SELECT id, name, role FROM users WHERE LOWER(role) IN ('trainer', 'officer', 'ci') ORDER BY name";
$instructors_result = $conn->query($instructors_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Management - ROTUFitTracker</title>
    <link rel="stylesheet" href="training.css?v=<?= time() ?>">
</head>
<body>
    <div class="training-container">
        <!-- Header -->
        <div class="training-header">
        <div class="header-top">
            <div class="logo-section">
                <img src="ROTUFitTrackerLogo.png" alt="ROTUFitTracker Logo" class="system-logo" onerror="this.style.display='none'">
                <div class="logo-text">
                    <h1>Training Management</h1>
                    <p>Create and manage training schedules for all levels</p>
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
        
        <div class="content-grid">
            <!-- Create Training Form -->
            <div class="card">
                <h2>➕ Create New Training</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Training Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               placeholder="e.g., Physical Fitness Assessment 2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                    
                    <div class="training-type-section">
                        <h3>Training Type Details</h3>
                        
                        <!-- Junior Level -->
                        <div class="level-card">
                            <h4>🟢 Junior Level</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="junior_instructor">Instructor</label>
                                    <select id="junior_instructor" name="junior_instructor" class="form-control">
                                        <option value="">Select Instructor</option>
                                        <?php 
                                        $instructors_result->data_seek(0);
                                        while ($instructor = $instructors_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $instructor['id'] ?>">
                                                <?= htmlspecialchars($instructor['name']) ?> (<?= htmlspecialchars($instructor['role']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="junior_location">Location</label>
                                    <input type="text" id="junior_location" name="junior_location" 
                                           class="form-control" placeholder="e.g., Training Ground A">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Intermediate Level -->
                        <div class="level-card">
                            <h4>🟡 Intermediate Level</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="intermediate_instructor">Instructor</label>
                                    <select id="intermediate_instructor" name="intermediate_instructor" class="form-control">
                                        <option value="">Select Instructor</option>
                                        <?php 
                                        $instructors_result->data_seek(0);
                                        while ($instructor = $instructors_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $instructor['id'] ?>">
                                                <?= htmlspecialchars($instructor['name']) ?> (<?= htmlspecialchars($instructor['role']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="intermediate_location">Location</label>
                                    <input type="text" id="intermediate_location" name="intermediate_location" 
                                           class="form-control" placeholder="e.g., Training Ground B">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Senior Level -->
                        <div class="level-card">
                            <h4>🔴 Senior Level</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="senior_instructor">Instructor</label>
                                    <select id="senior_instructor" name="senior_instructor" class="form-control">
                                        <option value="">Select Instructor</option>
                                        <?php 
                                        $instructors_result->data_seek(0);
                                        while ($instructor = $instructors_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $instructor['id'] ?>">
                                                <?= htmlspecialchars($instructor['name']) ?> (<?= htmlspecialchars($instructor['role']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="senior_location">Location</label>
                                    <input type="text" id="senior_location" name="senior_location" 
                                           class="form-control" placeholder="e.g., Training Ground C">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_schedule" class="btn btn-success" style="width: 100%;">
                        ✓ Create Training Schedule
                    </button>
                </form>
            </div>
            
            <!-- Training Schedules List -->
            <div class="card">
                <h2>📅 Training Schedules</h2>
                <div class="schedule-list">
                    <?php if ($schedules_result->num_rows > 0): ?>
                        <?php while ($schedule = $schedules_result->fetch_assoc()): ?>
                            <?php
                            // Fetch training types for this schedule
                            $types_query = "
                                SELECT tt.*, u.name as instructor_name 
                                FROM training_types tt 
                                LEFT JOIN users u ON tt.instructor_id = u.id 
                                WHERE tt.schedule_id = {$schedule['id']}
                            ";
                            $types_result = $conn->query($types_query);
                            ?>
                            
                            <div class="schedule-item">
                                <div class="schedule-header">
                                    <div>
                                        <div class="schedule-title"><?= htmlspecialchars($schedule['title']) ?></div>
                                        <div class="schedule-dates">
                                            📅 <?= date('M d, Y', strtotime($schedule['start_date'])) ?> 
                                            → <?= date('M d, Y', strtotime($schedule['end_date'])) ?>
                                        </div>
                                    </div>
                                    <?php if ($schedule['created_by'] == $user['id']): ?>
                                        <a href="?delete=<?= $schedule['id'] ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this training schedule?')">
                                            🗑️ Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="schedule-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Created By</span>
                                        <span class="detail-value"><?= htmlspecialchars($schedule['created_by_name']) ?></span>
                                    </div>
                                    
                                    <?php if ($types_result->num_rows > 0): ?>
                                        <div class="detail-row" style="border: none; padding-top: 15px;">
                                            <span class="detail-label">Training Levels</span>
                                        </div>
                                        <?php while ($type = $types_result->fetch_assoc()): ?>
                                            <div class="detail-row">
                                                <span class="detail-label">
                                                    <span class="level-badge level-<?= $type['level'] ?>">
                                                        <?= strtoupper($type['level']) ?>
                                                    </span>
                                                </span>
                                                <span class="detail-value">
                                                    👤 <?= $type['instructor_name'] ?? 'No instructor' ?> | 
                                                    📍 <?= htmlspecialchars($type['location']) ?>
                                                </span>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="detail-row">
                                            <span class="detail-value" style="color: #999;">No training type details added</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <h3>No Training Schedules Yet</h3>
                            <p>Create your first training schedule using the form on the left.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
