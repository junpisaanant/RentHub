<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// รับค่าคำค้นหาจาก AJAX
$where = [];
$params = [];
$param_types = '';

// หากมีการส่ง searchTerm มา ให้ใช้เงื่อนไขแบบค้นหาทั่วไป
// หากมีการส่ง searchTerm มา ให้ค้นหาใน RP.name
if (isset($_POST['searchTerm']) && $_POST['searchTerm'] !== '') {
    $where[] = "RP.name LIKE ?";
    $params[] = '%' . $_POST['searchTerm'] . '%';
    $param_types .= 's';
} else {
    // ประเภทอสังหา
    if (isset($_POST['type']) && $_POST['type'] !== '') {
        $where[] = "RP.type = ?";
        $params[] = $_POST['type'];
        $param_types .= 's';
    }
    // ราคาต่ำสุด
    if (isset($_POST['minPrice']) && $_POST['minPrice'] !== '') {
        $where[] = "RP.price >= ?";
        $params[] = $_POST['minPrice'];
        $param_types .= 'd';
    }
    // ราคาสูงสุด
    if (isset($_POST['maxPrice']) && $_POST['maxPrice'] !== '') {
        $where[] = "RP.price <= ?";
        $params[] = $_POST['maxPrice'];
        $param_types .= 'd';
    }
    // จำนวนห้องนอน
    if (isset($_POST['roomQty']) && $_POST['roomQty'] !== '') {
        $where[] = "RP.room_qty = ?";
        $params[] = $_POST['roomQty'];
        $param_types .= 'i';
    }
    // ขนาดต่ำสุด
    if (isset($_POST['minSize']) && $_POST['minSize'] !== '') {
        $where[] = "RP.size >= ?";
        $params[] = $_POST['minSize'];
        $param_types .= 'd';
    }
    // ขนาดสูงสุด
    if (isset($_POST['maxSize']) && $_POST['maxSize'] !== '') {
        $where[] = "RP.size <= ?";
        $params[] = $_POST['maxSize'];
        $param_types .= 'd';
    }
    // ระยะห่างจากสถานีรถไฟฟ้า
    if (isset($_POST['distance']) && $_POST['distance'] !== '') {
        $where[] = "RPL.distance <= ?";
        $params[] = $_POST['distance'];
        $param_types .= 'd';
    }
    // จำนวนห้องน้ำ
    if (isset($_POST['toiletQty']) && $_POST['toiletQty'] !== '') {
        $where[] = "RP.toilet_qty = ?";
        $params[] = $_POST['toiletQty'];
        $param_types .= 'i';
    }
    // จุดเด่น
    if (isset($_POST['feature']) && $_POST['feature'] !== '') {
        $featureIds = explode(',', $_POST['feature']);
        $featureIds = array_filter(array_map('intval', $featureIds));
        if (!empty($featureIds)) {
            $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
            // สมมุติว่าในตาราง RENT_PLACE_FACILITIES คอลัมน์สำหรับจุดเด่นคือ rent_facilities_id
            $where[] = "RP.id IN (SELECT RPF.id 
                        FROM RENT_PLACE_FACILITIES RPF
                        INNER JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RF.id)
                        WHERE 1=1
                        AND RPF.rent_place_id = RP.id
                        AND RF.type = 'P'
                        AND RPF.rent_facilities_id IN ($placeholders)";
            foreach ($featureIds as $id) {
                $params[] = $id;
                $param_types .= 'i';
            }
        }
    }
    // สิ่งอำนวยความสะดวก
    if (isset($_POST['facility']) && $_POST['facility'] !== '') {
        $facilityIds = explode(',', $_POST['facility']);
        $facilityIds = array_filter(array_map('intval', $facilityIds));
        if (!empty($facilityIds)) {
            $placeholders = implode(',', array_fill(0, count($facilityIds), '?'));
            $where[] = "RP.id IN (SELECT RPF.id 
                        FROM RENT_PLACE_FACILITIES RPF
                        INNER JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RF.id)
                        WHERE 1=1
                        AND RPF.rent_place_id = RP.id
                        AND RF.type = 'F'
                        AND RPF.rent_facilities_id IN ($placeholders)";
            foreach ($facilityIds as $id) {
                $params[] = $id;
                $param_types .= 'i';
            }
        }
    }
}

// สร้างคำสั่ง SQL เพื่อค้นหาข้อมูลจากตาราง RENT_PLACE ตามฟิลด์ name
$sql = "SELECT 
        RP.id,
        RP.name AS rp_name, 
        P.name AS province_name, 
        D.name AS district_name, 
        SD.name AS sub_district_name, 
        RP.price, 
        RP.size, 
        RP.room_qty, 
        RP.toilet_qty, 
        (
            SELECT CONCAT(RL.name, ' (', (RPL.distance*1000), ' เมตร)')
            FROM rent_place_landmarks RPL
            INNER JOIN RENT_LANDMARKS RL ON (RPL.rent_landmark_id = RL.id)
            WHERE RPL.rent_place_id = RP.id
            AND RL.type = 'M'
        ) AS near_rail,
        CASE RP.type 
            WHEN 'H' THEN 'บ้านเดี่ยว'
            WHEN 'C' THEN 'คอนโด'
            WHEN 'A' THEN 'อพาร์ทเม้นท์'
            WHEN 'V' THEN 'วิลล่า'
            WHEN 'T' THEN 'ทาวน์เฮ้าส์'
            WHEN 'L' THEN 'ที่ดิน'
            ELSE RP.type
        END AS property_type, 
        RP.create_datetime,
        (SELECT COUNT(*) FROM RENT_PLACE_ATTACH PA INNER JOIN RENT_ATTACH A ON (PA.attach_id = A.id) INNER JOIN RENT_FILE F ON (F.attach_id = A.id) WHERE 1=1 AND PA.rent_place_id = RP.id) AS place_cnt,
        (SELECT concat(RP.id, '/', A.name, '/', F.name) FROM RENT_PLACE_ATTACH PA INNER JOIN RENT_ATTACH A ON (PA.attach_id = A.id) INNER JOIN RENT_FILE F ON (F.attach_id = A.id) WHERE 1=1 AND PA.rent_place_id = RP.id AND F.cover_flag = 'Y') AS attach_name
        FROM RENT_PLACE RP 
        LEFT JOIN RENT_PROVINCE P ON (RP.province_id = P.id)
        LEFT JOIN RENT_DISTRICT D ON (RP.district_id = D.id)
        LEFT JOIN RENT_SUB_DISTRICT SD ON (RP.sub_district_id = SD.id)
        WHERE 1=1";

if (count($where) > 0) {
    $sql .= " AND " . implode(" AND ", $where);
}
$sql .= " ORDER BY RP.UPDATE_DATETIME DESC ";

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