<?php
ob_start();
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// --- [เพิ่มใหม่] โหลดไฟล์ภาษา ---
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'th'; // ค่าเริ่มต้นคือภาษาไทย
}
$lang_file = 'languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include 'languages/th.php'; // หากไฟล์ภาษาไม่มี ให้ใช้ภาษาไทยเป็นค่าเริ่มต้น
}
// --- จบส่วนโหลดไฟล์ภาษา ---

include 'header.php'; // ส่วนหัวของเว็บ

// ตรวจสอบสิทธิ์การเข้าถึงของผู้ใช้
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_id'];

// --- จัดการการลบสินทรัพย์ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id_to_delete = $_POST['property_id'];

    $check_sql = "SELECT COUNT(id) AS active_tenants FROM RENT_PLACE_APPOINTMENT WHERE rent_place_id = ? AND status IN ('W', 'T', 'O')";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $property_id_to_delete);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();

    if ($result_check['active_tenants'] == 0) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM RENT_PLACE_FACILITIES WHERE rent_place_id = $property_id_to_delete");
            $conn->query("DELETE FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = $property_id_to_delete");
            $conn->query("DELETE FROM RENT_PLACE_ATTACH WHERE rent_place_id = $property_id_to_delete");

            $delete_sql = "DELETE FROM RENT_PLACE WHERE id = ? AND user_id = ?";
            $stmt_delete = $conn->prepare($delete_sql);
            $stmt_delete->bind_param("ii", $property_id_to_delete, $admin_id);
            $stmt_delete->execute();

            $conn->commit();
            $_SESSION['message'] = sprintf($lang['delete_property_success'], $property_id_to_delete);

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $_SESSION['error'] = $lang['delete_error'] . $exception->getMessage();
        }

    } else {
        $_SESSION['error'] = sprintf($lang['delete_property_error_has_tenants'], $property_id_to_delete);
    }

    header("Location: admin_properties.php");
    exit();
}

// --- [แก้ไข] เลือกฟิลด์ชื่อตามภาษาที่ตั้งค่า ---
$lang_suffix = '';
if ($_SESSION['lang'] == 'en') {
    $lang_suffix = '_en';
} elseif ($_SESSION['lang'] == 'cn') {
    $lang_suffix = '_cn';
}

$name_column = "p.name" . $lang_suffix;
// --- จบส่วนแก้ไข ---

// --- จัดการการค้นหา ---
$search_term = $_GET['search'] ?? '';

// --- ดึงข้อมูลสินทรัพย์ทั้งหมด ---
$sql = "
    SELECT 
        p.id, 
        -- [แก้ไข] ถ้าค่าในภาษาที่เลือกเป็น NULL หรือค่าว่าง ให้แสดงชื่อภาษาไทยแทน
        IFNULL(NULLIF(TRIM($name_column), ''), p.name) as name, 
        p.price, 
        p.type, 
        p.status,
        (SELECT COUNT(id) FROM RENT_PLACE_APPOINTMENT WHERE rent_place_id = p.id AND status IN ('W', 'T', 'O')) AS active_tenants
    FROM RENT_PLACE p
    WHERE p.user_id = ?
";

$params = [$admin_id];
$types = 'i';

if (!empty($search_term)) {
    // [แก้ไข] ค้นหาจากทุกภาษา
    $sql .= " AND (p.name LIKE ? OR p.name_en LIKE ? OR p.name_cn LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$properties_result = null;
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $properties_result = $stmt->get_result();
}

// --- [แก้ไข] Helper Functions ให้ใช้ตัวแปรภาษา ---
function getPropertyType($type, $lang) {
    return $lang['property_types'][$type] ?? $lang['property_types']['unspecified'];
}
function getPropertyStatus($status, $lang) {
    return $lang['property_statuses'][$status] ?? $lang['property_statuses']['unspecified'];
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
                <h2><?php echo $lang['manage_properties_title']; ?></h2>
                <p><?php echo $lang['manage_properties_subtitle']; ?></p>
            </div>
            <a href="manage_property.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> <?php echo $lang['add_new_property']; ?></a>
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
            <form method="GET" action="admin_properties.php" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="<?php echo $lang['search_placeholder']; ?>" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?php echo $lang['col_id']; ?></th>
                            <th><?php echo $lang['col_property_name']; ?></th>
                            <th><?php echo $lang['col_type']; ?></th>
                            <th class="text-end"><?php echo $lang['col_price']; ?></th>
                            <th class="text-center"><?php echo $lang['col_status']; ?></th>
                            <th class="text-center"><?php echo $lang['col_manage']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($properties_result && $properties_result->num_rows > 0): ?>
                            <?php while ($row = $properties_result->fetch_assoc()): ?>
                                <?php $statusInfo = getPropertyStatus($row['status'], $lang); ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo getPropertyType($row['type'], $lang); ?></td>
                                    <td class="text-end"><?php echo number_format($row['price'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                            <?php echo $statusInfo['text']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <a href="manage_property.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i> <?php echo $lang['btn_edit']; ?></a>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo $lang['delete_confirm']; ?>');">
                                            <input type="hidden" name="property_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_property" class="btn btn-sm btn-danger" 
                                                <?php if ($row['active_tenants'] > 0) echo 'disabled'; ?>
                                                title="<?php if ($row['active_tenants'] > 0) echo $lang['tooltip_delete_disabled']; ?>">
                                                <i class="bi bi-trash"></i> <?php echo $lang['btn_delete']; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4"><?php echo $lang['no_data_found']; ?></td>
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