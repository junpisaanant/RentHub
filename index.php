<!DOCTYPE html>
<html lang="en">

<body class="index-page">
<link rel="stylesheet" type="text/css" href="assets/css/search.css">
<?php 
$mode = 'home';
include 'header.php'; 
?>
  <main class="main">
    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">

      <div id="hero-carousel" class="carousel slide" data-bs-ride="carousel" >

        <div class="carousel-item active">
          <img src="assets/img/theprestige-2.png" alt="">
        </div><!-- End Carousel Item -->

      </div>

    </section><!-- /Hero Section -->

    <!--Search -->
    <section id="search" class="services section">
    <div class="search-bar">
      <div class="search-input">
        <button class="search-icon-button" id="searchButton"><i class="bi bi-search"></i></button>
        <input type="text" placeholder="ค้นหา ชื่ออสังหา" id="searchInput">
        <button class="clear-button">x</button>
        <button class="filter-button" id="filterButton" data-bs-toggle="modal" data-bs-target="#filterModal">
          <i class="bi bi-funnel"></i> ตัวกรอง
        </button>
      </div>
    </div>
        <!-- เก็บไว้ใส่ทีหลัง 
        <div class="filter-buttons">
        <button class="filter-button" id="filterButton" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-funnel"></i> ตัวกรอง </button>
          <button class="filter-button"><i class="bi bi-house"></i> อสังหาริมทรัพย์เพื่ออยู่อาศัย</button>
          <button class="filter-button"><i class="bi bi-cash-coin"></i> ราคา</button>
          <button class="filter-button"><i class="bi bi-door-open"></i> ห้อง</button>
          <button class="filter-button"><i class="bi bi-building"></i> โครงการใหม่ <span class="new-dot"></span></button>
          <button class="filter-button"><i class="bi bi-paw"></i> ส่วนกลางเอาใจคนรักสัตว์</button>
          <button class="filter-button"><i class="bi bi-map"></i> โครงการใกล้โรงเรียน</button>
          <button class="filter-button">หรู</button>
        </div>
        -->
    </div>
  </section>

  <!-- Modal ตอนคลิกตัวกรอง -->
  <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">ตัวกรอง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="filter-group">
                    <label>ประเภทอสังหาฯ:</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">ทั้งหมด</option>
                        <option value="H">บ้าน</option>
                        <option value="C">คอนโด</option>
                        <option value="V">วิลล่า</option>
                        <option value="T">ทาวน์เฮาส์</option>
                        <option value="L">ที่ดิน</option>
                        <option value="A">อพาร์ทเม้นท์</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>ราคา:</label>
                    <div class="price-range">
                        <input type="number" class="form-control" placeholder="ต่ำสุด"  id="minPrice" name="minPrice"> - <input type="number" class="form-control" placeholder="สูงสุด" id="maxPrice" name="maxPrice">
                    </div>
                </div>
                <div class="filter-group">
                    <label>ห้องนอน:</label>
                    <select class="form-select" id="roomQty" name="roomQty">
                        <option value="">ทั้งหมด</option>
                        <option value="1">1 ห้องนอน</option>
                        <option value="2">2 ห้องนอน</option>
                        <option value="3">3 ห้องนอน</option>
                        <option value="4">4 ห้องนอน</option>
                        <option value="5">5+ ห้องนอน</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>พื้นที่ใช้สอย:</label>
                    <div class="area-range">
                        <input type="number" class="form-control" placeholder="ต่ำสุด" id="minSize" name="minSize"> - <input type="number" class="form-control" placeholder="สูงสุด" id="maxSize" name="maxSize">
                    </div>
                </div>
                <div class="filter-group">
                    <label>ระยะทางจากสถานีไฟฟ้า:</label>
                    <select class="form-select" id="distance" name="distance">
                        <option value="">ห่างเท่าใดก็ได้</option>
                        <option value="0.5">น้อยกว่า 500 เมตร (เดิน 5-7 นาที)</option>
                        <option value="1">น้อยกว่า 1 กิโลเมตร (เดิน 10-15 นาที)</option>
                        <option value="1.5">น้อยกว่า 1.5 กิโลเมตร (เดิน 15-20 นาที)</option>
                        <option value="2">น้อยกว่า 2 กิโลเมตร (เดิน 20-25 นาที)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>ห้องน้ำ:</label>
                    <select class="form-select" id="toiletQty" name="toiletQty">
                        <option value="">ทั้งหมด</option>
                        <option value="1">1 ห้องน้ำ</option>
                        <option value="2">2 ห้องน้ำ</option>
                        <option value="3">3 ห้องน้ำ</option>
                        <option value="4">4 ห้องน้ำ</option>
                        <option value="5">5+ ห้องน้ำ</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>จุดเด่น:</label>
                    <div id="rentFacilitiesCombo" class="feature-buttons">
                        </div>
                </div>

                <div class="filter-group">
                    <label>สิ่งอำนวยความสะดวก:</label>
                    <div id="rentFacilitiesFCombo" class="feature-buttons">
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary">ปรับตามเงื่อนไข</button>
            </div>
        </div>
    </div>
</div>

    <!-- สำหรับแสดงผลลัพธ์ หรือข้อมูลใน rent_place ทั้งหมด -->
    <section id="services" class="services section">

      <div class="container">

        <div class="row gy-4">
          <div class="search-results-info col-lg-12 col-md-12"  data-aos="fade-up" data-aos-delay="100">
          </div>

        </div>

      </div>

    </section><!-- /Services Section -->

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
  <script src="assets/js/search.js"></script>

</body>

</html>