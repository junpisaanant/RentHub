<?php 
session_start();
header('Content-Type: text/html; charset=utf-8');
?>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>The Prestige Living</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/css/search.css">

  <!-- =======================================================
  * Template Name: EstateAgency
  * Template URL: https://bootstrapmade.com/real-estate-agency-bootstrap-template/
  * Updated: Aug 09 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>
<?php 
if (isset($_REQUEST['rent_id'])) {
  $rent_id = $_REQUEST['rent_id'];
  $rent_name = $_REQUEST['rent_name'];
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
<body class="index-page">
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.php?rent_name=<?php echo $rent_name; ?>" class="logo d-flex align-items-center">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <!-- <img src="assets/img/logo.png" alt=""> -->
        <h1 class="sitename"><?php echo $rent_name; ?></h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='home'){ echo "active"; } else{echo "";} ?>">Home</a></li>
          <!--<li><a href="about.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='about'){ echo "active"; } else{echo "";} ?>">About</a></li>
          <li><a href="services.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='service'){ echo "active"; } else{echo "";} ?>">Services</a></li>
          <li><a href="properties.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='properties'){ echo "active"; } else{echo "";} ?>">Properties</a></li>
          <li><a href="agents.php?rent_name=<?php echo $rent_name; ?>" class="<?php if($mode=='agents'){ echo "active"; } else{echo "";} ?>">Agents</a></li>-->
          <?php
            if (!isset($user_id)) {
          ?>
          <li><a href="login.php" class="<?php if($mode=='login'){ echo "active"; } else{echo "";} ?>">Log In</a></li>
          <?php }else{ ?>
            <li class="dropdown"><a href="#"><span><i class="bi bi-person-circle me-1"></i><?php echo $fullname; ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="transaction.php">ตรวจสอบและยืนยันการชำระเงิน</a></li>
              <!--<li class="dropdown"><a href="#"><span>Deep Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                <ul>
                  <li><a href="#">Deep Dropdown 1</a></li>
                  <li><a href="#">Deep Dropdown 2</a></li>
                  <li><a href="#">Deep Dropdown 3</a></li>
                  <li><a href="#">Deep Dropdown 4</a></li>
                  <li><a href="#">Deep Dropdown 5</a></li>
                </ul>
              </li>-->
              <li><a href="logout.php" class="<?php if($mode=='logout'){ echo "active"; } else{echo "";} ?>">Log Out</a>
            </ul>
          </li>
          <?php } ?>
        
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>