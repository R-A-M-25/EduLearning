<?php
session_start();
include 'configuration.php';

// Ensure student is logged in
if (!isset($_SESSION['student_email'])) {
    header("Location: login.php");
    exit();
}

$student_email = $_SESSION['student_email'];

// Fetch student data
$studentQuery = $conn->prepare("SELECT name, email, phone FROM student_login WHERE email = ?");
$studentQuery->bind_param("s", $student_email);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();

// Handle demo request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect data from the form
    $studentName = $conn->real_escape_string($_POST['studentName']);
    $studentEmail = $conn->real_escape_string($_POST['studentEmail']);
    $studentPhone = $conn->real_escape_string($_POST['studentPhone']);
    $studentSubject = $conn->real_escape_string($_POST['studentSubject']);
    $studentBudget = $conn->real_escape_string($_POST['studentBudget']);
    $studentTimings = $conn->real_escape_string($_POST['studentTimings']);
    $requiredTutorType = $conn->real_escape_string($_POST['requiredTutorType']);
    $tutorEmail = $conn->real_escape_string($_POST['tutorEmail']); // Tutor's email from the form

    // Insert data into the booking_request table
    $stmt = $conn->prepare("INSERT INTO booking_request (username, email, contact_number, subject, budget, timings, required_tutor_type, teacher_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $studentName, $studentEmail, $studentPhone, $studentSubject, $studentBudget, $studentTimings, $requiredTutorType, $tutorEmail);
    
    if ($stmt->execute()) {
        // Send notification to the specific tutor
        $notificationMessage = "$studentName has requested a demo class in $studentSubject.";

        $notifStmt = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
        $notifStmt->bind_param("ss", $tutorEmail, $notificationMessage);
        $notifStmt->execute();
        $notifStmt->close();

        echo "<script>alert('Demo request submitted and notification sent to the tutor!');</script>";
    } else {
        echo "<script>alert('Failed to submit demo request.');</script>";
    }

    $stmt->close();
}

// Fetch professional tutors
$stmt = $conn->prepare("SELECT * FROM teacher_login WHERE type = 'Professional'");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Professional Tutors | Learn With Experts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #f8f9fc;
      --accent-color: #2e59d9;
      --text-color: #5a5c69;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7ff;
      color: var(--text-color);
    }

    .page-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
      color: white;
      padding: 2.5rem 0;
      margin-bottom: 3rem;
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .page-header h2 {
      font-weight: 700;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    }

    .tutor-card {
      border: none;
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
      background: white;
    }

    .tutor-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
    }

    .card-img-container {
      height: 180px;
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 4rem;
    }

    .card-body {
      padding: 1.75rem;
    }

    .card-title {
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 1.25rem;
      font-size: 1.3rem;
    }

    .card-text {
      margin-bottom: 1.5rem;
    }

    .card-text strong {
      color: var(--text-color);
      font-weight: 600;
    }

    .btn-demo {
      background-color: var(--primary-color);
      color: white;
      border-radius: 50px;
      padding: 0.6rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s;
      border: none;
      width: 100%;
      letter-spacing: 0.5px;
    }

    .btn-demo:hover {
      background-color: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
    }

    .subject-badge {
      background-color: rgba(78, 115, 223, 0.1);
      color: var(--primary-color);
      font-weight: 600;
      padding: 0.4rem 0.8rem;
      border-radius: 50px;
      font-size: 0.8rem;
      display: inline-block;
      margin-bottom: 1rem;
    }

    /* Modal Styles */
    .demo-modal .modal-header {
      background-color: var(--primary-color);
      color: white;
    }

    .demo-modal .btn-close {
      filter: invert(1);
    }

  </style>
</head>
<body>
   <!-- Navbar -->
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
                    
                    <li class="nav-item">
                        <a class="nav-link" href="tutors.html">Tutors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notification_student.php">Notification</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">Progreess</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_test.php">View Tests</a>
                    </li>
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
                      </li>
                 
                </ul>
            </div>
        </div>
    </nav>

  <!-- Page Header -->
  <div class="page-header">
    <div class="container text-center">
      <h2 class="mb-0">Professional Tutors</h2>
    </div>
  </div>

  <!-- Tutors Grid -->
  <div class="container mb-5">
    <div class="row">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tutor-card h-100">
              <!-- Card Image with Initials -->
              <div class="card-img-container">
                <?php 
                  $names = explode(' ', $row['name']);
                  $initials = '';
                  foreach ($names as $n) {
                    $initials .= strtoupper(substr($n, 0, 1));
                  }
                  echo $initials;
                ?>
              </div>

              <div class="card-body">
                <span class="subject-badge"><?= $row['subject'] ?></span>
                <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                <p class="card-text">
                  <strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?= $row['email'] ?><br>
                  <strong><i class="fas fa-phone-alt me-2"></i>Phone:</strong> <?= $row['phone'] ?><br>
                  <strong><i class="fas fa-venus-mars me-2"></i>Gender:</strong> <?= $row['gender'] ?><br>
                  <strong><i class="fas fa-map-marker-alt me-2"></i>Address:</strong> <?= $row['address'] ?>
                  
                </p>
                <button class="btn-demo" 
        data-bs-toggle="modal" 
        data-bs-target="#demoModal" 
        data-tutorname="<?= htmlspecialchars($row['name']) ?>"
        data-tutoremail="<?= htmlspecialchars($row['email']) ?>"
        data-tutorphone="<?= htmlspecialchars($row['phone']) ?>"
        data-tutorsubject="<?= htmlspecialchars($row['subject']) ?>">
  Request Demo
</button>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No tutors available at the moment.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal for Demo Request -->
  <div class="modal fade demo-modal" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="demoModalLabel">Request Demo Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="">
            <div class="mb-3">
              <label for="studentName" class="form-label">Student Name</label>
              <input type="text" class="form-control" id="studentName" name="studentName" value="<?= htmlspecialchars($student['name']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="studentEmail" class="form-label">Student Email</label>
              <input type="email" class="form-control" id="studentEmail" name="studentEmail" value="<?= htmlspecialchars($student['email']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="studentPhone" class="form-label">Student Phone</label>
              <input type="tel" class="form-control" id="studentPhone" name="studentPhone" value="<?= htmlspecialchars($student['phone']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="studentSubject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="studentSubject" name="studentSubject" required>
            </div>
            <div class="mb-3">
              <label for="studentBudget" class="form-label">Budget</label>
              <input type="text" class="form-control" id="studentBudget" name="studentBudget" required>
            </div>
            <div class="mb-3">
              <label for="studentTimings" class="form-label">Preferred Timings</label>
              <input type="time" class="form-control" id="studentTimings" name="studentTimings" required>
            </div>
            <div class="mb-3">
              <label for="requiredTutorType" class="form-label">Tutor Type</label>
              <select class="form-control" id="requiredTutorType" name="requiredTutorType" required>
                <option value="Professional">Professional</option>
                <option value="Peer">Peer</option>
              </select>
            </div>
            <input type="hidden" id="tutorEmail" name="tutorEmail">
            <button type="submit" class="btn btn-primary w-100">Submit Request</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

 <script>
  // Set modal content dynamically
  const modal = document.getElementById('demoModal');
  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget; // Button that triggered the modal
    const tutorName = button.getAttribute('data-tutorname');
    const tutorEmail = button.getAttribute('data-tutoremail');
    const tutorPhone = button.getAttribute('data-tutorphone');
    const tutorSubject = button.getAttribute('data-tutorsubject');

    // Set modal fields
    modal.querySelector('#studentSubject').value = tutorSubject;
    modal.querySelector('#tutorEmail').value = tutorEmail;
  });
</script>


</body>
</html>
