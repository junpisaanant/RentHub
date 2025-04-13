$(document).ready(function() {
   //Log In ธรรมดา
   $('#loginBtn').on('click', function(event) {
       event.preventDefault(); // ป้องกันการส่งฟอร์มแบบปกติ
       debugger;

       var username = $('input[name="username"]').val().trim();
       var password = $('input[name="password"]').val().trim();

       if (username !== '' && password !== '') {
           // ส่งข้อมูลไปยังสคริปต์ PHP สำหรับตรวจสอบการล็อกอิน
           $.ajax({
               url: 'login_process.php', // เปลี่ยนเป็นชื่อไฟล์ PHP ที่คุณจะสร้าง
               method: 'POST',
               data: {
                   username: username,
                   password: password
               },
               dataType: 'json', // คาดหวังข้อมูล JSON กลับมาจาก PHP
               success: function(response) {
                   if (response.success) {
                       // ล็อกอินสำเร็จ
                       alert('เข้าสู่ระบบสำเร็จ!');
                       // คุณสามารถเปลี่ยนเส้นทางผู้ใช้ไปยังหน้าอื่นได้ที่นี่
                       window.location.href = "index.php";
                   } else {
                       // ล็อกอินไม่สำเร็จ แสดงข้อผิดพลาด
                       alert('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
                   }
               },
               error: function() {
                   alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
               }
           });
       } else {
           alert('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
       }
   });

   //ปุ่มดวงตา
   $('.btn-show-pass').on('click', function() {
        var passwordInput = $('input[name="password"]');
        var eyeIcon = $(this).find('i');

        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    //ส่วนลืมรหัสผ่าน
    var forgotPasswordLink = $('.txt2.bo1:contains("ลืมรหัสผ่าน?")');
    var forgotPasswordDialog = $('#forgotPasswordDialog');
    var closeButton = $('.close-button');

    forgotPasswordLink.on('click', function(event) {
        event.preventDefault();
        forgotPasswordDialog.css('display', 'block');
    });

    closeButton.on('click', function() {
        forgotPasswordDialog.css('display', 'none');
    });

    $(window).on('click', function(event) {
        if (event.target == forgotPasswordDialog[0]) {
            forgotPasswordDialog.css('display', 'none');
        }
    });

    //ส่ง email ]n,isylzjko
    var forgotPasswordSubmit = $('#forgotPasswordSubmit');

    forgotPasswordSubmit.on('click', function() {
        var email = $('input[name="forgot_email"]').val().trim();

        if (email !== '') {
            $.ajax({
                url: 'forgot_password_process.php', // สร้างไฟล์ PHP นี้
                method: 'POST',
                data: {
                    email: email
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('รหัสผ่านใหม่ได้ถูกส่งไปยังอีเมลของคุณแล้ว');
                        $('#forgotPasswordDialog').css('display', 'none'); // ปิด dialog
                    } else {
                        alert(response.message || 'เกิดข้อผิดพลาดในการส่งอีเมล');
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                }
            });
        } else {
            alert('กรุณากรอกอีเมล');
        }
    });

    //สมัครสมาชิก
    var registerLink = $('.txt2.bo1:contains("สมัครสมาชิก")');
    var registerDialog = $('#registerDialog');
    var closeButton = registerDialog.find('.close-button'); // ใช้ registerDialog.find เพื่อหา element ภายใน dialog

    registerLink.on('click', function(event) {
        event.preventDefault();
        registerDialog.css('display', 'block');
    });

    closeButton.on('click', function() {
        registerDialog.css('display', 'none');
    });

    $(window).on('click', function(event) {
        if (event.target == registerDialog[0]) {
            registerDialog.css('display', 'none');
        }
    });
    var registerSubmit = $('#registerSubmit');

    //ตรวจสอบรูปแบบ Email
    function isValidEmail(email) {
        // Regular expression สำหรับตรวจสอบรูปแบบอีเมล
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    //ตรวจสอบรูปแบบรหัสผ่าน
    function checkPasswordStrength(password) {
        var strongRegex = new RegExp("^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$");
        return strongRegex.test(password);
    }

    //ตรวจสอบรูปแบบบัตรประชาชนไทย
    function isValidThaiIDCard(idCard) {
        if (idCard.length !== 13 || !/^\d+$/.test(idCard)) {
            return false;
        }
        var sum = 0;
        for (var i = 0; i < 12; i++) {
            sum += parseInt(idCard.charAt(i)) * (13 - i);
        }
        var lastDigit = (11 - (sum % 11)) % 10;
        return parseInt(idCard.charAt(12)) === lastDigit;
    }

    var registerSubmit = $('#registerSubmit');
    var passwordInput = $('#reg_password');
    var confirmPasswordInput = $('#reg_confirm_password');

    registerSubmit.on('click', function(event) {
        event.preventDefault();

        // ดึงข้อมูลจากช่อง input
        var username = $('input[name="reg_username"]').val().trim();
        var password = passwordInput.val().trim();
        var confirmPassword = confirmPasswordInput.val().trim();
        var email = $('input[name="reg_email"]').val().trim();
        var phone_no = $('input[name="reg_phone_no"]').val().trim();
        var line_id = $('input[name="reg_line_id"]').val().trim();
        var firstname = $('input[name="reg_firstname"]').val().trim();
        var lastname = $('input[name="reg_lastname"]').val().trim();
        var identification_no = $('input[name="reg_identification_no"]').val().trim();
        var passport_no = $('input[name="reg_passport_no"]').val().trim();

        // ตรวจสอบข้อมูลที่จำเป็นต้องกรอก
        if (username === '' || password === '' || confirmPassword === '' || email === '' || phone_no === '' || firstname === '' || lastname === '') {
            alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            return;
        }

        // ตรวจสอบความแข็งแรงของ Password
        if (!checkPasswordStrength(password)) {
            alert('รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว, สัญลักษณ์อย่างน้อย 1 ตัว, ตัวเลขอย่างน้อย 1 ตัว และมีความยาวอย่างน้อย 8 ตัวอักษร');
            return;
        }

        // ตรวจสอบการยืนยัน Password
        if (password !== confirmPassword) {
            alert('รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน');
            return;
        }

        // ตรวจสอบรูปแบบอีเมล
        if (!isValidEmail(email)) {
            alert('รูปแบบอีเมลไม่ถูกต้อง');
            return;
        }
        var identification_no = $('input[name="reg_identification_no"]').val().trim();

        // ตรวจสอบรูปแบบบัตรประชาชน (ถ้ามีการกรอก)
        if (identification_no !== '' && !isValidThaiIDCard(identification_no)) {
            alert('รูปแบบเลขบัตรประชาชนไม่ถูกต้อง');
            return;
        }

        // ส่งข้อมูลไปยังสคริปต์ PHP
        $.ajax({
            url: 'register_process.php',
            method: 'POST',
            data: {
                username: username,
                password: password,
                email: email,
                phone_no: phone_no,
                line_id: line_id,
                firstname: firstname,
                lastname: lastname,
                identification_no: identification_no,
                passport_no: passport_no
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('สมัครสมาชิกสำเร็จ!');
                    $('#registerDialog').css('display', 'none');
                    // คุณอาจจะเคลียร์ข้อมูลใน form ตรงนี้ด้วย
                } else {
                    alert(response.message || 'เกิดข้อผิดพลาดในการสมัครสมาชิก');
                }
            },
            error: function() {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            }
        });
    });

    // ปุ่มแสดง/ซ่อนรหัสผ่าน
    $('.btn-show-pass').on('click', function() {
        var passwordField = $(this).closest('.wrap-input100').find('input[type="password"]');
        var eyeIcon = $(this).find('i');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            eyeIcon.removeClass('zmdi-eye').addClass('zmdi-eye-off');
        } else {
            passwordField.attr('type', 'password');
            eyeIcon.removeClass('zmdi-eye-off').addClass('zmdi-eye');
        }
    });
});