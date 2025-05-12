<!DOCTYPE html>
<html lang="en">

<body class="about-page">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/search.css">
<style>
.nav-tabs .nav-link.active {
    background-color: #fff8e1;
    color: #c59d00;
    border-color: #c59d00;
}
.property-card {
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 1rem;
  background-color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  margin-bottom: 1rem;
  transition: all 0.3s ease-in-out;
}
.property-card:hover {
  box-shadow: 0 4px 14px rgba(0,0,0,0.1);
}
.property-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}
.property-detail {
  font-size: 0.95rem;
  color: #666;
  margin-bottom: 0.25rem;
}
.property-btn {
  margin-top: 1rem;
}
</style>

<?php 
$mode = 'about';
include 'header.php'; 
include 'db.php';

$keyword = $_GET['keyword'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$status_labels = [
    'A' => 'นัดหมาย',
    'C' => 'ไม่ตกลงเข้าพัก',
    'D' => 'ไม่มาตามนัดหมาย',
    'W' => 'รอชำระเงิน',
    'T' => 'ชำระเงินแล้วรอยืนยัน',
    'O' => 'ยืนยันชำระเงินแล้ว'
];

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

$appointments_by_status = [];
foreach ($appointments as $row) {
    $appointments_by_status[$row['status']][] = $row;
}
?>

<main class="main">
  <div class="container my-5">
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
                      <a href="rent_place.php?id=<?= $item['rent_place_id'] ?>&name=<?=$item['place_name']?>"
                      target="_blank">
                        <div class="property-card">
                          <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item['place_name']) ?></h5>

                            <p class="card-text"><strong>วันที่นัดหมาย:</strong>
                              <?= date('d/m/Y', strtotime($item['date'])) ?></p>

                            <?php if (!empty($item['in_date'])): ?>
                            <p class="card-text"><strong>วันที่ต้องการเข้าพัก:</strong>
                              <?= date('d/m/Y', strtotime($item['in_date'])) ?></p>
                            <?php endif; ?>

                            <p class="card-text"><strong>ยอดเงินที่ต้องชำระ:</strong>
                              <?= number_format($item['price'], 2) ?> บาท</p>

                            <?php if (!empty($item['transfer_date'])): ?>
                            <p class="card-text"><strong>วันที่ชำระเงิน:</strong>
                              <?= date('d/m/Y', strtotime($item['transfer_date'])) ?></p>
                            <?php endif; ?>

                            <?php if ($code === 'W'): // เฉพาะสถานะ รอชำระเงิน ?>
                            <a href="payment.php?appt_id=<?= $item['id'] ?>" class="btn btn-warning">
                              ชำระเงิน
                            </a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </a>
                    <?php endforeach;
                } ?>
            </div>
        <?php $first = false; endforeach; ?>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<div id="preloader"></div>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>
