<?php
session_start();
include 'configuration.php';

$student_email = $_SESSION['student_email'] ?? '';

// Step 1: Get accepted teacher's email from booking_request
$bookingQuery = "SELECT teacher_email FROM booking_request WHERE email = ? AND status = 'accepted' LIMIT 1";
$bookingStmt = $conn->prepare($bookingQuery);
$bookingStmt->bind_param("s", $student_email);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();
$bookingData = $bookingResult->fetch_assoc();

$teacher_email = $bookingData['teacher_email'] ?? '';

if ($teacher_email === '') {
    die("No accepted teacher found for the student.");
}

// ✅ Step 2: Total tests created by this teacher (using teacher_email)
$totalTestsQuery = "SELECT COUNT(*) AS total_tests FROM tests WHERE teacher_email = ?";
$totalTestsStmt = $conn->prepare($totalTestsQuery);
$totalTestsStmt->bind_param("s", $teacher_email);
$totalTestsStmt->execute();
$totalTestsResult = $totalTestsStmt->get_result();
$totalTests = $totalTestsResult->fetch_assoc()['total_tests'];

// ✅ Step 3: Total test submissions by student
$totalSubmittedQuery = "SELECT COUNT(*) AS total_submitted FROM test_submissions WHERE student_email = ?";
$totalSubmittedStmt = $conn->prepare($totalSubmittedQuery);
$totalSubmittedStmt->bind_param("s", $student_email);
$totalSubmittedStmt->execute();
$totalSubmittedResult = $totalSubmittedStmt->get_result();
$totalSubmitted = $totalSubmittedResult->fetch_assoc()['total_submitted'];

// ✅ Step 4: Average marks
$avgMarksQuery = "SELECT AVG(marks) AS average_marks FROM test_submissions WHERE student_email = ? AND marks IS NOT NULL";
$avgMarksStmt = $conn->prepare($avgMarksQuery);
$avgMarksStmt->bind_param("s", $student_email);
$avgMarksStmt->execute();
$avgMarksResult = $avgMarksStmt->get_result();
$averageMarks = round($avgMarksResult->fetch_assoc()['average_marks'], 2);

// ✅ Step 5: Get marks history for chart
$marksHistoryQuery = "SELECT marks, submitted_at FROM test_submissions WHERE student_email = ? AND marks IS NOT NULL ORDER BY submitted_at";
$marksHistoryStmt = $conn->prepare($marksHistoryQuery);
$marksHistoryStmt->bind_param("s", $student_email);
$marksHistoryStmt->execute();
$marksHistoryResult = $marksHistoryStmt->get_result();

$marksData = [];
$labels = [];
while ($row = $marksHistoryResult->fetch_assoc()) {
    $marksData[] = $row['marks'];
    $labels[] = date('M d', strtotime($row['submitted_at']));
}
?>



<!DOCTYPE html>
<html>
<head>
  <title>Student Progress Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4895ef;
      --light-color: #f8f9fa;
      --success-color: #4cc9f0;
    }
    body {
      background-color: #f5f7fb;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .progress-card {
      border-radius: 15px;
      border: none;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
      background: white;
    }
    .progress-card:hover {
      transform: translateY(-5px);
    }
    .stat-card {
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      text-align: center;
      color: white;
    }
    .stat-card h2 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .stat-card p {
      font-size: 1rem;
      opacity: 0.9;
      margin-bottom: 0;
    }
    .chart-container {
      position: relative;
      height: 300px;
      margin-bottom: 30px;
    }
    .subject-badge {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 20px;
    }
    .progress-title {
      color: var(--secondary-color);
      font-weight: 600;
      margin-bottom: 25px;
      position: relative;
    }
    .progress-title:after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -10px;
      width: 50px;
      height: 3px;
      background: var(--accent-color);
    }
  </style>
</head>
<body>
    
<div class="container py-5">
  <div class="row mb-4">
    <div class="col">
      <h1 class="progress-title">📊 Your Learning Progress</h1>
      <!--  -->
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="stat-card" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
        <p>Total Tests Available</p>
        <h2><?= $totalTests ?></h2>
        <i class="fas fa-clipboard-list fa-2x mt-2"></i>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background: linear-gradient(135deg, #4895ef, #4cc9f0);">
        <p>Tests Completed</p>
        <h2><?= $totalSubmitted ?></h2>
        <i class="fas fa-check-circle fa-2x mt-2"></i>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background: linear-gradient(135deg, #3f37c9, #7209b7);">
        <p>Average Score</p>
        <h2><?= is_null($averageMarks) ? 'N/A' : $averageMarks ?></h2>
        <i class="fas fa-chart-line fa-2x mt-2"></i>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="progress-card p-4">
        <h4 class="mb-4">Your Performance </h4>
        <div class="chart-container">
          <canvas id="performanceChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="progress-card p-4 h-100">
        <h4 class="mb-4">Completion Progress</h4>
        <div class="chart-container">
          <canvas id="completionChart"></canvas>
        </div>
        <div class="text-center mt-3">
          <?php
          $completionRate = $totalTests > 0 ? round(($totalSubmitted / $totalTests) * 100) : 0;
          ?>
          <h3><?= $completionRate ?>% Complete</h3>
          <p class="text-muted">You've completed <?= $totalSubmitted ?> of <?= $totalTests ?> tests</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Performance Line Chart
  const performanceCtx = document.getElementById('performanceChart').getContext('2d');
  const performanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Your Marks',
        data: <?= json_encode($marksData) ?>,
        borderColor: '#4361ee',
        backgroundColor: 'rgba(67, 97, 238, 0.1)',
        borderWidth: 3,
        tension: 0.3,
        fill: true,
        pointBackgroundColor: '#3a0ca3',
        pointRadius: 5,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: '#3a0ca3',
          titleFont: {
            size: 14,
            weight: 'bold'
          },
          bodyFont: {
            size: 12
          },
          padding: 12,
          cornerRadius: 10
        }
      }
    }
  });

  // Completion Doughnut Chart
  const completionCtx = document.getElementById('completionChart').getContext('2d');
  const completionChart = new Chart(completionCtx, {
    type: 'doughnut',
    data: {
      labels: ['Completed', 'Remaining'],
      datasets: [{
        data: [<?= $totalSubmitted ?>, <?= max($totalTests - $totalSubmitted, 0) ?>],
        backgroundColor: [
          '#4cc9f0',
          '#e9ecef'
        ],
        borderWidth: 0,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            padding: 20,
            font: {
              size: 12
            }
          }
        }
      }
    }
  });
</script>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>