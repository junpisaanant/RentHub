<?php
include('db.php');
include('header.php');

if (!isset($_GET['id'])) {
    die("No attachment ID provided.");
}
$attach_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM RENT_FILE WHERE attach_id = ?");
$stmt->bind_param("i", $attach_id);
$stmt->execute();
$result = $stmt->get_result();

function get_file_path_admin($filename) {
    if (strpos($filename, 'pay_') === 0) {
        return 'uploads/payments/' . $filename;
    } elseif (strpos($filename, 'id_') === 0 || strpos($filename, 'pass_') === 0) {
        return 'uploads/documents/' . $filename;
    }
    // Fallback for other potential files, you might need to adjust this
    return 'uploads/' . $filename;
}
?>

<main id="main">
    <div class="breadcrumbs d-flex align-items-center" style="background-image: url('assets/img/hero-carousel/hero-carousel-3.jpg');">
        <div class="container position-relative d-flex flex-column align-items-center" data-aos="fade">
            <h2>View Attachments</h2>
            <ol>
                <li><a href="admin_transactions.php">Transactions</a></li>
                <li>Attachments</li>
            </ol>
        </div>
    </div>

    <section id="view-attachments" class="contact">
        <div class="container" data-aos="fade-up">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h3 class="text-center">Files for Attachment ID: <?php echo htmlspecialchars($attach_id); ?></h3>
                    <div class="card">
                        <div class="card-body">
                            <?php if ($result->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php $filePath = get_file_path_admin($row['name']); ?>
                                        <li class="list-group-item">
                                            <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank">
                                                <i class="bi bi-file-earmark-arrow-down"></i> <?php echo htmlspecialchars($row['name']); ?>
                                            </a>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center">No files found for this attachment ID.</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center">
                             <a href="admin_transactions.php" class="btn btn-secondary">Back to Transactions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
$stmt->close();
include('footer.php');
?>