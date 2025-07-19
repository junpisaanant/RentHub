<?php
// สำหรับการดีบัก - สามารถลบ 2 บรรทัดนี้ออกได้เมื่อนำขึ้นใช้งานจริง
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8'); // ระบุ charset เป็น UTF-8
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

$response = [];

// --- ตรวจสอบความปลอดภัยและการรับค่าพื้นฐาน ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    $response['error'] = 'Access Denied: Not logged in.';
    echo json_encode($response);
    exit();
}

if (!isset($_GET['user_id']) || !filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400); // Bad Request
    $response['error'] = 'Invalid Request: Missing or invalid user ID.';
    echo json_encode($response);
    exit();
}

// --- ตรวจสอบการเชื่อมต่อฐานข้อมูล ---
if (!$conn || $conn->connect_error) {
    http_response_code(500); // Internal Server Error
    $response['error'] = 'Database connection failed: ' . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// *** เพิ่มส่วนนี้: บังคับให้การเชื่อมต่อเป็น UTF-8 ***
$conn->set_charset("utf8mb4");

$user_id = (int)$_GET['user_id'];

// --- เตรียมและรันคำสั่ง SQL ---
$stmt = $conn->prepare("SELECT firstname, lastname, phone_no, line_id, identification_no, passport_no FROM RENT_USER WHERE id = ?");

if ($stmt === false) {
    http_response_code(500);
    $response['error'] = 'Failed to prepare the database query.';
    echo json_encode($response);
    exit();
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    $response['error'] = 'Failed to execute the database query.';
    echo json_encode($response);
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    
    $response = [
        'firstname' => htmlspecialchars($user_data['firstname'] ?? ''),
        'lastname' => htmlspecialchars($user_data['lastname'] ?? ''),
        'phone_no' => htmlspecialchars($user_data['phone_no'] ?? ''),
        'line_id' => htmlspecialchars($user_data['line_id'] ?? ''),
        'identification_no' => htmlspecialchars($user_data['identification_no'] ?? ''),
        'passport_no' => htmlspecialchars($user_data['passport_no'] ?? '')
    ];
} else {
    http_response_code(404); // Not Found
    $response['error'] = 'ไม่พบข้อมูลผู้ใช้งาน';
}

// --- ส่งข้อมูล JSON กลับไป ---
echo json_encode($response);

$stmt->close();
$conn->close();
?>
