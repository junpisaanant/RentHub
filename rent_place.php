<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" type="text/css" href="assets/css/rent_place.css">
<body class="index-page">
<?php 
$mode = 'home';
include 'header.php'; 

//ดึงข้อมูลมาแสดงในหน้าจอ
include 'db.php'; // เชื่อมต่อฐานข้อมูลด้วย mysqli

$id = $_REQUEST['id'];
$name = $_REQUEST['name'];
//Query ดึงข้อมูลจุดเด่นของห้องเช่านี้
$sql = "SELECT RP.id, RP.name
, RP.price, RP.size, RP.room_qty, RP.toilet_qty, RP.description
, P.name AS province_name , D.name AS district_name, SD.name AS sub_district_name
, RU.firstname || RU.lastname AS fullname
FROM RENT_PLACE RP
INNER JOIN RENT_PROVINCE P ON (P.id = RP.province_id)
INNER JOIN RENT_DISTRICT D ON (D.id = RP.district_id)
INNER JOIN RENT_SUB_DISTRICT SD ON (SD.id = RP.sub_district_id)
INNER JOIN RENT_USER RU ON (RU.id = RP.user_id)
WHERE 1=1
AND RP.id = ?
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$heroItems = [];
if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
}

// Query ดึงข้อมูลสำหรับ แสดงภาพ (เลือกเฉพาะ 3 รายการแรก เช่น)
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_ATTACH A ON (RP.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$heroItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $heroItems[] = $row;
    }
}

//Query ดึงข้อมูลจุดเด่นของห้องเช่านี้
$sql = "SELECT RF.id, RF.name, RF.icon
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_FACILITIES RPF ON (RPF.rent_place_id = RP.id)
LEFT JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RF.id)
WHERE 1=1
AND RF.type='P'
AND RP.id = ?
ORDER BY RPF.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$points = [];//จุดเด่น
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $points[] = $row;
    }
}
?>
  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">
    <div id="hero-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <?php if (!empty($heroItems)): ?>
            <?php foreach ($heroItems as $index => $item): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <img src="assets/rent_place/<?php echo $item['attach_name']; ?>/<?php echo $item['file_name']; ?>" alt="<?php echo htmlspecialchars($item['file_name']); ?>">
                <!-- Overlay ข้อความ -->
                <div class="hero-overlay">
                    <?php echo htmlspecialchars($item['name']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <p>ไม่พบข้อมูลสำหรับ Hero Section</p>
        <?php endif; ?>
        
        <a class="carousel-control-prev" href="#hero-carousel" role="button" data-bs-slide="prev">
        <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
        </a>
        <a class="carousel-control-next" href="#hero-carousel" role="button" data-bs-slide="next">
        <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
        </a>
        <ol class="carousel-indicators"></ol>
    </div>
    </section>


    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2><?php echo $data['name']; ?></h2>
        <h3><?php echo $data['description']; ?></h3>
        <p><?php echo $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></p>
      </div><!-- End Section Title -->
      
      <!-- จุดเด่น -->
      <div class="container">

        <div class="row" style="row-gap: 0 !important;">
        
        <?php if (!empty($points)){ ?> 
            <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
            <h2>จุดเด่น</h2>
            </div>
            <?php foreach ($points as $index => $item): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-item  position-relative" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="icon">
                    <i class="<?php echo $item['icon'];?>"></i>
                    </div>
                    <h3><?php echo $item['name'];?></h3>
                    </div>
                </div><!-- End Service Item -->
            <?php endforeach; ?>
            <?php } ?>

        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- สิ่งอำนวยความสะดวก -->
    <section id="facilities" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2><?php echo $data['name']; ?></h2>
        <h3><?php echo $data['description']; ?></h3>
        <p><?php echo $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></p>
      </div><!-- End Section Title -->
      
      <!-- จุดเด่น -->
      <div class="container">

        <div class="row" style="row-gap: 0 !important;">
        
        <?php if (!empty($points)){ ?> 
            <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
            <h2>จุดเด่น</h2>
            </div>
            <?php foreach ($points as $index => $item): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-item  position-relative" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="icon">
                    <i class="<?php echo $item['icon'];?>"></i>
                    </div>
                    <h3><?php echo $item['name'];?></h3>
                    </div>
                </div><!-- End Service Item -->
            <?php endforeach; ?>
            <?php } ?>

        </div>

      </div>

    </section><!-- /Services Section -->


  <footer id="footer" class="footer light-background">

    <div class="container">
      <div class="row gy-3">
        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-geo-alt icon"></i>
          <div class="address">
            <h4>Address</h4>
            <p>A108 Adam Street</p>
            <p>New York, NY 535022</p>
            <p></p>
          </div>

        </div>

        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-telephone icon"></i>
          <div>
            <h4>Contact</h4>
            <p>
              <strong>Phone:</strong> <span>+1 5589 55488 55</span><br>
              <strong>Email:</strong> <span>info@example.com</span><br>
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-clock icon"></i>
          <div>
            <h4>Opening Hours</h4>
            <p>
              <strong>Mon-Sat:</strong> <span>11AM - 23PM</span><br>
              <strong>Sunday</strong>: <span>Closed</span>
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <h4>Follow Us</h4>
          <div class="social-links d-flex">
            <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">EstateAgency</strong> <span>All Rights Reserved</span></p>
    </div>

  </footer>

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