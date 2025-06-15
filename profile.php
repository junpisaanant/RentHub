<?php
session_start();
include 'db.php';

// --- PHP LOGIC (เหมือนเดิม) ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $phone_no = $_POST['phone_no'];
    $email = $_POST['email'];
    $line_id = $_POST['line_id'];

    $stmt_check_email = $conn->prepare("SELECT id FROM RENT_USER WHERE email = ? AND id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();

    if ($result_check_email->num_rows > 0) {
        $message = "อีเมลนี้ถูกใช้งานแล้ว";
        $message_type = "danger";
    } else {
        $update_datetime = date('Y-m-d H:i:s');
        $update_user = $_SESSION['username'] ?? 'user';
        $stmt_update = $conn->prepare("UPDATE RENT_USER SET firstname = ?, lastname = ?, phone_no = ?, email = ?, line_id = ?, update_user = ?, update_datetime = ? WHERE id = ?");
        $stmt_update->bind_param("sssssssi", $firstname, $lastname, $phone_no, $email, $line_id, $update_user, $update_datetime, $user_id);
        if ($stmt_update->execute()) {
            $message = "บันทึกข้อมูลสำเร็จ";
            $message_type = "success";
            $_SESSION['firstname'] = $firstname;
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            $message_type = "danger";
        }
        $stmt_update->close();
    }
    $stmt_check_email->close();
}

$stmt_select = $conn->prepare("SELECT firstname, lastname, email, phone_no, line_id, username FROM RENT_USER WHERE id = ?");
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$user = $result->fetch_assoc();
if ($user) {
    $_SESSION['username'] = $user['username'];
}
$conn->close();

include 'header.php';
?>

<section class="profile-dashboard section">
    <div class="container">
        <div class="row">

            <div class="col-lg-4 col-md-5">
                <div class="profile-sidebar card">
                    <div class="card-body">
                        <div class="profile-summary text-center">
                            <img src="https://via.placeholder.com/120" alt="Profile Picture" class="profile-picture rounded-circle mb-3">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-7">
                <div class="profile-content card">
                    <div class="card-body">
                        <h5 class="card-title-form">ข้อมูลส่วนตัว</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($user): ?>
                        <form action="profile.php" method="post" class="mt-4">
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
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
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
                            <div class="pt-2 text-end">
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger">ไม่พบข้อมูลผู้ใช้</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<?php include 'footer.php'; ?>