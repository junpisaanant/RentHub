<?php
ini_set('SMTP', 'localhost'); // หรือ 'smtp.gmail.com'
ini_set('smtp_port', 25);     // หรือ 587, 465
ini_set('sendmail_from', 'junpisa@gmail.com');

$to = 'junpisa@gmail.com'; // เปลี่ยนเป็นอีเมลทดสอบของคุณ
$subject = 'ทดสอบการส่งอีเมล';
$message = 'นี่คืออีเมลทดสอบ';
$headers = 'From: your_email@example.com';

if (mail($to, $subject, $message, $headers)) {
    echo 'ส่งอีเมลสำเร็จ';
} else {
    echo 'ส่งอีเมลไม่สำเร็จ';
}
?>