<?php
session_start();
require_once 'db.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = trim($_POST['full_name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);

    // Validate cơ bản
    if (strlen($username) < 3 || strlen($password) < 3) {
        $message = "❌ Tên đăng nhập và mật khẩu phải dài hơn 3 ký tự!";
    } else {
        // Kiểm tra trùng username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = "❌ Tên đăng nhập đã tồn tại!";
        } else {
            // Thêm user mới với đầy đủ thông tin
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $role = 0; // Mặc định là khách hàng
            
            // SQL Insert mới
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, phone, email, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $hashed_pass, $fullname, $phone, $email, $role);

            if ($stmt->execute()) {
                $message = "✅ Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a>";
            } else {
                $message = "❌ Lỗi hệ thống: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Thành viên - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px;}
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; color: #d32f2f; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #d32f2f; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 10px;}
        button:hover { background: #b71c1c; }
        .link { margin-top: 15px; font-size: 14px; text-align: center; }
        .msg { margin-bottom: 15px; padding: 10px; border-radius: 5px; text-align: center; background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Đăng Ký Hội Viên</h2>
        <?php if($message) echo "<div class='msg'>$message</div>"; ?>
        
        <form method="POST">
            <input type="text" name="full_name" placeholder="Họ và Tên thật" required>
            <input type="text" name="username" placeholder="Tên đăng nhập (Nick)" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <input type="text" name="phone" placeholder="Số điện thoại (để liên hệ)" required>
            <input type="email" name="email" placeholder="Email (nhận hóa đơn)">
            
            <button type="submit">Đăng Ký Ngay</button>
        </form>
        
        <div class="link">Đã có tài khoản? <a href="login.php">Đăng nhập</a></div>
        <div class="link"><a href="index.php">← Quay lại trang chủ</a></div>
    </div>
</body>
</html>