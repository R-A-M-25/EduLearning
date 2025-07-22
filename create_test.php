<?php
session_start();

// Check if the teacher is logged in
if (!isset($_SESSION['teacher_name']) || !isset($_SESSION['teacher_email'])) {
    die("Access denied. Please log in.");
}

$teacher_name = $_SESSION['teacher_name'];
$teacher_email = $_SESSION['teacher_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Test</title>
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
        
        .test-form-container {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        .question-label {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .btn-add-question {
            background-color: #858796;
            border-color: #858796;
        }
        
        .btn-add-question:hover {
            background-color: #717384;
            border-color: #6b6d7d;
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
                        <a class="nav-link active" href="create_test.php">Create Tests</a>
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

    <div class="container mt-5 pt-3">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="test-form-container">
                    <h2 class="form-title">Create a New Test</h2>
                    <form id="testForm">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Test Title</label>
                            <input type="text" name="test_title" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div id="questionsContainer" class="mb-4">
                            <h5 class="question-label mb-3">Questions</h5>
                            <div class="mb-3">
                                <label class="form-label">Question 1</label>
                                <input type="text" name="question[]" class="form-control mb-2" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <button type="button" class="btn btn-add-question" onclick="addQuestion()">
                                <i class="fas fa-plus me-2"></i>Add Another Question
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Create Test
                            </button>
                        </div>
                        
                        <div id="response" class="mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let questionCount = 1;

    function addQuestion() {
        questionCount++;
        const container = document.getElementById("questionsContainer");
        const newField = document.createElement("div");
        newField.className = "mb-3";
        newField.innerHTML = `<label class="form-label">Question ${questionCount}</label><input type="text" name="question[]" class="form-control mb-2" required>`;
        container.appendChild(newField);
    }

    document.getElementById("testForm").addEventListener("submit", function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const questions = formData.getAll("question[]");

        const testData = {
            teacher_name: "<?php echo $teacher_name; ?>",
            teacher_email: "<?php echo $teacher_email; ?>",
            subject: formData.get("subject"),
            test_title: formData.get("test_title"),
            description: formData.get("description"),
            questions: questions
        };

        fetch("save_test.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(testData)
        })
        .then(res => res.json())
        .then(data => {
            const responseDiv = document.getElementById("response");
            if (data.success) {
                responseDiv.innerHTML = `<div class='alert alert-success'><i class="fas fa-check-circle me-2"></i>Test created successfully!</div>`;
                this.reset();
                // Reset question counter and container
                questionCount = 1;
                document.getElementById("questionsContainer").innerHTML = `
                    <h5 class="question-label mb-3">Questions</h5>
                    <div class="mb-3">
                        <label class="form-label">Question 1</label>
                        <input type="text" name="question[]" class="form-control mb-2" required>
                    </div>
                `;
            } else {
                responseDiv.innerHTML = `<div class='alert alert-danger'><i class="fas fa-exclamation-circle me-2"></i>Error: ${data.error || 'Unknown error occurred'}</div>`;
            }
        })
        .catch(error => {
            document.getElementById("response").innerHTML = 
                `<div class='alert alert-danger'><i class="fas fa-exclamation-circle me-2"></i>An error occurred: ${error.message || error}</div>`;
        });
    });
    </script>
</body>
</html>