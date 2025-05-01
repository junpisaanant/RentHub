<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" type="text/css" href="assets/css/rent_place.css">
<link rel="stylesheet" type="text/css" href="assets/css/search.css">
<body class="index-page">
<?php 
$mode = 'home';
include 'header.php'; 

//ดึงข้อมูลมาแสดงในหน้าจอ
include 'db.php'; // เชื่อมต่อฐานข้อมูลด้วย mysqli

$id = $_REQUEST['id'];
$name = $_REQUEST['name'];
//Query ดึงข้อมูลของห้องเช่านี้
$sql = "SELECT RP.id, RP.name
, RP.price, RP.size, RP.room_qty, RP.toilet_qty, RP.description
, RP.address,P.name AS province_name , D.name AS district_name, SD.name AS sub_district_name
, CONCAT(RU.firstname, ' ', RU.lastname) AS fullname, RU.phone_no, RP.map_url
, CASE RP.type 
            WHEN 'H' THEN 'บ้านเดี่ยว'
            WHEN 'C' THEN 'คอนโด'
            WHEN 'A' THEN 'อพาร์ทเม้นท์'
            WHEN 'V' THEN 'วิลล่า'
            WHEN 'T' THEN 'ทาวน์เฮ้าส์'
            WHEN 'L' THEN 'ที่ดิน'
            ELSE RP.type
        END AS property_type
, CONCAT(RU.id, '/',RUF.name) AS user_image
, CONCAT(RP.id, '/', RPA.name, '/',RPF.name) AS map_image
FROM RENT_PLACE RP
INNER JOIN RENT_PROVINCE P ON (P.id = RP.province_id)
INNER JOIN RENT_DISTRICT D ON (D.id = RP.district_id)
INNER JOIN RENT_SUB_DISTRICT SD ON (SD.id = RP.sub_district_id)
INNER JOIN RENT_USER RU ON (RU.id = RP.user_id)
LEFT JOIN RENT_ATTACH RUA ON (RU.attach_id = RUA.id)
LEFT JOIN RENT_FILE RUF ON (RUF.attach_id = RUA.id)

LEFT JOIN RENT_ATTACH RPA ON (RP.attach_id = RPA.id)
LEFT JOIN RENT_FILE RPF ON (RPF.attach_id = RPA.id)
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

//การแสดงภาพ
// Query ดึงข้อมูลสำหรับ แสดงภาพ 
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
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

// Query ดึงข้อมูลสำหรับ แสดงภาพห้องรับแขก 
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'L'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$livingRoomItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $livingRoomItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงภาพห้องนอน
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'B'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$bedRoomItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bedRoomItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงภาพห้องน้ำ
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'T'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$toiletItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $toiletItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงภาพห้องครัว
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'K'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$kitchenItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kitchenItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงภาพห้องอื่นๆ
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'O'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$otherRoomItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $otherRoomItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงภาพแผนผัง
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'P'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$planRoomItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planRoomItems[] = $row;
    }
}

// Query ดึงข้อมูลสำหรับ แสดงวิดีโอ
$sql = "SELECT RP.id, RP.name
, A.name AS attach_name
, F.name AS file_name
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_ATTACH RPA ON (RP.id = RPA.rent_place_id)
LEFT JOIN RENT_ATTACH A ON (RPA.attach_id = A.id)
LEFT JOIN RENT_FILE F ON (A.id = F.attach_id)
WHERE 1=1
AND RP.id = ?
AND F.type= 'V'
ORDER BY RP.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$videoItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $videoItems[] = $row;
    }
}

//Query ดึงข้อมูลจุดเด่นของห้องเช่านี้
$sql = "SELECT RF.id, RF.name, RF.icon, RF.description
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
//Query ดึงข้อมูลสิ่งอำนวยความสะดวกของห้องเช่านี้
$sql = "SELECT RF.id, RF.name, RF.icon, RF.description
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_FACILITIES RPF ON (RPF.rent_place_id = RP.id)
LEFT JOIN RENT_FACILITIES RF ON (RPF.rent_facilities_id = RF.id)
WHERE 1=1
AND RF.type='F'
AND RP.id = ?
ORDER BY RPF.create_datetime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$facilities = [];//จุดเด่น
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
}
//สถานที่สำคัญ Landmark
$sql = "SELECT RL.id, RL.name, RL.type, RL.location_url,
CASE WHEN RL.type = 'D' THEN 'fa-solid fa-store'
WHEN RL.type = 'S' THEN 'fa-solid fa-school'
WHEN RL.type = 'P' THEN 'fa-solid fa-fan'
WHEN RL.type = 'M' THEN 'fa-solid fa-train-subway'
ELSE 'fa-solid fa-road'
END AS icon
FROM RENT_PLACE RP
LEFT JOIN RENT_PLACE_LANDMARKS RPL ON (RPL.rent_place_id = RP.id)
LEFT JOIN RENT_LANDMARKS RL ON (RPL.rent_landmark_id = RL.id)
WHERE 1=1
AND RP.id = ?
ORDER BY RL.type, RL.name DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
// execute statement
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();
$landmarks = [];//จุดเด่น
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $landmarks[] = $row;
    }
}
?>
  <main class="main">
    <!-- รายละเอียด Section -->
    <section id="real-estate-2" class="real-estate-2 section">

      <div class="container" data-aos="fade-up">

        <div class="portfolio-details-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "slidesPerView": "auto",
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              },
              "pagination": {
                "el": ".swiper-pagination",
                "type": "bullets",
                "clickable": true
              }
            }
          </script>
          <div class="swiper-wrapper align-items-center">
			<?php if (!empty($heroItems)): ?>
				<?php foreach ($heroItems as $index => $item): ?>
				<div class="swiper-slide">
				  <img src="assets/rent_place/<?php echo $item['id']; ?>/<?php echo $item['attach_name']; ?>/<?php echo $item['file_name']; ?>" alt="<?php echo htmlspecialchars($item['file_name']); ?>">
				</div>
				<?php endforeach; ?>
			<?php else: ?>
			<p>ไม่พบข้อมูลสำหรับ Hero Section</p>
			<?php endif; ?>

          </div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-pagination"></div>
        </div>

        <div class="row justify-content-between gy-4 mt-4">

          <div class="col-lg-8" data-aos="fade-up">

            <div class="portfolio-description">
              <h2><?php echo $data['name']; ?></h2>
              <p>
                <?php echo $data['description']; ?>
              </p>

              <div class="testimonial-item">
                <div>
                  <img src="assets/rent_user/<?php echo $data['user_image']; ?>" class="testimonial-img" alt="">
                  <h3><?php echo $data['fullname']; ?></h3>
                  <?php if (isset($_SESSION['user_id'])) { ?>
                  <!-- ปุ่ม ติดต่อขอเช่า -->
                  <a href="contact.php?rent_place_id=<?php echo $data['id']; ?>"
                    class="filter-button mt-2">
                    ติดต่อขอเช่า
                  </a>
                  <?php }else{ ?>
                      กรุณา Log in เพื่อติดต่อขอเช่า
                  <?php } ?>
                </div>
              </div>
            </div><!-- End Portfolio Description -->

            <!-- Tabs -->
            <ul class="nav nav-pills mb-3">
              <li><a class="nav-link active" data-bs-toggle="pill" href="#real-estate-2-tab1">Video</a></li>
              <li><a class="nav-link" data-bs-toggle="pill" href="#real-estate-2-tab3">Location</a></li>
            </ul><!-- End Tabs -->

            <!-- Tab Content -->
            <div class="tab-content">

              <div class="tab-pane fade show active" id="real-estate-2-tab1">

              </div><!-- End Tab 1 Content -->

              <div class="tab-pane fade" id="real-estate-2-tab3">
                <?php if($data['map_url']){ ?>
                  <iframe style="border:0; width: 100%; height: 400px;" src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48389.78314118045!2d-74.006138!3d40.710059!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php }else{ ?>
                  <img src="assets/rent_place/<?php echo $data['map_image'];?>" alt="" class="img-fluid">
                <?php } ?>
              </div><!-- End Tab 3 Content -->

            </div><!-- End Tab Content -->

          </div>

          <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="portfolio-info">
              <h3>Quick Summary</h3>
              <ul>
                <li><strong>Price:</strong><?php echo number_format($data['price']); ?> ฿</li>
                <li><strong>Property ID:</strong><?php echo $data['id']; ?></li>
                <li><strong>Location:</strong><?php echo $data['address'] . '<br>' . $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></li>
                <li><strong>Property Type:</strong><?php echo $data['property_type']; ?></li>
                <li><strong>Area:</strong> <span><?php echo $data['size']; ?> m <sup>2</sup></span></li>
                <li><strong>Beds:</strong><?php echo $data['room_qty']; ?></li>
                <li><strong>Baths:</strong><?php echo $data['toilet_qty']; ?></li>
              </ul>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Real Estate 2 Section -->

    <!-- จุดเด่น และสถานที่สำคัฯ Section -->
    <?php if (!empty($points)){ ?> 
    <section id="services" class="services section">
      <!-- จุดเด่น -->
      <div class="container">
        <div class="row" style="row-gap: 0 !important;">
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
                    <p><?php echo $item['description'];?></p>
                    </div>
                </div><!-- End Service Item -->
            <?php endforeach; ?>
        </div>
      </div>
    </section><!-- /Services Section -->
    <?php } ?>

    <?php if (!empty($facilities)){ ?> 
      <!-- สิ่งอำนวยความสะดวก -->
      <section id="facilities" class="services section">
        <!-- สิ่งอำนวยความสะดวก -->
        <div class="container">
          <div class="row" style="row-gap: 0 !important;">
              <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
              <h2>สิ่งอำนวยความสะดวก</h2>
              </div>
              <?php foreach ($facilities as $index => $item): ?>
                  <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                      <div class="service-item  position-relative" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                      <div class="icon">
                      <i class="<?php echo $item['icon'];?>"></i>
                      </div>
                      <h3><?php echo $item['name'];?></h3>
                      <p><?php echo $item['description'];?></p>
                      </div>
                  </div><!-- End Service Item -->
              <?php endforeach; ?>
          </div>
        </div>
      </section><!-- /Services Section -->
    <?php } ?>
    <?php if (!empty($landmarks)){ ?> 
      <!-- สถานที่สำคัญ Landmark -->
      <section id="facilities" class="services section" style="row-gap: 0 !important;">
        <!-- สถานที่สำคัญ Landmark -->
        <div class="container">
          <div class="row" style="row-gap: 0 !important;">
          
              <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
              <h2>สถานที่สำคัญ</h2>
              </div>
              <?php foreach ($landmarks as $index => $item): ?>
                  <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                      <div class="service-item  position-relative" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                      <div class="icon">
                      <i class="<?php echo $item['icon'];?>"></i>
                      </div>
                      <h3><?php echo $item['name'];?></h3>
                      </div>
                  </div><!-- End Service Item -->
              <?php endforeach; ?>
              

          </div>

        </div>

      </section><!-- /Services Section -->
    <?php } ?>

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