<?php
session_start();
include 'configuration.php';

// Get teacher name from session
$teacher_name = $_SESSION['teacher_name'] ?? '';

// Handle marks allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'], $_POST['marks'])) {
    $submission_id = $_POST['submission_id'];
    $marks = $_POST['marks'];

    $stmt = $conn->prepare("UPDATE test_submissions SET marks = ? WHERE id = ?");
    $stmt->bind_param("ii", $marks, $submission_id);
    $stmt->execute();
}

// Fetch distinct test titles by this teacher
$test_result = $conn->prepare("SELECT DISTINCT test_title FROM tests WHERE teacher_name = ?");
$test_result->bind_param("s", $teacher_name);
$test_result->execute();
$test_titles = $test_result->get_result();

// Check if a test is selected
$selected_test = $_GET['test_title'] ?? '';
$submissions = [];
if ($selected_test) {
    $stmt = $conn->prepare("
        SELECT ts.*, sl.name AS student_name 
        FROM test_submissions ts 
        JOIN student_login sl ON ts.student_email = sl.email 
        WHERE ts.test_title = ? AND ts.teacher_name = ?
    ");
    $stmt->bind_param("ss", $selected_test, $teacher_name);
    $stmt->execute();
    $submissions = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test Submissions | EduLearning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-dark: #5a5c69;
            --success-color: #1cc88a;
        }
        
        body {
            background-color: #f8f9fc;
            padding-top: 70px;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
        }
        
        .navbar {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background: white;
        }
        
        .nav-link {
            font-weight: 600;
            color: var(--text-dark);
            padding: 0.75rem 1rem;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .submissions-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        .test-selector {
            max-width: 500px;
            margin-bottom: 2rem;
        }
        
        .submissions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .submissions-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1rem;
        }
        
        .submissions-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .submissions-table tr:nth-child(even) {
            background-color: var(--secondary-color);
        }
        
        .submissions-table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .pdf-link {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .pdf-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .marks-input {
            max-width: 80px;
            text-align: center;
        }
        
        .no-submissions {
            color: var(--text-dark);
            font-style: italic;
            padding: 2rem;
            text-align: center;
            background-color: var(--secondary-color);
            border-radius: 0.5rem;
        }
        
        .badge-pending {
            background-color: #f6c23e;
            color: #000;
        }
        
        .badge-graded {
            background-color: var(--success-color);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="home_professional.html">
                <i class="fas fa-graduation-cap me-2"></i>EduLearning
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                  
                    <li class="nav-item">
                        <a class="nav-link" href="notification_tutor.php">Notification</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="yourstudent.php">Your Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_test.php">Create Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_submissions.php">Submissions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student_progress.php">Progress</a>
                    </li>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                          <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['teacher_name']) ?>
                        </a>
                          <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile_tutor.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                      </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="submissions-container">
            <h2 class="page-title">
                <i class="fas fa-file-upload me-2"></i>Test Submissions
            </h2>

            <!-- Test title selection form -->
            <form method="GET" class="test-selector">
                <div class="input-group mb-3">
                    <label class="input-group-text" for="test_title">
                        <i class="fas fa-book me-2"></i>Select Test
                    </label>
                    <select name="test_title" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Select Test --</option>
                        <?php while ($row = $test_titles->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['test_title']) ?>" <?= $selected_test === $row['test_title'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['test_title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php if ($submissions && $submissions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table submissions-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user-graduate me-2"></i>Student</th>
                                <th><i class="fas fa-file-pdf me-2"></i>Submission</th>
                                <th><i class="fas fa-clock me-2"></i>Submitted</th>
                                <th><i class="fas fa-star me-2"></i>Status</th>
                                <th><i class="fas fa-edit me-2"></i>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($submission = $submissions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($submission['student_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($submission['student_email']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= htmlspecialchars($submission['pdf_path']) ?>" target="_blank" class="pdf-link">
                                            <i class="fas fa-external-link-alt me-1"></i>View Submission
                                        </a>
                                    </td>
                                    <td>
                                        <?= date('M j, Y g:i A', strtotime($submission['submitted_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if (is_null($submission['marks'])): ?>
                                            <span class="badge badge-pending rounded-pill px-3 py-1">
                                                <i class="fas fa-hourglass-half me-1"></i>Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-graded rounded-pill px-3 py-1">
                                                <i class="fas fa-check me-1"></i>Graded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <input type="number" name="marks" min="0" 
                                                   value="<?= $submission['marks'] ?? '' ?>" 
                                                   class="form-control marks-input me-2" 
                                                   placeholder="Marks" required>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-save me-1"></i>Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($selected_test): ?>
                <div class="no-submissions">
                    <i class="fas fa-inbox fa-2x mb-3"></i>
                    <h4>No submissions found for this test</h4>
                    <p class="mb-0">Students haven't submitted any work for this test yet.</p>
                </div>
            <?php else: ?>
                <div class="no-submissions">
                    <i class="fas fa-book-open fa-2x mb-3"></i>
                    <h4>Select a test to view submissions</h4>
                    <p class="mb-0">Choose a test from the dropdown above to see student submissions.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>