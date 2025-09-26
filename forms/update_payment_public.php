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
$stmt_user = $conn->prepare("SELECT rent_user_id AS user_id FROM RENT_PLACE_APPOINTMENT WHERE id = ?");
$stmt_user->bind_param("i", $apptId);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if($row_user = $result_user->fetch_assoc()) {
    $userId = $row_user['user_id'];
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

  $conn->commit();
  header("Location: ../payment_success.php");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Error: " . $e->getMessage());
}
?>