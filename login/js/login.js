$(document).ready(function() {
    $('#loginBtn').click(function(event) {
        event.preventDefault(); // Prevent the form from submitting the traditional way

        var username = $('input[name="username"]').val().trim();
        var password = $('input[name="password"]').val().trim();
        var isValid = true;

        // Simple validation
        if (username === '') {
            $('input[name="username"]').parent().addClass('alert-validate');
            isValid = false;
        }
        if (password === '') {
            $('input[name="password"]').parent().addClass('alert-validate');
            isValid = false;
        }

        if (isValid) {
            // Using AJAX to submit the form
            $.ajax({
                url: 'login_process.php',
                type: 'POST',
                data: {
                    username: username,
                    password: password
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'index.php'; // Redirect on success
                    } else {
                        alert(response.message); // Show error message
                    }
                },
                error: function() {
                    alert('An error occurred during login. Please try again.');
                }
            });
        }
    });

    // Remove validation class on input focus
    $('.validate-input .input100').focus(function(){
        $(this).parent().removeClass('alert-validate');
    });
	
	 // Handle "Forgot Password?" link click
    $('#forgotPasswordLink').click(function(event) {
        event.preventDefault();
        $('#forgotPasswordDialog').show();
    });

    // Handle "Sign Up" link click
    $('#registerLink').click(function(event) {
        event.preventDefault();
        $('#registerDialog').show();
    });

    // Close dialogs when the close button is clicked
    $('.close-button').click(function() {
        $('#forgotPasswordDialog').hide();
        $('#registerDialog').hide();
    });

    // Handle Forgot Password submission
    $('#forgotPasswordForm').on('submit', function(event) {
        event.preventDefault(); // หยุดการทำงานของฟอร์ม

        // 1. ดึงข้อมูลฟอร์มด้วย FormData ซึ่งเป็นวิธีที่แน่นอนที่สุด
        const form = event.target;
        const formData = new FormData(form);
        const email = formData.get('forgot_email').trim();

        // 2. ตรวจสอบว่าอีเมลว่างเปล่าหรือไม่
        if (email === '') {
            alert('Please enter your email.');
            return;
        }

        // 3. ส่งข้อมูลด้วย Fetch API (มาตรฐานของเบราว์เซอร์)
        fetch('forgot_password_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // ตรวจสอบว่า Server ตอบกลับมาสำเร็จหรือไม่
            if (!response.ok) {
                // ถ้า Server error (เช่น 500), ให้โยน error ไปที่ .catch
                throw new Error('Network response was not ok');
            }
            return response.json(); // แปลงข้อมูลที่ตอบกลับเป็น JSON
        })
        .then(data => {
            // 4. แสดง message ที่ได้จาก PHP
            alert(data.message); 
            if (data.success) {
                $('#forgotPasswordDialog').hide();
            }
        })
        .catch(error => {
            // 5. แสดงข้อผิดพลาดหากการเชื่อมต่อล้มเหลว
            console.error('Fetch Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
        });
    });

    // Handle Registration submission
    $('#registerSubmit').click(function(event) {
        event.preventDefault(); // Prevent default form submission

        // Password matching validation
        var password = $('#reg_password').val();
        var confirmPassword = $('#reg_confirm_password').val();

        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            return; // Stop the function if passwords don't match
        }

        var formData = $('#registerForm').serialize(); // Serialize form data

        $.ajax({
            url: 'register_process.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                alert(response.message); // Show success or error message from server
                if (response.success) {
                    $('#registerDialog').hide(); // Hide dialog on successful registration
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred during registration. Please try again. ' + textStatus);
            }
        });
    });
	
});

function onSignIn(googleUser) {
  var profile = googleUser.getBasicProfile();
  console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
  console.log('Name: ' + profile.getName());
  console.log('Image URL: ' + profile.getImageUrl());
  console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.
}