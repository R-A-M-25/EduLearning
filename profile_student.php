<?php
session_start();
include 'configuration.php';

if (!isset($_SESSION['student_email'])) {
    header("Location: login.php");
    exit();
}

$student_email = $_SESSION['student_email'];

// Fetch student profile and assigned tutor
$sql = "
    SELECT 
        sl.name, sl.email, sl.phone, sl.class, sl.gender,
        sl.address, sl.city, sl.pincode,
        tl.name AS teacher_name,
        tl.email AS teacher_email
    FROM student_login sl
    LEFT JOIN booking_request br ON sl.email = br.email AND br.status = 'accepted'
    LEFT JOIN teacher_login tl ON br.teacher_email = tl.email
    WHERE sl.email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --text-dark: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            padding-top: 70px;
        }
        
        .profile-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .profile-table {
            width: 100%;
        }
        
        .profile-table th {
            width: 30%;
            font-weight: 600;
            color: var(--text-dark);
            background-color: var(--secondary-color);
        }
        
        .profile-table td {
            width: 70%;
        }
        
        .tutor-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .tutor-link:hover {
            text-decoration: underline;
        }
        
        .edit-profile-btn {
            background-color: var(--primary-color);
            border: none;
        }
        
        .edit-profile-btn:hover {
            background-color: #3a5ccc;
        }
        
        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top py-3 shadow-sm">
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
                        <a class="nav-link" href="view_test.php">View Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">Progreess</a>
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

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-container">
                    <div class="profile-header">
                        <h2><i class="fas fa-user-graduate me-2"></i>Student Profile</h2>
                        
                    </div>
                    
                    <?php if ($student): ?>
                        <div class="avatar-container">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['name']) ?>&background=4e73df&color=fff&size=120" 
                                 alt="Profile Avatar" class="avatar">
                        </div>
                        
                        <table class="table profile-table table-bordered table-hover">
                            <tr>
                                <th><i class="fas fa-user me-2"></i>Name</th>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-envelope me-2"></i>Email</th>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-phone me-2"></i>Phone</th>
                                <td><?= htmlspecialchars($student['phone']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-graduation-cap me-2"></i>Class</th>
                                <td><?= htmlspecialchars($student['class']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-venus-mars me-2"></i>Gender</th>
                                <td><?= htmlspecialchars($student['gender']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-home me-2"></i>Address</th>
                                <td><?= htmlspecialchars($student['address']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-city me-2"></i>City</th>
                                <td><?= htmlspecialchars($student['city']) ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-map-pin me-2"></i>Pincode</th>
                                <td><?= htmlspecialchars($student['pincode']) ?></td>
                            </tr>
                            
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Profile not found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>