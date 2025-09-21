<?php
session_start();
include 'db.php'; 

// เรียกใช้ PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// รับอีเมลจาก AJAX request
$email = $_REQUEST['email'] ?? '';
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}


// ป้องกัน SQL injection
$email = $conn->real_escape_string($email);

// ตรวจสอบว่ามีอีเมลนี้ในฐานข้อมูลหรือไม่ และดึงข้อมูลผู้ใช้
$sql = "SELECT id, firstname, lastname, lang FROM rent_user WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userName = $user['firstname'] . ' ' . $user['lastname'];
    $userLang = $user['lang'] ?? $_SESSION['lang'] ?? 'th'; // ใช้ภาษาของผู้ใช้ หรือภาษาปัจจุบัน หรือภาษาไทย

    // สร้างรหัสผ่านใหม่แบบสุ่ม 8 ตัวอักษร
    $newPassword = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);

    // เข้ารหัสรหัสผ่านใหม่
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่านในฐานข้อมูล
    $updateSql = "UPDATE rent_user SET password = ? WHERE email = ?";
    $stmt_update = $conn->prepare($updateSql);
    $stmt_update->bind_param("ss", $hashedPassword, $email);

    if ($stmt_update->execute()) {
        
        // --- เริ่มขั้นตอนการส่งอีเมลด้วย PHPMailer ---
        
        // โหลดไฟล์ภาษา
        if (file_exists("languages/{$userLang}.php")) {
            include("languages/{$userLang}.php");
        } else {
            include("languages/en.php"); 
        }

        $mail = new PHPMailer(true);

        try {
            // ตั้งค่า Server
            $mail->isSMTP();
            $mail->CharSet    = "utf-8";
            $mail->Host       = 'mail.the-prestige-living.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@the-prestige-living.com'; 
            $mail->Password   = 'Nj@12354'; // ใช้รหัสผ่านจริง
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // ตั้งค่าผู้รับ-ผู้ส่ง
            $mail->setFrom('admin@the-prestige-living.com', 'The Prestige Living');
            $mail->addAddress($email, $userName);

            // เนื้อหาอีเมล
            $mail->isHTML(true);
            $mail->Subject = $language['FORGOT_PASS_SUBJECT'];
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <p>" . $language['FORGOT_PASS_GREETING'] . " " . $userName . ",</p>
                    <p>" . $language['FORGOT_PASS_BODY1'] . "</p>
                    <p style='font-size: 18px; font-weight: bold; color: #333;'>" . $language['FORGOT_PASS_BODY2'] . " <span style='color: #d9534f;'>" . $newPassword . "</span></p>
                    <p>" . $language['FORGOT_PASS_BODY3'] . "</p>
                    <br>
                    <p>" . $language['FORGOT_PASS_REGARDS'] . ",<br>The Prestige Living Team</p>
                </body>
                </html>
            ";

            $mail->send();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            // กรณีส่งอีเมลไม่สำเร็จ (แต่รหัสผ่านเปลี่ยนแล้ว)
            error_log("Mailer Error: {$mail->ErrorInfo}");
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถส่งอีเมลได้ กรุณาติดต่อผู้ดูแลระบบ']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบอีเมลนี้ในระบบ']);
}

$stmt->close();
$conn->close();
?>