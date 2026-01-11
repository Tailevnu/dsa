<?php
session_start();
require_once 'db.php';

// 1. BẢO MẬT: Kiểm tra nếu không phải Admin (Role != 1) thì chặn
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2 style='color:red;'>⛔ TRUY CẬP BỊ TỪ CHỐI!</h2>
            <p>Trang này chỉ dành cho Admin.</p>
            <a href='index.php'>Về trang chủ</a>
         </div>");
}

// 2. XỬ LÝ DUYỆT / HỦY ĐƠN HÀNG
if (isset($_GET['action']) && isset($_GET['id'])) {
    $sub_id = intval($_GET['id']); // Dùng intval để bảo mật
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // Duyệt đơn -> Chuyển thành active
        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
    } elseif ($action == 'cancel') {
        // Hủy đơn -> Xóa khỏi database
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
    }
    // Refresh lại trang sau khi xử lý
    header("Location: admin.php");
    exit;
}

// 3. LẤY DANH SÁCH ĐƠN HÀNG
$sql = "SELECT s.*, u.username, u.full_name, u.phone, p.name as package_name, p.price 
        FROM subscriptions s 
        JOIN users u ON s.user_id = u.id 
        JOIN packages p ON s.package_id = p.id 
        ORDER BY s.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Header */
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        h1 { color: #d32f2f; margin: 0 0 10px 0; }
        .nav-links a { text-decoration: none; color: #555; margin: 0 10px; font-weight: bold; }
        .nav-links a:hover { color: #d32f2f; }
        
        /* Buttons */
        .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-approve { background: #28a745; }
        .btn-cancel { background: #dc3545; }
        .btn-manage { background: #333; padding: 10px 20px; font-size: 16px; font-weight: bold; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #333; color: white; }
        tr:hover { background-color: #f9f9f9; }

        /* Badge trạng thái */
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
        .bg-pending { background: #ff9800; }
        .bg-active { background: #28a745; }
        
        /* Info */
        .user-info small { display: block; color: #666; }
        .price { font-weight: bold; color: #d32f2f; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>QUẢN TRỊ VIÊN (ADMIN)</h1>
            <div class="nav-links">
                <a href="index.php">← Về trang chủ</a> |
                <a href="logout.php">Đăng xuất</a>
            </div>
        </div>

       <div style="text-align: center; margin-bottom: 30px;">
    <a href="admin_exercises.php" class="btn btn-manage">🏋️ Kho Bài Tập</a>
    <a href="admin_users.php" class="btn btn-manage" style="background: #007bff; margin-left: 10px;">👥 Quản lý User</a>
</div>

        <h3>📦 Danh sách Đơn đăng ký gói tập</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Gói tập</th>
                    <th>Giá tiền</th>
                    <th>Ngày ĐK</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td class="user-info">
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <small>User: <?php echo htmlspecialchars($row['username']); ?></small>
                                <small>📞 <?php echo htmlspecialchars($row['phone']); ?></small>
                            </td>
                            <td><?php echo $row['package_name']; ?></td>
                            <td class="price"><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</td>
                            <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                            <td>
                                <?php if($row['status'] == 'pending'): ?>
                                    <span class="badge bg-pending">Chờ duyệt</span>
                                <?php elseif($row['status'] == 'active'): ?>
                                    <span class="badge bg-active">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#999"><?php echo $row['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="admin.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-approve" onclick="return confirm('Xác nhận đã nhận tiền?')">✅ Duyệt</a>
                                    <a href="admin.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-cancel" onclick="return confirm('Hủy đơn này?')">❌ Hủy</a>
                                <?php else: ?>
                                    <span style="color: #aaa;">Hoàn tất</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 30px; color: #666;">
                            <p>Chưa có đơn hàng nào!</p>
                            <p>👉 Hãy thử tạo tài khoản khách, mua một gói tập, rồi quay lại đây để duyệt.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>