<?php
session_start();
include 'db.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// --- Language Setup ---
$lang_path = 'languages/';
$lang_fall = 'th'; // Default language
$lang_list = ['th', 'en', 'cn']; // Supported languages

// Set language from session or query parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], $lang_list)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang_use = $_SESSION['lang'] ?? $lang_fall;
include $lang_path . $lang_use . '.php';


include 'header.php'; // ส่วนหัวของเว็บ

// ตรวจสอบสิทธิ์การเข้าถึงของผู้ใช้
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// --- จัดการตัวกรองวันที่ ---
// ถ้าไม่มีการส่งค่ามา ให้ใช้เป็นวันแรกของเดือนปัจจุบัน ถึงวันปัจจุบัน
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');


// --- ดึงข้อมูลสรุปจากฐานข้อมูล (ตามช่วงวันที่ที่เลือก) ---

// 1. รายได้รวมทั้งหมด (ตามช่วงวันที่)
$stmt_total = $conn->prepare("SELECT SUM(t.PRICE) AS total_revenue FROM RENT_PLACE_APPOINTMENT t JOIN RENT_PLACE rp ON t.RENT_PLACE_ID = rp.ID WHERE rp.USER_ID = ? AND t.TRANSFER_DATE BETWEEN ? AND ?");
$stmt_total->bind_param("iss", $admin_id, $start_date, $end_date);
$stmt_total->execute();
$result_total = $stmt_total->get_result()->fetch_assoc();
$total_revenue = $result_total['total_revenue'] ?? 0;

// 2. รายได้เดือนปัจจุบัน (ยังคงแสดงของเดือนปัจจุบันเสมอ ไม่เกี่ยวกับตัวกรอง)
$stmt_monthly = $conn->prepare("SELECT SUM(t.PRICE) AS monthly_revenue FROM RENT_PLACE_APPOINTMENT t JOIN RENT_PLACE rp ON t.RENT_PLACE_ID = rp.ID WHERE rp.USER_ID = ? AND MONTH(t.TRANSFER_DATE) = MONTH(CURDATE()) AND YEAR(t.TRANSFER_DATE) = YEAR(CURDATE())");
$stmt_monthly->bind_param("i", $admin_id);
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result()->fetch_assoc();
$monthly_revenue = $result_monthly['monthly_revenue'] ?? 0;

// 3. จำนวนการเช่าใหม่ (ตามช่วงวันที่)
$stmt_new_rentals = $conn->prepare("SELECT COUNT(t.ID) AS new_rentals FROM RENT_PLACE_APPOINTMENT t JOIN RENT_PLACE rp ON t.RENT_PLACE_ID = rp.ID WHERE rp.USER_ID = ? AND t.TRANSFER_DATE BETWEEN ? AND ?");
$stmt_new_rentals->bind_param("iss", $admin_id, $start_date, $end_date);
$stmt_new_rentals->execute();
$result_new_rentals = $stmt_new_rentals->get_result()->fetch_assoc();
$new_rentals = $result_new_rentals['new_rentals'] ?? 0;

// 4. จำนวนห้องว่างทั้งหมด (ไม่เกี่ยวกับวันที่)
$stmt_available = $conn->prepare("SELECT COUNT(ID) AS available_rooms FROM RENT_PLACE WHERE USER_ID = ? AND STATUS = 'E'");
$stmt_available->bind_param("i", $admin_id);
$stmt_available->execute();
$result_available = $stmt_available->get_result()->fetch_assoc();
$available_rooms = $result_available['available_rooms'] ?? 0;


// --- ดึงข้อมูลสำหรับกราฟ (ตามช่วงวันที่ที่เลือก) ---
$chart_labels = [];
$chart_data = [];
$months = [
    '1' => $lang['jan'], '2' => $lang['feb'], '3' => $lang['mar'], '4' => $lang['apr'],
    '5' => $lang['may'], '6' => $lang['jun'], '7' => $lang['jul'], '8' => $lang['aug'],
    '9' => $lang['sep'], '10' => $lang['oct'], '11' => $lang['nov'], '12' => $lang['dec']
];

// สร้าง array ของเดือนทั้งหมดในช่วงวันที่ที่เลือกให้มีค่าเริ่มต้นเป็น 0
$monthly_revenues = [];
$period = new DatePeriod(
     new DateTime($start_date),
     new DateInterval('P1M'),
     (new DateTime($end_date))->modify('+1 month') // เพิ่ม 1 เดือนเพื่อให้ครอบคลุมเดือนสุดท้าย
);

foreach ($period as $date) {
    $month_key = $date->format('Y-m');
    $year_display = ($lang_use === 'th') ? ($date->format('Y') + 543) : $date->format('Y');
    $chart_labels[] = $months[$date->format('n')] . " " . $year_display;
    $monthly_revenues[$month_key] = 0;
}

$stmt_chart = $conn->prepare("
    SELECT
        DATE_FORMAT(t.TRANSFER_DATE, '%Y-%m') AS month,
        SUM(t.PRICE) AS monthly_sum
    FROM RENT_PLACE_APPOINTMENT t
    JOIN RENT_PLACE rp ON t.RENT_PLACE_ID = rp.ID
    WHERE rp.USER_ID = ? AND t.TRANSFER_DATE BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(t.TRANSFER_DATE, '%Y-%m')
    ORDER BY month ASC
");
$stmt_chart->bind_param("iss", $admin_id, $start_date, $end_date);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

while ($row = $result_chart->fetch_assoc()) {
    if (isset($monthly_revenues[$row['month']])) {
        $monthly_revenues[$row['month']] = (float)$row['monthly_sum'];
    }
}

$chart_data = array_values($monthly_revenues);

?>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        main { background-color: #f4f7f6; }
        .dashboard-card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.2s; }
        .dashboard-card:hover { transform: translateY(-5px); }
        .dashboard-card .card-body { display: flex; align-items: center; }
        .dashboard-card .card-title { font-size: 1rem; color: #6c757d; margin-bottom: 0.5rem; }
        .dashboard-card .card-text { font-size: 2rem; font-weight: 700; color: #343a40; }
        .dashboard-card .icon { font-size: 3rem; opacity: 0.3; margin-right: 1.5rem; }
        .chart-container { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .filter-form { background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>

<main id="main">
    <section class="container py-5">
        <div class="section-title mb-4">
            <h2><?php echo $lang['dashboard_title']; ?></h2>
            <p><?php echo $lang['dashboard_subtitle']; ?></p>
        </div>

        <div class="filter-form mb-5">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label"><?php echo $lang['start_date']; ?></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label"><?php echo $lang['end_date']; ?></label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-4 d-flex">
                    <button type="submit" class="btn btn-primary me-2 flex-grow-1"><i class="bi bi-funnel-fill"></i> <?php echo $lang['filter']; ?></button>
                    <a href="export_excel.php?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" class="btn btn-success flex-grow-1">
                        <i class="bi bi-file-earmark-excel-fill"></i> <?php echo $lang['export_excel']; ?>
                    </a>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <i class="bi bi-cash-stack icon text-success"></i>
                        <div>
                            <h5 class="card-title"><?php echo $lang['total_revenue_selected']; ?></h5>
                            <p class="card-text">฿<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <i class="bi bi-calendar-check icon text-primary"></i>
                        <div>
                            <h5 class="card-title"><?php echo $lang['this_month_revenue']; ?></h5>
                            <p class="card-text">฿<?php echo number_format($monthly_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <i class="bi bi-person-plus icon text-info"></i>
                        <div>
                            <h5 class="card-title"><?php echo $lang['new_rentals_selected']; ?></h5>
                            <p class="card-text"><?php echo $new_rentals; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <i class="bi bi-door-open icon text-warning"></i>
                        <div>
                            <h5 class="card-title"><?php echo $lang['available_rooms']; ?></h5>
                            <p class="card-text"><?php echo $available_rooms; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="incomeChart"></canvas>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartLabels = <?php echo json_encode($chart_labels); ?>;
    const chartData = <?php echo json_encode($chart_data); ?>;

    const ctx = document.getElementById('incomeChart').getContext('2d');
    const incomeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: '<?php echo $lang['income_baht']; ?>',
                data: chartData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { callback: value => '฿' + value.toLocaleString() } } },
            plugins: {
                title: { display: true, text: '<?php echo $lang['monthly_income_chart']; ?>', font: { size: 18, family: 'Sarabun' } },
                tooltip: {
                    callbacks: {
                        label: context => {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>