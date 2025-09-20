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

// *** บังคับให้การเชื่อมต่อเป็น UTF-8 ***
$conn->set_charset("utf8mb4");

/**
 * [เพิ่มใหม่] ฟังก์ชันสำหรับดึง Path เต็มของไฟล์จาก attach_id
 * @param mysqli $conn Connection object
 * @param int|null $attach_id ID จากตาราง RENT_ATTACH
 * @return string|null คืนค่า Path ของไฟล์ หรือ null ถ้าไม่พบ
 */
function get_document_path($conn, $attach_id) {
    if (empty($attach_id)) {
        return null;
    }
    $stmt = $conn->prepare(
        "SELECT CONCAT('assets/rent_user/', ra.name, '/', rf.name) as file_path 
         FROM RENT_FILE rf 
         JOIN RENT_ATTACH ra ON rf.attach_id = ra.id 
         WHERE ra.id = ?"
    );
    if ($stmt) {
        $stmt->bind_param("i", $attach_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($data = $result->fetch_assoc()) {
            // ตรวจสอบว่าไฟล์มีอยู่จริงบน server หรือไม่
            if (file_exists($data['file_path'])) {
                return $data['file_path'];
            }
        }
        $stmt->close();
    }
    return null;
}


$user_id = (int)$_GET['user_id'];

// --- [แก้ไข] เตรียมและรันคำสั่ง SQL ให้ดึง attach_id เพิ่ม ---
$sql = "SELECT firstname, lastname, phone_no, line_id, identification_no, passport_no, id_card_attach_id, passport_attach_id FROM RENT_USER WHERE id = ?";
$stmt = $conn->prepare($sql);

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
    
    // --- [แก้ไข] เพิ่มการดึง path รูปภาพและใส่เข้าไปใน response ---
    $response = [
        'firstname' => htmlspecialchars($user_data['firstname'] ?? ''),
        'lastname' => htmlspecialchars($user_data['lastname'] ?? ''),
        'phone_no' => htmlspecialchars($user_data['phone_no'] ?? ''),
        'line_id' => htmlspecialchars($user_data['line_id'] ?? ''),
        'identification_no' => htmlspecialchars($user_data['identification_no'] ?? ''),
        'passport_no' => htmlspecialchars($user_data['passport_no'] ?? ''),
        'id_card_path' => get_document_path($conn, $user_data['id_card_attach_id']),
        'passport_path' => get_document_path($conn, $user_data['passport_attach_id'])
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