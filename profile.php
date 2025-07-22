<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- ตรวจสอบหน้าปัจจุบันจาก URL (info หรือ password) ---
$page = $_GET['page'] ?? 'info';

$user_id = $_SESSION['user_id'];
$info_message = '';
$info_message_type = '';
$password_message = '';
$password_message_type = '';

// --- Logic สำหรับ "อัปเดตข้อมูลส่วนตัว" ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $page = 'info'; // กำหนดให้กลับมาหน้า info หลัง submit
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
    $page = 'password'; // กำหนดให้กลับมาหน้า password หลัง submit
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
    $attach_id = $user['attach_id'];
    $stmt_file = $conn->prepare(
        "SELECT CONCAT('assets/rent_user/', ra.name, '/', rf.name) as file_path FROM RENT_FILE rf JOIN RENT_ATTACH ra ON rf.attach_id = ra.id WHERE ra.id = ?"
    );
    $stmt_file->bind_param("i", $attach_id);
    $stmt_file->execute();
    $file_result = $stmt_file->get_result();
    if ($file_data = $file_result->fetch_assoc()) {
        if (file_exists($file_data['file_path'])) {
            $profile_picture_path = $file_data['file_path'];
        }
    }
    $stmt_file->close();
}

if ($user) {
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
            <!-- [เพิ่มใหม่] เพิ่ม img tag สำหรับแสดงภาพตัวอย่าง -->
            <div class="text-center mb-3">
                <img id="image-preview" src="<?php echo htmlspecialchars($profile_picture_path); ?>" alt="Image Preview" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <p>เลือกรูปภาพใหม่ที่ต้องการ (แนะนำขนาด 1:1 เช่น 500x500 pixels):</p>
            <!-- [เพิ่มใหม่] เพิ่ม id ให้ input -->
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

<!-- [เพิ่มใหม่] JavaScript สำหรับแสดงภาพตัวอย่าง -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile-picture-input');
    const imagePreview = document.getElementById('image-preview');

    if (fileInput && imagePreview) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                // สร้าง URL ชั่วคราวสำหรับไฟล์ที่เลือก
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

<?php include 'footer.php'; ?>
