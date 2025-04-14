<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// รับค่าคำค้นหาจาก AJAX
$where = [];
$params = [];

// หากมีการส่ง searchTerm มา ให้ใช้เงื่อนไขแบบค้นหาทั่วไป
if (isset($_POST['searchTerm']) && $_POST['searchTerm'] !== '') {
    $where[] = "RP.name LIKE :searchTerm";
    $params[':searchTerm'] = '%' . $_POST['searchTerm'] . '%';
} else {
    // รับและตรวจสอบค่าจากตัวกรองทีละตัว
    //ประเภทอสังหา
    if (isset($_POST['type']) && $_POST['type'] !== '') {
        $where[] = "RP.type = :type";
        $params[':type'] = $_POST['type'];
    }
    //ราคาต่ำสุด
    if (isset($_POST['minPrice']) && $_POST['minPrice'] !== '') {
        $where[] = "RP.price >= :minPrice";
        $params[':minPrice'] = $_POST['minPrice'];
    }
    //ราคาสูงสุด
    if (isset($_POST['maxPrice']) && $_POST['maxPrice'] !== '') {
        $where[] = "RP.price <= :maxPrice";
        $params[':maxPrice'] = $_POST['maxPrice'];
    }
    //จำนวนห้องนอน
    if (isset($_POST['roomQty']) && $_POST['roomQty'] !== '') {
        $where[] = "RP.room_qty = :roomQty";
        $params[':roomQty'] = $_POST['roomQty'];
    }
    //ขนาดต่ำสุด
    if (isset($_POST['minSize']) && $_POST['minSize'] !== '') {
        $where[] = "RP.size >= :minSize";
        $params[':minSize'] = $_POST['minSize'];
    }
    //ขนาดสูงสุด
    if (isset($_POST['maxSize']) && $_POST['maxSize'] !== '') {
        $where[] = "RP.size <= :maxSize";
        $params[':maxSize'] = $_POST['maxSize'];
    }
    //ระยะห่างจากสถานีรถไฟฟ้า
    if (isset($_POST['distance']) && $_POST['distance'] !== '') {
        $where[] = "RPL.distance <= :distance";
        $params[':distance'] = $_POST['distance'];
    }
    //จำนวนห้องน้ำ
    if (isset($_POST['toiletQty']) && $_POST['toiletQty'] !== '') {
        $where[] = "RP.toilet_qty = :toiletQty";
        $params[':toiletQty'] = $_POST['toiletQty'];
    }
    //จุดเด่น
    if (isset($_POST['feature']) && $_POST['feature'] !== '') {
        // สมมุติว่า $_POST['feature'] เป็น string ที่มี id คั่นด้วย comma เช่น "3,5,7"
        // แล้วคุณต้องการค้นหาในตารางที่มีคอลัมน์ feature_id
        $featureIds = explode(',', $_POST['feature']);
        // ทำให้เป็นค่าจำนวนเต็ม
        $featureIds = array_map('intval', $featureIds);
        // สร้างเงื่อนไขที่ใช้ IN clause
        $inClause = implode(',', array_fill(0, count($featureIds), '?'));
        $where[] = "RF.type = 'P'";
        $where[] = "RPF.rent_facilities_id IN ($inClause)";
        // รวมค่าลงใน $params ด้วย
        $params = array_merge($params, $featureIds);
    }
    //สิ่งอำนวยความสะดวก
    if (isset($_POST['facility']) && $_POST['facility'] !== '') {
        // สมมุติว่า $_POST['facility'] เป็น string ที่มี id คั่นด้วย comma เช่น "3,5,7"
        // แล้วคุณต้องการค้นหาในตารางที่มีคอลัมน์ feature_id
        $facilityIds = explode(',', $_POST['facility']);
        // ทำให้เป็นค่าจำนวนเต็ม
        $facilityIds = array_map('intval', $facilityIds);
        // สร้างเงื่อนไขที่ใช้ IN clause
        $inClause = implode(',', array_fill(0, count($facilityIds), '?'));
        $where[] = "RF.type = 'F'";
        $where[] = "RPF.rent_facilities_id IN ($inClause)";
        // รวมค่าลงใน $params ด้วย
        $params = array_merge($params, $facilityIds);
    }
}

// สร้างคำสั่ง SQL เพื่อค้นหาข้อมูลจากตาราง RENT_PLACE ตามฟิลด์ name
$sql = "SELECT 
            RP.name AS rp_name, 
            P.name AS province_name, 
            D.name AS district_name, 
            SD.name AS sub_district_name, 
            RP.price, 
            RP.size, 
            RP.room_qty, 
            RP.toilet_qty, 
            CASE WHEN RL.type = 'M' THEN CONCAT(RL.name, ' (', RPL.distance, ' เมตร)') ELSE '' END AS near_rail, 
            CASE RP.type 
                WHEN 'H' THEN 'บ้านเดี่ยว'
                WHEN 'C' THEN 'คอนโด'
                WHEN 'A' THEN 'อพาร์ทเม้นท์'
                WHEN 'V' THEN 'วิลล่า'
                WHEN 'T' THEN 'ทาวน์เฮ้าส์'
                WHEN 'L' THEN 'ที่ดิน'
                ELSE RP.type
            END AS C, 
            RP.create_datetime 
        FROM RENT_PLACE RP 
        LEFT JOIN RENT_PROVINCE P ON (RP.province_id = P.id)
        LEFT JOIN RENT_DISTRICT D ON (RP.district_id = D.id)
        LEFT JOIN RENT_SUB_DISTRICT SD ON (RP.sub_district_id = SD.id)
        LEFT JOIN RENT_PLACE_LANDMARKS RPL ON (RPL.rent_place_id = RP.id)
        LEFT JOIN RENT_LANDMARKS RL ON (RPL.rent_landmark_id = RL.id)
        LEFT JOIN RENT_PLACE_FACILITIES RPF ON (RPF.rent_place_id = RP.id)
        LEFT JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RF.id)
        WHERE 1=1";

if (count($where) > 0) {
    $sql .= " AND " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die(json_encode(['error' => $conn->error]));
}

// Bind parameters ถ้ามี
if (!empty($params)) {
    // สร้าง array ของอ้างอิงเพื่อใช้กับ call_user_func_array
    $bind_names = [];
    $bind_names[] = $param_types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);
}

if(!$stmt->execute()){
    die(json_encode(['error' => $stmt->error]));
}

$result = $stmt->get_result();
$results = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($results, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>