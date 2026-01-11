<?php
session_start();
require_once 'db.php';

// 1. BẢO MẬT: Chỉ Admin được vào
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    die("Bạn không có quyền truy cập!");
}

// 2. Kiểm tra ID người dùng
if (!isset($_GET['id'])) {
    die("Không tìm thấy người dùng!");
}

$user_id = intval($_GET['id']);

// 3. LẤY THÔNG TIN USER
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Người dùng không tồn tại!");
}

// 4. LẤY LỊCH SỬ GÓI TẬP
$sql = "SELECT s.*, p.name as package_name, p.price, p.duration_days 
        FROM subscriptions s 
        JOIN packages p ON s.package_id = p.id 
        WHERE s.user_id = ? 
        ORDER BY s.id DESC";
$subStmt = $conn->prepare($sql);
$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$history = $subStmt->get_result();

// 5. TÍNH TỔNG TIỀN ĐÃ CHI (Thống kê cho Admin xem)
$totalSpent = 0;
// Lưu kết quả history vào mảng để duyệt 2 lần (1 lần tính tiền, 1 lần hiển thị)
$historyData = []; 
while($row = $history->fetch_assoc()) {
    $historyData[] = $row;
    if ($row['status'] == 'active' || $row['status'] == 'expired') {
        $totalSpent += $row['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Thành viên: <?php echo htmlspecialchars($user['full_name']); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; display: flex; gap: 20px; flex-wrap: wrap; }
        
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .info-col { flex: 1; min-width: 300px; height: fit-content; }
        .history-col { flex: 2; min-width: 400px; }

        h2 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        h3 { color: #d32f2f; margin-top: 0; }
        
        .info-row { margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .info-row strong { display: block; color: #555; margin-bottom: 3px; font-size: 13px; }
        .info-row span { font-size: 16px; font-weight: 500; color: #000; }
        
        .total-spent { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; font-weight: bold; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #333; color: white; }
        
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .bg-pending { background: #ff9800; }
        .bg-active { background: #28a745; }
        .bg-expired { background: #999; }
        
        .btn-back { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #555; font-weight: bold; background: #ddd; padding: 8px 15px; border-radius: 5px;}
        .btn-back:hover { background: #ccc; }
    </style>
</head>
<body>

    <div style="max-width: 1000px; margin: 0 auto;">
        <a href="admin_users.php" class="btn-back">← Quay lại Danh sách</a>
    </div>

    <div class="container">
        <div class="card info-col">
            <h3>👤 Hồ sơ khách hàng</h3>
            
            <div class="info-row">
                <strong>Họ và Tên:</strong>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            
            <div class="info-row">
                <strong>Tên đăng nhập (Username):</strong>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>

            <div class="info-row">
                <strong>Số điện thoại:</strong>
                <span><?php echo htmlspecialchars($user['phone']); ?></span>
            </div>

            <div class="info-row">
                <strong>Email:</strong>
                <span><?php echo htmlspecialchars($user['email']); ?></span>
            </div>

            <div class="info-row">
                <strong>Ngày đăng ký tài khoản:</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
            </div>

            <div class="info-row">
                <strong>Vai trò:</strong>
                <span><?php echo $user['role'] == 1 ? '<span style="color:red">Admin</span>' : 'Khách hàng'; ?></span>
            </div>

            <div class="total-spent">
                💰 Tổng chi tiêu:<br>
                <?php echo number_format($totalSpent, 0, ',', '.'); ?> đ
            </div>
        </div>

        <div class="card history-col">
            <h3>📦 Lịch sử đăng ký gói tập</h3>
            
            <?php if (count($historyData) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Gói tập</th>
                            <th>Ngày ĐK</th>
                            <th>Hết hạn</th>
                            <th>Giá tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historyData as $row): ?>
                            <tr>
                                <td><strong><?php echo $row['package_name']; ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['end_date'])); ?></td>
                                <td><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</td>
                                <td>
                                    <?php 
                                        if($row['status'] == 'pending') 
                                            echo '<span class="badge bg-pending">Chờ duyệt</span>';
                                        elseif($row['status'] == 'active') 
                                            echo '<span class="badge bg-active">Active</span>';
                                        else 
                                            echo '<span class="badge bg-expired">Hết hạn</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #777; margin-top: 30px;">Khách hàng này chưa đăng ký gói tập nào.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>