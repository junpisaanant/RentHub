<?php
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
        $params[':propertytypeType'] = $_POST['type'];
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
        $where[] = "RP.roomQty = :roomQty";
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
        $where[] = "RP.toiletQty = :toiletQty";
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
        $where[] = "RPF.id IN ($inClause)";
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
        $where[] = "feature_id IN ($inClause)";
        // รวมค่าลงใน $params ด้วย
        $params = array_merge($params, $facilityIds);
    }


// สร้างคำสั่ง SQL เพื่อค้นหาข้อมูลจากตาราง RENT_PLACE ตามฟิลด์ name
$sql = "SELECT RP.name, P.name, D.name, SD.name, P.price, RP.room_qty ".
        ", RP.toilet_qty ".//จำนวนห้องน้ำ
        ", CASE WHEN RL.type = 'M' THEN RL.name || '(' || RPL.distance || ' เมตร)' ELSE END AS NEAR_RAIL ".//ใกล้สถานีรถไฟฟ้า กี่เมตร
        ", DECODE('H', 'บ้านเดี่ยว', 'C', 'คอนโด', 'A', 'อพาร์ตเม้นท์', 'V', 'วิลล่า', 'T', 'ทาวน์เฮ้าส์', 'L' 'ที่ดิน') ".//ประเภท
        ", RP.create_datetime ".//ลงประกาศไว้เมื่อ
        
        "FROM RENT_PLACE RP ".
        "LEFT JOIN RENT_PROVINCE P ON (P.province_id = RP.id)".
        "LEFT JOIN RENT_DISTRICT D ON (D.district_id = RP.id)".
        "LEFT JOIN RENT_SUB_DISTRICT SD ON (SD.sub_district_id = RP.id)".
        "LEFT JOIN RENT_PLACE_LANDMARKS RPL ON (RP.rent_place_id = RPL.id)".
        "LEFT JOIN RENT_LANDMARKS RL ON (RPL.rent_landmark_id = RL.id)".
        "LEFT JOIN RENT_PLACE_FACILITIES RPF ON (RP.rent_place_id = RPF.id)".
        "LEFT JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RP.id)".
        " WHERE 1=1 ";
if (count($where) > 0) {
    $sql =  $sql. implode(" AND ", $where);
}
try {
    // เตรียม statement และประมวลผลคำสั่ง SQL
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งผลลัพธ์ออกมาเป็น JSON
    echo json_encode($results);
} catch (PDOException $e) {
    // แจ้งข้อผิดพลาด (คุณอาจปรับปรุงให้มีการแจ้งเตือนที่เหมาะสม)
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>