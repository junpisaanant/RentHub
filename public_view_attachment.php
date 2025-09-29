<?php
include 'db.php';
include 'header.php';

// Get appointment ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Error: Invalid Appointment ID.');
}
$apptId = $_GET['id'];

// Fetch appointment and attachment details
$stmt = $conn->prepare("
    SELECT 
        p.name AS property_name, 
        u.name AS user_name,
        pa.status,
        pa.attach_id
    FROM RENT_PLACE_APPOINTMENT pa
    JOIN RENT_PLACE p ON pa.rent_place_id = p.id
    JOIN RENT_USER u ON pa.rent_user_id = u.id
    WHERE pa.id = ?
");
$stmt->bind_param("i", $apptId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Error: Appointment not found.');
}
$appointment = $result->fetch_assoc();
$attachId = $appointment['attach_id'];
$stmt->close();

// Fetch attached files
$files = [];
if ($attachId) {
    $stmt_files = $conn->prepare("SELECT name FROM RENT_FILE WHERE attach_id = ?");
    $stmt_files->bind_param("i", $attachId);
    $stmt_files->execute();
    $result_files = $stmt_files->get_result();
    while ($row = $result_files->fetch_assoc()) {
        $files[] = $row;
    }
    $stmt_files->close();
}

function get_file_path($filename) {
    if (strpos($filename, 'pay_') === 0) {
        return 'uploads/payments/' . $filename;
    } elseif (strpos($filename, 'id_') === 0 || strpos($filename, 'pass_') === 0) {
        return 'uploads/documents/' . $filename;
    }
    return 'uploads/' . $filename; // Fallback
}

?>
<main id="main">
    <div class="breadcrumbs d-flex align-items-center" style="background-image: url('assets/img/hero-carousel/hero-carousel-2.jpg');">
        <div class="container position-relative d-flex flex-column align-items-center" data-aos="fade">
            <h2>Review Documents</h2>
            <ol>
                <li><a href="index.php">Home</a></li>
                <li>Review</li>
            </ol>
        </div>
    </div>

    <section id="attachment-review" class="contact">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="info-item d-flex flex-column justify-content-center align-items-center">
                        <h3>Appointment #<?php echo $apptId; ?></h3>
                        <p><strong>Property:</strong> <?php echo htmlspecialchars($appointment['property_name']); ?></p>
                        <p><strong>User:</strong> <?php echo htmlspecialchars($appointment['user_name']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
                    </div>

                    <div class="php-email-form">
                        <h4>Attached Files</h4>
                        <?php if (empty($files)): ?>
                            <p>No files found for this appointment.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($files as $file): ?>
                                    <?php $filePath = get_file_path($file['name']); ?>
                                    <li><a href="<?php echo $filePath; ?>" target="_blank"><?php echo htmlspecialchars($file['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <hr>
                        <div class="text-center">
                            <?php if ($appointment['status'] == 'T'): // Show button only if status is 'Waiting for Approval' ?>
                                <form action="forms/approve_documents.php" method="POST">
                                    <input type="hidden" name="appointment_id" value="<?php echo $apptId; ?>">
                                    <button type="submit" class="btn btn-success">Confirm Documents are Correct</button>
                                </form>
                            <?php elseif ($appointment['status'] == 'A'): ?>
                                <div class="alert alert-success">This appointment has already been approved.</div>
                            <?php else: ?>
                                <div class="alert alert-info">Current status: <?php echo $appointment['status']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include 'footer.php'; ?>