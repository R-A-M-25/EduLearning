<?php
session_start();
if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.html");
    exit();
}

$tutor_email = $_SESSION['teacher_email'];
$tutor_name = $_SESSION['teacher_name'];




include 'configuration.php';

$action_message = "";
// Handle accept/reject/pending actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action_map = ['accept' => 'accepted', 'reject' => 'rejected', 'pending' => 'pending'];
    $action = $action_map[$_POST['action']] ?? 'pending';

    // If accepted, store tutor's email in the booking_request table
  if ($action === 'accepted') {
    $update = $conn->prepare("UPDATE booking_request SET status = ?, teacher_email = ?, teacher_name = ? WHERE id = ?");
    $update->bind_param("sssi", $action, $tutor_email, $tutor_name, $request_id);
}
 else {
        $update = $conn->prepare("UPDATE booking_request SET status = ? WHERE id = ?");
        $update->bind_param("si", $action, $request_id);
    }
    $update->execute();

    // Fetch student's email and username for notification
    $stmt = $conn->prepare("SELECT email, username FROM booking_request WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    // Prepare and send notification to the student
    $message = "Your demo request has been $action by the tutor.";
    $notify = $conn->prepare("INSERT INTO notifications (user_email, message, status, created_at) VALUES (?, ?, 'unread', NOW())");
    $notify->bind_param("ss", $student['email'], $message);
    $notify->execute();

    $action_message = "Student has been notified that their request was $action.";
}

// Fetch pending requests for this tutor type
$tutor_type = $_SESSION['tutor_type']; // Professional or Peer
$sql = "SELECT br.*, sl.address, sl.pincode 
        FROM booking_request br
        JOIN student_login sl ON br.email = sl.email
        WHERE br.required_tutor_type = ? AND br.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tutor_type);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Notifications | LearnAtHome</title>
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
            --accept: #28a745;
            --reject: #dc3545;
            --pending: #ffc107;
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
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
            background-color: white;
        }
        
        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .student-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-size: 1.25rem;
        }
        
        .subject-badge {
            background-color: var(--primary-light);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
        }
        
        .detail-icon {
            color: var(--primary);
            margin-right: 0.75rem;
            margin-top: 0.2rem;
            min-width: 20px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--dark);
            margin-right: 0.5rem;
        }
        
        .action-buttons {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }
        
        .btn-action i {
            margin-right: 0.5rem;
        }
        
        .btn-accept {
            background-color: var(--accept);
            color: white;
        }
        
        .btn-accept:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-reject {
            background-color: var(--reject);
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-pending {
            background-color: var(--pending);
            color: var(--dark);
        }
        
        .btn-pending:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
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
        
        .alert-success {
            background-color: rgba(75, 181, 67, 0.1);
            border: none;
            color: var(--success);
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .page-header {
                margin: 1.5rem 0;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-action {
                width: 100%;
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
                        <a class="nav-link active" href="notification_tutor.php">Notification</a>
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

    <div class="container py-4">
        <div class="page-header">
            <h1 class="page-title">New Student Requests</h1>
        </div>

        <?php if (!empty($action_message)): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i><?= $action_message ?>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="card-body">
                        <h4 class="student-name"><?= htmlspecialchars($row['username']) ?></h4>
                        <span class="subject-badge"><?= htmlspecialchars($row['subject']) ?></span>
                        
                        <div class="detail-item">
                            <i class="fas fa-phone-alt detail-icon"></i>
                            <span class="detail-label">Contact:</span>
                            <span><?= htmlspecialchars($row['contact_number']) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-clock detail-icon"></i>
                            <span class="detail-label">Time:</span>
                            <span><?= htmlspecialchars($row['timings']) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt detail-icon"></i>
                            <span class="detail-label">Location:</span>
                            <span><?= htmlspecialchars($row['address']) ?>, <?= htmlspecialchars($row['pincode']) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-rupee-sign detail-icon"></i>
                            <span class="detail-label">Budget:</span>
                            <span>₹<?= htmlspecialchars($row['budget']) ?>/month</span>
                        </div>
                        
                        <form method="post" class="action-buttons">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="accept" class="btn-action btn-accept">
                                <i class="fas fa-check"></i> Accept
                            </button>
                            <button type="submit" name="action" value="reject" class="btn-action btn-reject">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <button type="submit" name="action" value="pending" class="btn-action btn-pending">
                                <i class="fas fa-pause"></i> Pending
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="far fa-bell"></i>
                </div>
                <h4>No new student requests</h4>
                <p class="empty-state-text">You'll see new student requests here when they arrive</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>