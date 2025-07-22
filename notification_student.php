<?php
session_start();
if (!isset($_SESSION['student_email'])) {
    header("Location: home.html");
    exit();
}

$student_email = $_SESSION['student_email'];


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include 'configuration.php';

// Handle mark as read (individual)
if (isset($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $notif_id, $student_email);
    $stmt->execute();
    header("Location: notification_student.php");
    exit();
}

// Handle mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE user_email = ?");
    $stmt->bind_param("s", $student_email);
    $stmt->execute();
    header("Location: notification_student.php");
    exit();
}

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-store" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Notifications | LearnAtHome</title>
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
      --unread: #fff3cd;
      --read: #d4edda;
    }
    
    body {
      background-color: #f5f7ff;
      font-family: 'Poppins', sans-serif;
      color: var(--dark);
    }
    
    .navbar {
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    .nav-link {
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: all 0.3s;
    }
    
    .nav-link:hover {
      color: var(--primary);
    }
    
    .dropdown-menu {
      border: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    
    .dropdown-item {
      padding: 0.5rem 1.5rem;
      transition: all 0.3s;
    }
    
    .dropdown-item:hover {
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary);
    }
    
    .page-header {
      margin: 2rem 0;
      padding-bottom: 1rem;
      border-bottom: 2px solid rgba(0, 0, 0, 0.05);
    }
    
    .page-title {
      font-weight: 700;
      color: var(--dark);
      position: relative;
      display: inline-block;
    }
    
    .page-title::after {
      content: '';
      position: absolute;
      bottom: -12px;
      left: 0;
      width: 50px;
      height: 4px;
      background: var(--primary);
      border-radius: 2px;
    }
    
    .notification-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
      overflow: hidden;
      background-color: white;
    }
    
    .notification-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .notification-card.unread {
      border-left: 4px solid var(--primary);
    }
    
    .notification-card.read {
      opacity: 0.9;
    }
    
    .card-body {
      padding: 1.5rem;
    }
    
    .notification-title {
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: var(--dark);
    }
    
    .notification-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 1rem;
    }
    
    .notification-time {
      font-size: 0.85rem;
      color: #6c757d;
    }
    
    .status-badge {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .unread-badge {
      background-color: var(--unread);
      color: #856404;
    }
    
    .read-badge {
      background-color: var(--read);
      color: #155724;
    }
    
    .btn-mark-all {
      background-color: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1.25rem;
      font-weight: 500;
      transition: all 0.3s;
      margin-bottom: 1.5rem;
    }
    
    .btn-mark-all:hover {
      background-color: var(--secondary);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-mark-read {
      background-color: white;
      color: var(--primary);
      border: 1px solid var(--primary);
      border-radius: 8px;
      padding: 0.35rem 1rem;
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .btn-mark-read:hover {
      background-color: var(--primary);
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .empty-state-icon {
      font-size: 3rem;
      color: #adb5bd;
      margin-bottom: 1rem;
    }
    
    .empty-state-text {
      color: #6c757d;
      font-size: 1.1rem;
    }
    
    @media (max-width: 768px) {
      .page-header {
        margin: 1.5rem 0;
      }
      
      .card-body {
        padding: 1.25rem;
      }
      
      .notification-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
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
          <li class="nav-item">
            <a class="nav-link" href="tutors.html">Tutors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="notification_student.php">Notifications</a>
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

  <div class="container py-4">
    <div class="page-header">
      <h1 class="page-title">Your Notifications</h1>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <a href="?mark_all=true" class="btn btn-mark-all">
        <i class="fas fa-check-double me-2"></i>Mark All as Read
      </a>

      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="notification-card <?= $row['status'] === 'unread' ? 'unread' : '' ?>">
          <div class="card-body">
            <h5 class="notification-title"><?= strip_tags($row['message'], '<strong><b>') ?></h5>
            <div class="notification-meta">
              <span class="notification-time">
                <i class="far fa-clock me-1"></i><?= htmlspecialchars($row['created_at']) ?>
              </span>
              <div class="d-flex align-items-center gap-2">
                <span class="status-badge <?= $row['status'] === 'unread' ? 'unread-badge' : 'read-badge' ?>">
                  <i class="fas <?= $row['status'] === 'unread' ? 'fa-envelope' : 'fa-envelope-open' ?> me-1"></i>
                  <?= ucfirst($row['status']) ?>
                </span>
                <?php if ($row['status'] === 'unread'): ?>
                  <a href="?mark_read=<?= $row['id'] ?>" class="btn-mark-read">
                    <i class="fas fa-check me-1"></i>Mark as Read
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="far fa-bell"></i>
        </div>
        <h4>No notifications yet</h4>
        <p class="empty-state-text">You'll see important updates here when they arrive</p>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>