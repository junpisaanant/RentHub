<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login V5</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->
<script src="login/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="login/vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="login/vendor/bootstrap/js/popper.js"></script>
	<script src="login/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="login/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="login/vendor/daterangepicker/moment.min.js"></script>
	<script src="login/vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="login/vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="login/js/main.js"></script>
	<script src="https://apis.google.com/js/platform.js" async defer></script>
	<script src="login/js/login.js"></script>
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="login/images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="login/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="login/vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login/css/util.css">
	<link rel="stylesheet" type="text/css" href="login/css/main.css">
<!--===============================================================================================-->
</head>
<body>
<?php 
$mode = 'login';
include 'header.php'; 
//ถ้ามีการ login จะเรียกอีกหน้าจอ
if (isset($user_id)) {
    header("Location: index.php");
    exit();
}
?>
	<div class="limiter">
		<div class="container-login100" style="background-image: url('assets/img/theprestige-2.png');">
			<div class="wrap-login100 p-l-110 p-r-110 p-t-62 p-b-33">
				<form class="login100-form validate-form flex-sb flex-w" method="post">
					<span class="login100-form-title p-b-53">
						ลงชื่อเข้าใช้
					</span>
					<!--
						เดี๋ยวมาตามเก็บทีหลังตอนมีเวลาเหลือ
					<a href="#" class="btn-face m-b-20">
						<i class="fa fa-facebook-official"></i>
						Facebook
					</a>
					
						ปุ่มเดิมสวยกว่า
						<a href="#" class="btn-google m-b-20" data-onsuccess="onSignIn">
							<img src="login/images/icons/icon-google.png" alt="GOOGLE">
							Google
						</a>	
					<meta name="google-signin-client_id" content="976930577576-0s9uacn3q8u5dsd1ne76s1lm32lpclj6.apps.googleusercontent.com">
						<div class="g-signin2" data-onsuccess="onSignIn"></div>
					</meta>	-->
					
					
					
					<div class="p-b-9">
						<span class="txt1">
							Username
						</span>
					</div>
					<div class="wrap-input100 validate-input" data-validate = "Username is required">
						<input class="input100" type="text" name="username" autocomplete="username">
						<span class="focus-input100"></span>
					</div>
					
					<div class="p-t-13 p-b-9">
						<span class="txt1">
							Password
						</span>

						<a href="#" class="txt2 bo1 m-l-5">
							ลืมรหัสผ่าน?
						</a>
					</div>
					<div class="wrap-input100 validate-input" data-validate = "Password is required">
						<input class="input100" type="password" name="password"  autocomplete="current-password">
						<span class="focus-input100"></span>
						<span class="btn-show-pass">
							<i class="fa fa-eye"></i>
						</span>
					</div>

					<div class="container-login100-form-btn m-t-17">
						<button class="login100-form-btn" id="loginBtn">
							เข้าสู่ระบบ
						</button>
					</div>

					<div class="w-full text-center p-t-55">
						<span class="txt2">
							ยังไม่ได้เป็นสมาชิก?
						</span>

						<a href="#" class="txt2 bo1">
							สมัครสมาชิก
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<!--ลืมรหัสผ่าน -->
	<div id="forgotPasswordDialog" class="modal">
		<div class="modal-content">
			<span class="close-button">&times;</span>
			<h2>ลืมรหัสผ่าน</h2>
			<div class="p-b-9">
				<span class="txt1">
					อีเมล
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
				<input class="input100" type="text" name="forgot_email">
				<span class="focus-input100"></span>
			</div>
			<div class="container-login100-form-btn m-t-17">
				<button class="login100-form-btn" id="forgotPasswordSubmit">
					ส่งรหัสผ่านใหม่
				</button>
			</div>
		</div>
		
	</div>

	<!-- สมัครสมาชิก -->
	<div id="registerDialog" class="modal">
    <div class="modal-content-register">
        <span class="close-button">&times;</span>
        <h2>สมัครสมาชิก</h2>
		<form id="registerForm"> 
			<div class="p-b-9">
				<span class="txt1">
					Username (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Username is required">
				<input class="input100" type="text" name="reg_username" minlength="8" autocomplete="username">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					Password (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Password is required">
				<input class="input100" type="password" name="reg_password" id="reg_password" autocomplete="current-password" pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" required>
				<span class="focus-input100"></span>
				<span class="btn-show-pass">
					<i class="fa fa-eye"></i>
				</span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					ยืนยัน Password (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Password confirmation is required">
				<input class="input100" type="password" name="reg_confirm_password" autocomplete="current-password" id="reg_confirm_password" required>
				<span class="focus-input100"></span>
				<span class="btn-show-pass">
					<i class="fa fa-eye"></i>
				</span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					Email (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Valid email is required: ex@abc.xyz">
				<input class="input100" type="text" name="reg_email">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					เบอร์โทรศัพท์ (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Phone number is required">
				<input class="input100" type="text" name="reg_phone_no">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					Line ID (ไม่จำเป็น)
				</span>
			</div>
			<div class="wrap-input100">
				<input class="input100" type="text" name="reg_line_id">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					ชื่อจริง (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Firstname is required">
				<input class="input100" type="text" name="reg_firstname">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					นามสกุล (จำเป็น)
				</span>
			</div>
			<div class="wrap-input100 validate-input" data-validate = "Lastname is required">
				<input class="input100" type="text" name="reg_lastname">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					เลขบัตรประชาชน (ไม่จำเป็น)
				</span>
			</div>
			<div class="wrap-input100">
				<input class="input100" type="text" name="reg_identification_no">
				<span class="focus-input100"></span>
			</div>

			<div class="p-t-13 p-b-9">
				<span class="txt1">
					Passport (ไม่จำเป็น)
				</span>
			</div>
			<div class="wrap-input100">
				<input class="input100" type="text" name="reg_passport_no">
				<span class="focus-input100"></span>
			</div>

			<div class="container-login100-form-btn m-t-17">
				<button class="login100-form-btn" id="registerSubmit">
					ตกลง
				</button>
			</div>
		</form>
    </div>
</div>

</body>
</html>