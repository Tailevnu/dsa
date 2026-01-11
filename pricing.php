<?php
session_start();
require_once 'db.php'; // Kết nối CSDL

// Lấy danh sách gói tập từ Database
$sql = "SELECT * FROM packages ORDER BY price ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Giá - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        
        /* Header chung */
        .header { background: #333; color: white; padding: 15px; text-align: center; }
        .header h1 { margin: 0; color: #d32f2f; }
        .header a { color: #fff; text-decoration: none; font-size: 14px; margin-top: 10px; display: inline-block; }

        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .pricing-title { text-align: center; margin-bottom: 40px; color: #333; }
        
        /* Lưới hiển thị các gói (Flexbox) */
        .pricing-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        /* Thiết kế từng thẻ gói tập */
        .package-card {
            background: white;
            border-radius: 15px;
            width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Hiệu ứng khi di chuột */
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(211, 47, 47, 0.2); /* Bóng màu đỏ nhạt */
            border: 1px solid #d32f2f;
        }

        /* Phần đầu thẻ */
        .card-header {
            background: #333;
            color: white;
            padding: 20px;
        }
        .card-header h3 { margin: 0; font-size: 24px; }
        
        /* Phần giá tiền */
        .price {
            font-size: 32px;
            color: #d32f2f;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        .duration { color: #777; font-size: 14px; margin-bottom: 20px; }

        /* Danh sách quyền lợi */
        .features {
            list-style: none;
            padding: 0 20px;
            margin-bottom: 20px;
            flex-grow: 1; /* Đẩy nút xuống đáy */
        }
        .features li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        .features li:last-child { border-bottom: none; }

        /* Nút đăng ký */
        .btn-buy {
            display: block;
            background: #d32f2f;
            color: white;
            text-decoration: none;
            padding: 15px;
            font-weight: bold;
            text-transform: uppercase;
            transition: background 0.3s;
        }
        .btn-buy:hover { background: #b71c1c; }

        /* Responsive cho mobile */
        @media (max-width: 600px) {
            .package-card { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>GÓI TẬP GYM</h1>
        <a href="index.php">← Quay lại trang chủ</a>
    </div>

    <div class="container">
        <h2 class="pricing-title">Chọn gói tập phù hợp với bạn</h2>
        
        <div class="pricing-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Xử lý xuống dòng cho phần mô tả (nếu có dấu xuống dòng trong DB)
                    $features = explode("\n", $row['description']);
            ?>
                <div class="package-card">
                    <div class="card-header">
                        <h3><?php echo $row['name']; ?></h3>
                    </div>
                    
                    <div class="price">
                        <?php echo number_format($row['price'], 0, ',', '.'); ?> đ
                    </div>
                    <div class="duration">Thời hạn: <?php echo $row['duration_days']; ?> ngày</div>
                    
                    <ul class="features">
                        <?php foreach($features as $f): ?>
                            <li>✔️ <?php echo htmlspecialchars($f); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="order.php?id=<?php echo $row['id']; ?>" class="btn-buy">Đăng Ký Ngay</a>
                </div>
                <?php 
                }
            } else {
                echo "<p>Chưa có gói tập nào được tạo.</p>";
            }
            ?>
        </div>
    </div>

</body>
</html>