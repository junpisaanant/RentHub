<?php
include 'db.php';
include 'header.php'; // To get the general page structure and styles

// Get appointment ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Error: Invalid Appointment ID.');
}
$apptId = $_GET['id'];

// Fetch appointment details to display
$stmt = $conn->prepare("
    SELECT p.name, pa.price AS total_price, pa.date AS appointment_date
    FROM RENT_PLACE_APPOINTMENT pa
    JOIN RENT_PLACE p ON pa.rent_place_id = p.id
    WHERE pa.id = ? AND pa.status = 'A' -- Only show for approved appointments
");
$stmt->bind_param("i", $apptId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Error: Appointment not found or not in a valid state for payment.');
}
$appointment = $result->fetch_assoc();
$stmt->close();
?>
<style>
  /* สไตล์สำหรับปุ่มส่งข้อมูลโดยเฉพาะ */
  #payment-form-button {
    font-family: var(--font-primary);
    font-weight: 500;
    font-size: 16px;
    letter-spacing: 1px;
    display: inline-block;
    padding: 12px 40px;
    border-radius: 50px;
    transition: 0.5s;
    margin: 10px;
    color: #fff;
    background: var(--color-primary); /* ใช้สีหลักของธีม */
    border: none; /* ลบเส้นขอบเดิม */
  }

  /* สไตล์เมื่อนำเมาส์ไปวางบนปุ่ม */
  #payment-form-button:hover {
    background: rgba(var(--color-primary-rgb), 0.8);
  }
</style>
<main id="main">

    <div class="breadcrumbs d-flex align-items-center" style="background-image: url('assets/img/hero-carousel/hero-carousel-1.jpg');">
        <div class="container position-relative d-flex flex-column align-items-center" data-aos="fade">
            <h2>Upload Payment & Documents</h2>
            <ol>
                <li><a href="index.php">Home</a></li>
                <li>Upload</li>
            </ol>
        </div>
    </div><section id="contact" class="contact">
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row gy-4">
                <div class="col-lg-6">
                    <div class="info-item d-flex flex-column justify-content-center align-items-center">
                        <h3>Appointment Details</h3>
                        <p><strong>Property:</strong> <?php echo htmlspecialchars($appointment['name']); ?></p>
                        <p><strong>Total Price:</strong> <?php echo number_format($appointment['total_price'], 2); ?> THB</p>
                        <p><strong>Appointment Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                        <p>Please upload your payment proof and identification to finalize the rental process.</p>
                    </div>
                </div>

                <div class="col-lg-6">
                    <form action="forms/update_payment_public.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="appointment_id" value="<?php echo $apptId; ?>">
                        <div class="row gy-4">
                            <div class="col-md-12">
                                <label for="transfer_date">Transfer Date</label>
                                <input type="date" name="transfer_date" id="transfer_date" class="form-control" required>
                            </div>

                            <div class="col-md-12">
                                <label for="payment_proof">Payment Proof (Required)</label>
                                <input type="file" class="form-control" name="payment_proof" id="payment_proof" required accept="image/*,application/pdf">
                            </div>

                            <hr>
                            <p class="text-center">Please attach a copy of your ID card or Passport.</p>

                            <div class="col-md-12">
                                <label for="id_card_proof">ID Card</label>
                                <input type="file" class="form-control" name="id_card_proof" id="id_card_proof" accept="image/*,application/pdf">
                            </div>

                            <div class="col-md-12">
                                <label for="passport_proof">Passport</label>
                                <input type="file" class="form-control" name="passport_proof" id="passport_proof" accept="image/*,application/pdf">
                            </div>

                            <div class="col-md-12 text-center">
                            <button type="submit" class="btn-get-started">Submit Documents</button>
                            </div>

                        </div>
                    </form>
                </div></div>

        </div>
    </section></main><?php include 'footer.php'; ?>