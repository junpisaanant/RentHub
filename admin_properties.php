<?php
ob_start();
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
include 'header.php'; // ส่วนหัวของเว็บ

// ตรวจสอบสิทธิ์การเข้าถึงของผู้ใช้
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_id'];

// --- [เพิ่มใหม่] จัดการการลบสินทรัพย์ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id_to_delete = $_POST['property_id'];

    // 1. ตรวจสอบว่าสินทรัพย์นี้มีผู้เช่าที่ยัง Active อยู่หรือไม่ (W, T, O)
    $check_sql = "SELECT COUNT(id) AS active_tenants FROM RENT_PLACE_APPOINTMENT WHERE rent_place_id = ? AND status IN ('W', 'T', 'O')";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $property_id_to_delete);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();

    if ($result_check['active_tenants'] == 0) {
        // ไม่มีผู้เช่า สามารถลบได้
        // **สำคัญ:** ควรลบข้อมูลที่เกี่ยวข้องในตารางลูกก่อน (cascade delete)
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM RENT_PLACE_FACILITIES WHERE rent_place_id = $property_id_to_delete");
            $conn->query("DELETE FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = $property_id_to_delete");
            $conn->query("DELETE FROM RENT_PLACE_ATTACH WHERE rent_place_id = $property_id_to_delete");
            // (อาจจะต้องลบจาก RENT_FILE และ RENT_ATTACH ด้วยหากโครงสร้างซับซ้อนกว่านี้)

            // สุดท้าย ลบตัวสินทรัพย์หลัก
            $delete_sql = "DELETE FROM RENT_PLACE WHERE id = ? AND user_id = ?";
            $stmt_delete = $conn->prepare($delete_sql);
            $stmt_delete->bind_param("ii", $property_id_to_delete, $admin_id);
            $stmt_delete->execute();

            $conn->commit();
            $_SESSION['message'] = "ลบสินทรัพย์รหัส #$property_id_to_delete สำเร็จแล้ว";

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $exception->getMessage();
        }

    } else {
        // มีผู้เช่าอยู่ ลบไม่ได้
        $_SESSION['error'] = "ไม่สามารถลบสินทรัพย์รหัส #$property_id_to_delete ได้ เนื่องจากยังมีผู้เช่าอยู่";
    }

    header("Location: admin_properties.php");
    exit();
}


// --- จัดการการค้นหา ---
$search_term = $_GET['search'] ?? '';

// --- ดึงข้อมูลสินทรัพย์ทั้งหมด ---
$sql = "
    SELECT 
        p.id, 
        p.name, 
        p.price, 
        p.type, 
        p.status,
        -- ตรวจสอบว่าสินทรัพย์นี้สามารถลบได้หรือไม่ (ไม่มีผู้เช่าสถานะ W, T, O)
        (SELECT COUNT(id) FROM RENT_PLACE_APPOINTMENT WHERE rent_place_id = p.id AND status IN ('W', 'T', 'O')) AS active_tenants
    FROM RENT_PLACE p
    WHERE p.user_id = ?
";

$params = [$admin_id];
$types = 'i';

if (!empty($search_term)) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$properties_result = null;
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $properties_result = $stmt->get_result();
}

// --- Helper Functions ---
function getPropertyType($type) {
    $types = ['H' => 'บ้านเดี่ยว', 'C' => 'คอนโด', 'A' => 'อพาร์ตเมนต์', 'V' => 'วิลล่า', 'T' => 'ทาวน์เฮาส์', 'L' => 'ที่ดิน'];
    return $types[$type] ?? 'ไม่ระบุ';
}
function getPropertyStatus($status) {
    $statuses = ['E' => ['text' => 'ว่าง', 'class' => 'success'], 'F' => ['text' => 'เต็ม', 'class' => 'danger']];
    return $statuses[$status] ?? ['text' => 'ไม่ระบุ', 'class' => 'secondary'];
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
        .action-buttons .btn { margin: 0 2px; }
    </style>
</head>

<main id="main">
    <section class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="section-title mb-0">
                <h2>จัดการสินทรัพย์</h2>
                <p>เพิ่ม แก้ไข และลบสินทรัพย์สำหรับปล่อยเช่าของคุณ</p>
            </div>
            <a href="manage_property.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> เพิ่มสินทรัพย์ใหม่</a>
        </div>

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
            <!-- Search Form -->
            <form method="GET" action="admin_properties.php" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อสินทรัพย์..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อสินทรัพย์</th>
                            <th>ประเภท</th>
                            <th class="text-end">ราคา (บาท/เดือน)</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($properties_result && $properties_result->num_rows > 0): ?>
                            <?php while ($row = $properties_result->fetch_assoc()): ?>
                                <?php $statusInfo = getPropertyStatus($row['status']); ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo getPropertyType($row['type']); ?></td>
                                    <td class="text-end"><?php echo number_format($row['price'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                            <?php echo $statusInfo['text']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <a href="manage_property.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i> แก้ไข</a>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('คุณต้องการลบสินทรัพย์นี้ใช่หรือไม่?');">
                                            <input type="hidden" name="property_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_property" class="btn btn-sm btn-danger" 
                                                <?php if ($row['active_tenants'] > 0) echo 'disabled'; ?>
                                                title="<?php if ($row['active_tenants'] > 0) echo 'ไม่สามารถลบได้เนื่องจากมีผู้เช่าอยู่'; ?>">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูลสินทรัพย์</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<?php 
include 'footer.php'; 
ob_end_flush();
?>
