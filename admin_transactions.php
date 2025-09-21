<?php
ob_start(); // Start output buffering
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล
include 'header.php'; // ส่วนหัวของเว็บ (สันนิษฐานว่ามีการตั้งค่า $lang ที่นี่)

// --- [ย้ายมาไว้บนสุด] จัดการการอัปเดตสถานะ (เมื่อมีการส่งฟอร์ม) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $admin_id = $_SESSION['user_id'];
    $current_user_name = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];

    // Security check
    $check_sql = "SELECT rp.user_id FROM RENT_PLACE_APPOINTMENT t JOIN RENT_PLACE rp ON t.rent_place_id = rp.id WHERE t.id = ? AND rp.user_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $appointment_id, $admin_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $update_sql = "UPDATE RENT_PLACE_APPOINTMENT SET status = ?, update_user = ?, update_datetime = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ssi", $new_status, $current_user_name, $appointment_id);
        if ($stmt_update->execute()) {
            $_SESSION['message'] = $lang['update_status_success'] . $appointment_id;
        } else {
            $_SESSION['error'] = $lang['update_status_error'];
        }
    } else {
        $_SESSION['error'] = $lang['no_permission'];
    }

    $query_string = http_build_query($_GET);
    header("Location: admin_transactions.php?" . $query_string);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// --- Language-aware fields ---
$lang_suffix = '';
if ($_SESSION['lang'] == 'en') {
    $lang_suffix = '_en';
} elseif ($_SESSION['lang'] == 'cn') {
    $lang_suffix = '_cn';
}

$property_name_field = "NAME" . $lang_suffix;


// --- จัดการตัวกรอง ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$rent_place_id = $_GET['rent_place_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

// --- ดึงข้อมูลสินทรัพย์ทั้งหมดของ Admin สำหรับ Dropdown ---
$properties = [];
$stmt_properties = $conn->prepare("SELECT ID, {$property_name_field} AS NAME FROM RENT_PLACE WHERE USER_ID = ? ORDER BY NAME ASC");
$stmt_properties->bind_param("i", $admin_id);
$stmt_properties->execute();
$result_properties = $stmt_properties->get_result();
while ($row = $result_properties->fetch_assoc()) {
    $properties[] = $row;
}

// --- สร้าง SQL Query แบบไดนามิกตามตัวกรอง ---
$sql = "
    SELECT 
        t.id,
        t.date AS appointment_date,
        t.in_date,
        t.transfer_date,
        t.price,
        t.status,
        t.attach_id,
        t.rent_user_id,
        rp.{$property_name_field} AS place_name,
        u.firstname,
        u.lastname
    FROM RENT_PLACE_APPOINTMENT t
    JOIN RENT_PLACE rp ON t.rent_place_id = rp.id
    JOIN RENT_USER u ON t.rent_user_id = u.id 
    WHERE rp.user_id = ?
";

$params = [$admin_id];
$types = 'i';

if (!empty($start_date)) { $sql .= " AND t.date >= ?"; $params[] = $start_date; $types .= 's'; }
if (!empty($end_date)) { $sql .= " AND t.date <= ?"; $params[] = $end_date; $types .= 's'; }
if (!empty($rent_place_id)) { $sql .= " AND t.rent_place_id = ?"; $params[] = $rent_place_id; $types .= 'i'; }
if (!empty($filter_status)) { $sql .= " AND t.status = ?"; $params[] = $filter_status; $types .= 's'; }

$sql .= " ORDER BY t.date DESC, t.id DESC";

$stmt_transactions = $conn->prepare($sql);
$transactions_result = null;
if ($stmt_transactions) {
    if (!empty($types)) {
        $stmt_transactions->bind_param($types, ...$params);
    }
    $stmt_transactions->execute();
    $transactions_result = $stmt_transactions->get_result();
}

// ฟังก์ชันสำหรับแปลงรหัสสถานะเป็นข้อความที่อ่านง่าย
function getStatusText($status, $lang) {
    $statusMap = [
        'A' => ['text' => $lang['status_appointed'], 'class' => 'primary'],
        'C' => ['text' => $lang['status_cancelled'], 'class' => 'secondary'],
        'D' => ['text' => $lang['status_no_show'], 'class' => 'dark'],
        'W' => ['text' => $lang['status_waiting_payment'], 'class' => 'warning'],
        'T' => ['text' => $lang['status_waiting_verification'], 'class' => 'info'],
        'O' => ['text' => $lang['status_paid'], 'class' => 'success']
    ];
    return $statusMap[$status] ?? ['text' => $lang['status_unknown'], 'class' => 'light'];
}
$all_statuses = ['A', 'C', 'D', 'W', 'T', 'O'];

?>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        main { background-color: #f4f7f6; }
        .filter-card, .table-card { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .table thead th { background-color: #f8f9fa; white-space: nowrap; }
        .badge { font-size: 0.8rem; padding: 0.4em 0.7em; }
        .action-buttons .btn, .action-buttons .d-inline { margin: 2px 0; }
        .renter-link { cursor: pointer; text-decoration: underline; color: #0d6efd; }
        .modal-body dt { font-weight: 700; color: #555; }
        .modal-body dd { margin-left: 0; padding-left: 1rem; border-left: 3px solid #eee; }
        #lineQrCode { max-width: 250px; height: auto; margin: 1rem auto; display: block; }
        .doc-thumbnail { width: 100px; height: auto; cursor: pointer; border: 1px solid #ddd; padding: 2px; border-radius: 4px; transition: box-shadow 0.2s; }
        .doc-thumbnail:hover { box-shadow: 0 0 8px rgba(0,0,0,0.2); }
    </style>
</head>

<main id="main">
    <section class="container py-5">
        <div class="section-title mb-4">
            <h2><?php echo $lang['manage_appointments_title']; ?></h2>
            <p><?php echo $lang['manage_appointments_subtitle']; ?></p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="filter-card mb-5">
            <form method="GET" action="admin_transactions.php" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label for="rent_place_id" class="form-label"><?php echo $lang['property']; ?></label>
                    <select class="form-select" id="rent_place_id" name="rent_place_id">
                        <option value=""><?php echo $lang['all_properties']; ?></option>
                        <?php foreach ($properties as $prop): ?>
                        <option value="<?php echo $prop['ID']; ?>" <?php echo ($rent_place_id == $prop['ID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prop['NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="status" class="form-label"><?php echo $lang['status']; ?></label>
                    <select class="form-select" id="status" name="status">
                        <option value=""><?php echo $lang['all_statuses']; ?></option>
                        <?php foreach ($all_statuses as $status_code): $statusInfo = getStatusText($status_code, $lang); ?>
                        <option value="<?php echo $status_code; ?>" <?php echo ($filter_status == $status_code) ? 'selected' : ''; ?>><?php echo $statusInfo['text']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4"><label for="start_date" class="form-label"><?php echo $lang['appointment_date_range']; ?></label><input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"></div>
                <div class="col-lg-2 col-md-4"><label for="end_date" class="form-label"><?php echo $lang['to_date']; ?></label><input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"></div>
                <div class="col-lg-3 col-md-4 d-flex"><button type="submit" class="btn btn-primary w-100 me-2"><i class="bi bi-funnel-fill"></i> <?php echo $lang['filter_button']; ?></button><a href="export_transactions.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success w-100"><i class="bi bi-file-earmark-excel"></i> <?php echo $lang['export_button']; ?></a></div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?php echo $lang['id']; ?></th>
                            <th><?php echo $lang['property']; ?></th>
                            <th><?php echo $lang['renter']; ?></th>
                            <th><?php echo $lang['appointment_date']; ?></th>
                            <th><?php echo $lang['move_in_date']; ?></th>
                            <th><?php echo $lang['payment_date']; ?></th>
                            <th class="text-center"><?php echo $lang['status']; ?></th>
                            <th class="text-center"><?php echo $lang['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions_result && $transactions_result->num_rows > 0): while ($row = $transactions_result->fetch_assoc()): $statusInfo = getStatusText($row['status'], $lang); ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['place_name']); ?></td>
                            <td><a href="#" class="renter-link" data-bs-toggle="modal" data-bs-target="#userDetailModal" data-userid="<?php echo $row['rent_user_id']; ?>"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></a></td>
                            <td><?php echo date("d/m/Y", strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo $row['in_date'] ? date("d/m/Y", strtotime($row['in_date'])) : '-'; ?></td>
                            <td><?php echo $row['transfer_date'] ? date("d/m/Y", strtotime($row['transfer_date'])) : '-'; ?></td>
                            <td class="text-center"><span class="badge bg-<?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['text']; ?></span></td>
                            <td class="text-center action-buttons">
                                <?php if ($row['status'] == 'A'): ?>
                                    <form method="POST" class="d-inline"><input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="new_status" value="W"><button type="submit" name="update_status" class="btn btn-sm btn-primary" onclick="return confirm('<?php echo $lang['confirm_agreement']; ?>')"><?php echo $lang['agree_to_rent']; ?></button></form>
                                    <form method="POST" class="d-inline"><input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="new_status" value="C"><button type="submit" name="update_status" class="btn btn-sm btn-secondary" onclick="return confirm('<?php echo $lang['confirm_disagreement']; ?>')"><?php echo $lang['disagree_to_rent']; ?></button></form>
                                    <form method="POST" class="d-inline"><input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="new_status" value="D"><button type="submit" name="update_status" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo $lang['confirm_no_show']; ?>')"><?php echo $lang['no_show']; ?></button></form>
                                <?php elseif ($row['status'] == 'T'): ?>
                                     <form method="POST" class="d-inline"><input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="new_status" value="O"><button type="submit" name="update_status" class="btn btn-sm btn-success" onclick="return confirm('<?php echo $lang['confirm_payment_verification']; ?>')"><?php echo $lang['verify_payment']; ?></button></form>
                                    <?php if (!empty($row['attach_id'])): ?><a href="view_attachment.php?id=<?php echo $row['attach_id']; ?>" class="btn btn-sm btn-info" target="_blank"><?php echo $lang['view_document']; ?></a><?php endif; ?>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4"><?php echo $lang['no_data_found']; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userDetailModalLabel"><?php echo $lang['renter_information']; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="userDetailModalBody">
        <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['close_button']; ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="lineQrModal" tabindex="-1" aria-labelledby="lineQrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lineQrModalLabel">Line QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="lineQrCode" alt="Line QR Code">
        <p class="mt-2"><?php echo $lang['scan_qr_line']; ?></p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="imageEnlargeModal" tabindex="-1" aria-labelledby="imageEnlargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
       <div class="modal-header">
         <h5 class="modal-title" id="imageEnlargeModalLabel"><?php echo $lang['view_image']; ?></h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>
      <div class="modal-body text-center">
        <img src="" id="enlargedImage" class="img-fluid" alt="Enlarged Image">
      </div>
    </div>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#rent_place_id').select2({ theme: 'bootstrap-5' });

    $('#userDetailModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userId = button.data('userid');
        var modalBody = $(this).find('.modal-body');
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        fetch('get_renter_details.php?user_id=' + userId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.html('<p class="text-danger">' + data.error + '</p>');
                } else {
                    let id_info_label = data.identification_no ? `<?php echo $lang['id_card_no']; ?>` : (data.passport_no ? `<?php echo $lang['passport_no']; ?>` : `<?php echo $lang['id_card_or_passport']; ?>`);
                    let id_info_value = data.identification_no || data.passport_no || '-';

                    let line_link = data.line_id ? `<a href="#" class="line-qr-link" data-bs-toggle="modal" data-bs-target="#lineQrModal" data-lineid="${data.line_id}">${data.line_id}</a>` : '-';
                    
                    let content = `
                        <dl class="row">
                            <dt class="col-sm-5"><?php echo $lang['full_name']; ?></dt><dd class="col-sm-7">${data.firstname} ${data.lastname}</dd>
                            <dt class="col-sm-5"><?php echo $lang['phone_number']; ?></dt><dd class="col-sm-7"><a href="tel:${data.phone_no}">${data.phone_no}</a></dd>
                            <dt class="col-sm-5">Line ID</dt><dd class="col-sm-7">${line_link}</dd>
                            <dt class="col-sm-5">${id_info_label}</dt><dd class="col-sm-7">${id_info_value}</dd>
                        </dl>
                    `;
                    
                    let docsContent = '';
                    if (data.id_card_path || data.passport_path) {
                        docsContent += `<hr><h6 class="mt-3"><?php echo $lang['attached_documents']; ?></h6><div class="row">`;
                        if (data.id_card_path) {
                             docsContent += `
                                <div class="col-md-6">
                                    <strong><?php echo $lang['id_card']; ?>:</strong><br>
                                    <img src="${data.id_card_path}" class="doc-thumbnail enlarge-image mt-1" data-bs-toggle="modal" data-bs-target="#imageEnlargeModal">
                                </div>
                             `;
                        }
                        if (data.passport_path) {
                             docsContent += `
                                <div class="col-md-6">
                                    <strong><?php echo $lang['passport']; ?>:</strong><br>
                                    <img src="${data.passport_path}" class="doc-thumbnail enlarge-image mt-1" data-bs-toggle="modal" data-bs-target="#imageEnlargeModal">
                                </div>
                             `;
                        }
                        docsContent += '</div>';
                    }
                    modalBody.html(content + docsContent);
                }
            })
            .catch(error => {
                modalBody.html('<p class="text-danger"><?php echo $lang['cannot_load_data']; ?></p>');
                console.error('Error:', error);
            });
    });

    $('#lineQrModal').on('show.bs.modal', function (event) {
        var link = $(event.relatedTarget);
        var lineId = link.data('lineid');
        var qrImg = $('#lineQrCode');
        if (lineId) {
            var lineUrl = 'https://line.me/ti/p/~' + encodeURIComponent(lineId);
            var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(lineUrl);
            qrImg.attr('src', qrApiUrl);
        }
    });

    $('#imageEnlargeModal').on('show.bs.modal', function(event) {
        var thumbnail = $(event.relatedTarget);
        var imageSource = thumbnail.attr('src');
        var modalImage = $(this).find('#enlargedImage');
        modalImage.attr('src', imageSource);
    });
});
</script>

<?php 
include 'footer.php'; 
ob_end_flush();
?>