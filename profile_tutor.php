<?php
session_start();
include 'configuration.php';

$tutor_email = $_SESSION['teacher_email'] ?? '';

if (empty($tutor_email)) {
    header("Location: login.html");
    exit();
}

// Fetch tutor info
$sql = "SELECT name, email, phone, gender, type, subject, address, pincode FROM teacher_login WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tutor_email);
$stmt->execute();
$result = $stmt->get_result();
$tutor = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Profile | EduLearning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-dark: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            padding-top: 70px;
        }
        
        .profile-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.35rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.3);
            margin-bottom: 1rem;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .profile-table {
            width: 100%;
        }
        
        .profile-table th {
            width: 30%;
            font-weight: 600;
            color: var(--text-dark);
            background-color: var(--secondary-color);
            padding: 1rem;
        }
        
        .profile-table td {
            width: 70%;
            padding: 1rem;
        }
        
        .profile-table tr:nth-child(even) td {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .badge-subject {
            background-color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .edit-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            transition: all 0.3s;
        }
        
        .edit-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link {
            color: var(--text-dark);
            font-weight: 500;
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
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="profile-header">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name'] ?? 'T') ?>&background=ffffff&color=4e73df&size=256" 
                             alt="Profile Avatar" class="profile-avatar">
                        <h3><?= htmlspecialchars($tutor['name'] ?? 'Tutor') ?></h3>
                        <span class="badge badge-subject rounded-pill p-2">
                            <?= htmlspecialchars($tutor['type'] ?? 'Tutor') ?>
                        </span>
                        
                        </a>
                    </div>
                    
                    <div class="profile-body">
                        <?php if ($tutor): ?>
                            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="professional-tab" data-bs-toggle="tab" data-bs-target="#professional" type="button" role="tab">Professional</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="profileTabsContent">
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <table class="table profile-table">
                                        <tr>
                                            <th><i class="fas fa-envelope me-2"></i>Email</th>
                                            <td><?= htmlspecialchars($tutor['email']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-phone me-2"></i>Phone</th>
                                            <td><?= htmlspecialchars($tutor['phone']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-venus-mars me-2"></i>Gender</th>
                                            <td><?= htmlspecialchars($tutor['gender']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-map-marker-alt me-2"></i>Address</th>
                                            <td>
                                                <?= htmlspecialchars($tutor['address']) ?><br>
                                                <span class="text-muted">PIN: <?= htmlspecialchars($tutor['pincode']) ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="tab-pane fade" id="professional" role="tabpanel">
                                    <table class="table profile-table">
                                        <tr>
                                            <th><i class="fas fa-user-tie me-2"></i>Tutor Type</th>
                                            <td><?= htmlspecialchars($tutor['type']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-book me-2"></i>Subject</th>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($tutor['subject']) ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                           
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Tutor profile not found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tabs
        var tabElms = [].slice.call(document.querySelectorAll('button[data-bs-toggle="tab"]'));
        tabElms.forEach(function(tabEl) {
            new bootstrap.Tab(tabEl);
        });
    </script>
</body>
</html>