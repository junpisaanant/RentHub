<?php 
// 1. เริ่ม Session และตั้งค่าการเข้ารหัส (เหมือนเดิม)
session_start();
header('Content-Type: text/html; charset=utf-8');

// --- LANGUAGE LOGIC START ---
// 2. ส่วนจัดการการเลือกภาษา
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en', 'cn'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'th'; // ภาษาเริ่มต้นคือไทย
}

// 3. เรียกไฟล์ภาษาที่ถูกต้องเข้ามา (วิธีนี้ปลอดภัยที่สุด)
$lang_file = __DIR__ . '/languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    // กรณีฉุกเฉิน ถ้าหาไฟล์ไม่เจอ ให้ใช้ภาษาอังกฤษเป็นค่าสำรอง ป้องกันหน้าขาว
    $lang = [
        'home' => 'Home', 'login' => 'Log In', 'logout' => 'Log Out', 'profile_info' => 'Profile',
        'payment_verification' => 'Payment Verification', 'my_assets' => 'My Assets',
        'manage_appointments' => 'Manage Appointments', 'dashboard' => 'Dashboard', 'manage_assets' => 'Manage Assets'
    ];
}

// 4. ฟังก์ชันสร้าง URL เปลี่ยนภาษาอัจฉริยะ (เก็บพารามิเตอร์เดิมไว้ทั้งหมด)
function get_lang_url($lang_code) {
    $params = $_GET; // ดึงพารามิเตอร์ทั้งหมดใน URL ปัจจุบัน
    $params['lang'] = $lang_code; // เพิ่ม/ทับค่า lang
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}
// --- LANGUAGE LOGIC END ---

// 5. โค้ดส่วนจัดการข้อมูลเดิมของคุณ (เหมือนเดิมทุกประการ)
if (isset($_REQUEST['rent_id'])) {
    $rent_id = $_REQUEST['rent_id'];
    $rent_name = $_REQUEST['rent_name'];
} else {
    $rent_id = '';
    $rent_name = '';
}
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $firstname = $_SESSION['firstname'];
    $lastname = $_SESSION['lastname'];
    $fullname = $firstname.' ' .$lastname;
}
if(!$rent_name){
    $rent_name = "The Prestige Living";
}
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>The Prestige Living</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/favicon.png" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/search.css">
    <link href="assets/css/profile-dashboard.css" rel="stylesheet">
</head>

<body class="index-page">
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

        <a href="index.php?rent_name=<?php echo $rent_name; ?>" class="logo d-flex align-items-center">
            <h1 class="sitename"><?php echo $rent_name; ?></h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="index.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='home'){ echo "active"; } else{echo "";} ?>"><?php echo $lang['home']; ?></a></li>
                
                <?php if (!isset($user_id)): ?>
                    <li><a href="login.php" class="<?php if($mode=='login'){ echo "active"; } else{echo "";} ?>"><?php echo $lang['login']; ?></a></li>
                <?php else: ?>
                    <li class="dropdown"><a href="#"><span><i class="bi bi-person-circle me-1"></i><?php echo $fullname; ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="profile.php?rent_name=<?php echo $rent_name; ?>"><?php echo $lang['profile_info']; ?></a></li>
                            <li><a href="transaction.php?rent_name=<?php echo $rent_name; ?>"><?php echo $lang['payment_verification']; ?></a></li>
                            <li class="dropdown"><a href="#"><span><?php echo $lang['my_assets']; ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                                <ul>
                                    <li><a href="admin_transactions.php?rent_name=<?php echo $rent_name; ?>"><?php echo $lang['manage_appointments']; ?></a></li>
                                    <li><a href="dashboard.php?rent_name=<?php echo $rent_name; ?>"><?php echo $lang['dashboard']; ?></a></li>
                                    <li><a href="admin_properties.php?rent_name=<?php echo $rent_name; ?>"><?php echo $lang['manage_assets']; ?></a></li>
                                </ul>
                            </li>
                            <li><a href="logout.php" class="<?php if($mode=='logout'){ echo "active"; } else{echo "";} ?>"><?php echo $lang['logout']; ?></a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <li class="dropdown"><a href="#"><span><i class="bi bi-translate"></i> <?php echo strtoupper($_SESSION['lang']); ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                    <ul>
                        <li><a href="<?php echo get_lang_url('th'); ?>">ไทย</a></li>
                        <li><a href="<?php echo get_lang_url('en'); ?>">English</a></li>
                        <li><a href="<?php echo get_lang_url('cn'); ?>">中文</a></li>
                    </ul>
                </li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

    </div>
</header>