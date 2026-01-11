<?php
session_start();
require_once 'db.php';

if (!isset($_GET['id'])) die("Không tìm thấy lịch!");
$r_id = intval($_GET['id']);

// 1. Lấy thông tin lịch
$routine = $conn->query("SELECT * FROM routines WHERE id = $r_id")->fetch_assoc();

// 2. Lấy danh sách bài tập của lịch này
$sql = "SELECT re.*, e.name as ex_name, e.muscle_group 
        FROM routine_exercises re 
        JOIN exercises e ON re.exercise_id = e.id 
        WHERE re.routine_id = $r_id 
        ORDER BY re.day_number ASC";
$result = $conn->query($sql);

// 3. Gom nhóm dữ liệu theo ngày (Day 1, Day 2...)
$schedule = [];
while($row = $result->fetch_assoc()) {
    $schedule[$row['day_number']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Lịch tập</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header-box { background: #333; color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        
        .day-block { background: white; margin-bottom: 25px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .day-header { background: #d32f2f; color: white; padding: 15px; font-weight: bold; font-size: 18px; }
        
        .ex-row { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #eee; align-items: center; }
        .ex-row:last-child { border-bottom: none; }
        .ex-name { font-weight: bold; color: #333; font-size: 16px; display: block; text-decoration: none; }
        .ex-name:hover { color: #d32f2f; }
        .ex-meta { color: #666; font-size: 14px; background: #f9f9f9; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="routines.php" style="text-decoration:none; font-weight:bold; color:#555;">← Quay lại danh sách</a>
        
        <div class="header-box">
            <h1 style="margin:0;"><?php echo $routine['name']; ?></h1>
            <p style="opacity: 0.8;"><?php echo $routine['description']; ?></p>
        </div>

        <?php if (empty($schedule)): ?>
            <p style="text-align: center;">Chưa có bài tập nào trong lịch này.</p>
        <?php else: ?>
            <?php foreach ($schedule as $day => $exercises): ?>
                <div class="day-block">
                    <div class="day-header">🗓️ Buổi tập số <?php echo $day; ?></div>
                    
                    <?php foreach ($exercises as $ex): ?>
                        <div class="ex-row">
                            <a href="index.php?id=<?php echo $ex['exercise_id']; ?>" class="ex-name">
                                <?php echo $ex['ex_name']; ?>
                            </a>
                            <div class="ex-meta">
                                🔢 <?php echo $ex['sets']; ?> hiệp x <?php echo $ex['reps']; ?> cái
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>