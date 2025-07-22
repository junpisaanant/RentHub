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

// --- จัดการการลบรูปภาพ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $place_attach_id = $_POST['place_attach_id'];
    
    $q = "SELECT rpa.attach_id, rpa.rent_place_id, rpa.type, rf.name as filename FROM RENT_PLACE_ATTACH rpa JOIN RENT_FILE rf ON rpa.attach_id = rf.attach_id WHERE rpa.id = ? AND rpa.rent_place_id IN (SELECT id FROM RENT_PLACE WHERE user_id = ?)";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("ii", $place_attach_id, $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $attach_id_to_delete = $data['attach_id'];
        
        $folder_name_map = ['B' => 'bedroom', 'T' => 'toilet', 'O' => 'other', 'P' => 'plan', 'V' => 'video'];
        $folder_name = $folder_name_map[$data['type']] ?? 'misc';
        $file_to_delete = "assets/rent_place/" . $data['rent_place_id'] . "/" . $folder_name . "/" . $data['filename'];

        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM RENT_PLACE_ATTACH WHERE id = $place_attach_id");
            $conn->query("DELETE FROM RENT_FILE WHERE attach_id = $attach_id_to_delete");
            $conn->query("DELETE FROM RENT_ATTACH WHERE id = $attach_id_to_delete");
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
            $conn->commit();
            $_SESSION['message'] = "ลบรูปภาพสำเร็จ";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบรูปภาพ";
        }
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// --- จัดการการตั้งค่าภาพปกใน RENT_FILE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_cover'])) {
    $place_attach_id = $_POST['place_attach_id'];
    $property_id_for_cover = $_POST['property_id'];

    $conn->begin_transaction();
    try {
        $stmt_reset = $conn->prepare("
            UPDATE RENT_FILE rf
            JOIN RENT_PLACE_ATTACH rpa ON rf.attach_id = rpa.attach_id
            SET rf.cover_flag = 'N'
            WHERE rpa.rent_place_id = ?
        ");
        $stmt_reset->bind_param("i", $property_id_for_cover);
        $stmt_reset->execute();

        $stmt_set = $conn->prepare("
            UPDATE RENT_FILE SET cover_flag = 'Y' 
            WHERE attach_id = (SELECT attach_id FROM RENT_PLACE_ATTACH WHERE id = ?)
        ");
        $stmt_set->bind_param("i", $place_attach_id);
        $stmt_set->execute();

        $conn->commit();
        $_SESSION['message'] = "ตั้งค่าภาพปกสำเร็จ";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการตั้งค่าภาพปก";
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}


// --- กำหนดโหมด: 'Add' หรือ 'Edit' ---
$mode = 'Add';
$property_id = null;
$property_data = [];
$property_facilities = [];
$property_landmarks = [];
$property_images = [];
$map_image_path = null;
$video_file_path = null; // เพิ่มตัวแปรสำหรับเก็บ path วิดีโอ

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $mode = 'Edit';
    $property_id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM RENT_PLACE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $property_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $property_data = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "ไม่พบสินทรัพย์ที่ต้องการแก้ไข หรือคุณไม่มีสิทธิ์";
        header("Location: admin_properties.php");
        exit();
    }

    $stmt_fac = $conn->prepare("SELECT rent_facilities_id FROM RENT_PLACE_FACILITIES WHERE rent_place_id = ?");
    $stmt_fac->bind_param("i", $property_id);
    $stmt_fac->execute();
    $result_fac = $stmt_fac->get_result();
    while($row = $result_fac->fetch_assoc()) {
        $property_facilities[] = $row['rent_facilities_id'];
    }

    $stmt_land = $conn->prepare("SELECT rent_landmark_id, distance FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = ?");
    $stmt_land->bind_param("i", $property_id);
    $stmt_land->execute();
    $result_land = $stmt_land->get_result();
    while($row = $result_land->fetch_assoc()) {
        $property_landmarks[] = $row;
    }

    // ดึง cover_flag จาก RENT_FILE
    $stmt_img = $conn->prepare("SELECT rpa.id, rpa.type, rf.cover_flag, rf.name as filename, rf.id as file_id FROM RENT_PLACE_ATTACH rpa JOIN RENT_FILE rf ON rpa.attach_id = rf.attach_id WHERE rpa.rent_place_id = ? AND rpa.type != 'V' ORDER BY rf.cover_flag DESC, rpa.id ASC");
    $stmt_img->bind_param("i", $property_id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    while($row = $result_img->fetch_assoc()) {
        $property_images[] = $row;
    }

    // ดึงข้อมูลภาพแผนที่จาก RENT_FILE และ RENT_ATTACH
    if (!empty($property_data['attach_id'])) {
        $stmt_map = $conn->prepare("
            SELECT rf.name as filename, ra.NAME as foldername 
            FROM RENT_FILE rf 
            JOIN RENT_ATTACH ra ON rf.attach_id = ra.id 
            WHERE rf.attach_id = ? AND rf.type = 'M'
        ");
        $stmt_map->bind_param("i", $property_data['attach_id']);
        $stmt_map->execute();
        $result_map = $stmt_map->get_result();
        if ($result_map->num_rows > 0) {
            $map_data = $result_map->fetch_assoc();
            $map_image_path = "assets/rent_place/" . $property_id . "/" . $map_data['foldername'] . "/" . $map_data['filename'];
        }
    }
    
    // [เพิ่มใหม่] ดึงข้อมูลวิดีโอ
    if (!empty($property_data['video_attach_id'])) {
        $stmt_video = $conn->prepare("
            SELECT rf.name as filename, ra.NAME as foldername 
            FROM RENT_FILE rf 
            JOIN RENT_ATTACH ra ON rf.attach_id = ra.id 
            WHERE rf.attach_id = ? AND rf.type = 'V'
        ");
        $stmt_video->bind_param("i", $property_data['video_attach_id']);
        $stmt_video->execute();
        $result_video = $stmt_video->get_result();
        if ($result_video->num_rows > 0) {
            $video_data = $result_video->fetch_assoc();
            $video_file_path = "assets/rent_place/" . $property_id . "/" . $video_data['foldername'] . "/" . $video_data['filename'];
        }
    }
}

// --- จัดการการส่งฟอร์ม (เพิ่ม/แก้ไข) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_property'])) {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? 0;
    $room_qty = $_POST['room_qty'] ?? 0;
    $toilet_qty = $_POST['toilet_qty'] ?? 0;
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $address = $_POST['address'] ?? '';
    $province_id = !empty($_POST['province_id']) ? $_POST['province_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $sub_district_id = !empty($_POST['sub_district_id']) ? $_POST['sub_district_id'] : null;
    $map_url = $_POST['map_url'] ?? '';
    $status = $_POST['status'] ?? 'E';
    
    $selected_facilities = $_POST['facilities'] ?? [];
    $selected_landmarks = $_POST['landmarks'] ?? [];

    $conn->begin_transaction();
    try {
        if ($mode === 'Add') {
            $sql = "INSERT INTO RENT_PLACE (name, price, size, room_qty, toilet_qty, description, type, user_id, address, province_id, district_id, sub_district_id, map_url, status, create_user, create_datetime, update_user, update_datetime, attach_id, video_attach_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NULL, NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddiissisiiissss", $name, $price, $size, $room_qty, $toilet_qty, $description, $type, $admin_id, $address, $province_id, $district_id, $sub_district_id, $map_url, $status, $current_user_name, $current_user_name);
            $stmt->execute();
            $property_id = $conn->insert_id;
        } else {
            $sql = "UPDATE RENT_PLACE SET name=?, price=?, size=?, room_qty=?, toilet_qty=?, description=?, type=?, address=?, province_id=?, district_id=?, sub_district_id=?, map_url=?, status=?, update_user=?, update_datetime=NOW() WHERE id=? AND user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddiisssiiisssii", $name, $price, $size, $room_qty, $toilet_qty, $description, $type, $address, $province_id, $district_id, $sub_district_id, $map_url, $status, $current_user_name, $property_id, $admin_id);
            $stmt->execute();
        }

        // Logic การบันทึกไฟล์แผนที่
        if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] == 0) {
            // ... (โค้ดส่วนนี้คงเดิม) ...
        }

        // [เพิ่มใหม่] Logic การบันทึกไฟล์วิดีโอ
        if (isset($_FILES['property_video']) && $_FILES['property_video']['error'] == 0) {
            $old_video_attach_id = null;
            if ($mode === 'Edit' && !empty($property_data['video_attach_id'])) {
                $old_video_attach_id = $property_data['video_attach_id'];
                $stmt_old_video = $conn->prepare("SELECT rf.name as filename, ra.NAME as foldername FROM RENT_FILE rf JOIN RENT_ATTACH ra ON rf.attach_id = ra.id WHERE rf.attach_id = ? AND rf.type = 'V'");
                $stmt_old_video->bind_param("i", $old_video_attach_id);
                $stmt_old_video->execute();
                $result_old_video = $stmt_old_video->get_result();
                if ($result_old_video->num_rows > 0) {
                    $old_video_data = $result_old_video->fetch_assoc();
                    $old_video_file_to_delete = "assets/rent_place/" . $property_id . "/" . $old_video_data['foldername'] . "/" . $old_video_data['filename'];
                }
            }

            $video_file = $_FILES['property_video'];
            $video_folder_name = 'video';
            $video_target_dir = "assets/rent_place/$property_id/$video_folder_name/";
            if (!is_dir($video_target_dir)) mkdir($video_target_dir, 0755, true);

            $video_file_size = $video_file['size'];
            $video_file_extension = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
            $video_new_filename = "video_" . time() . "_" . uniqid() . "." . $video_file_extension;
            
            if (move_uploaded_file($video_file['tmp_name'], $video_target_dir . $video_new_filename)) {
                $video_attach_sql = "INSERT INTO RENT_ATTACH (NAME, SIZE, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
                $stmt_video_attach = $conn->prepare($video_attach_sql);
                $stmt_video_attach->bind_param("siss", $video_folder_name, $video_file_size, $current_user_name, $current_user_name);
                $stmt_video_attach->execute();
                $new_video_attach_id = $conn->insert_id;

                $video_file_sql = "INSERT INTO RENT_FILE (attach_id, type, name, size, create_user, create_datetime, update_user, update_datetime) VALUES (?, 'V', ?, ?, ?, NOW(), ?, NOW())";
                $stmt_video_file = $conn->prepare($video_file_sql);
                $stmt_video_file->bind_param("isiss", $new_video_attach_id, $video_new_filename, $video_file_size, $current_user_name, $current_user_name);
                $stmt_video_file->execute();

                $update_video_id_sql = "UPDATE RENT_PLACE SET video_attach_id = ? WHERE id = ?";
                $stmt_update_video_id = $conn->prepare($update_video_id_sql);
                $stmt_update_video_id->bind_param("ii", $new_video_attach_id, $property_id);
                $stmt_update_video_id->execute();

                if ($old_video_attach_id) {
                    if (isset($old_video_file_to_delete) && file_exists($old_video_file_to_delete)) {
                        unlink($old_video_file_to_delete);
                    }
                    $conn->query("DELETE FROM RENT_FILE WHERE attach_id = $old_video_attach_id");
                    $conn->query("DELETE FROM RENT_ATTACH WHERE id = $old_video_attach_id");
                }
            }
        }

        // Logic การบันทึกไฟล์รูปภาพอื่นๆ
        $first_rent_file_id = null;
        if (isset($_FILES['images'])) {
            $images = $_FILES['images'];
            foreach ($images['name'] as $type => $files) {
                foreach ($files as $key => $filename) {
                    if (isset($images['error'][$type][$key]) && $images['error'][$type][$key] == 0) {
                        
                        $folder_name_map = ['B' => 'bedroom', 'T' => 'toilet', 'O' => 'other', 'P' => 'plan'];
                        $folder_name = $folder_name_map[$type] ?? 'misc';
                        $target_dir = "assets/rent_place/$property_id/$folder_name/"; 

                        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                        
                        $file_size = $images['size'][$type][$key];
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $new_filename_on_server = time() . '_' . uniqid() . "." . $file_extension;

                        if (move_uploaded_file($images['tmp_name'][$type][$key], $target_dir . $new_filename_on_server)) {
                            
                            $attach_sql = "INSERT INTO RENT_ATTACH (NAME, SIZE, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
                            $stmt_attach = $conn->prepare($attach_sql);
                            $stmt_attach->bind_param("siss", $folder_name, $file_size, $current_user_name, $current_user_name);
                            $stmt_attach->execute();
                            $attach_id = $conn->insert_id;

                            $file_sql = "INSERT INTO RENT_FILE (attach_id, type, cover_flag, name, size, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, 'N', ?, ?, ?, NOW(), ?, NOW())";
                            $stmt_file = $conn->prepare($file_sql);
                            $stmt_file->bind_param("ississ", $attach_id, $type, $new_filename_on_server, $file_size, $current_user_name, $current_user_name);
                            $stmt_file->execute();
                            $rent_file_id = $conn->insert_id;

                            $place_attach_sql = "INSERT INTO RENT_PLACE_ATTACH (rent_place_id, type, attach_id, name, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())";
                            $stmt_pa = $conn->prepare($place_attach_sql);
                            $stmt_pa->bind_param("sissss", $property_id, $type, $attach_id, $folder_name, $current_user_name, $current_user_name);
                            $stmt_pa->execute();
                            
                            if ($first_rent_file_id === null) {
                                $first_rent_file_id = $rent_file_id;
                            }
                        }
                    }
                }
            }
        }

        if ($first_rent_file_id !== null) {
            $stmt_check_cover = $conn->prepare("SELECT rf.id FROM RENT_FILE rf JOIN RENT_PLACE_ATTACH rpa ON rf.attach_id = rpa.attach_id WHERE rpa.rent_place_id = ? AND rf.cover_flag = 'Y' LIMIT 1");
            $stmt_check_cover->bind_param("i", $property_id);
            $stmt_check_cover->execute();
            $result_cover = $stmt_check_cover->get_result();
            if ($result_cover->num_rows === 0) {
                $stmt_set_cover = $conn->prepare("UPDATE RENT_FILE SET cover_flag = 'Y' WHERE id = ?");
                $stmt_set_cover->bind_param("i", $first_rent_file_id);
                $stmt_set_cover->execute();
            }
        }

        // Logic การบันทึก Facilities และ Landmarks (คงเดิม)
        $stmt_del_fac = $conn->prepare("DELETE FROM RENT_PLACE_FACILITIES WHERE rent_place_id = ?");
        $stmt_del_fac->bind_param("i", $property_id);
        $stmt_del_fac->execute();

        if (!empty($selected_facilities)) {
            $sql_fac = "INSERT INTO RENT_PLACE_FACILITIES (rent_place_id, rent_facilities_id, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, NOW(), ?, NOW())";
            $stmt_fac = $conn->prepare($sql_fac);
            foreach ($selected_facilities as $facility_id) {
                $stmt_fac->bind_param("iiss", $property_id, $facility_id, $current_user_name, $current_user_name);
                $stmt_fac->execute();
            }
        }

        $stmt_del_land = $conn->prepare("DELETE FROM RENT_PLACE_LANDMARKS WHERE rent_place_id = ?");
        $stmt_del_land->bind_param("i", $property_id);
        $stmt_del_land->execute();

        if (!empty($selected_landmarks)) {
            $sql_land = "INSERT INTO RENT_PLACE_LANDMARKS (rent_place_id, rent_landmark_id, distance, create_user, create_datetime, update_user, update_datetime) VALUES (?, ?, ?, ?, NOW(), ?, NOW())";
            $stmt_land = $conn->prepare($sql_land);
            foreach ($selected_landmarks as $landmark) {
                if (isset($landmark['id'], $landmark['distance']) && !empty($landmark['id']) && is_numeric($landmark['distance'])) {
                    $stmt_land->bind_param("iidss", $property_id, $landmark['id'], $landmark['distance'], $current_user_name, $current_user_name);
                    $stmt_land->execute();
                }
            }
        }
        
        $conn->commit();
        if ($mode === 'Add') {
            $_SESSION['message'] = "เพิ่มสินทรัพย์ใหม่สำเร็จแล้ว";
        } else {
            $_SESSION['message'] = "อัปเดตข้อมูลสินทรัพย์สำเร็จแล้ว";
        }
        header("Location: admin_properties.php");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        // แสดง error แบบละเอียดเพื่อช่วยในการ debug
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
        .image-gallery { display: flex; flex-wrap: wrap; gap: 1rem; }
        .img-thumbnail-wrapper { position: relative; width: 150px; height: 150px; }
        .img-thumbnail-wrapper img, .img-thumbnail-wrapper video { width: 100%; height: 100%; object-fit: cover; }
        .img-actions { position: absolute; top: 5px; right: 5px; display: flex; flex-direction: column; gap: 5px; }
        .img-actions .btn { padding: 0.2rem 0.4rem; font-size: 0.75rem; }
        .cover-badge { position: absolute; top: 5px; left: 5px; }
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
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success" role="alert"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <form class="form-card" method="POST" action="manage_property.php<?php if ($property_id) echo "?id=$property_id"; ?>" enctype="multipart/form-data">
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
                <div class="col-md-4"><label for="district_id" class="form-label">อำเภอ/เขต</label><select id="district_id" name="district_id" class="form-select" required><option value="">-- เลือกอำเภอ --</option></select></div>
                <div class="col-md-4"><label for="sub_district_id" class="form-label">ตำบล/แขวง</label><select id="sub_district_id" name="sub_district_id" class="form-select" required><option value="">-- เลือกตำบล --</option></select></div>
                <div class="col-12"><label for="map_url" class="form-label">Google Maps URL</label><input type="url" class="form-control" id="map_url" name="map_url" value="<?php echo htmlspecialchars($property_data['map_url'] ?? ''); ?>"></div>
                
                <div class="col-12">
                    <label for="map_image" class="form-label">อัปโหลดภาพแผนที่</label>
                    <?php if ($mode === 'Edit' && $map_image_path && file_exists($map_image_path)): ?>
                        <div class="mb-2">
                            <img src="<?php echo htmlspecialchars($map_image_path); ?>" alt="Map" class="img-thumbnail" style="max-width: 200px;">
                            <p class="form-text">อัปโหลดไฟล์ใหม่เพื่อแทนที่ภาพปัจจุบัน</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="map_image" name="map_image" accept="image/*">
                </div>
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
            
            <!-- รูปภาพและวิดีโอประกอบ -->
            <div class="card-header mt-4">รูปภาพและวิดีโอประกอบ</div>
            <?php if($mode === 'Edit' && !empty($property_images)): ?>
            <div class="image-gallery my-3">
                <?php foreach($property_images as $img): ?>
                <?php
                    $folder_name_map = ['B' => 'bedroom', 'T' => 'toilet', 'O' => 'other', 'P' => 'plan'];
                    $folder_name = $folder_name_map[$img['type']] ?? 'misc';
                    $full_path = "assets/rent_place/" . $property_id . "/" . $folder_name . "/" . $img['filename'];
                ?>
                <div class="img-thumbnail-wrapper">
                    <img src="<?php echo htmlspecialchars($full_path); ?>" class="img-thumbnail">
                    <?php if($img['cover_flag'] == 'Y'): ?>
                        <span class="badge bg-success cover-badge">ภาพปก</span>
                    <?php endif; ?>
                    <div class="img-actions">
                        <form method="POST" class="d-inline" onsubmit="return confirm('ต้องการตั้งเป็นภาพปก?');">
                            <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                            <input type="hidden" name="place_attach_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" name="set_cover" class="btn btn-sm btn-info" <?php if($img['cover_flag'] == 'Y') echo 'disabled'; ?>>
                                <i class="bi bi-star-fill"></i>
                            </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('ต้องการลบรูปภาพนี้?');">
                            <input type="hidden" name="place_attach_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" name="delete_image" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-md-6"><label for="img_bedroom" class="form-label">รูปห้องนอน</label><input type="file" name="images[B][]" class="form-control" multiple accept="image/*"></div>
                <div class="col-md-6"><label for="img_toilet" class="form-label">รูปห้องน้ำ</label><input type="file" name="images[T][]" class="form-control" multiple accept="image/*"></div>
                <div class="col-md-6"><label for="img_other" class="form-label">รูปอื่นๆ</label><input type="file" name="images[O][]" class="form-control" multiple accept="image/*"></div>
                <div class="col-md-6"><label for="img_plan" class="form-label">รูปผัง</label><input type="file" name="images[P][]" class="form-control" multiple accept="image/*"></div>
                
                <!-- [เพิ่มใหม่] ช่องอัปโหลดวิดีโอ -->
                <div class="col-12 mt-3">
                    <label for="property_video" class="form-label">วิดีโอแนะนำ (ไฟล์ MP4, AVI, MOV)</label>
                    <?php if ($mode === 'Edit' && $video_file_path && file_exists($video_file_path)): ?>
                        <div class="my-2">
                            <video width="320" height="240" controls>
                                <source src="<?php echo htmlspecialchars($video_file_path); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <p class="form-text">วิดีโอปัจจุบัน (อัปโหลดไฟล์ใหม่เพื่อแทนที่)</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="property_video" name="property_video" accept="video/*">
                </div>
            </div>


            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="status" class="form-label">สถานะ</label><select id="status" name="status" class="form-select"><option value="E" <?php if(($property_data['status'] ?? 'E') == 'E') echo 'selected'; ?>>ว่าง</option><option value="F" <?php if(($property_data['status'] ?? '') == 'F') echo 'selected'; ?>>เต็ม</option></select></div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="admin_properties.php" class="btn btn-secondary me-2">ยกเลิก</a>
                <button type="submit" name="save_property" class="btn btn-primary"><?php echo $mode === 'Add' ? 'บันทึก' : 'อัปเดต'; ?></button>
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

    function filterDistricts() {
        const provinceId = $('#province_id').val();
        const $districtSelect = $('#district_id');
        const currentDistrictId = '<?php echo $property_data['district_id'] ?? ''; ?>';
        
        $districtSelect.html('<option value="">-- เลือกอำเภอ --</option>');
        $('#sub_district_id').html('<option value="">-- เลือกตำบล --</option>');

        if (provinceId) {
            const filteredDistricts = allDistricts.filter(d => d.province_id == provinceId);
            filteredDistricts.forEach(d => {
                const selected = d.id == currentDistrictId ? 'selected' : '';
                $districtSelect.append(`<option value="${d.id}" ${selected}>${d.name}</option>`);
            });
        }
        $districtSelect.trigger('change');
    }

    function filterSubDistricts() {
        const districtId = $('#district_id').val();
        const $subDistrictSelect = $('#sub_district_id');
        const currentSubDistrictId = '<?php echo $property_data['sub_district_id'] ?? ''; ?>';

        $subDistrictSelect.html('<option value="">-- เลือกตำบล --</option>');

        if (districtId) {
            const filteredSubDistricts = allSubDistricts.filter(sd => sd.district_id == districtId);
            filteredSubDistricts.forEach(sd => {
                const selected = sd.id == currentSubDistrictId ? 'selected' : '';
                $subDistrictSelect.append(`<option value="${sd.id}" ${selected}>${sd.name}</option>`);
            });
        }
    }

    $('#province_id').on('change', filterDistricts);
    $('#district_id').on('change', filterSubDistricts);
    
    if('<?php echo $mode; ?>' === 'Edit') {
        filterDistricts();
    }


    // --- Dynamic Landmarks ---
    let landmarkIndex = <?php echo count($property_landmarks); ?>;
    $('#add-landmark-btn').on('click', function() {
        landmarkIndex++;
        const landmarkRow = `
            <div class="row g-3 mb-2 landmark-row">
                <div class="col-md-6">
                    <select name="landmarks[${landmarkIndex}][id]" class="form-select landmark-select-new">
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
        $('.landmark-select-new').select2({ theme: 'bootstrap-5' }).removeClass('landmark-select-new');
    });

    $('#landmarks-container').on('click', '.remove-landmark-btn', function() {
        $(this).closest('.landmark-row').remove();
    });
});
</script>

<?php 
include 'footer.php'; 
ob_end_flush();
?>
