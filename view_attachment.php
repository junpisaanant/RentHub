<?php
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// --- 1. ตรวจสอบความปลอดภัยพื้นฐาน ---
// ผู้ใช้ต้องล็อกอินก่อน
if (!isset($_SESSION['user_id'])) {
    // ใช้ die() หรือ exit() เพื่อหยุดการทำงานทันทีและแสดงข้อความ
    die("Access Denied. Please log in.");
}
$admin_id = $_SESSION['user_id'];

// ID ของไฟล์แนบต้องถูกส่งมาและเป็นตัวเลขเท่านั้น
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid Attachment ID.");
}
$attach_id = (int)$_GET['id'];


// --- 2. ดึงข้อมูลที่อยู่ไฟล์ (File Path) พร้อมตรวจสอบสิทธิ์ ---
// Query นี้จะดึงข้อมูลไฟล์ก็ต่อเมื่อ attach_id ที่ส่งมานั้น
// เชื่อมโยงกับสินทรัพย์ (RENT_PLACE) ที่มี user_id ตรงกับ admin ที่ล็อกอินอยู่
$sql = "
    SELECT 
        rf.NAME AS file_path
    FROM RENT_FILE rf
    JOIN RENT_ATTACH ra ON rf.attach_id = ra.id
    JOIN RENT_PLACE_APPOINTMENT rpa ON ra.id = rpa.attach_id
    JOIN RENT_PLACE rp ON rpa.rent_place_id = rp.id
    WHERE 
        rf.attach_id = ? 
        AND rp.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing the database query.");
}

$stmt->bind_param("ii", $attach_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $file_path = $row['file_path'];

    // --- 3. ตรวจสอบว่าไฟล์มีอยู่จริงบนเซิร์ฟเวอร์หรือไม่ ---
    if (file_exists($file_path)) {
        // --- 4. แสดงผลไฟล์รูปภาพ ---
        // สร้างหน้า HTML ง่ายๆ เพื่อแสดงรูปภาพ
        ?>
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ดูเอกสารแนบ</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background-color: #212529;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    box-sizing: border-box;
                }
                img {
                    max-width: 100%;
                    max-height: 95vh;
                    border-radius: 8px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                    background-color: white;
                }
            </style>
        </head>
        <body>
            <img src="<?php echo htmlspecialchars($file_path); ?>" alt="เอกสารแนบการชำระเงิน">
        </body>
        </html>
        <?php
    } else {
        // กรณีหาไฟล์ไม่พบบนเซิร์ฟเวอร์ (อาจถูกลบไปแล้ว)
        die("File not found on server at path: " . htmlspecialchars($file_path));
    }
} else {
    // กรณีไม่พบข้อมูลในฐานข้อมูล หรือไม่มีสิทธิ์ดู
    die("Attachment not found or you do not have permission to view this file.");
}

$stmt->close();
$conn->close();
?>
