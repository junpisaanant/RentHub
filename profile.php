<?php
ob_start(); // เพิ่ม ob_start() เพื่อจัดการ header redirection
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'info';

// --- ข้อความแจ้งเตือนสำหรับแต่ละฟอร์ม ---
$info_message = '';
$info_message_type = '';
$password_message = '';
$password_message_type = '';
$file_message = ''; // [เพิ่มใหม่] สำหรับฟอร์มไฟล์
$file_message_type = ''; // [เพิ่มใหม่]

// ==============================================================================
// [เพิ่มใหม่] ฟังก์ชันสำหรับจัดการไฟล์
// ==============================================================================

// ฟังก์ชันสำหรับอัปโหลดเอกสาร
function upload_document($conn, $user_id, $file_key, $column_to_update) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = mime_content_type($_FILES[$file_key]['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            return "ประเภทไฟล์ไม่ได้รับอนุญาต (อนุญาตเฉพาะ jpg, png, gif, pdf)";
        }

        // สร้างโฟลเดอร์สำหรับ user และสำหรับเก็บเอกสาร
        $user_dir = "assets/rent_user/" . $user_id;
        $doc_dir = $user_dir . "/documents";
        if (!is_dir($user_dir)) mkdir($user_dir, 0777, true);
        if (!is_dir($doc_dir)) mkdir($doc_dir, 0777, true);

        // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
        $file_extension = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid($file_key . '_', true) . '.' . $file_extension;
        $destination = $doc_dir . '/' . $new_filename;
        
        // ย้ายไฟล์
        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $destination)) {
            $conn->begin_transaction();
            try {
                // 1. เพิ่มข้อมูลใน RENT_ATTACH
                $stmt_attach = $conn->prepare("INSERT INTO RENT_ATTACH (name) VALUES (?)");
                $attach_name = $user_id . "/documents";
                $stmt_attach->bind_param("s", $attach_name);
                $stmt_attach->execute();
                $attach_id = $stmt_attach->insert_id;

                // 2. เพิ่มข้อมูลใน RENT_FILE
                $stmt_file = $conn->prepare("INSERT INTO RENT_FILE (attach_id, name) VALUES (?, ?)");
                $stmt_file->bind_param("is", $attach_id, $new_filename);
                $stmt_file->execute();
                $file_id = $stmt_file->insert_id;

                // 3. อัปเดต RENT_USER
                $stmt_user = $conn->prepare("UPDATE RENT_USER SET $column_to_update = ? WHERE id = ?");
                $stmt_user->bind_param("ii", $attach_id, $user_id);
                $stmt_user->execute();
                
                $conn->commit();
                return "สำเร็จ";
            } catch (Exception $e) {
                $conn->rollback();
                unlink($destination); // ลบไฟล์ที่อัปโหลดถ้าเกิดข้อผิดพลาด
                return "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
            }
        } else {
            return "ไม่สามารถย้ายไฟล์ได้";
        }
    }
    return null; // ไม่มีไฟล์ถูกอัปโหลด
}

// ฟังก์ชันสำหรับลบเอกสาร
function delete_document($conn, $user_id, $column_to_update) {
    // 1. ดึง attach_id จาก RENT_USER
    $stmt_get = $conn->prepare("SELECT $column_to_update FROM RENT_USER WHERE id = ?");
    $stmt_get->bind_param("i", $user_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $user_data = $result->fetch_assoc();
    $attach_id = $user_data[$column_to_update];

    if ($attach_id) {
        // 2. ดึงข้อมูลไฟล์
        $stmt_file = $conn->prepare("SELECT rf.id as file_id, CONCAT('assets/rent_user/', ra.name, '/', rf.name) as file_path FROM RENT_FILE rf JOIN RENT_ATTACH ra ON rf.attach_id = ra.id WHERE ra.id = ?");
        $stmt_file->bind_param("i", $attach_id);
        $stmt_file->execute();
        $file_result = $stmt_file->get_result();
        $file_data = $file_result->fetch_assoc();

        $conn->begin_transaction();
        try {
            // 3. ตั้งค่า attach_id ใน RENT_USER เป็น NULL
            $stmt_update = $conn->prepare("UPDATE RENT_USER SET $column_to_update = NULL WHERE id = ?");
            $stmt_update->bind_param("i", $user_id);
            $stmt_update->execute();
            
            if ($file_data) {
                // 4. ลบไฟล์ออกจาก server
                if (file_exists($file_data['file_path'])) {
                    unlink($file_data['file_path']);
                }

                // 5. ลบข้อมูลจาก RENT_FILE และ RENT_ATTACH
                $stmt_del_file = $conn->prepare("DELETE FROM RENT_FILE WHERE id = ?");
                $stmt_del_file->bind_param("i", $file_data['file_id']);
                $stmt_del_file->execute();
                
                $stmt_del_attach = $conn->prepare("DELETE FROM RENT_ATTACH WHERE id = ?");
                $stmt_del_attach->bind_param("i", $attach_id);
                $stmt_del_attach->execute();
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }
    return false;
}

// ฟังก์ชันสำหรับดึง path ของไฟล์
function get_document_path($conn, $attach_id) {
    if (empty($attach_id)) return null;
    $stmt = $conn->prepare("SELECT CONCAT('assets/rent_user/', ra.name, '/', rf.name) as file_path FROM RENT_FILE rf JOIN RENT_ATTACH ra ON rf.attach_id = ra.id WHERE ra.id = ?");
    $stmt->bind_param("i", $attach_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($data = $result->fetch_assoc()) {
        if(file_exists($data['file_path'])){
            return $data['file_path'];
        }
    }
    return null;
}

// ==============================================================================
// Logic การจัดการ POST requests
// ==============================================================================

// --- [เพิ่มใหม่] Logic สำหรับ "อัปเดตไฟล์เอกสาร" ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_files'])) {
    $page = 'info';
    $id_card_result = upload_document($conn, $user_id, 'id_card_image', 'id_card_attach_id');
    $passport_result = upload_document($conn, $user_id, 'passport_image', 'passport_attach_id');

    $messages = [];
    if ($id_card_result) $messages['บัตรประชาชน'] = $id_card_result;
    if ($passport_result) $messages['พาสปอร์ต'] = $passport_result;

    $success_count = 0;
    $error_messages = [];
    foreach ($messages as $key => $msg) {
        if ($msg === "สำเร็จ") {
            $success_count++;
        } else {
            $error_messages[] = "$key: $msg";
        }
    }

    if ($success_count > 0 && empty($error_messages)) {
        $file_message = "อัปโหลดไฟล์สำเร็จ";
        $file_message_type = "success";
    } elseif (!empty($error_messages)) {
        $file_message = "เกิดข้อผิดพลาด: <br>" . implode("<br>", $error_messages);
        $file_message_type = "danger";
    }
    // ไม่ต้องแสดงข้อความถ้าไม่มีไฟล์อัปโหลด
}

// --- [เพิ่มใหม่] Logic สำหรับ "ลบไฟล์เอกสาร" ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_file'])) {
    $page = 'info';
    $file_to_delete = $_POST['delete_file']; // 'id_card' or 'passport'
    $column = ($file_to_delete == 'id_card') ? 'id_card_attach_id' : 'passport_attach_id';

    if (delete_document($conn, $user_id, $column)) {
        $file_message = "ลบไฟล์เรียบร้อยแล้ว";
        $file_message_type = "success";
    } else {
        $file_message = "เกิดข้อผิดพลาดในการลบไฟล์";
        $file_message_type = "danger";
    }
}


// --- Logic สำหรับ "อัปเดตข้อมูลส่วนตัว" ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $page = 'info'; 
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $phone_no = $_POST['phone_no'];
    $email = $_POST['email'];
    $line_id = $_POST['line_id'];
    $identification_no = $_POST['identification_no'];
    $passport_no = $_POST['passport_no'];

    $stmt_check_email = $conn->prepare("SELECT id FROM RENT_USER WHERE email = ? AND id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();

    if ($result_check_email->num_rows > 0) {
        $info_message = "อีเมลนี้ถูกใช้งานแล้ว";
        $info_message_type = "danger";
    } else {
        $update_datetime = date('Y-m-d H:i:s');
        $update_user = $_SESSION['username'] ?? 'user';
        
        $stmt_update = $conn->prepare(
            "UPDATE RENT_USER SET firstname = ?, lastname = ?, phone_no = ?, email = ?, line_id = ?, identification_no = ?, passport_no = ?, update_user = ?, update_datetime = ? WHERE id = ?"
        );
        $stmt_update->bind_param("sssssssssi", $firstname, $lastname, $phone_no, $email, $line_id, $identification_no, $passport_no, $update_user, $update_datetime, $user_id);

        if ($stmt_update->execute()) {
            $info_message = "บันทึกข้อมูลส่วนตัวสำเร็จ";
            $info_message_type = "success";
            $_SESSION['firstname'] = $firstname;
        } else {
            $info_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูลส่วนตัว";
            $info_message_type = "danger";
        }
        $stmt_update->close();
    }
    $stmt_check_email->close();
}

// --- Logic สำหรับ "เปลี่ยนรหัสผ่าน" ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $page = 'password'; 
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $password_message = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";
        $password_message_type = "danger";
    } else {
        $stmt_pass = $conn->prepare("SELECT password FROM RENT_USER WHERE id = ?");
        $stmt_pass->bind_param("i", $user_id);
        $stmt_pass->execute();
        $result_pass = $stmt_pass->get_result();
        $user_data = $result_pass->fetch_assoc();

        if ($user_data && password_verify($current_password, $user_data['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update_pass = $conn->prepare("UPDATE RENT_USER SET password = ? WHERE id = ?");
            $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt_update_pass->execute()) {
                $password_message = "เปลี่ยนรหัสผ่านสำเร็จแล้ว";
                $password_message_type = "success";
            } else {
                $password_message = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
                $password_message_type = "danger";
            }
            $stmt_update_pass->close();
        } else {
            $password_message = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
            $password_message_type = "danger";
        }
        $stmt_pass->close();
    }
}


// --- ดึงข้อมูลผู้ใช้ทั้งหมดมาแสดง ---
$stmt_select = $conn->prepare("SELECT * FROM RENT_USER WHERE id = ?");
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$user = $result->fetch_assoc();

// ดึงรูปโปรไฟล์
$profile_picture_path = 'https://placehold.co/120x120/EFEFEF/AAAAAA&text=Profile'; // รูปเริ่มต้น
if ($user && !empty($user['attach_id'])) {
    $path = get_document_path($conn, $user['attach_id']);
    if($path) $profile_picture_path = $path;
}

// [เพิ่มใหม่] ดึงรูปเอกสาร
$id_card_path = null;
$passport_path = null;
if ($user) {
    if(!empty($user['id_card_attach_id'])) {
       $id_card_path = get_document_path($conn, $user['id_card_attach_id']);
    }
    if(!empty($user['passport_attach_id'])) {
        $passport_path = get_document_path($conn, $user['passport_attach_id']);
    }
    $_SESSION['username'] = $user['username'];
}
$conn->close();

?>
<main id="main">
<section class="profile-dashboard section">
    <div class="container">
        <div class="row">

            <div class="col-lg-4 col-md-5">
                <div class="profile-sidebar card">
                    <div class="card-body">
                        <div class="profile-summary text-center">
                            <img src="<?php echo htmlspecialchars($profile_picture_path); ?>" alt="Profile Picture" class="profile-picture rounded-circle mb-3">
                            <a href="#" class="edit-button" data-bs-toggle="modal" data-bs-target="#editProfilePicModal" title="เปลี่ยนรูปโปรไฟล์">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                        </div>
                        
                        <nav class="profile-nav mt-4">
                            <ul class="list-group">
                                <li class="list-group-item <?php echo ($page === 'info') ? 'active' : ''; ?>">
                                    <a href="profile.php?page=info"><i class="bi bi-person-circle me-2"></i>ข้อมูลส่วนตัว</a>
                                </li>
                                <li class="list-group-item <?php echo ($page === 'password') ? 'active' : ''; ?>">
                                    <a href="profile.php?page=password"><i class="bi bi-key me-2"></i>เปลี่ยนรหัสผ่าน</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-7">
                <?php if ($page === 'info'): ?>
                    <div class="profile-content card">
                        <div class="card-body">
                            <h5 class="card-title-form">ข้อมูลส่วนตัว</h5>
                            
                            <?php if ($info_message): ?>
                                <div class="alert alert-<?php echo $info_message_type; ?>"><?php echo htmlspecialchars($info_message); ?></div>
                            <?php endif; ?>

                            <?php if ($user): ?>
                            <form action="profile.php?page=info" method="post" class="mt-4">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstname" class="form-label">ชื่อจริง</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastname" class="form-label">นามสกุล</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="identification_no" class="form-label">เลขบัตรประชาชน</label>
                                    <input type="text" class="form-control" id="identification_no" name="identification_no" value="<?php echo htmlspecialchars($user['identification_no'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="passport_no" class="form-label">เลขพาสปอร์ต</label>
                                    <input type="text" class="form-control" id="passport_no" name="passport_no" value="<?php echo htmlspecialchars($user['passport_no'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone_no" class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="text" class="form-control" id="phone_no" name="phone_no" value="<?php echo htmlspecialchars($user['phone_no']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="line_id" class="form-label">Line ID</label>
                                    <input type="text" class="form-control" id="line_id" name="line_id" value="<?php echo htmlspecialchars($user['line_id'] ?? ''); ?>">
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">วันที่สมัคร</label>
                                        <p class="form-control-static"><?php echo date('d/m/Y H:i', strtotime($user['create_datetime'])); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">แก้ไขล่าสุด</label>
                                        <p class="form-control-static"><?php echo date('d/m/Y H:i', strtotime($user['update_datetime'])); ?></p>
                                    </div>
                                </div>
                                <div class="pt-2 text-end">
                                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-danger">ไม่พบข้อมูลผู้ใช้</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-content card mt-4">
                        <div class="card-body">
                            <h5 class="card-title-form">เอกสารยืนยันตัวตน</h5>
                             <?php if ($file_message): ?>
                                <div class="alert alert-<?php echo $file_message_type; ?>"><?php echo $file_message; ?></div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>บัตรประชาชน</h6>
                                    <?php if ($id_card_path): ?>
                                        <div class="mb-2">
                                            <a href="<?php echo htmlspecialchars($id_card_path); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($id_card_path); ?>" alt="ID Card" class="img-thumbnail" style="max-height: 150px;">
                                            </a>
                                        </div>
                                        <form action="profile.php?page=info" method="post" onsubmit="return confirm('คุณต้องการลบไฟล์นี้ใช่หรือไม่?');">
                                            <button type="submit" name="delete_file" value="id_card" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> ลบไฟล์
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-muted">ยังไม่มีการอัปโหลดไฟล์บัตรประชาชน</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6>พาสปอร์ต</h6>
                                     <?php if ($passport_path): ?>
                                        <div class="mb-2">
                                             <a href="<?php echo htmlspecialchars($passport_path); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($passport_path); ?>" alt="Passport" class="img-thumbnail" style="max-height: 150px;">
                                            </a>
                                        </div>
                                        <form action="profile.php?page=info" method="post" onsubmit="return confirm('คุณต้องการลบไฟล์นี้ใช่หรือไม่?');">
                                            <button type="submit" name="delete_file" value="passport" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> ลบไฟล์
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-muted">ยังไม่มีการอัปโหลดไฟล์พาสปอร์ต</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <form action="profile.php?page=info" method="post" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="update_files" value="1">
                                <div class="mb-3">
                                    <label for="id_card_image" class="form-label">อัปโหลดบัตรประชาชนใหม่</label>
                                    <input type="file" name="id_card_image" id="id_card_image" class="form-control" accept="image/*,application/pdf">
                                </div>
                                <div class="mb-3">
                                    <label for="passport_image" class="form-label">อัปโหลดพาสปอร์ตใหม่</label>
                                    <input type="file" name="passport_image" id="passport_image" class="form-control" accept="image/*,application/pdf">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">อัปเดตไฟล์</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($page === 'password'): ?>
                    <div class="profile-content card">
                        <div class="card-body">
                            <h5 class="card-title-form">เปลี่ยนรหัสผ่าน</h5>

                            <?php if ($password_message): ?>
                                <div class="alert alert-<?php echo $password_message_type; ?>"><?php echo htmlspecialchars($password_message); ?></div>
                            <?php endif; ?>

                            <form action="profile.php?page=password" method="post" class="mt-4">
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="pt-2 text-end">
                                    <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
<div class="modal fade" id="editProfilePicModal" tabindex="-1" aria-labelledby="editProfilePicModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfilePicModalLabel">เปลี่ยนรูปโปรไฟล์</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="update_profile_picture.php" method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="text-center mb-3">
                <img id="image-preview" src="<?php echo htmlspecialchars($profile_picture_path); ?>" alt="Image Preview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <p>เลือกรูปภาพใหม่ที่ต้องการ (แนะนำขนาด 1:1 เช่น 500x500 pixels):</p>
            <input type="file" id="profile-picture-input" name="profile_picture" class="form-control" accept="image/png, image/jpeg, image/gif" required>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึกรูปใหม่</button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile-picture-input');
    const imagePreview = document.getElementById('image-preview');

    if (fileInput && imagePreview) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php 
include 'footer.php'; 
ob_end_flush(); // ส่ง output ทั้งหมด
?>