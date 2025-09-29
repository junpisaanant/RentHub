<?php
// forms/update_payment_public.php
include '../db.php';

// Helper function to process file uploads
function process_and_save_file($file, $attachId, $prefix, $subDir, $conn) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/' . $subDir . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $tmpName  = $file['tmp_name'];
        $origName = basename($file['name']);
        $ext      = pathinfo($origName, PATHINFO_EXTENSION);
        $newName  = uniqid($prefix) . '.' . $ext;
        $dest     = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $dest)) {
            // Save to RENT_FILE
            $stmt = $conn->prepare("INSERT INTO RENT_FILE (attach_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $attachId, $newName);
            $stmt->execute();
            $stmt->close();
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php');
  exit;
}

if (!isset($_POST['appointment_id']) || !isset($_POST['transfer_date'])) {
    die('Error: Missing form data.');
}

// Payment proof is mandatory
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
  die('Error: Please attach the proof of payment image.');
}

$apptId       = $_POST['appointment_id'];
$transferDate = $_POST['transfer_date'];

$userId = 'GUEST'; // Default value
$userName = 'Guest User';
$stmt_user = $conn->prepare("SELECT a.rent_user_id AS user_id, u.name AS user_name FROM RENT_PLACE_APPOINTMENT a JOIN RENT_USER u ON a.rent_user_id = u.id WHERE a.id = ?");
$stmt_user->bind_param("i", $apptId);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if($row_user = $result_user->fetch_assoc()) {
    $userId = $row_user['user_id'];
    $userName = $row_user['user_name'];
}
$stmt_user->close();

$conn->begin_transaction();
try {
  // 1) Create a record in RENT_ATTACH for all files
  $stmt = $conn->prepare("INSERT INTO RENT_ATTACH (create_user, create_datetime, update_user, update_datetime) VALUES (?, NOW(), ?, NOW())");
  $stmt->bind_param("ss", $userId, $userId);
  $stmt->execute();
  $attachId = $stmt->insert_id;
  $stmt->close();

  // 2) Process and save payment proof to 'uploads/payments/'
  process_and_save_file($_FILES['payment_proof'], $attachId, 'pay_', 'payments', $conn);
  
  // 3) Process and save ID card if uploaded to 'uploads/documents/'
  process_and_save_file($_FILES['id_card_proof'], $attachId, 'id_', 'documents', $conn);

  // 4) Process and save Passport if uploaded to 'uploads/documents/'
  process_and_save_file($_FILES['passport_proof'], $attachId, 'pass_', 'documents', $conn);

  // 5) Update RENT_PLACE_APPOINTMENT
  $stmt = $conn->prepare("
    UPDATE RENT_PLACE_APPOINTMENT
        SET transfer_date = ?, attach_id = ?, status='T'
      WHERE id = ?
  ");
  $stmt->bind_param("sii", $transferDate, $attachId, $apptId);
  $stmt->execute();
  $stmt->close();
  
  // ****** START: NEW FEATURE - SEND EMAIL TO ADMIN ******
  $adminEmail = "admin@the-prestige-living.com";
  $subject = "แจ้งเตือน: มีการอัปโหลดเอกสารใหม่เข้าระบบ";
  
  // Create a link for the admin to view the documents
  // Note: Replace 'yourwebsite.com' with your actual domain name
  $actual_link = "http://$_SERVER[HTTP_HOST]".dirname($_SERVER['PHP_SELF'])."/../public_view_attachment.php?id=" . $apptId;
  
  $message = "
  <html>
  <head>
    <title>แจ้งเตือนการอัปโหลดเอกสาร</title>
  </head>
  <body>
    <p>เรียน ทีมงานแอดมิน,</p>
    <p>ผู้ใช้งานชื่อ: <strong>" . htmlspecialchars($userName) . "</strong> ได้ทำการอัปโหลดเอกสารสำหรับการนัดหมายเข้าระบบเรียบร้อยแล้ว</p>
    <p>กรุณาคลิกที่ลิงก์ด้านล่างเพื่อตรวจสอบเอกสารและดำเนินการยืนยันข้อมูล:</p>
    <p><a href='" . $actual_link . "'>ตรวจสอบเอกสาร</a></p>
    <br>
    <p>ขอแสดงความนับถือ,<br>ระบบ RentHub</p>
  </body>
  </html>
  ";
  
  // To send HTML mail, the Content-type header must be set
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  
  // More headers
  $headers .= 'From: RentHub System <noreply@yourwebsite.com>' . "\r\n";
  
  // Send the email
  @mail($adminEmail, $subject, $message, $headers);
  // ****** END: NEW FEATURE - SEND EMAIL TO ADMIN ******

  $conn->commit();
  header("Location: ../payment_success.php");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Error: " . $e->getMessage());
}
?>