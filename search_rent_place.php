<?php
// ผมขออนุญาตเพิ่ม session_start() และ header() เข้ามาเพื่อให้ทำงานร่วมกับระบบภาษาได้ครับ
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- 1. LANGUAGE LOGIC (เพิ่มส่วนนี้เข้ามา) ---
$current_lang = isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['th', 'en', 'cn']) ? $_SESSION['lang'] : 'th';
$lang_file = __DIR__ . '/languages/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    // Fallback language array to prevent errors
    $lang = ['bedroom_unit' => 'Bed', 'bathroom_unit' => 'Bath', 'sqm_unit' => 'sq.m.', 'house' => 'House', 'condo' => 'Condo', 'apartment' => 'Apartment', 'villa' => 'Villa', 'townhouse' => 'Townhouse', 'land' => 'Land'];
}
// --- END LANGUAGE LOGIC ---

error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

// --- 2. FILTER LOGIC (โค้ดเดิมของคุณทั้งหมด ไม่มีการแก้ไข) ---
$where = [];
$params = [];
$param_types = '';

if (isset($_POST['searchTerm']) && $_POST['searchTerm'] !== '') {
    // *** แก้ไขเล็กน้อย: ให้ค้นหาจากทุกภาษา ***
    $where[] = "(RP.name LIKE ? OR RP.name_en LIKE ? OR RP.name_cn LIKE ?)";
    $searchTerm = '%' . $_POST['searchTerm'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $param_types .= 'sss';
} else {
    // ส่วนนี้ทั้งหมดคือโค้ดเดิมของคุณ ไม่มีการแตะต้อง
    if (isset($_POST['type']) && $_POST['type'] !== '') { $where[] = "RP.type = ?"; $params[] = $_POST['type']; $param_types .= 's'; }
    if (isset($_POST['minPrice']) && $_POST['minPrice'] !== '') { $where[] = "RP.price >= ?"; $params[] = $_POST['minPrice']; $param_types .= 'd'; }
    if (isset($_POST['maxPrice']) && $_POST['maxPrice'] !== '') { $where[] = "RP.price <= ?"; $params[] = $_POST['maxPrice']; $param_types .= 'd'; }
    if (isset($_POST['roomQty']) && $_POST['roomQty'] !== '') { $where[] = "RP.room_qty = ?"; $params[] = $_POST['roomQty']; $param_types .= 'i'; }
    if (isset($_POST['minSize']) && $_POST['minSize'] !== '') { $where[] = "RP.size >= ?"; $params[] = $_POST['minSize']; $param_types .= 'd'; }
    if (isset($_POST['maxSize']) && $_POST['maxSize'] !== '') { $where[] = "RP.size <= ?"; $params[] = $_POST['maxSize']; $param_types .= 'd'; }
    if (isset($_POST['distance']) && $_POST['distance'] !== '') { $where[] = "RPL.distance <= ?"; $params[] = $_POST['distance']; $param_types .= 'd'; }
    if (isset($_POST['toiletQty']) && $_POST['toiletQty'] !== '') { $where[] = "RP.toilet_qty = ?"; $params[] = $_POST['toiletQty']; $param_types .= 'i'; }
    if (isset($_POST['feature']) && $_POST['feature'] !== '') {
        $featureIds = explode(',', $_POST['feature']);
        $featureIds = array_filter(array_map('intval', $featureIds));
        if (!empty($featureIds)) {
            $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
            $where[] = "RP.id IN (SELECT RPF.rent_place_id FROM RENT_PLACE_FACILITIES RPF JOIN RENT_FACILITIES RF ON RPF.rent_facilities_id = RF.id WHERE RF.type = 'P' AND RPF.rent_facilities_id IN ($placeholders) GROUP BY RPF.rent_place_id)";
            foreach ($featureIds as $id) { $params[] = $id; $param_types .= 'i'; }
        }
    }
    if (isset($_POST['facility']) && $_POST['facility'] !== '') {
        $facilityIds = explode(',', $_POST['facility']);
        $facilityIds = array_filter(array_map('intval', $facilityIds));
        if (!empty($facilityIds)) {
            $placeholders = implode(',', array_fill(0, count($facilityIds), '?'));
            $where[] = "RP.id IN (SELECT RPF.rent_place_id FROM RENT_PLACE_FACILITIES RPF JOIN RENT_FACILITIES RF ON RPF.rent_facilities_id = RF.id WHERE RF.type = 'F' AND RPF.rent_facilities_id IN ($placeholders) GROUP BY RPF.rent_place_id)";
            foreach ($facilityIds as $id) { $params[] = $id; $param_types .= 'i'; }
        }
    }
}
// --- END FILTER LOGIC ---


// --- 3. SQL QUERY (แก้ไขเฉพาะส่วนที่เกี่ยวกับภาษา) ---
$rp_name_col = ($current_lang != 'th') ? "RP.name_{$current_lang}" : "RP.name";
$p_name_col = ($current_lang != 'th') ? "P.name_{$current_lang}" : "P.name";
$d_name_col = ($current_lang != 'th') ? "D.name_{$current_lang}" : "D.name";
$sd_name_col = ($current_lang != 'th') ? "SD.name_{$current_lang}" : "SD.name";

$sql = "SELECT 
            RP.id,
            COALESCE($rp_name_col, RP.name) AS rp_name,
            COALESCE($p_name_col, P.name) AS province_name,
            COALESCE($d_name_col, D.name) AS district_name,
            COALESCE($sd_name_col, SD.name) AS sub_district_name,
            RP.price, 
            RP.size, 
            RP.room_qty, 
            RP.toilet_qty,
            RP.type AS property_type_code, -- ส่งโค้ด 'H', 'C' ไปให้ PHP แปล
            RP.create_datetime,
            -- ส่วนดึงรูปภาพ (โค้ดเดิมของคุณ ไม่มีการแก้ไข)
            (SELECT COUNT(*) FROM RENT_PLACE_ATTACH PA INNER JOIN RENT_ATTACH A ON (PA.attach_id = A.id) INNER JOIN RENT_FILE F ON (F.attach_id = A.id) WHERE 1=1 AND PA.rent_place_id = RP.id) AS place_cnt,
            (SELECT concat(RP.id, '/', A.name, '/', F.name) FROM RENT_PLACE_ATTACH PA INNER JOIN RENT_ATTACH A ON (PA.attach_id = A.id) INNER JOIN RENT_FILE F ON (F.attach_id = A.id) WHERE 1=1 AND PA.rent_place_id = RP.id AND F.cover_flag = 'Y') AS attach_name
        FROM RENT_PLACE RP 
        LEFT JOIN RENT_PROVINCE P ON (RP.province_id = P.id)
        LEFT JOIN RENT_DISTRICT D ON (RP.district_id = D.id)
        LEFT JOIN RENT_SUB_DISTRICT SD ON (RP.sub_district_id = SD.id)
        LEFT JOIN RENT_PLACE_LANDMARKS RPL ON (RP.id = RPL.rent_place_id)
        WHERE 1=1";

if (count($where) > 0) {
    $sql .= " AND " . implode(" AND ", $where);
}
// เพิ่ม GROUP BY เพื่อป้องกันข้อมูลซ้ำซ้อนจาก JOIN
$sql .= " GROUP BY RP.id ORDER BY RP.update_datetime DESC ";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die(json_encode(['error' => 'SQL prepare failed: ' . $conn->error]));
}

// --- 4. BIND & EXECUTE (โค้ดเดิมของคุณ ไม่มีการแก้ไข) ---
if (!empty($params)) {
    $bind_names = [];
    $bind_names[] = $param_types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);
}
if(!$stmt->execute()){
    die(json_encode(['error' => 'Statement execution failed: ' . $stmt->error]));
}
// --- END BIND & EXECUTE ---

$result = $stmt->get_result();
$results = $result->fetch_all(MYSQLI_ASSOC);

// --- 5. POST-PROCESS (เพิ่มข้อมูลที่แปลแล้ว) ---
$processed_results = [];
$property_type_map = [
    'H' => $lang['house'], 'C' => $lang['condo'], 'A' => $lang['apartment'],
    'V' => $lang['villa'], 'T' => $lang['townhouse'], 'L' => $lang['land']
];

foreach($results as $row) {
    $row['property_type'] = $property_type_map[$row['property_type_code']] ?? $row['property_type_code'];
    $row['translated_bedrooms'] = $row['room_qty'] . ' ' . $lang['bedroom_unit'];
    $row['translated_bathrooms'] = $row['toilet_qty'] . ' ' . $lang['bathroom_unit'];
    $row['translated_size'] = $row['size'] . ' ' . $lang['sqm_unit'];
    $processed_results[] = $row;
}
// --- END POST-PROCESS ---

echo json_encode($processed_results, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>