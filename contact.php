<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<?php
$mode = 'contect';
include 'header.php';

//ดึงข้อมูลมาแสดงในหน้าจอ
include 'db.php'; // เชื่อมต่อฐานข้อมูลด้วย mysqli

$id = $_REQUEST['rent_place_id'];

// Language selection
$lang_sql = '';
if (isset($_SESSION['lang']) && $_SESSION['lang'] != 'th') {
    if ($_SESSION['lang'] == 'en') {
        $lang_sql = '_en';
    } else if ($_SESSION['lang'] == 'cn') {
        $lang_sql = '_cn';
    }
}

//Query ดึงข้อมูลของห้องเช่านี้
// แก้ไข: เพิ่มการเลือกฟิลด์ name, description, address ตามภาษาที่เลือก
$sql = "SELECT RP.id, 
       COALESCE(NULLIF(RP.name{$lang_sql}, ''), RP.name) AS name,
       RP.price, 
       RP.size, 
       RP.room_qty, 
       RP.toilet_qty, 
       COALESCE(NULLIF(RP.description{$lang_sql}, ''), RP.description) AS description,
       COALESCE(NULLIF(RP.address{$lang_sql}, ''), RP.address) AS address,
       COALESCE(NULLIF(P.name{$lang_sql}, ''), P.name) AS province_name, 
       COALESCE(NULLIF(D.name{$lang_sql}, ''), D.name) AS district_name, 
       COALESCE(NULLIF(SD.name{$lang_sql}, ''), SD.name) AS sub_district_name,
       CONCAT(RU.firstname, ' ', RU.lastname) AS fullname, 
       RU.phone_no, 
       RP.map_url,
       RU.email, 
       RU.line_id,
       CASE RP.type 
            WHEN 'H' THEN '{$lang['house']}'
            WHEN 'C' THEN '{$lang['condo']}'
            WHEN 'A' THEN '{$lang['apartment']}'
            WHEN 'V' THEN '{$lang['villa']}'
            WHEN 'T' THEN '{$lang['townhouse']}'
            WHEN 'L' THEN '{$lang['land']}'
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
      alert("<?php echo $lang['contact_alert_no_date']; ?>");
      e.preventDefault(); // ยกเลิกการส่ง
    }
  });
});
</script>
  <main class="main">

    <section id="contact" class="contact section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="mb-4" data-aos="fade-up" data-aos-delay="200">
          <iframe style="border:0; width: 100%; height: 270px;" src="<?php echo $data['map_url'];?>" frameborder="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div><div class="row gy-4">

          <div class="col-lg-4">
            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
              <i class="bi bi-geo-alt flex-shrink-0"></i>
              <div>
                <h3><?php echo $lang['contact_address']; ?></h3>
                <p><?php echo $data['address'] . '<br>' . $data['sub_district_name'] . ' ' . $data['district_name'] . ' ' . $data['province_name']; ?></p>
              </div>
            </div><div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
              <i class="bi bi-telephone flex-shrink-0"></i>
              <div>
                <h3><?php echo $lang['contact_call_us']; ?></h3>
                <p><?php echo $data['phone_no'];?></p>
              </div>
            </div><div class="info-item d-flex" data-aos="fade-up" data-aos-delay="500">
              <i class="bi bi-envelope flex-shrink-0"></i>
              <div>
                <h3><?php echo $lang['contact_email_us']; ?></h3>
                <p><?php echo $data['email'];?></p>
              </div>
            </div><?php if($data['line_id']){ ?>
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="500">
                <i class="fab fa-line flex-shrink-0"></i>
                <div>
                  <h3><?php echo $lang['contact_line_id']; ?></h3>
                  <p><?php echo $data['line_id'];?></p>
                </div>
              </div><?php } ?>

          </div>

          <div class="col-lg-8">
            <form id="contactForm" action="forms/contact.php" method="post" class="php-form" data-aos="fade-up" data-aos-delay="200">
              <input type="hidden" name="rent_place_id" value="<?php echo $id; ?>">
              <input type="hidden" name="price" value="<?php echo $data['price']; ?>">
              <div class="row gy-4">
                <div class="col-md-6">
                  <label for="appointment_date"><?php echo $lang['contact_appointment_date']; ?> <span style="color:red;">*</span></label>
                  <input type="text" id="appointment_date" name="appointment_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                  <label for="in_date"><?php echo $lang['contact_move_in_date']; ?></label>
                  <input type="text" id="in_date" name="in_date" class="form-control">
                </div>

                <div class="col-md-12">
                  <textarea class="form-control" name="remark" rows="6" placeholder="<?php echo $lang['contact_message_placeholder']; ?>" ></textarea>
                </div>

                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>

                  <button type="submit" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                  <?php echo $lang['contact_send_appointment']; ?>
                  </button>
                </div>

              </div>
            </form>
          </div></div>

      </div>

    </section></main>

  <?php include 'footer.php'; ?>

</html>