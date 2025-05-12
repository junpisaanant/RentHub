<!DOCTYPE html>
<html lang="en">

<body class="about-page">
<style>
  .nav-tabs-elegant {
    border-bottom: 1px solid #dee2e6;
  }
  .nav-tabs-elegant .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #555;
    background-color: transparent;
    font-weight: 500;
    padding: 12px 16px;
    transition: all 0.3s ease-in-out;
  }
  .nav-tabs-elegant .nav-link:hover {
    color: #d4af37;
    border-color: #d4af37;
  }
  .nav-tabs-elegant .nav-link.active {
    color: #d4af37;
    border-color: #d4af37;
    background-color: transparent;
    font-weight: 600;
  }

  .tab-pane {
    padding: 25px 10px;
    background: none;
    border-top: none;
    margin-top: 10px;
  }
</style>

<?php 
$mode = 'about';
include 'header.php'; 
include 'db.php';

// 2. ดึง count ตาม status
$status_counts = [];
$sql = "SELECT status, COUNT(*) as total 
        FROM RENT_PLACE_APPOINTMENT 
        WHERE rent_user_id = ? 
        GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = (int)$row['total'];
}

// 3. กำหนด label ภาษาไทย
$status_labels = [
    'A' => 'นัดหมาย',
    'C' => 'ไม่ตกลงเข้าพัก',
    'D' => 'ไม่มาตามนัดหมาย',
    'W' => 'รอชำระเงิน',
    'T' => 'ชำระเงินแล้วรอยืนยัน',
    'O' => 'ยืนยันชำระเงินแล้ว'
];

// 4. เติมสถานะที่ไม่มีในฐานข้อมูลให้เป็น 0
foreach ($status_labels as $key => $label) {
    if (!isset($status_counts[$key])) {
        $status_counts[$key] = 0;
    }
}
?>

  <main class="main">
    
    <!-- Tabs Section -->
    <section id="tabs-section" class="section">
      <div class="container" data-aos="fade-up">

        <!-- Elegant Tabs -->
        <ul class="nav nav-tabs nav-tabs-elegant" id="myTab" role="tablist">
          <?php $first = true; ?>
          <?php foreach ($status_labels as $key => $label): ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?= $first ? 'active' : '' ?>" id="tab-<?= $key ?>"
                      data-bs-toggle="tab" data-bs-target="#content-<?= $key ?>" type="button" role="tab">
                <?= $label ?> (<?= $status_counts[$key] ?>)
              </button>
            </li>
            <?php $first = false; ?>
          <?php endforeach; ?>
        </ul>

        <!-- Content -->
        <div class="tab-content" id="myTabContent">
          <?php $first = true; ?>
          <?php foreach ($status_labels as $key => $label): ?>
            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="content-<?= $key ?>" role="tabpanel">
              <h5><?= $label ?> (<?= $status_counts[$key] ?>)</h5>
              <p>ข้อมูลของสถานะ <strong><?= $label ?></strong> จะแสดงตรงนี้</p>
            </div>
            <?php $first = false; ?>
          <?php endforeach; ?>
        </div>

      </div>
    </section>

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