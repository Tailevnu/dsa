<?php
session_start();
$result = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu đầu vào
    $gender = $_POST['gender']; // male/female
    $age    = floatval($_POST['age']);
    $height = floatval($_POST['height']); // cm
    $weight = floatval($_POST['weight']); // kg
    $activity = floatval($_POST['activity']);
    $goal   = $_POST['goal']; // cut/maintain/bulk

    if ($age > 0 && $height > 0 && $weight > 0) {
        // 2. Tính BMR (Công thức Mifflin-St Jeor - Chuẩn nhất hiện nay)
        if ($gender == 'male') {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }

        // 3. Tính TDEE (Năng lượng tiêu thụ mỗi ngày)
        $tdee = $bmr * $activity;

        // 4. Điều chỉnh theo mục tiêu (Calories Target)
        $targetCalories = $tdee;
        $goalText = "Giữ cân";
        
        if ($goal == 'cut') {
            $targetCalories = $tdee - 500; // Giảm cân: thâm hụt 500 calo
            $goalText = "Giảm mỡ (Cutting)";
        } elseif ($goal == 'bulk') {
            $targetCalories = $tdee + 400; // Tăng cân: dư 400 calo
            $goalText = "Tăng cơ (Bulking)";
        }

        // 5. Tính Macro (Tỷ lệ vàng cho Gym: 30% Protein - 35% Carb - 35% Fat)
        // 1g Protein = 4 calo, 1g Carb = 4 calo, 1g Fat = 9 calo
        $proteinGram = round(($targetCalories * 0.30) / 4);
        $carbGram    = round(($targetCalories * 0.35) / 4);
        $fatGram     = round(($targetCalories * 0.35) / 9);
        
        $targetCalories = round($targetCalories);

        $result = [
            'calories' => $targetCalories,
            'protein'  => $proteinGram,
            'carb'     => $carbGram,
            'fat'      => $fatGram,
            'goal'     => $goalText
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tính TDEE & Macro - Gym Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        h1 { text-align: center; color: #d32f2f; margin-top: 0; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        
        label { font-weight: bold; display: block; margin-top: 15px; color: #333; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        
        .radio-group { display: flex; gap: 20px; margin-top: 5px; }
        .radio-label { font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        .btn-calc { width: 100%; background: #d32f2f; color: white; padding: 15px; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; margin-top: 25px; cursor: pointer; transition: 0.3s; }
        .btn-calc:hover { background: #b71c1c; }
        
        .result-box { background: #e8f5e9; border: 2px solid #4caf50; border-radius: 10px; padding: 20px; margin-top: 30px; text-align: center; }
        .calories-big { font-size: 40px; font-weight: bold; color: #2e7d32; display: block; margin: 10px 0; }
        
        .macro-grid { display: flex; justify-content: space-between; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px; }
        .macro-item { flex: 1; }
        .macro-val { font-size: 24px; font-weight: bold; display: block; }
        .macro-label { font-size: 14px; color: #555; }
        
        .p-color { color: #d32f2f; } /* Protein đỏ */
        .c-color { color: #fbc02d; } /* Carb vàng */
        .f-color { color: #1976d2; } /* Fat xanh */
        
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #555; }
    </style>
</head>
<body>

    <div class="container">
        <h1>📊 TÍNH MACRO & TDEE</h1>
        <p class="subtitle">Tìm ra con số dinh dưỡng chính xác cho body của bạn</p>

        <form method="POST">
            <label>Giới tính:</label>
            <div class="radio-group">
                <label class="radio-label"><input type="radio" name="gender" value="male" checked> Nam</label>
                <label class="radio-label"><input type="radio" name="gender" value="female"> Nữ</label>
            </div>

            <div style="display: flex; gap: 10px;">
                <div style="flex:1">
                    <label>Tuổi:</label>
                    <input type="number" name="age" placeholder="VD: 25" required>
                </div>
                <div style="flex:1">
                    <label>Chiều cao (cm):</label>
                    <input type="number" name="height" placeholder="VD: 175" required>
                </div>
                <div style="flex:1">
                    <label>Cân nặng (kg):</label>
                    <input type="number" name="weight" placeholder="VD: 70" required>
                </div>
            </div>

            <label>Mức độ vận động:</label>
            <select name="activity">
                <option value="1.2">Ít vận động (Làm văn phòng, ít tập)</option>
                <option value="1.375">Nhẹ (Tập 1-3 buổi/tuần)</option>
                <option value="1.55" selected>Vừa phải (Tập 3-5 buổi/tuần)</option>
                <option value="1.725">Năng động (Tập 6-7 buổi/tuần)</option>
                <option value="1.9">Vận động viên (Tập 2 lần/ngày)</option>
            </select>

            <label>Mục tiêu của bạn:</label>
            <select name="goal">
                <option value="maintain">Giữ cân (Maintenance)</option>
                <option value="cut">Giảm mỡ (Cutting)</option>
                <option value="bulk">Tăng cơ (Bulking)</option>
            </select>

            <button type="submit" class="btn-calc">TÍNH NGAY 🚀</button>
        </form>

        <?php if ($result): ?>
        <div class="result-box">
            <h3>🎯 Kết quả cho mục tiêu: <?php echo $result['goal']; ?></h3>
            
            <span>Bạn cần ăn khoảng:</span>
            <span class="calories-big"><?php echo number_format($result['calories']); ?> CALORIES / ngày</span>
            
            <div class="macro-grid">
                <div class="macro-item">
                    <span class="macro-val p-color"><?php echo $result['protein']; ?>g</span>
                    <span class="macro-label">Protein (Đạm)</span>
                </div>
                <div class="macro-item">
                    <span class="macro-val c-color"><?php echo $result['carb']; ?>g</span>
                    <span class="macro-label">Carb (Tinh bột)</span>
                </div>
                <div class="macro-item">
                    <span class="macro-val f-color"><?php echo $result['fat']; ?>g</span>
                    <span class="macro-label">Fat (Chất béo)</span>
                </div>
            </div>
            
            <p style="margin-top: 15px; font-size: 13px; color: #555;">
                <i>*Đây là ước tính khoa học. Hãy theo dõi cân nặng hàng tuần để điều chỉnh thêm.</i>
            </p>
        </div>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Quay lại trang chủ</a>
    </div>

</body>
</html>