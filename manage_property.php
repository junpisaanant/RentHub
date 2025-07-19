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

// --- กำหนดโหมด: 'Add' หรือ 'Edit' ---
$mode = 'Add';
$property_id = null;
$property_data = [];
$property_facilities = [];
$property_landmarks = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $mode = 'Edit';
    $property_id = (int)$_GET['id'];

    // ดึงข้อมูลสินทรัพย์ที่จะแก้ไข
    $stmt = $conn->prepare("SELECT * FROM RENT_PLACE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $property_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $property_data = $result->fetch_assoc();
    } else {
        // ไม่พบข้อมูล หรือไม่มีสิทธิ์แก้ไข
        $_SESSION['error'] = "ไม่พบสินทรัพย์ที่ต้องการแก้ไข หรือคุณไม่มีสิทธิ์";
        header("Location: admin_properties.php");
        exit();
    }

    // ดึงข้อมูลสิ่งอำนวยความสะดวกที่เคยเลือกไว้
    $stmt_fac = $conn->prepare("SELECT rent_facilities_id FROM RENT_PLACE_FACILITIES WHERE rent_place_id = ?");
    $stmt_fac->bind_param("i", $property_id);
    $stmt_fac->execute();
    $result_fac = $stmt_fac->get_result();
    while($row = $result_fac->fetch_assoc()) {
        $property_facilities[] = $row['rent_facilities_id'];
    }

    // ดึงข้อมูลสถานที่สำคัญที่เคยเลือกไว้
    $stmt_land = $conn->prepare("SELECT rent_landmark_id, distance FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = ?");
    $stmt_land->bind_param("i", $property_id);
    $stmt_land->execute();
    $result_land = $stmt_land->get_result();
    while($row = $result_land->fetch_assoc()) {
        $property_landmarks[] = $row;
    }
}

// --- จัดการการส่งฟอร์ม (เพิ่ม/แก้ไข) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? 0;
    $room_qty = $_POST['room_qty'] ?? 0;
    $toilet_qty = $_POST['toilet_qty'] ?? 0;
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $address = $_POST['address'] ?? '';
    $province_id = $_POST['province_id'] ?? null;
    $district_id = $_POST['district_id'] ?? null;
    $sub_district_id = $_POST['sub_district_id'] ?? null;
    $map_url = $_POST['map_url'] ?? '';
    $status = $_POST['status'] ?? 'E';
    
    $selected_facilities = $_POST['facilities'] ?? [];
    $selected_landmarks = $_POST['landmarks'] ?? [];

    $conn->begin_transaction();
    try {
        if ($mode === 'Add') {
            // --- INSERT ---
            $sql = "INSERT INTO RENT_PLACE (name, price, size, room_qty, toilet_qty, description, type, user_id, address, province_id, district_id, sub_district_id, map_url, status, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddiisssiiisssss", $name, $price, $size, $room_qty, $toilet_qty, $description, $type, $admin_id, $address, $province_id, $district_id, $sub_district_id, $map_url, $status, $current_user_name, $current_user_name);
            $stmt->execute();
            $property_id = $conn->insert_id; // เอา ID ของสินทรัพย์ที่เพิ่งสร้าง
            $_SESSION['message'] = "เพิ่มสินทรัพย์ใหม่สำเร็จแล้ว";

        } else {
            // --- UPDATE ---
            $sql = "UPDATE RENT_PLACE SET name=?, price=?, size=?, room_qty=?, toilet_qty=?, description=?, type=?, address=?, province_id=?, district_id=?, sub_district_id=?, map_url=?, status=?, update_user=?, update_datetime=NOW() WHERE id=? AND user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddiisssiiisssii", $name, $price, $size, $room_qty, $toilet_qty, $description, $type, $address, $province_id, $district_id, $sub_district_id, $map_url, $status, $current_user_name, $property_id, $admin_id);
            $stmt->execute();
            $_SESSION['message'] = "อัปเดตข้อมูลสินทรัพย์สำเร็จแล้ว";
        }

        // --- จัดการสิ่งอำนวยความสะดวก (ลบของเก่าทั้งหมด แล้วเพิ่มใหม่) ---
        $stmt_del_fac = $conn->prepare("DELETE FROM RENT_PLACE_FACILITIES WHERE rent_place_id = ?");
        $stmt_del_fac->bind_param("i", $property_id);
        $stmt_del_fac->execute();

        if (!empty($selected_facilities)) {
            $sql_fac = "INSERT INTO RENT_PLACE_FACILITIES (rent_place_id, rent_facilities_id, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
            $stmt_fac = $conn->prepare($sql_fac);
            foreach ($selected_facilities as $facility_id) {
                // [แก้ไขแล้ว] แก้ไข type string จาก "iisss" เป็น "iiss"
                $stmt_fac->bind_param("iiss", $property_id, $facility_id, $current_user_name, $current_user_name);
                $stmt_fac->execute();
            }
        }

        // --- จัดการสถานที่สำคัญ (ลบของเก่าทั้งหมด แล้วเพิ่มใหม่) ---
        $stmt_del_land = $conn->prepare("DELETE FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = ?");
        $stmt_del_land->bind_param("i", $property_id);
        $stmt_del_land->execute();

        if (!empty($selected_landmarks)) {
            $sql_land = "INSERT INTO RENT_PLACE_LANDMARKS (rent_place_id, rent_landmark_id, distance, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, ?, NOW(), ?, NOW())";
            $stmt_land = $conn->prepare($sql_land);
            foreach ($selected_landmarks as $landmark) {
                $landmark_id = $landmark['id'];
                $distance = $landmark['distance'];
                if (!empty($landmark_id) && is_numeric($distance)) {
                    $stmt_land->bind_param("iidss", $property_id, $landmark_id, $distance, $current_user_name, $current_user_name);
                    $stmt_land->execute();
                }
            }
        }

        $conn->commit();
        header("Location: admin_properties.php");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $exception->getMessage();
        header("Location: manage_property.php" . ($property_id ? "?id=$property_id" : ""));
        exit();
    }
}

// --- ดึงข้อมูลสำหรับ Dropdowns ---
$provinces = $conn->query("SELECT id, name FROM RENT_PROVINCE ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$districts = $conn->query("SELECT id, province_id, name FROM RENT_DISTRICT ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$sub_districts = $conn->query("SELECT id, district_id, name, postal_code FROM RENT_SUB_DISTRICT ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$facilities = $conn->query("SELECT id, name FROM RENT_FACILITIES ORDER BY type, name ASC")->fetch_all(MYSQLI_ASSOC);
$landmarks = $conn->query("SELECT id, name FROM RENT_LANDMARKS ORDER BY type, name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        main { background-color: #f4f7f6; }
        .form-card { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .card-header { font-size: 1.2rem; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>

<main id="main">
    <section class="container py-5">
        <div class="section-title mb-4">
            <h2><?php echo $mode === 'Add' ? 'เพิ่มสินทรัพย์ใหม่' : 'แก้ไขสินทรัพย์'; ?></h2>
            <p>กรุณากรอกข้อมูลสินทรัพย์ให้ครบถ้วน</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form class="form-card" method="POST" action="manage_property.php<?php if ($property_id) echo "?id=$property_id"; ?>">
            <!-- ข้อมูลหลัก -->
            <div class="card-header">ข้อมูลหลัก</div>
            <div class="row g-3 mb-4">
                <div class="col-md-8"><label for="name" class="form-label">ชื่อสินทรัพย์</label><input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($property_data['name'] ?? ''); ?>" required></div>
                <div class="col-md-4"><label for="type" class="form-label">ประเภท</label><select id="type" name="type" class="form-select" required><option value="H" <?php if(($property_data['type'] ?? '') == 'H') echo 'selected'; ?>>บ้านเดี่ยว</option><option value="C" <?php if(($property_data['type'] ?? '') == 'C') echo 'selected'; ?>>คอนโด</option><option value="A" <?php if(($property_data['type'] ?? '') == 'A') echo 'selected'; ?>>อพาร์ตเมนต์</option><option value="V" <?php if(($property_data['type'] ?? '') == 'V') echo 'selected'; ?>>วิลล่า</option><option value="T" <?php if(($property_data['type'] ?? '') == 'T') echo 'selected'; ?>>ทาวน์เฮาส์</option><option value="L" <?php if(($property_data['type'] ?? '') == 'L') echo 'selected'; ?>>ที่ดิน</option></select></div>
                <div class="col-12"><label for="description" class="form-label">คำอธิบาย</label><textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($property_data['description'] ?? ''); ?></textarea></div>
                <div class="col-md-3"><label for="price" class="form-label">ราคา (บาท/เดือน)</label><input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($property_data['price'] ?? ''); ?>" required></div>
                <div class="col-md-3"><label for="size" class="form-label">พื้นที่ใช้สอย (ตร.ม.)</label><input type="number" step="0.01" class="form-control" id="size" name="size" value="<?php echo htmlspecialchars($property_data['size'] ?? ''); ?>" required></div>
                <div class="col-md-3"><label for="room_qty" class="form-label">จำนวนห้องนอน</label><input type="number" class="form-control" id="room_qty" name="room_qty" value="<?php echo htmlspecialchars($property_data['room_qty'] ?? '0'); ?>"></div>
                <div class="col-md-3"><label for="toilet_qty" class="form-label">จำนวนห้องน้ำ</label><input type="number" class="form-control" id="toilet_qty" name="toilet_qty" value="<?php echo htmlspecialchars($property_data['toilet_qty'] ?? '0'); ?>"></div>
            </div>

            <!-- ที่อยู่ -->
            <div class="card-header">ที่อยู่และแผนที่</div>
            <div class="row g-3 mb-4">
                <div class="col-12"><label for="address" class="form-label">ที่อยู่</label><input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($property_data['address'] ?? ''); ?>" required></div>
                <div class="col-md-4"><label for="province_id" class="form-label">จังหวัด</label><select id="province_id" name="province_id" class="form-select" required><option value="">-- เลือกจังหวัด --</option><?php foreach($provinces as $p): ?><option value="<?php echo $p['id']; ?>" <?php if(($property_data['province_id'] ?? '') == $p['id']) echo 'selected'; ?>><?php echo $p['name']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label for="district_id" class="form-label">อำเภอ/เขต</label><select id="district_id" name="district_id" class="form-select" required><option value="">-- เลือกอำเภอ --</option><?php foreach($districts as $d): ?><option value="<?php echo $d['id']; ?>" data-province="<?php echo $d['province_id']; ?>" <?php if(($property_data['district_id'] ?? '') == $d['id']) echo 'selected'; ?>><?php echo $d['name']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label for="sub_district_id" class="form-label">ตำบล/แขวง</label><select id="sub_district_id" name="sub_district_id" class="form-select" required><option value="">-- เลือกตำบล --</option><?php foreach($sub_districts as $sd): ?><option value="<?php echo $sd['id']; ?>" data-district="<?php echo $sd['district_id']; ?>" <?php if(($property_data['sub_district_id'] ?? '') == $sd['id']) echo 'selected'; ?>><?php echo $sd['name']; ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><label for="map_url" class="form-label">Google Maps URL</label><input type="url" class="form-control" id="map_url" name="map_url" value="<?php echo htmlspecialchars($property_data['map_url'] ?? ''); ?>"></div>
            </div>

            <!-- สิ่งอำนวยความสะดวก -->
            <div class="card-header">สิ่งอำนวยความสะดวก</div>
            <div class="row g-3 mb-4">
                <div class="col-12"><select id="facilities" name="facilities[]" class="form-select" multiple="multiple"><?php foreach($facilities as $f): ?><option value="<?php echo $f['id']; ?>" <?php if(in_array($f['id'], $property_facilities)) echo 'selected'; ?>><?php echo $f['name']; ?></option><?php endforeach; ?></select></div>
            </div>

            <!-- สถานที่สำคัญใกล้เคียง -->
            <div class="card-header">สถานที่สำคัญใกล้เคียง</div>
            <div id="landmarks-container">
                <?php if(empty($property_landmarks)): ?>
                <div class="row g-3 mb-2 landmark-row"><div class="col-md-6"><select name="landmarks[0][id]" class="form-select landmark-select"><option value="">-- เลือกสถานที่ --</option><?php foreach($landmarks as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo $l['name']; ?></option><?php endforeach; ?></select></div><div class="col-md-4"><input type="number" step="0.1" name="landmarks[0][distance]" class="form-control" placeholder="ระยะทาง (กม.)"></div><div class="col-md-2"><button type="button" class="btn btn-danger w-100 remove-landmark-btn">ลบ</button></div></div>
                <?php else: ?>
                    <?php foreach($property_landmarks as $i => $pl): ?>
                    <div class="row g-3 mb-2 landmark-row"><div class="col-md-6"><select name="landmarks[<?php echo $i; ?>][id]" class="form-select landmark-select"><option value="">-- เลือกสถานที่ --</option><?php foreach($landmarks as $l): ?><option value="<?php echo $l['id']; ?>" <?php if($pl['rent_landmark_id'] == $l['id']) echo 'selected'; ?>><?php echo $l['name']; ?></option><?php endforeach; ?></select></div><div class="col-md-4"><input type="number" step="0.1" name="landmarks[<?php echo $i; ?>][distance]" class="form-control" placeholder="ระยะทาง (กม.)" value="<?php echo htmlspecialchars($pl['distance']); ?>"></div><div class="col-md-2"><button type="button" class="btn btn-danger w-100 remove-landmark-btn">ลบ</button></div></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-landmark-btn" class="btn btn-outline-primary mt-2"><i class="bi bi-plus"></i> เพิ่มสถานที่</button>

            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="status" class="form-label">สถานะ</label><select id="status" name="status" class="form-select"><option value="E" <?php if(($property_data['status'] ?? 'E') == 'E') echo 'selected'; ?>>ว่าง</option><option value="F" <?php if(($property_data['status'] ?? '') == 'F') echo 'selected'; ?>>เต็ม</option></select></div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="admin_properties.php" class="btn btn-secondary me-2">ยกเลิก</a>
                <button type="submit" class="btn btn-primary"><?php echo $mode === 'Add' ? 'บันทึก' : 'อัปเดต'; ?></button>
            </div>
        </form>
    </section>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // --- Initialize Select2 ---
    $('#facilities').select2({ theme: 'bootstrap-5', placeholder: 'เลือกสิ่งอำนวยความสะดวก' });
    $('.landmark-select').select2({ theme: 'bootstrap-5' });

    // --- Cascading Dropdowns for Location ---
    const allDistricts = <?php echo json_encode($districts); ?>;
    const allSubDistricts = <?php echo json_encode($sub_districts); ?>;

    $('#province_id').on('change', function() {
        const provinceId = $(this).val();
        const $districtSelect = $('#district_id');
        const $subDistrictSelect = $('#sub_district_id');
        
        $districtSelect.html('<option value="">-- เลือกอำเภอ --</option>');
        $subDistrictSelect.html('<option value="">-- เลือกตำบล --</option>');

        if (provinceId) {
            const filteredDistricts = allDistricts.filter(d => d.province_id == provinceId);
            filteredDistricts.forEach(d => {
                $districtSelect.append(`<option value="${d.id}">${d.name}</option>`);
            });
        }
    });

    $('#district_id').on('change', function() {
        const districtId = $(this).val();
        const $subDistrictSelect = $('#sub_district_id');

        $subDistrictSelect.html('<option value="">-- เลือกตำบล --</option>');

        if (districtId) {
            const filteredSubDistricts = allSubDistricts.filter(sd => sd.district_id == districtId);
            filteredSubDistricts.forEach(sd => {
                $subDistrictSelect.append(`<option value="${sd.id}">${sd.name}</option>`);
            });
        }
    });

    // --- Dynamic Landmarks ---
    let landmarkIndex = <?php echo count($property_landmarks); ?>;
    $('#add-landmark-btn').on('click', function() {
        landmarkIndex++;
        const landmarkRow = `
            <div class="row g-3 mb-2 landmark-row">
                <div class="col-md-6">
                    <select name="landmarks[${landmarkIndex}][id]" class="form-select landmark-select">
                        <option value="">-- เลือกสถานที่ --</option>
                        <?php foreach($landmarks as $l): ?>
                        <option value="<?php echo $l['id']; ?>"><?php echo $l['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.1" name="landmarks[${landmarkIndex}][distance]" class="form-control" placeholder="ระยะทาง (กม.)">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100 remove-landmark-btn">ลบ</button>
                </div>
            </div>
        `;
        $('#landmarks-container').append(landmarkRow);
        // Initialize select2 for the new row
        $(`select[name="landmarks[${landmarkIndex}][id]"]`).select2({ theme: 'bootstrap-5' });
    });

    // Remove landmark row
    $('#landmarks-container').on('click', '.remove-landmark-btn', function() {
        $(this).closest('.landmark-row').remove();
    });
});
</script>

<?php 
include 'footer.php'; 
ob_end_flush();
?>
