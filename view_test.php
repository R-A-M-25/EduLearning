<?php
session_start();
include 'configuration.php';

// 1. Session validation
if (!isset($_SESSION['student_email']) || !isset($_SESSION['student_name'])) {
    echo "Unauthorized access. Please login.";
    exit();
}

$student_email = $_SESSION['student_email'];
$student_name = $_SESSION['student_name'];

$allowedTests = [];

try {
    // 2. Fetch accepted bookings for student
    $stmt = $conn->prepare("SELECT subject, teacher_email FROM booking_request WHERE email = ? AND status = 'accepted'");
    $stmt->bind_param("s", $student_email);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $teacher_email = $row['teacher_email'];

        // 3. Get tests for this teacher
        $testStmt = $conn->prepare("SELECT * FROM tests WHERE teacher_email = ?");
        $testStmt->bind_param("s", $teacher_email);
        $testStmt->execute();
        $testResult = $testStmt->get_result();

        while ($test = $testResult->fetch_assoc()) {
            $allowedTests[] = $test;
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching tests: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Tests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
       <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
.test-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.test-header {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.test-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.test-card {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.test-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.test-card-body {
    padding: 20px;
}

.test-title {
    color: #3498db;
    margin-top: 0;
    margin-bottom: 10px;
}

.test-description {
    color: #555;
    margin-bottom: 15px;
    line-height: 1.5;
}

.test-details {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.detail-label {
    font-weight: 600;
    color: #2c3e50;
}

.test-date {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-top: 10px;
    font-style: italic;
}

.submit-section {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center; 
    justify-content: center; 
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.submit-button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 12px 25px;
    font-size: 1.1em;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: auto;
    max-width: 400px;
}


.submit-button:hover {
    background-color: #2980b9;
}

.no-tests {
    text-align: center;
    color: #7f8c8d;
    font-size: 1.2em;
    margin-top: 50px;
}

.navbar {
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        padding: 0.5rem 1rem;
        position: sticky;
        top: 0;
        z-index: 1020;
        transition: all 0.3s ease;
    }
    
    /* Navbar on scroll */
    .navbar.scrolled {
        padding: 0.25rem 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    /* Brand/Logo */
    .navbar-brand {
        font-weight: 700;
        color: var(--primary);
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .navbar-brand:hover {
        color: var(--primary-dark);
        transform: translateY(-1px);
    }
    
    .navbar-brand i {
        margin-right: 0.5rem;
        font-size: 1.25em;
    }
    
    /* Nav Items */
    .nav-link {
        font-weight: 500;
        color: var(--dark);
        padding: 0.75rem 1.25rem;
        margin: 0 0.25rem;
        border-radius: 0.5rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        color: var(--primary);
        background: rgba(79, 70, 229, 0.05);
    }
    
    .nav-link.active {
        color: var(--primary);
        font-weight: 600;
        background: rgba(79, 70, 229, 0.1);
    }
    
    /* Active link indicator */
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background: var(--primary);
        border-radius: 3px 3px 0 0;
    }
    
    /* Dropdown Menu */
    .dropdown-menu {
        border: none;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
        padding: 0.5rem 0;
        margin-top: 0.5rem;
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
        color: var(--dark);
        transition: all 0.2s ease;
        border-radius: 0.25rem;
        margin: 0 0.5rem;
        width: auto;
    }
    
    .dropdown-item:hover {
        background: var(--primary);
        
    }
    
    .dropdown-divider {
        margin: 0.25rem 0.75rem;
        border-color: rgba(0, 0, 0, 0.05);
    }
    
    /* User Profile Dropdown */
    .user-avatar {
        display: inline-flex;
        align-items: center;
    }
    
    .user-avatar i {
        font-size: 1.25rem;
        color: var(--primary);
        margin-right: 0.5rem;
    }
    
    /* Mobile Toggle Button */
    .navbar-toggler {
        border: none;
        padding: 0.5rem;
    }
    
    .navbar-toggler:focus {
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
    }
    
    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            padding: 1rem 0;
        }
        
        .nav-link {
            margin: 0.25rem 0;
            padding: 0.75rem 1.5rem;
        }
        
        .nav-link.active::after {
            display: none;
        }
        
        .dropdown-menu {
            box-shadow: none;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin: 0.5rem 1rem;
        }
    }
</style>


</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="home_student.html">
      <i class="fas fa-graduation-cap me-2"></i>EduLearning
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="tutors.html">Tutors</a></li>
        <li class="nav-item"><a class="nav-link" href="notification_student.php">Notifications</a></li>
        <li class="nav-item"><a class="nav-link active" href="view_test.php">View Tests</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($student_name) ?>
          </a>
            <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile_student.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
  <?php if (count($allowedTests) > 0): ?>
    <div class="test-container">
      <h2 class="test-header">All Tests</h2>
      <form action="submission_test.php" method="post" class="test-form">
        <?php foreach ($allowedTests as $test): ?>
          <div class="test-card">
            <div class="test-card-body">
              <h3 class="test-title"><?= htmlspecialchars($test['test_title']) ?></h3>
              <p class="test-description"><?= htmlspecialchars($test['description']) ?></p>
              <div class="test-details">
                <p><span class="detail-label">Subject:</span> <?= htmlspecialchars($test['subject']) ?></p>
                <p><span class="detail-label">Teacher Name:</span> <?= htmlspecialchars($test['teacher_name']) ?></p>
                <p class="test-date">Created on: <?= htmlspecialchars($test['created_at']) ?></p>
              </div>

              <?php
              $test_id = $test['id'];
              $question_result = $conn->query("SELECT * FROM test_questions WHERE test_id = $test_id");

              if ($question_result && $question_result->num_rows > 0) {
                  echo "<div class='questions-container'>";
                  $qnum = 1;
                  while ($q = $question_result->fetch_assoc()) {
                      echo "<div class='question-item'>";
                      echo "<p><strong>Q{$qnum}:</strong> " . htmlspecialchars($q['question']) . "</p>";
                      echo "</div>";
                      $qnum++;
                  }
                  echo "</div>";
              } else {
                  echo "<p>No questions available for this test.</p>";
              }
              ?>

              <input type="hidden" name="test_options[]" value="<?= $test['id'] ?>">
            </div>
          </div>
        <?php endforeach; ?>
        <div class="submit-section">
          <button type="submit" class="submit-button">Submit Test</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <p class="no-tests">No tests found for your accepted tutors.</p>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
