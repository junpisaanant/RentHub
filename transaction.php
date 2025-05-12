<!DOCTYPE html>
<html lang="en">

<body class="about-page">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.nav-tabs .nav-link.active {
    background-color: #fff8e1;
    color: #c59d00;
    border-color: #c59d00;
}
.card {
    border: 1px solid #e0e0e0;
    border-radius: 0;
    margin-bottom: 1rem;
}
</style>

<?php 
$mode = 'about';
include 'header.php'; 
include 'db.php';

// รับค่าฟิลเตอร์
$keyword = $_GET['keyword'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// สถานะ
$status_labels = [
    'A' => 'นัดหมาย',
    'C' => 'ไม่ตกลงเข้าพัก',
    'D' => 'ไม่มาตามนัดหมาย',
    'W' => 'รอชำระเงิน',
    'T' => 'ชำระเงินแล้วรอยืนยัน',
    'O' => 'ยืนยันชำระเงินแล้ว'
];

// ดึง count
$status_counts = [];
$sql = "SELECT status, COUNT(*) as total 
        FROM RENT_PLACE_APPOINTMENT a
        JOIN RENT_PLACE p ON a.rent_place_id = p.id
        WHERE a.rent_user_id = ?";

$params = [$user_id];
$types = "i";

if ($keyword !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}
if ($from_date !== '') {
    $sql .= " AND a.date >= ?";
    $params[] = $from_date;
    $types .= "s";
}
if ($to_date !== '') {
    $sql .= " AND a.date <= ?";
    $params[] = $to_date;
    $types .= "s";
}
$sql .= " GROUP BY status";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = (int)$row['total'];
}

// ดึงข้อมูลนัดหมาย
$sql = "SELECT a.*, p.name AS place_name 
        FROM RENT_PLACE_APPOINTMENT a
        JOIN RENT_PLACE p ON a.rent_place_id = p.id
        WHERE a.rent_user_id = ? ";

$params = [$user_id];
$types = "i";

if ($keyword !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}
if ($from_date !== '') {
    $sql .= " AND a.date >= ?";
    $params[] = $from_date;
    $types .= "s";
}
if ($to_date !== '') {
    $sql .= " AND a.date <= ?";
    $params[] = $to_date;
    $types .= "s";
}
$sql .= " ORDER BY a.date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// แยกตามสถานะ
$appointments_by_status = [];
foreach ($appointments as $row) {
    $appointments_by_status[$row['status']][] = $row;
}
?>

  <main class="main">
    
  <div class="container my-5">
    <!-- ฟิลเตอร์ -->
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control" placeholder="ค้นหาชื่อสถานที่">
        </div>
        <div class="col-md-3">
            <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="form-control">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-dark">ค้นหา</button>
        </div>
    </form>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
        <?php $first = true; foreach ($status_labels as $code => $label): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $first ? 'active' : '' ?>" id="tab-<?= $code ?>" data-bs-toggle="tab" data-bs-target="#content-<?= $code ?>" type="button" role="tab">
                    <?= $label ?> (<?= $status_counts[$code] ?? 0 ?>)
                </button>
            </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <div class="tab-content border border-top-0 p-3" id="appointmentTabsContent">
        <?php $first = true; foreach ($status_labels as $code => $label): ?>
            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="content-<?= $code ?>" role="tabpanel">
                <?php
                $items = $appointments_by_status[$code] ?? [];
                if (empty($items)) {
                    echo "<p class='text-muted'>ไม่มีข้อมูล</p>";
                } else {
                    foreach ($items as $item): ?>
                        <div class="card p-3">
                            <h5><?= htmlspecialchars($item['place_name']) ?></h5>
                            <p class="mb-1"><strong>วันที่นัดหมาย:</strong> <?= date('d/m/Y', strtotime($item['date'])) ?></p>
                            <p class="mb-1"><strong>วันที่ต้องการเข้าพัก:</strong> <?= date('d/m/Y', strtotime($item['in_date'])) ?></p>
                            <p class="mb-1"><strong>ยอดเงินที่ต้องชำระ:</strong> <?= number_format($item['price'], 2) ?> บาท</p>
                            <?php if (!empty($item['transfer_date'])){ ?>
                                <p class="mb-0"><strong>วันที่ชำระเงิน:</strong> <?= date('d/m/Y', strtotime($item['transfer_date'])) ?></p>
                            <?php } ?>
                            <!-- เงื่อนไขเฉพาะสถานะ "W" -->
                            <div class="mt-3">
                              <button class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-1.5 px-4 rounded flex items-center gap-2">
                                🐣 <span>ชำระเงิน</span>
                              </button>
                            </div>
                        </div>
                    <?php endforeach;
                } ?>
            </div>
        <?php $first = false; endforeach; ?>
    </div>
</div>

  </main>

  <?php include 'footer.php'; ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>