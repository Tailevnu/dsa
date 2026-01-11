<?php
session_start();
require_once 'db.php';

// 1. CHẶN KHÁCH CHƯA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$msgType = ""; // success hoặc error

// Lấy username từ session
$username = $_SESSION['user_id'];

// Lấy ID của user từ DB để đảm bảo chính xác
$uStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$uStmt->bind_param("s", $username);
$uStmt->execute();
$userId = $uStmt->get_result()->fetch_assoc()['id'];

// 2. XỬ LÝ CẬP NHẬT THÔNG TIN (Khi bấm nút Lưu)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    
    if (empty($fullName) || empty($phone)) {
        $message = "Vui lòng điền Họ tên và SĐT!";
        $msgType = "error";
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $fullName, $phone, $email, $userId);
        
        if ($updateStmt->execute()) {
            $message = "Cập nhật thông tin thành công!";
            $msgType = "success";
            // Cập nhật lại Session tên để hiển thị ngay trên Header
            $_SESSION['full_name'] = $fullName;
        } else {
            $message = "Có lỗi xảy ra, vui lòng thử lại.";
            $msgType = "error";
        }
    }
}

// 3. LẤY THÔNG TIN USER ĐỂ HIỂN THỊ VÀO FORM
$infoStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$infoStmt->bind_param("i", $userId);
$infoStmt->execute();
$userInfo = $infoStmt->get_result()->fetch_assoc();

// 4. LẤY LỊCH SỬ ĐĂNG KÝ GÓI TẬP
$subSql = "SELECT s.*, p.name as package_name, p.price 
           FROM subscriptions s 
           JOIN packages p ON s.package_id = p.id 
           WHERE s.user_id = ? 
           ORDER BY s.id DESC";
$subStmt = $conn->prepare($subSql);
$subStmt->bind_param("i", $userId);
$subStmt->execute();
$history = $subStmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; display: flex; gap: 20px; flex-wrap: wrap; }
        
        /* Cột trái: Form thông tin */
        .profile-box { flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); height: fit-content; }
        
        /* Cột phải: Lịch sử */
        .history-box { flex: 2; min-width: 400px; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        h2 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        h3 { margin-top: 0; color: #d32f2f; }

        /* Form Style */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;}
        input:disabled { background: #f9f9f9; color: #999; cursor: not-allowed; }
        
        .btn-save { width: 100%; background: #d32f2f; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: #b71c1c; }

        /* Table Style */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f8f8; color: #333; }
        
        /* Status Badge */
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        .bg-pending { background: #ff9800; }
        .bg-active { background: #28a745; }
        .bg-expired { background: #999; }

        /* Alert Message */
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        .back-link { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #555; font-weight: bold; }
        .back-link:hover { color: #d32f2f; }
    </style>
</head>
<body>

    <div class="container">
        <div style="width: 100%;">
            <a href="index.php" class="back-link">← Quay lại trang chủ</a>
        </div>

        <div class="profile-box">
            <h3>👤 Thông tin tài khoản</h3>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $msgType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập:</label>
                    <input type="text" value="<?php echo htmlspecialchars($userInfo['username']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Họ và Tên:</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($userInfo['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($userInfo['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($userInfo['email']); ?>">
                </div>

                <div class="form-group">
                    <label>Ngày tham gia:</label>
                    <input type="text" value="<?php echo date('d/m/Y', strtotime($userInfo['created_at'])); ?>" disabled>
                </div>

                <button type="submit" name="update_profile" class="btn-save">💾 Lưu thay đổi</button>
            </form>
        </div>

        <div class="history-box">
            <h3>📦 Gói tập của tôi</h3>
            
            <?php if ($history->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Gói tập</th>
                            <th>Ngày ĐK</th>
                            <th>Hết hạn</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['package_name']; ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['end_date'])); ?></td>
                                <td><?php echo number_format($row['price'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <?php 
                                        if($row['status'] == 'pending') 
                                            echo '<span class="badge bg-pending">Chờ duyệt</span>';
                                        elseif($row['status'] == 'active') 
                                            echo '<span class="badge bg-active">Đang tập</span>';
                                        else 
                                            echo '<span class="badge bg-expired">Hết hạn</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; color: #666;">
                    <p>Bạn chưa đăng ký gói tập nào.</p>
                    <a href="pricing.php" style="color: #d32f2f; font-weight: bold;">👉 Xem bảng giá ngay</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>