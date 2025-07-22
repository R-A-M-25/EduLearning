<?php
session_start();
if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.html");
    exit();
}

$tutor_email = $_SESSION['teacher_email']; // Get the logged-in tutor's email
$tutor_type = $_SESSION['tutor_type']; // Get the tutor type (Professional or Peer)

include 'configuration.php';

// Fetch accepted requests for this tutor based on the logged-in tutor's email
$sql = "SELECT br.*, sl.address, sl.pincode 
        FROM booking_request br
        JOIN student_login sl ON br.email = sl.email
        WHERE br.required_tutor_type = ? 
          AND br.status = 'accepted' 
          AND br.teacher_email = ?"; // Filter by teacher_email (specific to the logged-in tutor)
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $tutor_type, $tutor_email); // Bind tutor_type and tutor_email
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Students | EduLearning</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4361ee;
      --primary-light: #4895ef;
      --secondary: #3f37c9;
      --accent: #4cc9f0;
      --dark: #1a1a2e;
      --light: #f8f9fa;
      --success: #4bb543;
      --danger: #f44336;
      --warning: #ff9800;
      --professional: #3a86ff;
      --peer: #8338ec;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7ff;
      color: var(--dark);
      min-height: 100vh;
    }
    
    .dashboard-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 2rem 0;
      margin-bottom: 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-title {
      font-weight: 700;
      margin: 0;
      font-size: 2rem;
      letter-spacing: -0.5px;
    }
    
    .tutor-badge {
      font-size: 0.9rem;
      font-weight: 600;
      padding: 0.35rem 0.8rem;
      border-radius: 50px;
      margin-left: 0.75rem;
      vertical-align: middle;
    }
    
    .professional-badge {
      background-color: var(--professional);
    }
    
    .peer-badge {
      background-color: var(--peer);
    }
    
    .student-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    
    .student-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      background: linear-gradient(to right, var(--primary-light), var(--primary));
      color: white;
      padding: 1.25rem 1.5rem;
      border-bottom: none;
    }
    
    .card-title {
      font-weight: 600;
      margin: 0;
      font-size: 1.3rem;
    }
    
    .card-subject {
      font-size: 0.9rem;
      opacity: 0.9;
      margin-top: 0.25rem;
    }
    
    .card-body {
      padding: 1.5rem;
    }
    
    .detail-item {
      margin-bottom: 0.75rem;
      display: flex;
      align-items: flex-start;
    }
    
    .detail-icon {
      color: var(--primary);
      margin-right: 0.75rem;
      font-size: 1.1rem;
      margin-top: 0.2rem;
      min-width: 20px;
    }
    
    .detail-label {
      font-weight: 500;
      margin-right: 0.5rem;
    }
    
    .budget-badge {
      background-color: rgba(75, 181, 67, 0.15);
      color: var(--success);
      padding: 0.35rem 0.8rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-block;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
    }
    
    .empty-icon {
      font-size: 3rem;
      color: var(--primary-light);
      margin-bottom: 1rem;
    }
    
    .empty-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .empty-text {
      color: #666;
      max-width: 500px;
      margin: 0 auto 1.5rem;
    }
    
    .navbar {
      background: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 0.75rem 0;
    }
    
    .navbar-brand {
      font-weight: 700;
      color: var(--primary);
    }
    
    .nav-link {
      font-weight: 500;
      color: var(--dark);
      padding: 0.5rem 1rem;
      transition: color 0.3s;
    }
    
    .nav-link:hover {
      color: var(--primary);
    }
    
    .nav-link.active {
      color: var(--primary);
      font-weight: 600;
    }
    
    .user-dropdown img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 0.5rem;
    }
    
    @media (max-width: 768px) {
      .dashboard-header {
        padding: 1.5rem 0;
      }
      
      .dashboard-title {
        font-size: 1.5rem;
      }
      
      .student-card {
        margin-bottom: 1rem;
      }
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
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
                        <a class="nav-link" href="view_submissions.php">Submissions</a>
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

  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="dashboard-title">
          Your Students
          <span class="tutor-badge <?= $tutor_type === 'Professional' ? 'professional-badge' : 'peer-badge' ?>">
            <?= htmlspecialchars($tutor_type) ?> Tutor
          </span>
        </h1>
     
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mb-5">
    <?php if ($result->num_rows > 0): ?>
      <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-lg-6">
            <div class="student-card">
              <div class="card-header">
                <h5 class="card-title"><?= htmlspecialchars($row['username']) ?></h5>
                <div class="card-subject"><?= htmlspecialchars($row['subject']) ?></div>
              </div>
              <div class="card-body">
                <div class="detail-item">
                  <div class="detail-icon"><i class="fas fa-phone"></i></div>
                  <div>
                    <span class="detail-label">Contact:</span>
                    <?= htmlspecialchars($row['contact_number']) ?>
                  </div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-icon"><i class="fas fa-clock"></i></div>
                  <div>
                    <span class="detail-label">Schedule:</span>
                    <?= htmlspecialchars($row['timings']) ?>
                  </div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-icon"><i class="fas fa-map-marker-alt"></i></div>
                  <div>
                    <span class="detail-label">Address:</span>
                    <?= htmlspecialchars($row['address']) ?>, <?= htmlspecialchars($row['pincode']) ?>
                  </div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-icon"><i class="fas fa-rupee-sign"></i></div>
                  <div>
                    <span class="detail-label">Budget:</span>
                    <span class="budget-badge">₹<?= htmlspecialchars($row['budget']) ?>/month</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-user-graduate"></i>
        </div>
        <h4 class="empty-title">No Students Assigned Yet</h4>
        <p class="empty-text">You currently don't have any students. When students book sessions with you, they'll appear here.</p>
        <button class="btn btn-primary">
          <i class="fas fa-search me-1"></i> Find Students
        </button>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>