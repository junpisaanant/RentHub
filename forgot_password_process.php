<?php
session_start();
include 'db.php'; 

// เรียกใช้ PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// --- [ปรับปรุง] กำหนดข้อความ 3 ภาษาสำหรับอีเมลลืมรหัสผ่าน ---
$lang_en = [
    'FORGOT_PASS_SUBJECT'   => 'Your New Password for The Prestige Living',
    'FORGOT_PASS_GREETING'  => 'Dear',
    'FORGOT_PASS_BODY1'     => 'Your password has been successfully reset.',
    'FORGOT_PASS_BODY2'     => 'Your new password is:',
    'FORGOT_PASS_BODY3'     => 'Please log in with this new password and change it immediately for your security.',
    'FORGOT_PASS_REGARDS'   => 'Best regards',
    'FORGOT_PASS_SUCCESS'   => 'A new password has been sent to your email.',
    'EMAIL_SEND_ERROR'      => 'Could not send email. Please contact the administrator.',
    'EMAIL_NOT_FOUND'       => 'This email was not found in the system.'
];

$lang_th = [
    'FORGOT_PASS_SUBJECT'   => 'รหัสผ่านใหม่ของคุณสำหรับ The Prestige Living',
    'FORGOT_PASS_GREETING'  => 'เรียน',
    'FORGOT_PASS_BODY1'     => 'รหัสผ่านของคุณถูกตั้งค่าใหม่เรียบร้อยแล้ว',
    'FORGOT_PASS_BODY2'     => 'รหัสผ่านใหม่ของคุณคือ:',
    'FORGOT_PASS_BODY3'     => 'กรุณาใช้รหัสผ่านนี้เพื่อเข้าสู่ระบบ และทำการเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย',
    'FORGOT_PASS_REGARDS'   => 'ขอแสดงความนับถือ',
    'FORGOT_PASS_SUCCESS'   => 'รหัสผ่านใหม่ได้ถูกส่งไปยังอีเมลของคุณแล้ว',
    'EMAIL_SEND_ERROR'      => 'ไม่สามารถส่งอีเมลได้ กรุณาติดต่อผู้ดูแลระบบ',
    'EMAIL_NOT_FOUND'       => 'ไม่พบอีเมลนี้ในระบบ'
];

$lang_cn = [
    'FORGOT_PASS_SUBJECT'   => '您在 The Prestige Living 的新密码',
    'FORGOT_PASS_GREETING'  => '尊敬的',
    'FORGOT_PASS_BODY1'     => '您的密码已成功重置。',
    'FORGOT_PASS_BODY2'     => '您的新密码是：',
    'FORGOT_PASS_BODY3'     => '请使用此新密码登录，并为了您的安全立即更改密码。',
    'FORGOT_PASS_REGARDS'   => '诚挚的问候',
    'FORGOT_PASS_SUCCESS'   => '新密码已发送到您的电子邮件。',
    'EMAIL_SEND_ERROR'      => '无法发送电子邮件。请联系管理员。',
    'EMAIL_NOT_FOUND'       => '系统中未找到此电子邮件。'
];
// --- [สิ้นสุดส่วนข้อความ] ---


// รับอีเมลจาก AJAX request (ฟีเจอร์เดิม)
$email = $_REQUEST['forgot_email'] ?? '';
header('Content-Type: application/json; charset=utf-8');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}

// ป้องกัน SQL injection (ฟีเจอร์เดิม)
$email = $conn->real_escape_string($email);

// ตรวจสอบว่ามีอีเมลนี้ในฐานข้อมูลหรือไม่ และดึงข้อมูลผู้ใช้ (ฟีเจอร์เดิม)
$sql = "SELECT id, firstname, lastname FROM RENT_USER WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database Prepare Failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userName = $user['firstname'] . ' ' . $user['lastname'];

    // สร้างรหัสผ่านใหม่แบบสุ่ม 8 ตัวอักษร (ฟีเจอร์เดิม)
    $newPassword = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);

    // เข้ารหัสรหัสผ่านใหม่ (ฟีเจอร์เดิม)
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่านในฐานข้อมูล (ฟีเจอร์เดิม)
    $updateSql = "UPDATE RENT_USER SET password = ? WHERE email = ?";
    $stmt_update = $conn->prepare($updateSql);
    $stmt_update->bind_param("ss", $hashedPassword, $email);

    if ($stmt_update->execute()) {
        
        // --- [ปรับปรุง] เริ่มขั้นตอนการส่งอีเมลแบบหลายภาษา ---
        $mail = new PHPMailer(true);

        try {
            // การตั้งค่า Server
            $mail->isSMTP();
            $mail->CharSet    = "utf-8";
            $mail->Host       = 'mail.the-prestige-living.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@the-prestige-living.com'; 
            $mail->Password   = 'Nj@12354'; // ใช้รหัสผ่านจริง
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // ผู้รับ-ผู้ส่ง
            $mail->setFrom('admin@the-prestige-living.com', 'The Prestige Living');
            $mail->addAddress($email, $userName);

            // เนื้อหาอีเมล (สร้างแบบ 3 ภาษาในฉบับเดียว)
            $mail->isHTML(true);
            $mail->Subject = $lang_en['FORGOT_PASS_SUBJECT'] . " / " . $lang_th['FORGOT_PASS_SUBJECT'] . " / " . $lang_cn['FORGOT_PASS_SUBJECT'];
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6;'>

                    <div>
                        <p>" . $lang_en['FORGOT_PASS_GREETING'] . " " . $userName . ",</p>
                        <p>" . $lang_en['FORGOT_PASS_BODY1'] . "</p>
                        <p style='font-size: 18px; font-weight: bold; color: #333;'>" . $lang_en['FORGOT_PASS_BODY2'] . " <span style='color: #d9534f;'>" . $newPassword . "</span></p>
                        <p>" . $lang_en['FORGOT_PASS_BODY3'] . "</p>
                        <p>" . $lang_en['FORGOT_PASS_REGARDS'] . ",<br>The Prestige Living Team</p>
                    </div>

                    <hr style='margin: 20px 0; border: 0; border-top: 1px solid #eee;'>

                    <div>
                        <p>" . $lang_th['FORGOT_PASS_GREETING'] . " " . $userName . ",</p>
                        <p>" . $lang_th['FORGOT_PASS_BODY1'] . "</p>
                        <p style='font-size: 18px; font-weight: bold; color: #333;'>" . $lang_th['FORGOT_PASS_BODY2'] . " <span style='color: #d9534f;'>" . $newPassword . "</span></p>
                        <p>" . $lang_th['FORGOT_PASS_BODY3'] . "</p>
                        <p>" . $lang_th['FORGOT_PASS_REGARDS'] . ",<br>ทีมงาน The Prestige Living</p>
                    </div>

                    <hr style='margin: 20px 0; border: 0; border-top: 1px solid #eee;'>

                    <div>
                        <p>" . $lang_cn['FORGOT_PASS_GREETING'] . " " . $userName . ",</p>
                        <p>" . $lang_cn['FORGOT_PASS_BODY1'] . "</p>
                        <p style='font-size: 18px; font-weight: bold; color: #333;'>" . $lang_cn['FORGOT_PASS_BODY2'] . " <span style='color: #d9534f;'>" . $newPassword . "</span></p>
                        <p>" . $lang_cn['FORGOT_PASS_BODY3'] . "</p>
                        <p>" . $lang_cn['FORGOT_PASS_REGARDS'] . ",<br>The Prestige Living 团队</p>
                    </div>

                </body>
                </html>
            ";

            $mail->send();
            $success_message = $lang_th['FORGOT_PASS_SUCCESS'] . ' / ' . $lang_en['FORGOT_PASS_SUCCESS'];
            echo json_encode(['success' => true, 'message' => $success_message]);

        } catch (Exception $e) {
            error_log("Mailer Error: {$mail->ErrorInfo}");
            $error_message = $lang_th['EMAIL_SEND_ERROR'] . ' / ' . $lang_en['EMAIL_SEND_ERROR'];
            echo json_encode(['success' => false, 'message' => $error_message]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน: ' . $conn->error]);
    }
} else {
    $not_found_message = $lang_th['EMAIL_NOT_FOUND'] . ' / ' . $lang_en['EMAIL_NOT_FOUND'];
    echo json_encode(['success' => false, 'message' => $not_found_message]);
}

$stmt->close();
$conn->close();
?>