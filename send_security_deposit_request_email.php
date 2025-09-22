<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// ฟังก์ชันนี้ไม่ต้องรับตัวแปร $lang อีกต่อไป
function sendSecurityDepositRequestEmail($userEmail, $userName, $transactionId) {
    
    // --- [เพิ่มเข้ามา] กำหนดข้อความ 3 ภาษาไว้ในนี้โดยตรง ---
    $lang_en = [
        "DEPOSIT_REQUEST_SUBJECT"      => "Action Required: Security Deposit for Your Booking",
        "DEPOSIT_REQUEST_GREETING"     => "Dear",
        "DEPOSIT_REQUEST_THANK_YOU"    => "Thank you for your booking. To finalize your reservation, a security deposit is required.",
        "DEPOSIT_REQUEST_INSTRUCTIONS" => "Please upload proof of payment for the security deposit through our secure portal. This deposit is refundable and will be returned to you after your stay, provided there are no damages to the property.",
        "DEPOSIT_REQUEST_LOGIN_PROMPT" => "You can upload your proof of payment by accessing your transaction details here:",
        "DEPOSIT_REQUEST_LINK_TEXT"    => "Upload Proof of Payment",
        "DEPOSIT_REQUEST_REGARDS"      => "Best regards",
    ];

    $lang_th = [
        "DEPOSIT_REQUEST_SUBJECT"      => "โปรดดำเนินการ: ชำระเงินมัดจำสำหรับการจองของคุณ",
        "DEPOSIT_REQUEST_GREETING"     => "เรียน",
        "DEPOSIT_REQUEST_THANK_YOU"    => "ขอขอบคุณสำหรับการจองของคุณ เพื่อให้การจองของท่านเสร็จสมบูรณ์ กรุณาชำระเงินค่ามัดจำ",
        "DEPOSIT_REQUEST_INSTRUCTIONS" => "กรุณาอัปโหลดหลักฐานการชำระเงินค่ามัดจำผ่านทางเว็บไซต์ของเรา เงินมัดจำนี้สามารถขอคืนได้และจะถูกส่งคืนให้ท่านหลังจากการเข้าพัก หากไม่มีความเสียหายใดๆ เกิดขึ้นกับที่พัก",
        "DEPOSIT_REQUEST_LOGIN_PROMPT" => "ท่านสามารถอัปโหลดหลักฐานการชำระเงินโดยเข้าไปที่รายละเอียดการทำธุรกรรมของท่านได้ที่นี่:",
        "DEPOSIT_REQUEST_LINK_TEXT"    => "อัปโหลดหลักฐานการชำระเงิน",
        "DEPOSIT_REQUEST_REGARDS"      => "ขอแสดงความนับถือ",
    ];

    $lang_cn = [
        "DEPOSIT_REQUEST_SUBJECT"      => "需要您采取行动：为您的预订支付押金",
        "DEPOSIT_REQUEST_GREETING"     => "尊敬的",
        "DEPOSIT_REQUEST_THANK_YOU"    => "感谢您的预订。为了最终确定您的预订，需要支付押金。",
        "DEPOSIT_REQUEST_INSTRUCTIONS" => "请通过我们的安全门户上传押金付款证明。这笔押金是可退还的，如果在您住宿后财产没有损坏，押金将退还给您。",
        "DEPOSIT_REQUEST_LOGIN_PROMPT" => "您可以在此处访问您的交易详情来上传您的付款证明：",
        "DEPOSIT_REQUEST_LINK_TEXT"    => "上传付款证明",
        "DEPOSIT_REQUEST_REGARDS"      => "诚挚的问候",
    ];
    // --- [สิ้นสุดส่วนข้อความ] ---


    $mail = new PHPMailer(true);

    try {
        // การตั้งค่า Server
        $mail->isSMTP();
        $mail->CharSet    = "utf-8";
        $mail->Host       = 'mail.the-prestige-living.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@the-prestige-living.com'; 
        $mail->Password   = 'Nj@12354'; // ใช้รหัสผ่านจริงของคุณ
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ผู้รับ-ผู้ส่ง
        $mail->setFrom('admin@the-prestige-living.com', 'The Prestige Living');
        $mail->addAddress($userEmail, $userName);

        // สร้างเนื้อหาอีเมล 3 ภาษา
        $mail->isHTML(true);
        $mail->Subject = $lang_en['DEPOSIT_REQUEST_SUBJECT'] . " / " . $lang_th['DEPOSIT_REQUEST_SUBJECT'] . " / " . $lang_cn['DEPOSIT_REQUEST_SUBJECT'];
        $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>

                <div>
                    <p>" . $lang_en['DEPOSIT_REQUEST_GREETING'] . " " . $userName . ",</p>
                    <p>" . $lang_en['DEPOSIT_REQUEST_THANK_YOU'] . "</p>
                    <p>" . $lang_en['DEPOSIT_REQUEST_INSTRUCTIONS'] . "</p>
                    <p>" . $lang_en['DEPOSIT_REQUEST_LOGIN_PROMPT'] . "</p>
                    <p><a href='https://the-prestige-living.com/transaction.php?id=" . $transactionId . "'>" . $lang_en['DEPOSIT_REQUEST_LINK_TEXT'] . "</a></p>
                    <p>" . $lang_en['DEPOSIT_REQUEST_REGARDS'] . ",<br>The Prestige Living Team</p>
                </div>

                <hr style='margin: 20px 0; border: 0; border-top: 1px solid #eee;'>

                <div>
                    <p>" . $lang_th['DEPOSIT_REQUEST_GREETING'] . " " . $userName . ",</p>
                    <p>" . $lang_th['DEPOSIT_REQUEST_THANK_YOU'] . "</p>
                    <p>" . $lang_th['DEPOSIT_REQUEST_INSTRUCTIONS'] . "</p>
                    <p>" . $lang_th['DEPOSIT_REQUEST_LOGIN_PROMPT'] . "</p>
                    <p><a href='https://the-prestige-living.com/transaction.php?id=" . $transactionId . "'>" . $lang_th['DEPOSIT_REQUEST_LINK_TEXT'] . "</a></p>
                    <p>" . $lang_th['DEPOSIT_REQUEST_REGARDS'] . ",<br>ทีมงาน The Prestige Living</p>
                </div>

                <hr style='margin: 20px 0; border: 0; border-top: 1px solid #eee;'>

                <div>
                    <p>" . $lang_cn['DEPOSIT_REQUEST_GREETING'] . " " . $userName . ",</p>
                    <p>" . $lang_cn['DEPOSIT_REQUEST_THANK_YOU'] . "</p>
                    <p>" . $lang_cn['DEPOSIT_REQUEST_INSTRUCTIONS'] . "</p>
                    <p>" . $lang_cn['DEPOSIT_REQUEST_LOGIN_PROMPT'] . "</p>
                    <p><a href='https://the-prestige-living.com/transaction.php?id=" . $transactionId . "'>" . $lang_cn['DEPOSIT_REQUEST_LINK_TEXT'] . "</a></p>
                    <p>" . $lang_cn['DEPOSIT_REQUEST_REGARDS'] . ",<br>The Prestige Living 团队</p>
                </div>

            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>