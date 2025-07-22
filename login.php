<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'configuration.php';

$success = "";
$error = "";

// Handle registration
if (isset($_POST['register'])) {
    $name     = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $phone    = $conn->real_escape_string($_POST['phone']);
    $gender   = $conn->real_escape_string($_POST['gender']);
    $class    = $conn->real_escape_string($_POST['class']);
    $address  = $conn->real_escape_string($_POST['address']);
    $city     = $conn->real_escape_string($_POST['city']);
    $pincode  = $conn->real_escape_string($_POST['pincode']);
    $raw_password = $_POST['password'];

// Password strength validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/', $raw_password)) {
    $error = "Password must be at least 8 characters long and include uppercase, lowercase, and special characters.";
} else {
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    // Check if email exists
    $check = $conn->query("SELECT * FROM student_login WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $sql = "INSERT INTO student_login (name, email, phone, gender, class, address, city, pincode, password)
                VALUES ('$name', '$email', '$phone', '$gender', '$class', '$address', '$city', '$pincode', '$password')";
        if ($conn->query($sql)) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}

}

// Handle login
if (isset($_POST['login'])) {
    $email    = $conn->real_escape_string($_POST['login_email']);
    $password = $_POST['login_password'];

    $sql = "SELECT * FROM student_login WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['student_email'] = $user['email'];
            $_SESSION['student_name'] = $user['name'];
            header("Location: home_student.html");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Portal | Login & Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #f8f9fc;
      --accent-color: #2e59d9;
      --text-color: #5a5c69;
    }
    
    body {
      background-color: var(--secondary-color);
      color: var(--text-color);
      font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    
    .auth-container {
      max-width: 800px;
      margin: 5rem auto;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      border-radius: 0.35rem;
      overflow: hidden;
      background: white;
    }
    
    .auth-header {
      background: var(--primary-color);
      color: white;
      padding: 1.5rem;
      text-align: center;
    }
    
    .auth-header h2 {
      font-weight: 600;
      margin: 0;
    }
    
    .nav-tabs {
      border-bottom: none;
      padding: 0 1.5rem;
    }
    
    .nav-tabs .nav-link {
      color: var(--text-color);
      border: none;
      padding: 1rem 1.5rem;
      font-weight: 600;
      border-radius: 0;
    }
    
    .nav-tabs .nav-link.active {
      color: var(--primary-color);
      background: transparent;
      border-bottom: 3px solid var(--primary-color);
    }
    
    .tab-content {
      padding: 2rem;
    }
    
    .form-control, .form-select {
      padding: 0.75rem 1rem;
      border-radius: 0.35rem;
      border: 1px solid #d1d3e2;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 0.75rem 1.5rem;
      font-weight: 600;
    }
    
    .btn-primary:hover {
      background-color: var(--accent-color);
      border-color: var(--accent-color);
    }
    
    .btn-success {
      padding: 0.75rem 1.5rem;
      font-weight: 600;
    }
    
    .input-group-text {
      background-color: #eaecf4;
      border: 1px solid #d1d3e2;
    }
    
    .password-toggle {
      cursor: pointer;
      background: transparent;
      border: none;
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }
    
    .password-container {
      position: relative;
    }
    
    .form-icon {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      color: #6c757d;
    }
    
    .form-group {
      position: relative;
    }
    
    .form-group input {
      padding-left: 40px;
    }
    
    @media (max-width: 768px) {
      .auth-container {
        margin: 2rem auto;
      }
    }
  </style>
</head>
<body>
<div class="auth-container">
  <div class="auth-header">
    <h2><i class="fas fa-user-graduate me-2"></i>Student Portal</h2>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
      <?= $error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php elseif (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
      <?= $success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <ul class="nav nav-tabs" id="authTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
        <i class="fas fa-sign-in-alt me-2"></i>Login
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
        <i class="fas fa-user-plus me-2"></i>Register
      </button>
    </li>
  </ul>

  <div class="tab-content p-4">
    <!-- Login -->
    <div class="tab-pane fade show active" id="login" role="tabpanel">
      <form method="POST">
        <div class="form-group mb-4">
          <i class="fas fa-envelope form-icon"></i>
          <input type="email" name="login_email" class="form-control" placeholder="Email Address" required>
        </div>
        
        <div class="form-group mb-4 password-container">
          <i class="fas fa-lock form-icon"></i>
          <input type="password" name="login_password" class="form-control" placeholder="Password" required id="loginPassword">
        </div>
        
      
        
        <button type="submit" name="login" class="btn btn-primary w-100">
          <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
      </form>
    </div>

    <!-- Register -->
    <div class="tab-pane fade" id="register" role="tabpanel">
      <form method="POST">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Full Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-phone"></i></span>
              <input type="text" name="phone" class="form-control" placeholder="Phone" required>
            </div>
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select" required>
              <option value="">Select Gender</option>
              <option>Male</option>
              <option>Female</option>
            </select>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Class</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
            <input type="text" name="class" class="form-control" placeholder="Class" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" placeholder="Address" rows="2" required></textarea>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" placeholder="City" required>
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">Pincode</label>
            <input type="text" name="pincode" class="form-control" placeholder="Pincode" required>
          </div>
        </div>
        
        <div class="mb-4 password-container">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Password" required id="registerPassword">
          
        </div>
        <button type="submit" name="register" class="btn btn-success w-100">
          <i class="fas fa-user-plus me-2"></i>Register
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

  
  // Switch to login tab if there's a success message from registration
  <?php if (!empty($success)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      const loginTab = new bootstrap.Tab(document.getElementById('login-tab'));
      loginTab.show();
    });
  <?php endif; ?>
</script>
</body>
</html>