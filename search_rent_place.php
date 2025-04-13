<?php
include 'db.php';

// รับค่าคำค้นหาจาก AJAX
$where = [];
$params = [];

// หากมีการส่ง searchTerm มา ให้ใช้เงื่อนไขแบบค้นหาทั่วไป
if (isset($_POST['searchTerm']) && $_POST['searchTerm'] !== '') {
    $where[] = "name LIKE :searchTerm";
    $params[':searchTerm'] = '%' . $_POST['searchTerm'] . '%';
} else {
    // รับและตรวจสอบค่าจากตัวกรองทีละตัว
    //
    if (isset($_POST['name']) && $_POST['name'] !== '') {
        $where[] = "name LIKE :name";
        $params[':name'] = '%' . $_POST['name'] . '%';
    }
    if (isset($_POST['propertyType']) && $_POST['propertyType'] !== '') {
        $where[] = "property_type = :propertyType";
        $params[':propertyType'] = $_POST['propertyType'];
    }
    if (isset($_POST['minPrice']) && $_POST['minPrice'] !== '') {
        $where[] = "price >= :minPrice";
        $params[':minPrice'] = $_POST['minPrice'];
    }
    if (isset($_POST['maxPrice']) && $_POST['maxPrice'] !== '') {
        $where[] = "price <= :maxPrice";
        $params[':maxPrice'] = $_POST['maxPrice'];
    }
    if (isset($_POST['bedrooms']) && $_POST['bedrooms'] !== '') {
        $where[] = "bedrooms = :bedrooms";
        $params[':bedrooms'] = $_POST['bedrooms'];
    }
    if (isset($_POST['minArea']) && $_POST['minArea'] !== '') {
        $where[] = "area >= :minArea";
        $params[':minArea'] = $_POST['minArea'];
    }
    if (isset($_POST['maxArea']) && $_POST['maxArea'] !== '') {
        $where[] = "area <= :maxArea";
        $params[':maxArea'] = $_POST['maxArea'];
    }
    if (isset($_POST['stationDistance']) && $_POST['stationDistance'] !== '') {
        $where[] = "station_distance <= :stationDistance";
        $params[':stationDistance'] = $_POST['stationDistance'];
    }
    if (isset($_POST['minPricePerSqm']) && $_POST['minPricePerSqm'] !== '') {
        $where[] = "price_per_sqm >= :minPricePerSqm";
        $params[':minPricePerSqm'] = $_POST['minPricePerSqm'];
    }
    if (isset($_POST['maxPricePerSqm']) && $_POST['maxPricePerSqm'] !== '') {
        $where[] = "price_per_sqm <= :maxPricePerSqm";
        $params[':maxPricePerSqm'] = $_POST['maxPricePerSqm'];
    }
    if (isset($_POST['bathrooms']) && $_POST['bathrooms'] !== '') {
        $where[] = "bathrooms = :bathrooms";
        $params[':bathrooms'] = $_POST['bathrooms'];
    }
    if (isset($_POST['ownership']) && $_POST['ownership'] !== '') {
        $where[] = "ownership = :ownership";
        $params[':ownership'] = $_POST['ownership'];
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
        $where[] = "feature_id IN ($inClause)";
        // รวมค่าลงใน $params ด้วย
        $params = array_merge($params, $featureIds);
    }


// สร้างคำสั่ง SQL เพื่อค้นหาข้อมูลจากตาราง RENT_PLACE ตามฟิลด์ name
$sql = "SELECT name FROM RENT_PLACE WHERE 1=1 ";
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