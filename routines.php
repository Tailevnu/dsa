<?php
session_start();
require_once 'db.php';
$result = $conn->query("SELECT * FROM routines");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch tập mẫu</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        
        .routine-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .routine-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; border-top: 5px solid #d32f2f; text-decoration: none; color: #333; display: block; }
        .routine-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .level-badge { background: #eee; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: #555; }
        
        .btn-home { text-decoration: none; color: #555; font-weight: bold; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-home">← Quay lại trang chủ</a>
        
        <div class="header">
            <h1 style="color: #d32f2f; margin: 0;">📋 LỊCH TẬP MẪU</h1>
            <p>Được thiết kế chuẩn khoa học cho từng cấp độ</p>
        </div>

        <div class="routine-grid">
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="routine_detail.php?id=<?php echo $row['id']; ?>" class="routine-card">
                    <h2 style="margin-top: 0;"><?php echo $row['name']; ?></h2>
                    <span class="level-badge"><?php echo $row['level']; ?></span>
                    <p style="color: #666; line-height: 1.5;"><?php echo $row['description']; ?></p>
                    <div style="margin-top: 15px; color: #d32f2f; font-weight: bold;">Xem chi tiết →</div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>