<?php
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบสิทธิ์การเข้าถึงของผู้ใช้
if (!isset($_SESSION['user_id'])) {
    // ไม่ควรมี output ใดๆ ก่อน header()
    exit('Access Denied');
}

$admin_id = $_SESSION['user_id'];

// --- จัดการตัวกรอง ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$rent_place_id = $_GET['rent_place_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

// --- สร้าง SQL Query แบบไดนามิกตามตัวกรอง ---
$sql = "
    SELECT 
        t.id,
        t.date AS appointment_date,
        t.in_date,
        t.transfer_date,
        t.price,
        t.status,
        rp.name AS place_name,
        u.firstname,
        u.lastname
    FROM RENT_PLACE_APPOINTMENT t
    JOIN RENT_PLACE rp ON t.rent_place_id = rp.id
    JOIN RENT_USER u ON t.rent_user_id = u.id 
    WHERE rp.user_id = ?
";

$params = [$admin_id];
$types = 'i';

if (!empty($start_date)) {
    $sql .= " AND t.date >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $sql .= " AND t.date <= ?";
    $params[] = $end_date;
    $types .= 's';
}
if (!empty($rent_place_id)) {
    $sql .= " AND t.rent_place_id = ?";
    $params[] = $rent_place_id;
    $types .= 'i';
}
if (!empty($filter_status)) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql .= " ORDER BY t.date DESC, t.id DESC";

$stmt_transactions = $conn->prepare($sql);
$transactions_result = null;
if ($stmt_transactions) {
    $stmt_transactions->bind_param($types, ...$params);
    $stmt_transactions->execute();
    $transactions_result = $stmt_transactions->get_result();
}

// ฟังก์ชันสำหรับแปลงรหัสสถานะเป็นข้อความ
function getStatusTextForExport($status) {
    $statusMap = [
        'A' => 'นัดหมาย',
        'C' => 'ไม่ตกลง',
        'D' => 'ไม่มาตามนัด',
        'W' => 'รอชำระเงิน',
        'T' => 'รอยืนยัน',
        'O' => 'ชำระเงินแล้ว'
    ];
    return $statusMap[$status] ?? 'ไม่ระบุ';
}

// --- สร้างไฟล์ CSV สำหรับ Export ---
$filename = "transactions_report_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM (Byte Order Mark) for UTF-8 to make Excel read Thai characters correctly
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// เขียนหัวข้อคอลัมน์
fputcsv($output, array('รหัส', 'สินทรัพย์', 'ผู้เช่า', 'วันที่นัดหมาย', 'วันที่ต้องการเข้าพัก', 'วันที่ชำระเงิน', 'ราคา (บาท)', 'สถานะ'));

// เขียนข้อมูลลงในไฟล์
if ($transactions_result && $transactions_result->num_rows > 0) {
    while ($row = $transactions_result->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['place_name'],
            $row['firstname'] . ' ' . $row['lastname'],
            $row['appointment_date'],
            $row['in_date'] ?? '-',
            $row['transfer_date'] ?? '-',
            $row['price'],
            getStatusTextForExport($row['status'])
        ));
    }
}

fclose($output);
exit();
?>
