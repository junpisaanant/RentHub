<?php
// เชื่อมต่อกับฐานข้อมูล (แทนที่คุณด้วยข้อมูลการเชื่อมต่อฐานข้อมูลของคุณ)
//Dev
/*
$servername = "127.0.0.1";
$db_username = "root";
$db_password = "";
$dbname = "prestig6_rent_hub";
*/
//Prod
$servername = "119.59.104.22";
$db_username = "prestig6_rent_hub";
$db_password = "Junpi@012";
$dbname = "prestig6_rent_hub";


$conn = new mysqli($servername, $db_username, $db_password, $dbname);
$conn->set_charset('utf8mb4');

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>