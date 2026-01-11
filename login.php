<?php
session_start();
require_once 'db.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    // Lấy thêm role và full_name từ Database
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass, $row['password'])) {
            // LƯU THÔNG TIN QUAN TRỌNG VÀO SESSION
            $_SESSION['user_id'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name']; // Lưu tên thật
            $_SESSION['role'] = $row['role']; // Lưu quyền (1=Admin, 0=Khách)
            
            // Chuyển hướng
            if ($row['role'] == 1) {
                header("Location: admin.php"); // Nếu là admin, vào thẳng trang quản trị
            } else {
                header("Location: index.php"); // Khách thì về trang chủ
            }
            exit;
        } else {
            $message = "❌ Mật khẩu không đúng!";
        }
    } else {
        $message = "❌ Tên đăng nhập không tồn tại!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Gym Assistant</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #333; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;}
        button:hover { background: #000; }
        .link { margin-top: 15px; font-size: 14px; }
        .msg { margin-bottom: 15px; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Đăng Nhập</h2>
        <?php if($message): ?><div class="msg"><?php echo $message; ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <button type="submit">Đăng Nhập</button>
        </form>
        <div class="link">Chưa có tài khoản? <a href="register.php">Đăng ký</a></div>
        <div class="link"><a href="index.php">← Quay lại trang chủ</a></div>
    </div>
</body>
</html>