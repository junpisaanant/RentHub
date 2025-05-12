<?php
// forms/update_payment.php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../transaction.php');
  exit;
}

// รับค่าจากฟอร์ม
$apptId       = $_POST['appointment_id'];
$transferDate = $_POST['transfer_date'];
$userId       = $_SESSION['user_id'] ?? 'GUEST';

// ประมวลผลอัพโหลดไฟล์
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
  die('Error: กรุณาแนบภาพหลักฐาน');
}

$uploadDir = __DIR__ . '/../uploads/payments/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$tmpName  = $_FILES['payment_proof']['tmp_name'];
$origName = basename($_FILES['payment_proof']['name']);
$ext      = pathinfo($origName, PATHINFO_EXTENSION);
$newName  = uniqid('pay_') . '.' . $ext;
$dest     = $uploadDir . $newName;

if (!move_uploaded_file($tmpName, $dest)) {
  die('Error: เกิดปัญหาในการอัพโหลดไฟล์');
}

// บันทึกลง RENT_ATTACH และ RENT_FILE
$conn->begin_transaction();
try {
  // 1) สร้าง record ใน RENT_ATTACH
  $stmt = $conn->prepare("INSERT INTO RENT_ATTACH (create_user, create_datetime, update_user, update_datetime) VALUES (?, NOW(), ?, NOW())");
  $stmt->bind_param("ss", $userId, $userId);
  $stmt->execute();
  $attachId = $stmt->insert_id;
  $stmt->close();

  // 2) สร้าง record ใน RENT_FILE
  $stmt = $conn->prepare("INSERT INTO RENT_FILE (attach_id, name) VALUES (?, ?)");
  $stmt->bind_param("is", $attachId, $newName);
  $stmt->execute();
  $stmt->close();

  // 3) อัพเดต RENT_PLACE_APPOINTMENT
  $stmt = $conn->prepare("
    UPDATE RENT_PLACE_APPOINTMENT
       SET transfer_date = ?, attach_id = ?,status='T'
     WHERE id = ?
  ");
  $stmt->bind_param("sii", $transferDate, $attachId, $apptId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  header("Location: transaction.php?success=1");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Error: " . $e->getMessage());
}
