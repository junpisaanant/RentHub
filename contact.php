<!DOCTYPE html>
<html lang="en">
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<body class="contact-page">

<?php 
$mode = 'contect';
include 'header.php'; 

//ดึงข้อมูลมาแสดงในหน้าจอ
include 'db.php'; // เชื่อมต่อฐานข้อมูลด้วย mysqli

$id = $_REQUEST['rent_place_id'];
//Query ดึงข้อมูลของห้องเช่านี้
$sql = "SELECT RP.id, RP.name
, RP.price, RP.size, RP.room_qty, RP.toilet_qty, RP.description
, RP.address,P.name AS province_name , D.name AS district_name, SD.name AS sub_district_name
, CONCAT(RU.firstname, ' ', RU.lastname) AS fullname, RU.phone_no, RP.map_url
, RU.email, RU.line_id
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

// คิวรี่วันนัดหมายที่ถูกจองไว้แล้ว
$sql = "SELECT date FROM RENT_PLACE_APPOINTMENT WHERE rent_place_id = ? AND status IN ('A', 'W', 'T', 'O')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$bookedDates = [];
while ($row = $result->fetch_assoc()) {
    $bookedDates[] = $row['date'];
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const booked = <?php echo json_encode($bookedDates); ?>;

  flatpickr("#appointment_date", {
    dateFormat: "Y-m-d",
    disable: booked,
    minDate: "today"
  });

  flatpickr("#in_date", {
    dateFormat: "Y-m-d",
    minDate: "today"
  });
  document.getElementById("contactForm").addEventListener("submit", function(e) {
    const date = document.getElementById("appointment_date").value;
    if (!date) {
      alert("กรุณาระบุวันที่นัดหมายด้วยค่ะ");
      e.preventDefault(); // ยกเลิกการส่ง
    }
  });
});
</script>
  <main class="main">

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="mb-4" data-aos="fade-up" data-aos-delay="200">
          <iframe style="border:0; width: 100%; height: 270px;" src="<?php echo $data['map_url'];?>" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div><!-- End Google Maps -->

        <div class="row gy-4">

          <div class="col-lg-4">
            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
              <i class="bi bi-geo-alt flex-shrink-0"></i>
              <div>
                <h3>Address</h3>
                <p><?php echo $data['address'] . '<br>' . $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></p>
              </div>
            </div><!-- End Info Item -->

            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
              <i class="bi bi-telephone flex-shrink-0"></i>
              <div>
                <h3>Call Us</h3>
                <p><?php echo $data['phone_no'];?></p>
              </div>
            </div><!-- End Info Item -->

            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="500">
              <i class="bi bi-envelope flex-shrink-0"></i>
              <div>
                <h3>Email Us</h3>
                <p><?php echo $data['email'];?></p>
              </div>
            </div><!-- End Info Item -->

            <?php if($data['line_id']){ ?>
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="500">
                <i class="fab fa-line flex-shrink-0"></i>
                <div>
                  <h3>Line id</h3>
                  <p><?php echo $data['line_id'];?></p>
                </div>
              </div><!-- End Info Item -->
            <?php } ?>

          </div>

          <div class="col-lg-8">
            <form id="contactForm" action="forms/contact.php" method="post" class="php-form" data-aos="fade-up" data-aos-delay="200">
              <input type="hidden" name="rent_place_id" value="<?php echo $id; ?>">
              <input type="hidden" name="price" value="<?php echo $data['price']; ?>">
              <div class="row gy-4">
                <div class="col-md-6">
                  <label for="appointment_date">วันที่นัดหมาย <span style="color:red;">*</span></label>
                  <input type="text" id="appointment_date" name="appointment_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                  <label for="in_date">วันที่ต้องการเข้าพัก</label>
                  <input type="text" id="in_date" name="in_date" class="form-control">
                </div>

                <div class="col-md-12">
                  <textarea class="form-control" name="remark" rows="6" placeholder="Message" ></textarea>
                </div>

                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>

                  <button type="submit" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                    บันทึกนัดหมาย
                  </button>
                </div>

              </div>
            </form>
          </div><!-- End Contact Form -->

        </div>

      </div>

    </section><!-- /Contact Section -->

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