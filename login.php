<?php
session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'th';
}

$lang_file = 'languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include 'languages/th.php';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
	<title><?php echo $lang['login_title']; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="login/vendor/jquery/jquery-3.2.1.min.js"></script>
<script src="login/vendor/animsition/js/animsition.min.js"></script>
<script src="login/vendor/bootstrap/js/popper.js"></script>
	<script src="login/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="login/vendor/select2/select2.min.js"></script>
<script src="login/vendor/daterangepicker/moment.min.js"></script>
	<script src="login/vendor/daterangepicker/daterangepicker.js"></script>
<script src="login/vendor/countdowntime/countdowntime.js"></script>
<script src="login/js/main.js"></script>
	<script src="https://apis.google.com/js/platform.js" async defer></script>
	<script src="login/js/login.js"></script>
<link rel="icon" type="image/png" href="assets/img/favicon.png"/>
<link rel="stylesheet" type="text/css" href="login/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="login/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="login/fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
<link rel="stylesheet" type="text/css" href="login/vendor/animate/animate.css">
<link rel="stylesheet" type="text/css" href="login/vendor/css-hamburgers/hamburgers.min.css">
<link rel="stylesheet" type="text/css" href="login/vendor/animsition/css/animsition.min.css">
<link rel="stylesheet" type="text/css" href="login/vendor/select2/select2.min.css">
<link rel="stylesheet" type="text/css" href="login/vendor/daterangepicker/daterangepicker.css">
<link rel="stylesheet" type="text/css" href="login/css/util.css">
	<link rel="stylesheet" type="text/css" href="login/css/main.css">
</head>
<?php 
$mode = 'login';
include 'header.php'; 
//ถ้ามีการ login จะเรียกอีกหน้าจอ
if (isset($user_id)) {
    header("Location: index.php");
    exit();
}
?>
<body>
	<div style="position: absolute; top: 10px; right: 10px; z-index: 1000;">
		<a href="?lang=th" style="text-decoration: none; color: white; margin-right: 10px;">TH</a>
		<a href="?lang=en" style="text-decoration: none; color: white; margin-right: 10px;">EN</a>
		<a href="?lang=cn" style="text-decoration: none; color: white;">CN</a>
	</div>
	<div class="limiter">
		<div class="container-login100" style="background-image: url('assets/img/theprestige-2.png');">
			<div class="wrap-login100 p-l-110 p-r-110 p-t-62 p-b-33">
				<form class="login100-form validate-form flex-sb flex-w" method="post">
					<span class="login100-form-title p-b-53">
						<?php echo $lang['sign_in']; ?>
					</span>
					
					<div class="p-b-9">
						<span class="txt1">
							<?php echo $lang['username']; ?>
						</span>
					</div>
					<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['username_is_required']; ?>">
						<input class="input100" type="text" name="username" autocomplete="username">
						<span class="focus-input100"></span>
					</div>
					
					<div class="p-t-13 p-b-9">
						<span class="txt1">
							<?php echo $lang['password']; ?>
						</span>

						<a href="#" id="forgotPasswordLink" class="txt2 bo1 m-l-5">
							<?php echo $lang['forgot_password_q']; ?>
						</a>
					</div>
					<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['password_is_required']; ?>">
						<input class="input100" type="password" name="password"  autocomplete="current-password">
						<span class="focus-input100"></span>
						<span class="btn-show-pass">
							<i class="fa fa-eye"></i>
						</span>
					</div>

					<div class="container-login100-form-btn m-t-17">
						<button class="login100-form-btn" id="loginBtn">
							<?php echo $lang['login_button']; ?>
						</button>
					</div>

					<div class="w-full text-center p-t-55">
						<span class="txt2">
							<?php echo $lang['not_a_member_q']; ?>
						</span>

						<a href="#" id="registerLink" class="txt2 bo1">
							<?php echo $lang['sign_up_now']; ?>
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div id="forgotPasswordDialog" class="modal">
		<form id="forgotPasswordForm">
			<div class="modal-content">
				<span class="close-button">&times;</span>
				<h2><?php echo $lang['forgot_password']; ?></h2>
				<div class="p-b-9">
					<span class="txt1">
						<?php echo $lang['email']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate="<?php echo $lang['valid_email_is_required']; ?>">
					<input class="input100" type="text" name="forgot_email">
					<span class="focus-input100"></span>
				</div>
				<div class="container-login100-form-btn m-t-17">
					<button class="login100-form-btn" id="forgotPasswordSubmit">
						<?php echo $lang['send_new_password']; ?>
					</button>
				</div>
			</div>
		</form>
	</div>

	<div id="registerDialog" class="modal">
		<div class="modal-content-register">
			<span class="close-button">&times;</span>
			<h2><?php echo $lang['register']; ?></h2>
			<form id="registerForm"> 
				<div class="p-b-9">
					<span class="txt1">
						<?php echo $lang['username_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['username_is_required']; ?>">
					<input class="input100" type="text" name="reg_username" minlength="8" autocomplete="username">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['password_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['password_is_required']; ?>">
					<input class="input100" type="password" name="reg_password" id="reg_password" autocomplete="current-password" pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" required>
					<span class="focus-input100"></span>
					<span class="btn-show-pass">
						<i class="fa fa-eye"></i>
					</span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['confirm_password_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['password_confirmation_is_required']; ?>">
					<input class="input100" type="password" name="reg_confirm_password" autocomplete="current-password" id="reg_confirm_password" required>
					<span class="focus-input100"></span>
					<span class="btn-show-pass">
						<i class="fa fa-eye"></i>
					</span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['email_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['valid_email_is_required']; ?>">
					<input class="input100" type="text" name="reg_email">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['phone_number_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['phone_number_is_required']; ?>">
					<input class="input100" type="text" name="reg_phone_no">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['line_id_optional']; ?>
					</span>
				</div>
				<div class="wrap-input100">
					<input class="input100" type="text" name="reg_line_id">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['firstname_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['firstname_is_required']; ?>">
					<input class="input100" type="text" name="reg_firstname">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['lastname_required']; ?>
					</span>
				</div>
				<div class="wrap-input100 validate-input" data-validate = "<?php echo $lang['lastname_is_required']; ?>">
					<input class="input100" type="text" name="reg_lastname">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['identification_no_optional']; ?>
					</span>
				</div>
				<div class="wrap-input100">
					<input class="input100" type="text" name="reg_identification_no">
					<span class="focus-input100"></span>
				</div>

				<div class="p-t-13 p-b-9">
					<span class="txt1">
						<?php echo $lang['passport_no_optional']; ?>
					</span>
				</div>
				<div class="wrap-input100">
					<input class="input100" type="text" name="reg_passport_no">
					<span class="focus-input100"></span>
				</div>

				<div class="container-login100-form-btn m-t-17">
					<button class="login100-form-btn" id="registerSubmit">
						<?php echo $lang['ok']; ?>
					</button>
				</div>
			</form>
		</div>
	</div>

</body>
</html>