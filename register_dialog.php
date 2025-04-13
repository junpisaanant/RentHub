<link rel="stylesheet" type="text/css" href="login/css/main.css">
<!-- สมัครสมาชิก -->
    <div class="modal-content-register">
        <span class="close-button">&times;</span>
        <h2>สมัครสมาชิก</h2>

        <div class="p-b-9">
            <span class="txt1">
                Username (จำเป็น)
            </span>
        </div>
        <div class="wrap-input100 validate-input" data-validate = "Username is required">
            <input class="input100" type="text" name="reg_username" minlength="8">
            <span class="focus-input100"></span>
        </div>

        <div class="p-t-13 p-b-9">
            <span class="txt1">
                Password (จำเป็น)
            </span>
        </div>
        <div class="wrap-input100 validate-input" data-validate = "Password is required">
			<input class="input100" type="password" name="reg_password" id="reg_password" pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" required>
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
			<input class="input100" type="password" name="reg_confirm_password" id="reg_confirm_password" required>
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
    </div>