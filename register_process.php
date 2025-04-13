<?php
include 'db.php'; 

// รับข้อมูลจาก AJAX request
$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];
$phone_no = $_POST['phone_no'];
$line_id = $_POST['line_id'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$identification_no = $_POST['identification_no'];
$passport_no = $_POST['passport_no'];

// ป้องกัน SQL injection (ควรใช้ prepared statements)
$username = mysqli_real_escape_string($conn, $username);
$email = mysqli_real_escape_string($conn, $email);
$phone_no = mysqli_real_escape_string($conn, $phone_no);
$line_id = mysqli_real_escape_string($conn, $line_id);
$firstname = mysqli_real_escape_string($conn, $firstname);
$lastname = mysqli_real_escape_string($conn, $lastname);
$identification_no = mysqli_real_escape_string($conn, $identification_no);
$passport_no = mysqli_real_escape_string($conn, $passport_no);

// ตรวจสอบรูปแบบอีเมลใน PHP
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array('success' => false, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง'));
    $conn->close();
    exit();
}

// ตรวจสอบ Username ซ้ำ
$checkUsernameQuery = "SELECT COUNT(*) FROM rent_user WHERE username = '$username'";
$checkUsernameResult = $conn->query($checkUsernameQuery);
$usernameCount = $checkUsernameResult->fetch_row()[0];
if ($usernameCount > 0) {
    echo json_encode(array('success' => false, 'message' => 'Username นี้ถูกใช้งานแล้ว'));
    $conn->close();
    exit();
}

// ตรวจสอบ Email ซ้ำ
$checkEmailQuery = "SELECT COUNT(*) FROM rent_user WHERE email = '$email'";
$checkEmailResult = $conn->query($checkEmailQuery);
$emailCount = $checkEmailResult->fetch_row()[0];
if ($emailCount > 0) {
    echo json_encode(array('success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้ว'));
    $conn->close();
    exit();
}

// เข้ารหัสรหัสผ่าน (สำคัญมาก!)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// สร้างคำสั่ง SQL สำหรับ INSERT
$sql = "INSERT INTO rent_user (
    email,
    phone_no,
    line_id,
    firstname,
    lastname,
    username,
    password,
    identification_no,
    passport_no,
    create_user,
    create_datetime,
    update_user,
    update_datetime
) VALUES (
    '$email',
    '$phone_no',
    '$line_id',
    '$firstname',
    '$lastname',
    '$username',
    '$hashedPassword',
    '$identification_no',
    '$passport_no',
    'user',  -- หรือชื่อ user ที่ทำการสร้าง
    NOW(),
    'user',  -- หรือชื่อ user ที่ทำการ update
    NOW()
)";

if ($conn->query($sql) === TRUE) {
    // ดึง ID ของผู้ใช้ที่เพิ่งสมัคร
    $lastInsertedId = $conn->insert_id;

    // เก็บข้อมูลผู้ใช้ลงใน Session
    $_SESSION['user_id'] = $lastInsertedId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_phone_no'] = $phone_no;
    $_SESSION['user_line_id'] = $line_id;
    $_SESSION['user_firstname'] = $firstname;
    $_SESSION['user_lastname'] = $lastname;
    $_SESSION['user_username'] = $username;
    $_SESSION['user_identification_no'] = $identification_no;
    $_SESSION['user_passport_no'] = $passport_no;
    
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('success' => false, 'message' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก: ' . $conn->error));
}

$conn->close();
?>