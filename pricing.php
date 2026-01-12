<?php
session_start();
require_once 'db.php';
require_once 'MergeSort.php';

$sql = "SELECT * FROM packages";
$result = $conn->query($sql);
$packages = [];
while($row = $result->fetch_assoc()) { $packages[] = $row; }

// Áp dụng Merge Sort để sắp xếp theo giá
mergeSort($packages, 'price');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Giá - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        .header { background: #333; color: white; padding: 15px; text-align: center; }
        .header h1 { margin: 0; color: #d32f2f; }
        .container { max-width: 1000px; margin: 40px auto; display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .package-card { background: white; border-radius: 15px; width: 280px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; overflow: hidden; display: flex; flex-direction: column; }
        .card-header { background: #333; color: white; padding: 20px; }
        .price { font-size: 32px; color: #d32f2f; font-weight: bold; padding: 20px; }
        .btn-buy { background: #d32f2f; color: white; text-decoration: none; padding: 15px; font-weight: bold; margin-top: auto; }
    </style>
</head>
<body>
    <div class="header"><h1>GÓI TẬP GYM</h1><a href="index.php" style="color:#fff;">← Quay lại</a></div>
    <div class="container">
        <?php foreach($packages as $row): ?>
            <div class="package-card">
                <div class="card-header"><h3><?php echo $row['name']; ?></h3></div>
                <div class="price"><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</div>
                <div style="padding: 20px; color: #666;">Thời hạn: <?php echo $row['duration_days']; ?> ngày</div>
                <a href="order.php?id=<?php echo $row['id']; ?>" class="btn-buy">ĐĂNG KÝ</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>