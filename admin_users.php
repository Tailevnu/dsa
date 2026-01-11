<?php
session_start();
require_once 'db.php';

// 1. BẢO MẬT
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    die("Bạn không có quyền truy cập!");
}

// 2. XỬ LÝ XÓA
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $current_user = $_SESSION['user_id'];
    $checkSelf = $conn->query("SELECT username FROM users WHERE id = $del_id")->fetch_assoc();
    
    if ($checkSelf['username'] == $current_user) {
        echo "<script>alert('❌ Không thể tự xóa chính mình!'); window.location.href='admin_users.php';</script>";
    } else {
        $conn->query("DELETE FROM subscriptions WHERE user_id = $del_id");
        $conn->query("DELETE FROM users WHERE id = $del_id");
        echo "<script>alert('✅ Đã xóa thành viên!'); window.location.href='admin_users.php';</script>";
    }
}

// 3. LẤY DANH SÁCH
$sql = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Thành viên</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #d32f2f; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #333; color: white; }
        tr:hover { background-color: #f9f9f9; }

        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .role-admin { background: #d32f2f; color: white; }
        .role-user { background: #2196F3; color: white; }
        
        /* Style cho nút Xem và Xóa */
        .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; margin-right: 5px;}
        .btn-view { background: #333; color: white; }
        .btn-view:hover { background: #555; }
        .btn-del { border: 1px solid red; color: red; background: white; }
        .btn-del:hover { background: red; color: white; }
        
        .back-btn { text-decoration: none; color: #555; font-weight: bold; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <a href="admin.php" class="back-btn">← Quay lại Dashboard Admin</a>
        
        <h2>👥 Danh sách Thành viên (<?php echo $result->num_rows; ?>)</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và Tên</th>
                    <th>Username</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
                        📞 <?php echo htmlspecialchars($row['phone']); ?>
                    </td>
                    <td>
                        <?php if($row['role'] == 1): ?>
                            <span class="role-badge role-admin">Admin</span>
                        <?php else: ?>
                            <span class="role-badge role-user">Khách</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="admin_user_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-view">👁️ Xem</a>

                        <?php if($row['role'] != 1): ?>
                            <a href="admin_users.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-del" onclick="return confirm('⚠️ Xóa user này sẽ xóa luôn lịch sử mua gói.\nBạn chắc chắn chứ?')">Xóa</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>