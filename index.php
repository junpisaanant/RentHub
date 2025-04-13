<!DOCTYPE html>
<html lang="en">

<body class="index-page">
<link rel="stylesheet" type="text/css" href="assets/css/search.css">
<?php 
$mode = 'home';
include 'header.php'; 
?>
  <main class="main">
    <!--Search -->
    <section id="search" class="services section">
      <div class="search-bar">
        <div class="search-input">
            <button class="search-icon-button" id="searchButton"><i class="bi bi-search"></i></button>
            <input type="text" placeholder="ค้นหา ชื่ออสังหา" id="searchInput">
            <button class="clear-button">x</button>
        </div>
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
        <div class="search-results-info">
        </div>
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
                    <select class="form-select">
                        <option value="0">ทั้งหมด</option>
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
                        <input type="number" class="form-control" placeholder="ต่ำสุด"> - <input type="number" class="form-control" placeholder="สูงสุด">
                    </div>
                </div>
                <div class="filter-group">
                    <label>ห้องนอน:</label>
                    <select class="form-select">
                        <option value="0">ทั้งหมด</option>
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
                        <input type="number" class="form-control" placeholder="ต่ำสุด"> - <input type="number" class="form-control" placeholder="สูงสุด">
                    </div>
                </div>
                <div class="filter-group">
                    <label>ระยะทางจากสถานีไฟฟ้า:</label>
                    <select class="form-select">
                        <option>ห่างเท่าใดก็ได้</option>
                        <option>น้อยกว่า 500 เมตร (เดิน 5-7 นาที)</option>
                        <option>น้อยกว่า 1 กิโลเมตร (เดิน 10-15 นาที)</option>
                        <option>น้อยกว่า 1.5 กิโลเมตร (เดิน 15-20 นาที)</option>
                        <option>น้อยกว่า 2 กิโลเมตร (เดิน 20-25 นาที)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>ราคาต่อตร.ม.:</label>
                    <div class="price-per-sqm-range">
                        <input type="number" class="form-control" placeholder="ต่ำสุด"> - <input type="number" class="form-control" placeholder="สูงสุด">
                    </div>
                </div>
                <div class="filter-group">
                    <label>ห้องน้ำ:</label>
                    <select class="form-select">
                        <option value="0">ทั้งหมด</option>
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

    <!-- Services Section -->
    <section id="services" class="services section">

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item  position-relative">
              <div class="icon">
                <i class="bi bi-activity"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Nesciunt Mete</h3>
              </a>
              <p>Provident nihil minus qui consequatur non omnis maiores. Eos accusantium minus dolores iure perferendis tempore et consequatur.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-broadcast"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Eosle Commodi</h3>
              </a>
              <p>Ut autem aut autem non a. Sint sint sit facilis nam iusto sint. Libero corrupti neque eum hic non ut nesciunt dolorem.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-easel"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Ledo Markt</h3>
              </a>
              <p>Ut excepturi voluptatem nisi sed. Quidem fuga consequatur. Minus ea aut. Vel qui id voluptas adipisci eos earum corrupti.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-bounding-box-circles"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Asperiores Commodit</h3>
              </a>
              <p>Non et temporibus minus omnis sed dolor esse consequatur. Cupiditate sed error ea fuga sit provident adipisci neque.</p>
              <a href="service-details.html" class="stretched-link"></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-calendar4-week"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Velit Doloremque</h3>
              </a>
              <p>Cumque et suscipit saepe. Est maiores autem enim facilis ut aut ipsam corporis aut. Sed animi at autem alias eius labore.</p>
              <a href="service-details.html" class="stretched-link"></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-chat-square-text"></i>
              </div>
              <a href="service-details.html" class="stretched-link">
                <h3>Dolori Architecto</h3>
              </a>
              <p>Hic molestias ea quibusdam eos. Fugiat enim doloremque aut neque non et debitis iure. Corrupti recusandae ducimus enim.</p>
              <a href="service-details.html" class="stretched-link"></a>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>

    </section><!-- /Services Section -->

  </main>

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
  <script src="assets/js/search.js"></script>

</body>

</html>