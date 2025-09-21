<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendSecurityDepositRequestEmail($userEmail, $userName, $transactionId, $lang) {
    if (file_exists("languages/{$lang}.php")) {
        include("languages/{$lang}.php");
    } else {
        include("languages/en.php"); 
    }

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2; // เพิ่มบรรทัดนี้เข้าไป

    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.the-prestige-living.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@the-prestige-living.com'; 
        $mail->Password   = 'Nj@12354'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@the-prestige-living.com', 'The Prestige Living');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = $language['DEPOSIT_REQUEST_SUBJECT'];
        $mail->Body    = "
            <html>
            <body>
                <p>" . $language['DEPOSIT_REQUEST_GREETING'] . " " . $userName . ",</p>
                <p>" . $language['DEPOSIT_REQUEST_THANK_YOU'] . "</p>
                <p>" . $language['DEPOSIT_REQUEST_INSTRUCTIONS'] . "</p>
                <p>" . $language['DEPOSIT_REQUEST_LOGIN_PROMPT'] . "</p>
                <p><a href='https://the-prestige-living.com//transaction.php?id=" . $transactionId . "'>" . $language['DEPOSIT_REQUEST_LINK_TEXT'] . "</a></p>
                <p>" . $language['DEPOSIT_REQUEST_REGARDS'] . ",</p>
                <p>The RentHub Team</p>
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