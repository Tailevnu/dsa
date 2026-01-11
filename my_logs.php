<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = 0;
// Lấy ID user
$uStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$uStmt->bind_param("s", $_SESSION['user_id']);
$uStmt->execute();
$user_id = $uStmt->get_result()->fetch_assoc()['id'];

// Lấy nhật ký
$sql = "SELECT w.*, e.name as ex_name 
        FROM workout_logs w 
        JOIN exercises e ON w.exercise_id = e.id 
        WHERE w.user_id = ? 
        ORDER BY w.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhật ký tập luyện</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #333; color: white; }
        .date { color: #888; font-size: 13px; }
        .weight { font-weight: bold; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" style="text-decoration:none; color:#555;">← Quay lại tập luyện</a>
        <h2 style="color: #d32f2f;">📅 Nhật ký của tôi</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ngày giờ</th>
                        <th>Bài tập</th>
                        <th>Thành tích</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="date"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td><strong><?php echo $row['ex_name']; ?></strong></td>
                            <td>
                                <span class="weight"><?php echo $row['weight']; ?>kg</span> 
                                x <?php echo $row['reps']; ?> reps
                            </td>
                            <td><?php echo htmlspecialchars($row['note']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Bạn chưa ghi lại buổi tập nào.</p>
        <?php endif; ?>
    </div>
</body>
</html>