<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" type="text/css" href="assets/css/rent_place.css">
<link rel="stylesheet" type="text/css" href="assets/css/search.css">
<?php 
$mode = 'home';
include 'header.php'; 

// Include language file
if (isset($_SESSION['lang'])) {
    include 'languages/' . $_SESSION['lang'] . '.php';
} else {
    include 'languages/th.php'; // Default language
}

//ดึงข้อมูลมาแสดงในหน้าจอ
include 'db.php'; // เชื่อมต่อฐานข้อมูลด้วย mysqli

$id = $_REQUEST['id'];
$name = $_REQUEST['name'];

// Language suffix mapping
$lang_suffix = '';
if (isset($_SESSION['lang'])) {
    if ($_SESSION['lang'] == 'en') {
        $lang_suffix = '_en';
    } elseif ($_SESSION['lang'] == 'cn') {
        $lang_suffix = '_cn';
    }
}


//Query ดึงข้อมูลของห้องเช่านี้
$sql = "SELECT RP.id, 
       COALESCE(NULLIF(RP.name" . $lang_suffix . ", ''), RP.name) AS name,
       RP.price, 
       RP.size, 
       RP.room_qty, 
       RP.toilet_qty, 
       COALESCE(NULLIF(RP.description" . $lang_suffix . ", ''), RP.description) AS description,
       COALESCE(NULLIF(RP.address" . $lang_suffix . ", ''), RP.address) AS address,
       COALESCE(NULLIF(P.name" . $lang_suffix . ", ''), P.name) AS province_name, 
       COALESCE(NULLIF(D.name" . $lang_suffix . ", ''), D.name) AS district_name, 
       COALESCE(NULLIF(SD.name" . $lang_suffix . ", ''), SD.name) AS sub_district_name,
       CONCAT(RU.firstname, ' ', RU.lastname) AS fullname, 
       RU.phone_no, 
       RP.map_url,
       CASE RP.type 
            WHEN 'H' THEN '" . $lang['house'] . "'
            WHEN 'C' THEN '" . $lang['condo'] . "'
            WHEN 'A' THEN '" . $lang['apartment'] . "'
            WHEN 'V' THEN '" . $lang['villa'] . "'
            WHEN 'T' THEN '" . $lang['townhouse'] . "'
            WHEN 'L' THEN '" . $lang['land'] . "'
            ELSE RP.type
        END AS property_type,
       CONCAT(RU.id, '/',RUF.name) AS user_image,
       CONCAT(RP.id, '/', RPA.name, '/',RPF.name) AS map_image
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
LEFT JOIN RENT_ATTACH A ON (RP.video_attach_id = A.id)
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
$videoItems = [];
if ($result && $result->num_rows > 0) {
  $video_data = $result->fetch_assoc();
  // สร้าง Path เต็มของไฟล์วิดีโอ
  $video_file_path = "assets/rent_place/" . $video_data['id'] . "/video/" . $video_data['file_name'];
}

//Query ดึงข้อมูลจุดเด่นของห้องเช่านี้
$sql = "SELECT RF.id, 
       COALESCE(NULLIF(RF.name" . $lang_suffix . ", ''), RF.name) AS name, 
       RF.icon, 
       COALESCE(NULLIF(RF.description" . $lang_suffix . ", ''), RF.description) AS description
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
$sql = "SELECT RF.id, 
       COALESCE(NULLIF(RF.name" . $lang_suffix . ", ''), RF.name) AS name, 
       RF.icon, 
       COALESCE(NULLIF(RF.description" . $lang_suffix . ", ''), RF.description) AS description
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
$sql = "SELECT RL.id, 
       COALESCE(NULLIF(RL.name" . $lang_suffix . ", ''), RL.name) AS name, 
       RL.type, 
       RL.location_url,
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
			<p><?php echo $lang['no_data_hero']; ?></p>
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
                  <a href="contact.php?rent_place_id=<?php echo $data['id']; ?>"
                    class="filter-button mt-2">
                    <?php echo $lang['contact_for_rent']; ?>
                  </a>
                  <?php }else{ ?>
                      <?php echo $lang['login_to_contact']; ?>
                  <?php } ?>
                </div>
              </div>
            </div><ul class="nav nav-pills mb-3">
              <li><a class="nav-link active" data-bs-toggle="pill" href="#real-estate-2-tab1"><?php echo $lang['video']; ?></a></li>
              <?php if($data['map_image']){ ?>
                <li><a class="nav-link" data-bs-toggle="pill" href="#real-estate-2-tab2"><?php echo $lang['map_image']; ?></a></li>
              <?php } ?> 
              <?php if($data['map_url']){ ?>
                <li><a class="nav-link" data-bs-toggle="pill" href="#real-estate-2-tab3"><?php echo $lang['location']; ?></a></li>
              <?php } ?> 
            </ul><div class="tab-content">

              <div class="tab-pane fade show active" id="real-estate-2-tab1">
                <video width="100%" controls style="max-width: 800px; border-radius: 8px;">
                    <source src="<?php echo htmlspecialchars($video_file_path); ?>" type="video/mp4">
                    <?php echo $lang['browser_no_support_video']; ?>
                </video>
              </div><div class="tab-pane fade" id="real-estate-2-tab2">
                <img src="assets/rent_place/<?php echo $data['map_image'];?>" alt="" class="img-fluid">
              </div><div class="tab-pane fade" id="real-estate-2-tab3">
              <iframe style="border:0; width: 100%; height: 400px;" src="<?php echo $data['map_url'];?>" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
              </div></div></div>

          <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="portfolio-info">
              <h3><?php echo $lang['quick_summary']; ?></h3>
              <ul>
                <li><strong><?php echo $lang['price']; ?>:</strong><?php 
                if($data['price']=='0.00'){
                  echo $lang['price_negotiable'];
                }else{
                  echo number_format($data['price']).' ฿'; 
                }
                
                
                ?> </li>
                <li><strong><?php echo $lang['property_id']; ?>:</strong><?php echo $data['id']; ?></li>
                <li><strong><?php echo $lang['location']; ?>:</strong><?php echo $data['address'] . '<br>' . $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></li>
                <li><strong><?php echo $lang['property_type']; ?>:</strong><?php echo $data['property_type']; ?></li>
                <li><strong><?php echo $lang['area']; ?>:</strong> <span><?php echo $data['size']; ?> m <sup>2</sup></span></li>
                <li><strong><?php echo $lang['beds']; ?>:</strong><?php echo $data['room_qty']; ?></li>
                <li><strong><?php echo $lang['baths']; ?>:</strong><?php echo $data['toilet_qty']; ?></li>
              </ul>
            </div>
          </div>

        </div>

      </div>

    </section><?php if (!empty($points)){ ?> 
    <section id="services" class="services section">
      <div class="container">
        <div class="row" style="row-gap: 0 !important;">
            <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
            <h2><?php echo $lang['highlights']; ?></h2>
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
                </div><?php endforeach; ?>
        </div>
      </div>
    </section><?php } ?>

    <?php if (!empty($facilities)){ ?> 
      <section id="facilities" class="services section">
        <div class="container">
          <div class="row" style="row-gap: 0 !important;">
              <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
              <h2><?php echo $lang['facilities']; ?></h2>
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
                  </div><?php endforeach; ?>
          </div>
        </div>
      </section><?php } ?>
    <?php if (!empty($landmarks)){ ?> 
      <section id="facilities" class="services section" style="row-gap: 0 !important;">
        <div class="container">
          <div class="row" style="row-gap: 0 !important;">
          
              <div class="container section-title" data-aos="fade-up" style="margin-bottom: 0px;">
              <h2><?php echo $lang['landmarks']; ?></h2>
              </div>
              <?php foreach ($landmarks as $index => $item): ?>
                  <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                      <div class="service-item  position-relative" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                      <div class="icon">
                      <i class="<?php echo $item['icon'];?>"></i>
                      </div>
                      <h3><?php echo $item['name'];?></h3>
                      </div>
                  </div><?php endforeach; ?>
              

          </div>

        </div>

      </section><?php } ?>

    <?php include 'footer.php'; ?>

</html>