<?php
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบสิทธิ์การเข้าถึงของผู้ใช้
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// รับค่าวันที่ ถ้าไม่มีให้ใช้ค่าว่าง
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// --- สร้าง Query สำหรับดึงข้อมูล ---
$sql = "
    SELECT 
        t.TRANSFER_DATE,
        rp.NAME AS place_name,
        t.PRICE,
        u.firstname,
        u.lastname
    FROM RENT_PLACE_APPOINTMENT t
    JOIN RENT_PLACE rp ON t.RENT_PLACE_ID = rp.ID
    JOIN RENT_USER u ON t.rent_user_id = u.id
    WHERE rp.USER_ID = ?
";

// เพิ่มเงื่อนไขวันที่ถ้ามีการระบุ
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND t.TRANSFER_DATE BETWEEN ? AND ?";
}
$sql .= " ORDER BY t.TRANSFER_DATE DESC";

$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($start_date) && !empty($end_date)) {
    $stmt->bind_param("iss", $admin_id, $start_date, $end_date);
} else {
    $stmt->bind_param("i", $admin_id);
}

$stmt->execute();
$result = $stmt->get_result();

// --- สร้างไฟล์ CSV สำหรับ Export ---
$filename = "revenue_report_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM (Byte Order Mark) for UTF-8 to make Excel read Thai characters correctly
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// เขียนหัวข้อคอลัมน์
fputcsv($output, array('วันที่ทำรายการ', 'ชื่อห้องเช่า', 'ผู้เช่า', 'ราคา (บาท)'));

// เขียนข้อมูลลงในไฟล์
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $renter_name = $row['firstname'] . ' ' . $row['lastname'];
        fputcsv($output, array(
            $row['TRANSFER_DATE'],
            $row['place_name'],
            $renter_name,
            $row['PRICE']
        ));
    }
}

fclose($output);
exit();
?>
