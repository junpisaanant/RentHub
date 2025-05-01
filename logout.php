<?php
session_start();
session_unset();      // ล้างตัวแปร session ทั้งหมด
session_destroy();    // ทำลาย session
header("Location: login.php"); // เปลี่ยนเส้นทางไปหน้าแรก
exit();
?>