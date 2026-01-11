<?php
session_start();
require_once 'db.php';

// Check quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) die("Cấm truy cập!");

// 1. XỬ LÝ TẠO LỊCH MỚI
if (isset($_POST['add_routine'])) {
    $name = $_POST['name'];
    $level = $_POST['level'];
    $desc = $_POST['description'];
    $conn->query("INSERT INTO routines (name, level, description) VALUES ('$name', '$level', '$desc')");
    echo "<script>alert('Đã tạo lịch mới!');</script>";
}

// 2. XỬ LÝ THÊM BÀI VÀO LỊCH
if (isset($_POST['add_ex_to_routine'])) {
    $r_id = $_POST['routine_id'];
    $ex_id = $_POST['exercise_id'];
    $day = $_POST['day_number'];
    $sets = $_POST['sets'];
    $reps = $_POST['reps'];
    
    $conn->query("INSERT INTO routine_exercises (routine_id, exercise_id, day_number, sets, reps) VALUES ($r_id, $ex_id, $day, '$sets', '$reps')");
    echo "<script>alert('Đã thêm bài tập vào lịch!');</script>";
}

// Lấy dữ liệu để hiển thị vào Select Box
$routines = $conn->query("SELECT * FROM routines");
$exercises = $conn->query("SELECT * FROM exercises ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Lịch tập</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .container { display: flex; gap: 20px; max-width: 1000px; margin: 0 auto; }
        .box { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h3 { margin-top: 0; color: #d32f2f; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        input, select, textarea { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; background: #333; color: white; padding: 10px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;}
        button:hover { background: #d32f2f; }
    </style>
</head>
<body>
    <a href="admin.php" style="text-decoration:none; font-weight:bold; color:#555;">← Về Dashboard</a>
    
    <div class="container" style="margin-top: 20px;">
        <div class="box">
            <h3>1. Tạo Lịch Mới</h3>
            <form method="POST">
                <label>Tên lịch (VD: Full Body 3 ngày):</label>
                <input type="text" name="name" required>
                
                <label>Trình độ:</label>
                <select name="level">
                    <option value="Newbie">Người mới (Newbie)</option>
                    <option value="Intermediate">Trung cấp</option>
                    <option value="Advanced">Nâng cao</option>
                </select>
                
                <label>Mô tả ngắn:</label>
                <textarea name="description" rows="3"></textarea>
                
                <button type="submit" name="add_routine">Tạo Lịch</button>
            </form>
        </div>

        <div class="box">
            <h3>2. Thêm Bài vào Lịch</h3>
            <form method="POST">
                <label>Chọn Lịch tập:</label>
                <select name="routine_id">
                    <?php while($r = $routines->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Chọn Bài tập:</label>
                <select name="exercise_id">
                    <?php while($ex = $exercises->fetch_assoc()): ?>
                        <option value="<?php echo $ex['id']; ?>"><?php echo $ex['name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <div style="display: flex; gap: 10px;">
                    <div style="flex:1">
                        <label>Ngày (1,2..):</label>
                        <input type="number" name="day_number" value="1" required>
                    </div>
                    <div style="flex:1">
                        <label>Số Hiệp (Sets):</label>
                        <input type="number" name="sets" value="3">
                    </div>
                    <div style="flex:1">
                        <label>Số Cái (Reps):</label>
                        <input type="text" name="reps" value="8-12">
                    </div>
                </div>

                <button type="submit" name="add_ex_to_routine">Thêm bài này vào lịch</button>
            </form>
        </div>
    </div>
</body>
</html>