<?php
include '../db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_POST['appointment_id'])) {
    die('Error: Missing appointment ID.');
}

$apptId = $_POST['appointment_id'];

// Update the appointment status to 'A' (Approved)
$stmt = $conn->prepare("UPDATE RENT_PLACE_APPOINTMENT SET status = 'A' WHERE id = ? AND status = 'T'");
$stmt->bind_param("i", $apptId);
$stmt->execute();

$success = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

// Redirect back to the review page with a status message
header('Location: ../public_view_attachment.php?id=' . $apptId . '&status=' . ($success ? 'approved' : 'error'));
exit;
?>