<?php
include 'db.php'; // ตรวจสอบว่าไฟล์ db.php มีอยู่จริงและมีการเชื่อมต่อฐานข้อมูลที่ถูกต้อง

// ตั้งค่า Header เป็น JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate'); // ป้องกันการ cache

// ดึงข้อมูลจุดเด่นจาก RENT_FACILITIES
$sql_facilities = "SELECT id,name FROM RENT_FACILITIES WHERE type = 'P'";
$result_facilities = $conn->query($sql_facilities);

$features = array();
if ($result_facilities->num_rows > 0) {
    while ($row = $result_facilities->fetch_assoc()) {
        $features[] = array(
            'id' => $row['id'],
            'name' => $row['name']
        );
    }
}

// สร้าง array เพื่อเก็บข้อมูลทั้งหมด
$response = array(
    'results' => array(), // **สำคัญ:** Initialize เป็น array ว่างเสมอ
    'features' => $features
);


// ส่งข้อมูลกลับในรูปแบบ JSON พร้อมตัวเลือกสำหรับภาษาไทย
echo json_encode($response, JSON_UNESCAPED_UNICODE);

$conn->close();
?>