<?php
// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);


include 'configuration.php';

// Extract data from the request
$teacher_name = $data['teacher_name'];
$teacher_email = $data['teacher_email'];
$subject = $data['subject'];
$test_title = $data['test_title'];
$description = $data['description'];
$questions = $data['questions'];  // An array of questions

// Insert the test details into the `tests` table
$stmt = $conn->prepare("INSERT INTO tests (teacher_name, teacher_email, subject, test_title, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssss", $teacher_name, $teacher_email, $subject, $test_title, $description);

if ($stmt->execute()) {
    $test_id = $stmt->insert_id;  // Get the ID of the inserted test

    // Insert questions into the `test_questions` table
    if (!empty($questions)) {
        foreach ($questions as $question) {
            $q_stmt = $conn->prepare("INSERT INTO test_questions (test_id, question) VALUES (?, ?)");
            $q_stmt->bind_param("is", $test_id, $question);
            $q_stmt->execute();
            $q_stmt->close();
        }
    }

    // 🔔 Send notifications to students who have accepted bookings
    $notif_message = "New test '<b>$test_title</b>' has been created for subject <strong>$subject</strong>.";

    // Get students with accepted bookings for this teacher & subject
    $student_query = $conn->prepare("
        SELECT br.email 
        FROM booking_request br
        JOIN teacher_login t ON t.email = br.required_tutor_type = t.type
        WHERE br.subject = ? AND br.status = 'accepted' AND t.email = ?
    ");
    $student_query->bind_param("ss", $subject, $teacher_email);
    $student_query->execute();
    $result = $student_query->get_result();

    while ($row = $result->fetch_assoc()) {
        $student_email = $row['email'];

        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_email, message, status, created_at) VALUES (?, ?, 'unread', NOW())");
        $notif_stmt->bind_param("ss", $student_email, $notif_message);
        $notif_stmt->execute();
        $notif_stmt->close();
    }

    $student_query->close();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
