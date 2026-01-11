<?php
// db.php
$servername = "sql106.infinityfree.com"; // Thay bằng MySQL Host Name của bạn
$username   = "if0_40721067";           // Thay bằng MySQL User Name
$password   = "RPSJ0175J5x44";           // Thay bằng MySQL Password
$dbname     = "if0_40721067_gym_db";    // Thay bằng MySQL Database Name

// Tạo kết nối
// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// --- DÒNG QUAN TRỌNG ĐỂ SỬA LỖI FONT ---
$conn->set_charset("utf8mb4"); 
// ---------------------------------------
?>