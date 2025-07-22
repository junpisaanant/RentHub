<?php
session_start();
include 'db.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งฟอร์มและไฟล์มาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES["profile_picture"];

    // ตรวจสอบข้อผิดพลาดในการอัปโหลด
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        header("Location: profile.php");
        exit();
    }

    // กำหนดโฟลเดอร์และสร้างถ้ายังไม่มี
    $target_dir = "assets/rent_user/" . $user_id . "/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // ตรวจสอบประเภทไฟล์
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        $_SESSION['error'] = "รองรับไฟล์ประเภท JPG, JPEG, PNG, & GIF เท่านั้น";
        header("Location: profile.php");
        exit();
    }

    // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // ย้ายไฟล์ไปยังโฟลเดอร์เป้าหมาย
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        
        $conn->begin_transaction();
        try {
            // 1. ดึง attach_id เก่า (ถ้ามี) เพื่อลบไฟล์และข้อมูลเก่า
            $stmt_old = $conn->prepare("SELECT attach_id FROM RENT_USER WHERE id = ?");
            $stmt_old->bind_param("i", $user_id);
            $stmt_old->execute();
            $result_old = $stmt_old->get_result();
            $old_attach_id = null;
            if ($row_old = $result_old->fetch_assoc()) {
                $old_attach_id = $row_old['attach_id'];
            }
            $stmt_old->close();

            // 2. เพิ่มข้อมูลใน RENT_ATTACH
            $attach_sql = "INSERT INTO RENT_ATTACH (NAME, SIZE, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
            $stmt_attach = $conn->prepare($attach_sql);
            $folder_name = (string)$user_id;
            $file_size = $file['size'];
            $current_user_name = $_SESSION['username'] ?? 'user';
            $stmt_attach->bind_param("siss", $folder_name, $file_size, $current_user_name, $current_user_name);
            $stmt_attach->execute();
            $new_attach_id = $conn->insert_id;
            $stmt_attach->close();

            // 3. เพิ่มข้อมูลใน RENT_FILE
            $file_sql = "INSERT INTO RENT_FILE (attach_id, type, name, size, create_user, create_datetime, update_user, update_datetime) VALUES (?, 'U', ?, ?, ?, NOW(), ?, NOW())"; // 'U' for User profile
            $stmt_file = $conn->prepare($file_sql);
            $stmt_file->bind_param("isiss", $new_attach_id, $new_filename, $file_size, $current_user_name, $current_user_name);
            $stmt_file->execute();
            $stmt_file->close();

            // 4. อัปเดต attach_id ในตาราง RENT_USER
            $stmt_update_user = $conn->prepare("UPDATE RENT_USER SET attach_id = ? WHERE id = ?");
            $stmt_update_user->bind_param("ii", $new_attach_id, $user_id);
            $stmt_update_user->execute();
            $stmt_update_user->close();

            // 5. ลบไฟล์และข้อมูลเก่า (ถ้ามี)
            if ($old_attach_id) {
                $stmt_old_file = $conn->prepare("SELECT rf.name as filename, ra.NAME as foldername FROM RENT_FILE rf JOIN RENT_ATTACH ra ON rf.attach_id = ra.id WHERE rf.attach_id = ?");
                $stmt_old_file->bind_param("i", $old_attach_id);
                $stmt_old_file->execute();
                $result_old_file = $stmt_old_file->get_result();
                if ($old_file_data = $result_old_file->fetch_assoc()) {
                    $old_file_path = "assets/rent_user/" . $old_file_data['foldername'] . "/" . $old_file_data['filename'];
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
                $stmt_old_file->close();

                $conn->query("DELETE FROM RENT_FILE WHERE attach_id = $old_attach_id");
                $conn->query("DELETE FROM RENT_ATTACH WHERE id = $old_attach_id");
            }

            $conn->commit();
            $_SESSION['message'] = "อัปเดตรูปโปรไฟล์สำเร็จ";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล: " . $e->getMessage();
        }

    } else {
        $_SESSION['error'] = "ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ที่ต้องการได้";
    }
} else {
    $_SESSION['error'] = "ไม่มีไฟล์ถูกส่งมา หรือเกิดข้อผิดพลาด";
}

header("Location: profile.php");
exit();
?>
