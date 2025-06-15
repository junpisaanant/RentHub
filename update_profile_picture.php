<?php
session_start();
include 'db.php';

// --- 1. ตรวจสอบความปลอดภัยเบื้องต้น ---
// ตรวจสอบว่ามีการ login หรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ login ให้หยุดการทำงานและแจ้งข้อผิดพลาด
    header('HTTP/1.1 403 Forbidden');
    $_SESSION['profile_message'] = 'เซสชั่นหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง';
    $_SESSION['profile_message_type'] = 'danger';
    header('Location: profile.php');
    exit();
}

// ตรวจสอบว่าเป็น Method POST และมีการส่งไฟล์มาหรือไม่
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['profile_message'] = 'เกิดข้อผิดพลาดในการอัปโหลด หรือไม่ได้เลือกไฟล์';
    $_SESSION['profile_message_type'] = 'danger';
    header('Location: profile.php');
    exit();
}


// --- 2. เตรียมข้อมูลไฟล์และผู้ใช้ ---
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'user'; // ดึง username จาก session ที่บันทึกไว้ใน profile.php
$file = $_FILES['profile_picture'];

$original_filename = basename($file["name"]);
$file_tmp_path = $file["tmp_name"];
$file_size = $file["size"];
$file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));


// --- 3. ตรวจสอบไฟล์ (ขนาดและนามสกุล) ---
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($file_ext, $allowed_extensions)) {
    $_SESSION['profile_message'] = 'อนุญาตเฉพาะไฟล์รูปภาพนามสกุล JPG, JPEG, PNG, GIF เท่านั้น';
    $_SESSION['profile_message_type'] = 'danger';
    header('Location: profile.php');
    exit();
}

// จำกัดขนาดไฟล์ไม่เกิน 5MB
if ($file_size > 5 * 1024 * 1024) { 
    $_SESSION['profile_message'] = 'ไฟล์ต้องมีขนาดไม่เกิน 5MB';
    $_SESSION['profile_message_type'] = 'danger';
    header('Location: profile.php');
    exit();
}


// --- 4. เตรียมไดเรกทอรีและชื่อไฟล์ใหม่ ---
$upload_dir_base = 'assets/rent_user/';
$user_upload_dir_name = (string)$user_id; // ชื่อโฟลเดอร์ของผู้ใช้ (ใช้ user_id)
$user_upload_path = $upload_dir_base . $user_upload_dir_name;

// สร้างโฟลเดอร์สำหรับ user คนนี้ถ้ายังไม่มี
if (!file_exists($user_upload_path)) {
    mkdir($user_upload_path, 0775, true);
}

// สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกันเพื่อป้องกันการเขียนทับ
$new_filename = 'profile_' . uniqid() . '.' . $file_ext;
$destination_path = $user_upload_path . '/' . $new_filename;


// --- 5. เริ่มกระบวนการบันทึกลงฐานข้อมูล (Database Transaction) ---
$conn->begin_transaction();

try {
    $current_datetime = date('Y-m-d H:i:s');
    $attach_id = null;

    // ค้นหาว่าเคยมี RENT_ATTACH สำหรับผู้ใช้นี้ (ref_id) แล้วหรือยัง
    $stmt_find_attach = $conn->prepare("SELECT id FROM RENT_ATTACH WHERE ref_table = 'RENT_USER' AND ref_id = ?");
    $stmt_find_attach->bind_param("i", $user_id);
    $stmt_find_attach->execute();
    $result_attach = $stmt_find_attach->get_result();
    
    if ($row = $result_attach->fetch_assoc()) {
        // --- กรณีมี ATTACH อยู่แล้ว ---
        $attach_id = $row['id'];
        
        // อัปเดตข้อมูล RENT_ATTACH (ตามที่คุณต้องการ)
        $stmt_update_attach = $conn->prepare("UPDATE RENT_ATTACH SET name = ?, update_user = ?, update_datetime = ? WHERE id = ?");
        $stmt_update_attach->bind_param("sssi", $user_upload_dir_name, $username, $current_datetime, $attach_id);
        if (!$stmt_update_attach->execute()) throw new Exception("Error updating RENT_ATTACH.");
        
        // ลบไฟล์รูปโปรไฟล์เก่าออกจาก Server และฐานข้อมูล
        $stmt_get_old_files = $conn->prepare("SELECT name FROM RENT_FILE WHERE attach_id = ?");
        $stmt_get_old_files->bind_param("i", $attach_id);
        $stmt_get_old_files->execute();
        $old_files_result = $stmt_get_old_files->get_result();
        while($old_file_row = $old_files_result->fetch_assoc()){
            $old_file_path = $user_upload_path . '/' . $old_file_row['name'];
            if(file_exists($old_file_path)) {
                unlink($old_file_path); // ลบไฟล์ออกจาก server
            }
        }
        $stmt_get_old_files->close();

        $stmt_delete_files = $conn->prepare("DELETE FROM RENT_FILE WHERE attach_id = ?");
        $stmt_delete_files->bind_param("i", $attach_id);
        if (!$stmt_delete_files->execute()) throw new Exception("Error deleting old RENT_FILE entries.");

    } else {
        // --- กรณีที่ยังไม่มี ATTACH ---
        // 1. Insert ข้อมูลลง RENT_ATTACH ก่อน
        $stmt_insert_attach = $conn->prepare("INSERT INTO RENT_ATTACH (name, ref_id, ref_table, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, 'RENT_USER', ?, ?, ?, ?)");
        $stmt_insert_attach->bind_param("sissss", $user_upload_dir_name, $user_id, $username, $current_datetime, $username, $current_datetime);
        if (!$stmt_insert_attach->execute()) throw new Exception("Error inserting into RENT_ATTACH.");
        $attach_id = $conn->insert_id; // ดึง ID ของ RENT_ATTACH ที่เพิ่งสร้าง
        $stmt_insert_attach->close();

        // 2. อัปเดตตาราง RENT_USER ให้มี attach_id
        $stmt_update_user = $conn->prepare("UPDATE RENT_USER SET attach_id = ? WHERE id = ?");
        $stmt_update_user->bind_param("ii", $attach_id, $user_id);
        if (!$stmt_update_user->execute()) throw new Exception("Error updating RENT_USER.");
        $stmt_update_user->close();
    }
    $stmt_find_attach->close();

    // --- Insert ข้อมูลไฟล์ใหม่ลง RENT_FILE ---
    $stmt_insert_file = $conn->prepare("INSERT INTO RENT_FILE (name, attach_id, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert_file->bind_param("sissss", $new_filename, $attach_id, $username, $current_datetime, $username, $current_datetime);
    if (!$stmt_insert_file->execute()) throw new Exception("Error inserting into RENT_FILE.");
    $stmt_insert_file->close();
    
    // --- 6. ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์ปลายทาง ---
    if (!move_uploaded_file($file_tmp_path, $destination_path)) {
        throw new Exception("ไม่สามารถย้ายไฟล์ที่อัปโหลดได้");
    }

    // --- 7. ถ้าทุกอย่างสำเร็จ ให้ Commit Transaction ---
    $conn->commit();
    $_SESSION['profile_message'] = 'อัปเดตรูปโปรไฟล์สำเร็จ';
    $_SESSION['profile_message_type'] = 'success';

} catch (Exception $e) {
    // --- 8. ถ้ามีข้อผิดพลาด ให้ Rollback Transaction ---
    $conn->rollback();
    // ส่ง message กลับไปพร้อมรายละเอียดข้อผิดพลาด (สำหรับ developer)
    $_SESSION['profile_message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    $_SESSION['profile_message_type'] = 'danger';

    // ลบไฟล์ที่อาจจะถูกย้ายไปแล้วถ้าเกิดข้อผิดพลาดทีหลัง
    if (isset($destination_path) && file_exists($destination_path)) {
        unlink($destination_path);
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// --- 9. Redirect กลับไปหน้าโปรไฟล์ ---
header('Location: profile.php');
exit();
?>