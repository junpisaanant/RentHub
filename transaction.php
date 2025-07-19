<?php
// เปิดการแสดงผล Error เพื่อการดีบัก (สามารถลบออกได้เมื่อใช้งานจริง)
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// --- จัดการการส่งฟอร์มชำระเงิน ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    $current_user_name = $_SESSION['user_id']; // หรือ $_SESSION['username']

    // --- จัดการการอัปโหลดไฟล์ ---
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
        $target_dir = "assets/payment_proofs/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $original_filename = basename($_FILES["payment_proof"]["name"]);
        $file_size = $_FILES["payment_proof"]["size"];
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $new_filename_on_server = "proof_" . $appointment_id . "_" . time() . "." . $file_extension;
        $target_file_path = $target_dir . $new_filename_on_server;
        
        $check = getimagesize($_FILES["payment_proof"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file_path)) {
                
                // --- Step 1: สร้าง "กลุ่มไฟล์แนบ" ใน RENT_ATTACH ---
                $attach_group_name = "Payment for #" . $appointment_id;
                $attach_sql = "INSERT INTO RENT_ATTACH (NAME, SIZE, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
                $stmt_attach = $conn->prepare($attach_sql);
                $stmt_attach->bind_param("siss", $attach_group_name, $file_size, $current_user_name, $current_user_name);
                $stmt_attach->execute();
                $attach_id = $conn->insert_id; // ID ของกลุ่มไฟล์แนบ

                if ($attach_id > 0) {
                    // --- Step 2: บันทึกข้อมูล "ไฟล์จริง" ลงใน RENT_FILE ---
                    $file_sql = "INSERT INTO RENT_FILE (attach_id, type, name, size, create_user, create_datetime, update_user, update_datetime) VALUES (?, 'O', ?, ?, ?, NOW(), ?, NOW())";
                    $stmt_file = $conn->prepare($file_sql);
                    $stmt_file->bind_param("isiss", $attach_id, $target_file_path, $file_size, $current_user_name, $current_user_name);
                    $stmt_file->execute();

                    // --- Step 3: อัปเดตรายการนัดหมายด้วย ID ของกลุ่มไฟล์แนบ ---
                    $update_sql = "UPDATE RENT_PLACE_APPOINTMENT SET status = 'T', transfer_date = CURDATE(), attach_id = ?, update_user = ?, update_datetime = NOW() WHERE id = ? AND rent_user_id = ?";
                    $stmt_update = $conn->prepare($update_sql);
                    $stmt_update->bind_param("isii", $attach_id, $current_user_name, $appointment_id, $user_id);
                    
                    if ($stmt_update->execute()) {
                        $_SESSION['message'] = "ส่งหลักฐานการชำระเงินสำหรับรายการ #$appointment_id สำเร็จแล้ว กรุณารอการยืนยัน";
                    } else {
                        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูลการนัดหมาย";
                    }
                } else {
                     $_SESSION['error'] = "เกิดข้อผิดพลาดในการสร้างกลุ่มไฟล์แนบ";
                }

            } else {
                $_SESSION['error'] = "ขออภัย, เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        } else {
            $_SESSION['error'] = "ไฟล์ที่อัปโหลดไม่ใช่ไฟล์รูปภาพ";
        }
    } else {
        $_SESSION['error'] = "กรุณาแนบไฟล์หลักฐานการชำระเงิน";
    }

    header("Location: transaction.php");
    exit();
}


include 'header.php'; // ส่วนหัวของเว็บ

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- ดึงข้อมูลประวัติการทำรายการของผู้ใช้ ---
$sql = "
    SELECT 
        t.id,
        t.date AS appointment_date,
        t.in_date,
        t.transfer_date,
        t.price,
        t.status,
        rp.name AS place_name
    FROM RENT_PLACE_APPOINTMENT t
    JOIN RENT_PLACE rp ON t.rent_place_id = rp.id
    WHERE t.rent_user_id = ?
    ORDER BY t.date DESC, t.id DESC
";

$stmt = $conn->prepare($sql);
$transactions_result = null;
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $transactions_result = $stmt->get_result();
}

// ฟังก์ชันสำหรับแปลงรหัสสถานะเป็นข้อความที่อ่านง่าย
function getStatusText($status) {
    $statusMap = [
        'A' => ['text' => 'นัดหมาย', 'class' => 'primary'],
        'C' => ['text' => 'ไม่ตกลง', 'class' => 'secondary'],
        'D' => ['text' => 'ไม่มาตามนัด', 'class' => 'dark'],
        'W' => ['text' => 'รอชำระเงิน', 'class' => 'warning'],
        'T' => ['text' => 'รอยืนยัน', 'class' => 'info'],
        'O' => ['text' => 'ชำระเงินแล้ว', 'class' => 'success']
    ];
    return $statusMap[$status] ?? ['text' => 'ไม่ระบุ', 'class' => 'light'];
}
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        main { background-color: #f4f7f6; }
        .table-card { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .table thead th { background-color: #f8f9fa; white-space: nowrap; }
        .badge { font-size: 0.8rem; padding: 0.4em 0.7em; }
    </style>
</head>

<main id="main">
    <section class="container py-5">
        <div class="section-title mb-4">
            <h2>ประวัติการทำรายการ</h2>
            <p>รายการนัดหมายและชำระเงินทั้งหมดของคุณ</p>
        </div>
        
        <!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>รหัสรายการ</th>
                            <th>ชื่อสินทรัพย์</th>
                            <th>วันที่นัดหมาย</th>
                            <th>วันที่ต้องการเข้าพัก</th>
                            <th>วันที่ชำระเงิน</th>
                            <th class="text-end">ราคา (บาท)</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                            <?php while ($row = $transactions_result->fetch_assoc()): ?>
                                <?php $statusInfo = getStatusText($row['status']); ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['place_name']); ?></td>
                                    <td><?php echo date("d/m/Y", strtotime($row['appointment_date'])); ?></td>
                                    <td><?php echo $row['in_date'] ? date("d/m/Y", strtotime($row['in_date'])) : '-'; ?></td>
                                    <td><?php echo $row['transfer_date'] ? date("d/m/Y", strtotime($row['transfer_date'])) : '-'; ?></td>
                                    <td class="text-end"><?php echo number_format($row['price'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                            <?php echo $statusInfo['text']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'W'): ?>
                                            <button class="btn btn-primary btn-sm payment-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-price="<?php echo number_format($row['price'], 2); ?>">
                                                ชำระเงิน
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">ไม่พบประวัติการทำรายการ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">ยืนยันการชำระเงิน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="transaction.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" id="appointment_id" name="appointment_id" value="">
            <div class="mb-3">
                <label class="form-label">รหัสรายการ</label>
                <input type="text" id="display_appointment_id" class="form-control" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">ยอดที่ต้องชำระ (บาท)</label>
                <input type="text" id="display_price" class="form-control" disabled>
            </div>
            <div class="mb-3">
                <label for="payment_proof" class="form-label">แนบหลักฐานการชำระเงิน</label>
                <input class="form-control" type="file" id="payment_proof" name="payment_proof" required accept="image/*">
                <div class="form-text">กรุณาแนบไฟล์รูปภาพ .jpg, .jpeg, .png</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" name="submit_payment" class="btn btn-primary">ยืนยันการชำระเงิน</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // เมื่อ Modal การชำระเงินกำลังจะแสดง
    $('#paymentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // ปุ่มที่ถูกคลิก
        var appointmentId = button.data('id'); // ดึงข้อมูลจาก data-id
        var price = button.data('price'); // ดึงข้อมูลจาก data-price

        var modal = $(this);
        modal.find('#appointment_id').val(appointmentId);
        modal.find('#display_appointment_id').val('#' + appointmentId);
        modal.find('#display_price').val(price);
    });
});
</script>

<?php 
include 'footer.php'; 
ob_end_flush();
?>
