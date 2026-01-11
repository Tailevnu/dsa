<?php
session_start();
require_once 'db.php';

// 1. CHẶN KHÁCH CHƯA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Bạn cần đăng nhập để mua gói tập!'); window.location.href='login.php';</script>";
    exit;
}

// 2. Lấy ID gói tập từ URL (ví dụ: order.php?id=1)
if (!isset($_GET['id'])) {
    die("Không tìm thấy gói tập!");
}

$package_id = $_GET['id'];

// Lấy thông tin gói tập từ DB
$stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();

if (!$package) {
    die("Gói tập không tồn tại.");
}

// 3. XỬ LÝ KHI NGƯỜI DÙNG BẤM "XÁC NHẬN MUA"
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentUsername = $_SESSION['user_id'];
    
    // Lấy ID số của user từ username (vì bảng subscriptions cần user_id dạng số)
    $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $uStmt->bind_param("s", $currentUsername);
    $uStmt->execute();
    $userRow = $uStmt->get_result()->fetch_assoc();
    $userId = $userRow['id'];

    // Tính ngày hết hạn (Ngày hiện tại + số ngày của gói)
    $duration = $package['duration_days'];
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$duration days"));

    // Insert vào bảng subscriptions
    $insertStmt = $conn->prepare("INSERT INTO subscriptions (user_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'pending')");
    $insertStmt->bind_param("iiss", $userId, $package_id, $startDate, $endDate);

    if ($insertStmt->execute()) {
        // Mua thành công -> Chuyển hướng về trang Profile (sẽ làm ở bước sau)
        header("Location: profile.php?msg=success");
        exit;
    } else {
        $msg = "Có lỗi xảy ra, vui lòng thử lại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Gym Assistant</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding-top: 50px; }
        .checkout-box { background: white; width: 600px; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #333; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 18px; }
        .total { font-weight: bold; color: #d32f2f; font-size: 22px; border-top: 1px dashed #ccc; padding-top: 15px; }
        
        .bank-info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #90caf9; }
        .bank-info h3 { margin-top: 0; color: #0d47a1; }
        
        .btn-confirm { width: 100%; background: #d32f2f; color: white; padding: 15px; border: none; font-size: 18px; font-weight: bold; cursor: pointer; border-radius: 5px; }
        .btn-confirm:hover { background: #b71c1c; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #666; }
    </style>
</head>
<body>

    <div class="checkout-box">
        <div class="header">
            <h2>📦 Xác nhận đơn hàng</h2>
        </div>

        <div class="info-row">
            <span>Gói tập:</span>
            <strong><?php echo $package['name']; ?></strong>
        </div>
        <div class="info-row">
            <span>Thời hạn:</span>
            <span><?php echo $package['duration_days']; ?> ngày</span>
        </div>
        <div class="info-row total">
            <span>Thành tiền:</span>
            <span><?php echo number_format($package['price'], 0, ',', '.'); ?> đ</span>
        </div>

        <div class="bank-info">
            <h3>🏦 Thông tin chuyển khoản</h3>
            
            <p><strong>Ngân hàng:</strong> MB Bank (Quân Đội)</p>
            <p><strong>Số tài khoản:</strong> 9999.9999.9999</p>
            <p><strong>Chủ tài khoản:</strong> NGUYEN VAN A (Chủ phòng Gym)</p>
            <p><strong>Nội dung CK:</strong> GYM <?php echo $_SESSION['user_id']; ?></p>
            <p><i>Vui lòng chuyển khoản trước khi bấm xác nhận.</i></p>
        </div>

        <?php if($msg) echo "<p style='color:red'>$msg</p>"; ?>

        <form method="POST">
            <button type="submit" class="btn-confirm">✅ Đã chuyển tiền & Đăng ký</button>
        </form>

        <a href="pricing.php" class="back-link">← Hủy bỏ</a>
    </div>

</body>
</html>