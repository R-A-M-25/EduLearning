<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_email'])) {
    die("Access denied. Please log in as a student.");
}

include 'configuration.php';

$student_email = $_SESSION['student_email'];
$submission_status = "";

// Fetch teacher name assigned to the student (from booking request or a similar table)
$stmt = $conn->prepare("SELECT teacher_name FROM booking_request WHERE email = ? AND status = 'accepted' LIMIT 1");
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result = $stmt->get_result();

$teacher_name = "";
if ($row = $result->fetch_assoc()) {
    $teacher_name = $row['teacher_name'];
} else {
    // Handle case where no teacher is assigned or booking request is not accepted
    die("No accepted teacher found for this student.");
}

// Fetch test titles for the teacher assigned to the student, excluding tests the student has already submitted for
$stmt = $conn->prepare("
    SELECT DISTINCT test_title 
    FROM tests 
    WHERE teacher_name = ? 
    AND test_title NOT IN (
        SELECT test_title 
        FROM test_submissions 
        WHERE student_email = ?
    )
");
$stmt->bind_param("ss", $teacher_name, $student_email);
$stmt->execute();
$tests = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_title = $_POST['test_title'] ?? '';
    
    // Check if the file was uploaded
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK && $test_title !== '') {
        $file = $_FILES['pdf_file'];
        $file_name = basename($file['name']);
        $target_dir = "uploads/";

        // Ensure uploads directory exists and is writable
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_path = $target_dir . uniqid() . "_" . $file_name;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Get the test ID and teacher name
            $stmt = $conn->prepare("SELECT id, teacher_name FROM tests WHERE test_title = ?");
            $stmt->bind_param("s", $test_title);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $test_id = $row['id'];
                $teacher_name = $row['teacher_name'];

                // Insert into test_submissions table
                $insert = $conn->prepare("INSERT INTO test_submissions (student_email, test_id, test_title, pdf_path, submitted_at, teacher_name) VALUES (?, ?, ?, ?, NOW(), ?)");
                $insert->bind_param("sisss", $student_email, $test_id, $test_title, $target_path, $teacher_name);
                if ($insert->execute()) {
                    $submission_status = "Submission successful!";
                } else {
                    $submission_status = "Error: Unable to save submission.";
                }
            } else {
                $submission_status = "Error: Test not found.";
            }
        } else {
            $submission_status = "File upload failed.";
        }
    } else {
        $submission_status = "Please select a test and upload a PDF.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Test Answers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body >
     <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="home_student.html">
        <i class="fas fa-graduation-cap me-2"></i>EduLearning
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link" href="tutors.html">Tutors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link " href="notification_student.php">Notifications</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="view_test.php">View Tests</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="progress.php">Progress</a>
          </li>
          
          <li class="nav-item dropdown ms-lg-3">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['student_name']) ?>
            </a>
               <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile_student.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
      </div>
    </div>
  </nav>
  <div class="container mt-5">

    <h2>Submit Test Answers (PDF)</h2>

    <?php if ($submission_status): ?>
        <div class="alert alert-info"><?= htmlspecialchars($submission_status) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="border p-4 rounded bg-light shadow">
        <div class="mb-3">
            <label class="form-label">Select Test</label>
            <select name="test_title" class="form-select" required>
                <option value="">-- Select Test --</option>
                <?php while ($row = $tests->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['test_title']) ?>"><?= htmlspecialchars($row['test_title']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Answer Sheet (PDF only)</label>
            <input type="file" name="pdf_file" accept=".pdf" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    </div>
</body>
</html>
