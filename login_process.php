<?php
include 'db.php';

// รับข้อมูล username และ password จาก AJAX request
$username = $_POST['username'];
$password = $_POST['password'];

// 1. ใช้ Prepared Statements เพื่อป้องกัน SQL Injection
$sql = "SELECT id, email, phone_no, line_id, firstname, lastname, username, identification_no, passport_no, password FROM RENT_USER WHERE username = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // กรณีเกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL
    $response = array('success' => false, 'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล');
    header('Content-Type: application/json');
    echo json_encode($response);
    $conn->close();
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // 2. ตรวจสอบรหัสผ่านด้วย password_verify()
    if (password_verify($password, $row['password'])) {
        // รหัสผ่านถูกต้อง
        // เริ่ม session (ถ้ายังไม่ได้เริ่ม)
        session_start();
        // เก็บข้อมูลผู้ใช้ลงใน session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['phone_no'] = $row['phone_no'];
        $_SESSION['line_id'] = $row['line_id'];
        $_SESSION['firstname'] = $row['firstname'];
        $_SESSION['lastname'] = $row['lastname'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['identification_no'] = $row['identification_no'];
        $_SESSION['passport_no'] = $row['passport_no'];

        $response = array('success' => true);
    } else {
        // รหัสผ่านไม่ถูกต้อง
        $response = array('success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
    }
} else {
    // ไม่พบ Username ในฐานข้อมูล
    $response = array('success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
}

// ส่งผลลัพธ์กลับไปเป็น JSON
header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
?>