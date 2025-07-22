<?php
session_start();
$teacher_name = $_SESSION['teacher_name'] ?? '';
$tutor_type = $_SESSION['tutor_type'] ?? '';
$teacher_email = $_SESSION['teacher_email'] ?? '';  // only if your table has this

include 'configuration.php';

if (!isset($_SESSION['teacher_name'])) {
    die("Access denied.");
}




// Fetch the tutor's type (Professional or Peer)
$type_query = $conn->query("SELECT type FROM teacher_login WHERE name = '$teacher_name'");
$tutor_type = ($row = $type_query->fetch_assoc()) ? $row['type'] : '';

// Get total tests created by the tutor
$total_tests_query = $conn->query("SELECT COUNT(*) AS total_tests FROM tests WHERE teacher_name = '$teacher_name'");
$total_tests = ($row = $total_tests_query->fetch_assoc()) ? (int)$row['total_tests'] : 0;

// Fetch all students accepted by this teacher and their submission/marks info
$result = $conn->query("
    SELECT 
        br.username AS student_name,
        br.email AS student_email,
        COUNT(ts.id) AS total_tests_submitted,
        AVG(ts.marks) AS average_marks
    FROM booking_request br
    LEFT JOIN test_submissions ts 
        ON br.email = ts.student_email 
        AND ts.teacher_name = '$teacher_name'
    WHERE br.status = 'accepted' 
      AND br.required_tutor_type = '$tutor_type'
      AND br.teacher_email = '$teacher_email'
    GROUP BY br.email, br.username
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; }
        .container { margin-top: 40px; }
        .card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header { background-color: #4e73df; color: white; border-radius: 12px 12px 0 0; }
        .progress-bar { font-size: 0.85rem; }
    .navbar {
        padding: 0.5rem 1rem;
    }
    
    .nav-link {
        font-weight: 500;
        color: #495057;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover, .nav-link:focus {
        color: #4e73df;
        background-color: #f8f9fa;
    }
    
    .nav-link.active {
        color: #4e73df;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    
    .dropdown-item {
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #4e73df;
    }
    
    .avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    </style>
</head>
<body class="container-fluid">
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="home_professional.html" style="font-size: 1.5rem;">
            <i class="fas fa-graduation-cap me-2" style="color: #4e73df;"></i>EduLearning
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
              
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded" href="notification_tutor.php">
                        <i class="fas fa-bell me-1 d-lg-none"></i> Notification
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded" href="yourstudent.php">
                        <i class="fas fa-users me-1 d-lg-none"></i> Your Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded" href="create_test.php">
                        <i class="fas fa-edit me-1 d-lg-none"></i> Create Tests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded" href="view_submissions.php">
                        <i class="fas fa-file-upload me-1 d-lg-none"></i> Submissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded active" href="student_progress.php">
                        <i class="fas fa-chart-line me-1 d-lg-none"></i> Progress
                    </a>
                </li>
                
              <li class="nav-item dropdown ms-lg-3">
    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="avatar avatar-sm me-1">
            <i class="fas fa-user-circle fs-4" style="color: #4e73df;"></i>
        </div>
        <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
    </a>
    
    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 200px;">
        <li><a class="dropdown-item" href="profile_tutor.php"><i class="fas fa-user me-2"></i> Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
    </ul>
</li>

                </li>
            </ul>
        </div>
    </div>
</nav>


    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Student Progress Overview</h3>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student Email</th>
                                <th>Total Tests Assigned</th>
                                <th>Tests Submitted</th>
                                <th>Submission %</th>
                                <th>Average Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()):
                                $submitted = (int)$row['total_tests_submitted'];
                                $avg_marks = is_null($row['average_marks']) ? '-' : round($row['average_marks'], 2);
                                $percent = $total_tests > 0 ? round(($submitted / $total_tests) * 100) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= $total_tests ?></td>
                                <td><?= $submitted ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?= $percent ?>%;">
                                            <?= $percent ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?= $avg_marks ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No students found.</div>
            <?php endif; ?>
        </div>
    </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

