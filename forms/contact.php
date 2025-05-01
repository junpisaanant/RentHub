<?php
include '../db.php';
session_start();

// ตรวจสอบว่ามี session หรือไม่
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.php';</script>";
    exit();
}

$rent_place_id = $_POST['rent_place_id'];
$appointment_date = $_POST['appointment_date'];
$in_date = !empty($_POST['in_date']) ? $_POST['in_date'] : null;
$remark = $_POST['remark'];

$user_id = $_SESSION['user_id'];
$create_user = $user_id;

$sql = "INSERT INTO RENT_PLACE_APPOINTMENT (
    rent_place_id, rent_user_id, date, in_date, remark, status,
    create_user, create_datetime, update_user, update_datetime
) VALUES (?, ?, ?, ?, ?, 'A', ?, NOW(), ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL'); history.back();</script>";
    exit();
}

$stmt->bind_param("iisssss", 
    $rent_place_id, 
    $user_id,
    $appointment_date, 
    $in_date, 
    $remark,
    $create_user, 
    $create_user
);

if ($stmt->execute()) {
    echo "<script>alert('บันทึกนัดหมายเรียบร้อยแล้ว'); window.location.href='../index.php';</script>";
    exit();
} else {
    echo "<script>alert('เกิดข้อผิดพลาด: ไม่สามารถบันทึกนัดหมายได้'); history.back();</script>";
    exit();
}
?>
