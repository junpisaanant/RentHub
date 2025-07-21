<!DOCTYPE html>
<html lang="en">



<?php 
$mode = 'home';
include 'header.php'; 
?>
<style>
    /* --- ปะทับ main.css ตามข้อด้านบน --- */
    /* --- override main.css --- */
#hero.section {
  height: auto !important;
  padding: 0 !important;
  min-height: 0 !important;
}
#hero.section img {
  width: 100%;
  height: auto;
  display: block;
}

/* --- ปรับให้ search-input ยืดหยุ่น --- */
.search-bar .search-input {
  width: 100%;
  max-width: 600px;
  margin: 20px auto;
  padding: 10px 15px;
  border: 2px solid #b08b5b;
  border-radius: 8px;
  background: #fff;
  display: flex;
  gap: 5px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: transform .3s;
}
@media (max-width: 576px) {
  .search-bar .search-input {
    margin: 20px 10px;
  }
}
.search-bar .search-input:hover {
  transform: translateY(-5px);
}
.search-bar input {
  flex: 1;
  border: none;
  outline: none;
} 
</style>
  <main class="main">
    <!-- Hero Section -->
    <section id="hero">
      <img src="assets/img/theprestige-2.png"
           class="img-fluid w-100" alt="The Prestige Living">
    </section>

    <!-- Search -->
    <section id="search" class="services section mb-5">
      <div class="search-bar">
        <div class="search-input">
          <button id="searchButton" class="btn"><i class="bi bi-search"></i></button>
          <input id="searchInput" type="text" placeholder="ค้นหา ชื่ออสังหา">
          <button class="clear-button btn">x</button>
          <button id="filterButton" class="btn filter-button"
                  data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="bi bi-funnel"></i> ตัวกรอง
          </button>
        </div>
      </div>
    </section>
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
    <section id="services" class="py-5">
      <div class="container">
        <div class="row gy-4 search-results-info" data-aos="fade-up" data-aos-delay="100">
          <!-- JS จะ inject
               <div class="col-12 col-md-6 col-lg-4">…card…</div>
               ลงที่นี่อัตโนมัติ -->
        </div>
      </div>
    </section>

  </main>

  <?php include 'footer.php'; ?>
  <script src="assets/js/search.js"></script>

</html>