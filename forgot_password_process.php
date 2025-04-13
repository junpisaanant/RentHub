<?php
include 'db.php'; 

// รับอีเมลจาก AJAX request
$email = $_REQUEST['email'];

// ป้องกัน SQL injection
$email = mysqli_real_escape_string($conn, $email);

// ตรวจสอบว่ามีอีเมลนี้ในฐานข้อมูลหรือไม่
$sql = "SELECT * FROM rent_user WHERE email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // สร้างรหัสผ่านใหม่แบบสุ่ม 6 หลัก
    $newPassword = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);

    // เข้ารหัสรหัสผ่านใหม่ (สำคัญมาก!)
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่านในฐานข้อมูล
    $updateSql = "UPDATE rent_user SET password = '$hashedPassword' WHERE email = '$email'";
    if ($conn->query($updateSql) === TRUE) {
        // ส่งอีเมลพร้อมรหัสผ่านใหม่
        $to = $email;
        $subject = "รหัสผ่านใหม่ของคุณ";
        $message = "รหัสผ่านใหม่ของคุณคือ: " . $newPassword . "\n\nกรุณาเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบ";
        $headers = "From: your_email@example.com"; // เปลี่ยนเป็นอีเมลของคุณ

        if (mail($to, $subject, $message, $headers)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'ไม่สามารถส่งอีเมลได้'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน: ' . $conn->error));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'ไม่พบอีเมลนี้ในระบบ'));
}

$conn->close();
?>