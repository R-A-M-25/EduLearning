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
    $type     = $conn->real_escape_string($_POST['type']);
    $subject  = $conn->real_escape_string($_POST['subject']);
    $address  = $conn->real_escape_string($_POST['address']);
    $pincode  = $conn->real_escape_string($_POST['pincode']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // File upload handling for professionals
    if ($type === 'Professional') {
      if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
          $file = $_FILES['certificate'];
          
          // Check file type
          $file_type = mime_content_type($file['tmp_name']);
          $allowed_types = ['application/pdf'];
          
          if (!in_array($file_type, $allowed_types)) {
              $error = "Only PDF files are allowed for certification.";
          } else {
              // Create uploads directory if it doesn't exist
              if (!file_exists('uploads')) {
                  mkdir('uploads', 0777, true);
              }
  
              // Generate unique filename
              $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
              $filename = uniqid('cert_') . '.' . $file_ext;
              $upload_path = 'uploads/' . $filename;
  
              if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                  $certificate_path = $upload_path;
              } else {
                  $error = "Failed to move uploaded file.";
              }
          }
      } else {
          $error = "Professional teachers must upload a valid PDF certification.";
      }
  } else {
      // Peer tutor: no certificate required
      $certificate_path = null;
  }
  

    // Proceed with registration if no errors
    if (empty($error)) {
        // Check if email exists
        $check = $conn->query("SELECT * FROM teacher_login WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $sql = "INSERT INTO teacher_login (name, email, phone, gender, type, subject, address, pincode, password, certificate_path)
                    VALUES ('$name', '$email', '$phone', '$gender', '$type', '$subject', '$address', '$pincode', '$password', " . 
                    ($certificate_path ? "'$certificate_path'" : "NULL") . ")";
            if ($conn->query($sql)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed: " . $conn->error;
                // Remove uploaded file if registration failed
                if ($certificate_path && file_exists($certificate_path)) {
                    unlink($certificate_path);
                }
            }
        }
    }
}

// Handle login
if (isset($_POST['login'])) {
    $email    = $conn->real_escape_string($_POST['login_email']);
    $password = $_POST['login_password'];

    $sql = "SELECT * FROM teacher_login WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['teacher_email'] = $user['email'];
            $_SESSION['teacher_name'] = $user['name'];
            $_SESSION['tutor_type'] = $user['type'];

            // Redirect based on tutor type
            if ($user['type'] === 'Professional') {
                header("Location: home_professional.html");
            } elseif ($user['type'] === 'Peer') {
                header("Location: home_peer.html");
            } else {
                $error = "Unknown tutor type. Please contact admin.";
            }
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
  <title>EduConnect | Teacher Portal</title>
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
      display: flex;
      align-items: center;
    }
    
    .portal-container {
      max-width: 1000px;
      margin: 2rem auto;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      background: white;
    }
    
    .portal-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 2.5rem;
      text-align: center;
      position: relative;
    }
    
    .portal-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, var(--accent), var(--primary-light));
    }
    
    .portal-icon {
      font-size: 2.8rem;
      margin-bottom: 1rem;
      color: white;
      background: rgba(255, 255, 255, 0.15);
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .portal-title {
      font-weight: 700;
      margin: 0;
      font-size: 2rem;
      letter-spacing: -0.5px;
    }
    
    .portal-subtitle {
      font-weight: 400;
      opacity: 0.9;
      margin-top: 0.5rem;
      font-size: 1rem;
    }
    
    .nav-tabs {
      border-bottom: none;
      background: var(--light);
      padding: 0 2rem;
    }
    
    .nav-tabs .nav-link {
      color: var(--dark);
      border: none;
      padding: 1.2rem 1.5rem;
      font-weight: 500;
      font-size: 0.95rem;
      letter-spacing: 0.3px;
      transition: all 0.3s ease;
      position: relative;
      opacity: 0.7;
    }
    
    .nav-tabs .nav-link.active {
      color: var(--primary);
      opacity: 1;
      background: transparent;
    }
    
    .nav-tabs .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--primary);
      border-radius: 3px 3px 0 0;
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
      color: var(--primary);
      opacity: 1;
      background: rgba(67, 97, 238, 0.05);
    }
    
    .tab-content {
      padding: 2.5rem;
    }
    
    .form-control, .form-select {
      padding: 0.85rem 1.2rem;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s;
      font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
    }
    
    .form-label {
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: var(--dark);
      font-size: 0.95rem;
    }
    
    .btn {
      padding: 0.85rem 1.5rem;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.3s;
      letter-spacing: 0.3px;
      font-size: 0.95rem;
    }
    
    .btn-primary {
      background: var(--primary);
      border-color: var(--primary);
    }
    
    .btn-primary:hover {
      background: var(--secondary);
      border-color: var(--secondary);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-success {
      background: var(--success);
      border-color: var(--success);
    }
    
    .btn-success:hover {
      background: #3da336;
      border-color: #3da336;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(75, 181, 67, 0.3);
    }
    
    .form-icon {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary);
      font-size: 1rem;
    }
    
    .form-group {
      position: relative;
      margin-bottom: 1.5rem;
    }
    
    .form-group input, .form-group select, .form-group textarea {
      padding-left: 3rem;
    }
    
    .password-toggle {
      position: absolute;
      right: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: #a0a0a0;
      cursor: pointer;
      transition: color 0.3s;
    }
    
    .password-toggle:hover {
      color: var(--primary);
    }
    
    /* File Upload Styling */
    .file-upload-container {
      display: none;
      margin: 1.5rem 0;
    }
    
    .file-upload-label {
      display: block;
      padding: 2rem;
      border: 2px dashed #e0e0e0;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: rgba(67, 97, 238, 0.03);
    }
    
    .file-upload-label:hover {
      border-color: var(--primary);
      background: rgba(67, 97, 238, 0.05);
    }
    
    .file-upload-icon {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    
    .file-upload-text {
      font-weight: 500;
      color: var(--dark);
      margin-bottom: 0.25rem;
    }
    
    .file-upload-hint {
      font-size: 0.85rem;
      color: #888;
    }
    
    .file-name-display {
      margin-top: 1rem;
      padding: 0.75rem 1rem;
      background: #f5f5f5;
      border-radius: 6px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
    }
    
    .file-name-display i {
      margin-right: 0.5rem;
      color: var(--primary);
    }
    
    .file-input {
      display: none;
    }
    
    /* Alert Styling */
    .alert {
      border-radius: 8px;
      padding: 1rem 1.5rem;
      margin: 0 2rem 1.5rem;
      border: none;
    }
    
    .alert-danger {
      background: rgba(244, 67, 54, 0.1);
      color: var(--danger);
    }
    
    .alert-success {
      background: rgba(75, 181, 67, 0.1);
      color: var(--success);
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .portal-container {
        margin: 1rem;
        border-radius: 10px;
      }
      
      .portal-header {
        padding: 1.5rem;
      }
      
      .nav-tabs {
        padding: 0 1rem;
      }
      
      .nav-tabs .nav-link {
        padding: 1rem;
        font-size: 0.9rem;
      }
      
      .tab-content {
        padding: 1.5rem;
      }
      
      .alert {
        margin: 0 1rem 1rem;
      }
    }
  </style>
</head>
<body>
<div class="portal-container">
  <div class="portal-header">
    <div class="portal-icon">
      <i class="fas fa-chalkboard-teacher"></i>
    </div>
    <h1 class="portal-title">EduLearning Teacher Portal</h1>
    <p class="portal-subtitle">Join our community of educators</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i>
      <?= $error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php elseif (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i>
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

  <div class="tab-content">
    <!-- Login Tab -->
    <div class="tab-pane fade show active" id="login" role="tabpanel">
      <form method="POST" class="px-2">
        <div class="form-group">
          <i class="fas fa-envelope form-icon"></i>
          <input type="email" name="login_email" class="form-control" placeholder="Email Address" required>
        </div>
        
        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input type="password" name="login_password" class="form-control" placeholder="Password" required id="loginPassword">
        </div>
        
        
        <button type="submit" name="login" class="btn btn-primary w-100 py-2">
          <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
        </button>
      </form>
    </div>

    <!-- Register Tab -->
    <div class="tab-pane fade" id="register" role="tabpanel">
      <form method="POST" enctype="multipart/form-data" class="px-2">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <i class="fas fa-user form-icon"></i>
              <input type="text" name="name" class="form-control" placeholder="" required>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <i class="fas fa-envelope form-icon"></i>
              <input type="email" name="email" class="form-control" placeholder="" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <i class="fas fa-phone form-icon"></i>
              <input type="text" name="phone" class="form-control" placeholder="" required>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Gender</label>
              <i class="fas fa-venus-mars form-icon"></i>
              <select name="gender" class="form-control" required>
                <option value="">Select Gender</option>
                <option>Male</option>
                <option>Female</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Teacher Type</label>
              <i class="fas fa-user-tag form-icon"></i>
              <select name="type" id="teacherType" class="form-control" required>
                <option value="">Select Type</option>
                <option value="Professional">Professional Teacher</option>
                <option value="Peer">Peer Teacher</option>
              </select>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Subject Specialization</label>
              <i class="fas fa-book form-icon"></i>
              <input type="text" name="subject" class="form-control" placeholder="" required>
            </div>
          </div>
        </div>
        
        <!-- Certificate Upload (shown only for Professional) -->
        <div id="certificateUploadContainer" class="file-upload-container">
          <label for="certificate" class="file-upload-label">
            <div class="file-upload-icon">
              <i class="fas fa-file-pdf"></i>
            </div>
            <div class="file-upload-text">Upload Professional Certification</div>
            <div class="file-upload-hint">PDF format only (max 5MB)</div>
          </label>
          <input type="file" id="certificate" name="certificate" class="file-input" accept=".pdf">
          <div id="fileNameDisplay" class="file-name-display" style="display: none;">
            <i class="fas fa-file-alt"></i>
            <span id="fileNameText"></span>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Address</label>
          <i class="fas fa-map-marker-alt form-icon"></i>
          <textarea name="address" class="form-control" placeholder="Your full address" rows="2" required></textarea>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Pincode</label>
              <i class="fas fa-map-pin form-icon"></i>
              <input type="text" name="pincode" class="form-control" placeholder="" required>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Password</label>
              <i class="fas fa-key form-icon"></i>
              <input type="password" name="password" class="form-control" placeholder="" required id="registerPassword">
            </div>
          </div>
        </div>
        <button type="submit" name="register" class="btn btn-success w-100 py-2">
          <i class="fas fa-user-plus me-2"></i>Create Teacher Account
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
 
  
  // Show/hide certificate upload based on teacher type
  document.getElementById('teacherType').addEventListener('change', function() {
    const uploadContainer = document.getElementById('certificateUploadContainer');
    if (this.value === 'Professional') {
      uploadContainer.style.display = 'block';
      document.getElementById('certificate').required = true;
    } else {
      uploadContainer.style.display = 'none';
      document.getElementById('certificate').required = false;
      document.getElementById('fileNameDisplay').style.display = 'none';
    }
  });
  
  // Display selected file name
  document.getElementById('certificate').addEventListener('change', function(e) {
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileNameText = document.getElementById('fileNameText');
    
    if (this.files.length > 0) {
      fileNameText.textContent = this.files[0].name;
      fileNameDisplay.style.display = 'flex';
    } else {
      fileNameDisplay.style.display = 'none';
    }
  });
  
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