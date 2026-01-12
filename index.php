<?php
session_start();
require_once 'db.php';
require_once 'HashTable.php';
require_once 'MergeSort.php'; // Đã tích hợp Merge Sort

// =========================================================================
// PHẦN 1: XỬ LÝ POST (NHẬT KÝ & BÌNH LUẬN)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $current_user = $_SESSION['user_id'];
    $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $uStmt->bind_param("s", $current_user);
    $uStmt->execute();
    $uResult = $uStmt->get_result();
    if ($uResult->num_rows > 0) {
        $uID = $uResult->fetch_assoc()['id'];
        if (isset($_POST['action']) && $_POST['action'] == 'log_workout') {
            $ex_id = intval($_POST['exercise_id']);
            $weight = floatval($_POST['weight']); $reps = intval($_POST['reps']);
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';
            $stmt = $conn->prepare("INSERT INTO workout_logs (user_id, exercise_id, weight, reps, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidis", $uID, $ex_id, $weight, $reps, $note);
            if ($stmt->execute()) { header("Location: index.php?id=$ex_id"); exit; }
        }
        if (isset($_POST['action']) && $_POST['action'] == 'post_comment') {
            $ex_id = intval($_POST['exercise_id']);
            $content = trim($_POST['content']);
            if (!empty($content)) {
                $stmt = $conn->prepare("INSERT INTO comments (user_id, exercise_id, content) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $uID, $ex_id, $content); $stmt->execute();
            }
            header("Location: index.php?id=$ex_id"); exit;
        }
    }
}

// =========================================================================
// PHẦN 2: KHỞI TẠO DỮ LIỆU & THUẬT TOÁN
// =========================================================================
$exercises = [];
$sql = "SELECT * FROM exercises";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { $row['video'] = $row['video_id']; $exercises[] = $row; }
}

$gymTable = new GymHashTable(20);
foreach ($exercises as $ex) { $gymTable->insert($ex['muscle_group'], $ex); }

$viewMode = 'home'; $dataToShow = []; $detailExercise = null; $pageTitle = "Khám phá bài tập"; $commentsList = []; 

if (isset($_GET['id'])) {
    $viewMode = 'detail'; $id = intval($_GET['id']);
    foreach ($exercises as $ex) {
        if ($ex['id'] == $id) {
            $detailExercise = $ex; $pageTitle = $ex['name'];
            $cSql = "SELECT c.*, u.full_name, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.exercise_id = $id ORDER BY c.created_at DESC";
            $cRes = $conn->query($cSql);
            while($cRow = $cRes->fetch_assoc()) { $commentsList[] = $cRow; }
            break;
        }
    }
}
elseif (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $viewMode = 'list'; $keyword = strtolower(trim($_GET['keyword']));
    $pageTitle = "Tìm kiếm: \"" . htmlspecialchars($_GET['keyword']) . "\"";
    foreach ($exercises as $ex) {
        if (strpos(strtolower($ex['name']), $keyword) !== false || strpos(strtolower($ex['muscle_group']), $keyword) !== false) { $dataToShow[] = $ex; }
    }
    mergeSort($dataToShow, 'name'); // DSA: Sắp xếp kết quả tìm kiếm A-Z
}
elseif (isset($_GET['group'])) {
    $viewMode = 'list'; $group = $_GET['group'];
    $dataToShow = $gymTable->search($group); $pageTitle = ucfirst($group);
    mergeSort($dataToShow, 'name'); // DSA: Sắp xếp kết quả lọc nhóm cơ A-Z
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Gym Assistant</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 15px; min-height: 90vh; display: flex; flex-direction: column;}
        .app-header { text-align: center; margin-bottom: 20px; }
        .logo { color: #d32f2f; font-size: 24px; font-weight: bold; text-decoration: none; display: block; margin-bottom: 15px; }
        .search-container { position: relative; max-width: 500px; margin: 0 auto; }
        .search-input { width: 100%; padding: 15px 20px; border-radius: 30px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05); font-size: 16px; outline: none; }
        .search-btn { position: absolute; right: 5px; top: 5px; background: #d32f2f; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }
        .quick-nav { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; justify-content: flex-start; scrollbar-width: none; }
        .chip { white-space: nowrap; padding: 8px 16px; background: white; border: 1px solid #eee; border-radius: 20px; text-decoration: none; color: #555; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .chip:hover, .chip.active { background: #d32f2f; color: white; border-color: #d32f2f; }
        
        .home-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px; }
        .category-card { background: white; padding: 35px 20px; border-radius: 12px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; border: 2px solid transparent; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; }
        .category-card:hover { border-color: #d32f2f; transform: translateY(-3px); }

        .exercise-item { display: flex; align-items: center; background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; text-decoration: none; color: #333; border-left: 4px solid #ddd; transition: 0.2s; }
        .exercise-item:hover { border-left-color: #d32f2f; transform: translateX(5px); }
        .detail-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 20px; }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; background: black; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .detail-content { padding: 20px; }
        .section-header { font-weight: bold; color: #d32f2f; margin-top: 25px; display: block; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .warning-box { background: #fff5f5; border: 1px dashed #ffcdd2; padding: 15px; border-radius: 8px; color: #c62828; margin-top: 15px; }
        .log-box { background: #e3f2fd; padding: 15px; border-radius: 8px; border: 1px solid #90caf9; margin-top: 20px; }
        .log-form { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .log-input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-log { background: #1976d2; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .comment-item { background: #f9f9f9; padding: 10px; border-radius: 8px; margin-bottom: 10px; border-bottom: 1px solid #eee; }
        .user-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 13px; border-bottom: 1px solid #eee; margin-bottom: 20px;}
        .btn-link { color: #d32f2f; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-bar">
            <div><?php if (isset($_SESSION['user_id'])): ?>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_id']); ?></strong><?php endif; ?></div>
            <div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my_logs.php" style="color: #1976d2; font-weight:bold; margin-right:10px; text-decoration:none;">📅 Nhật ký</a>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?><a href="admin.php" class="btn-link">Admin</a> | <?php endif; ?>
                    <a href="profile.php" class="btn-link">Hồ sơ</a> | <a href="logout.php">Thoát</a>
                <?php else: ?>
                    <a href="login.php" class="btn-link">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="app-header">
            <a href="index.php" class="logo">GYM ASSISTANT 🏋️</a>
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="text" name="keyword" class="search-input" placeholder="Tìm bài tập..." value="<?php echo $_GET['keyword'] ?? ''; ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>
            <div class="quick-nav">
                <a href="index.php" class="chip <?php echo $viewMode=='home'?'active':''; ?>">🏠 Tất cả</a>
                <a href="index.php?group=chest" class="chip">Ngực</a>
                <a href="index.php?group=back" class="chip">Lưng</a>
                <a href="routines.php" class="chip" style="color: #fff; background: #333;">📅 Lịch tập</a>
                <a href="tdee.php" class="chip" style="color: #fff; background: #28a745;">🥗 Tính Macro</a>
                <a href="pricing.php" class="chip" style="color: #fff; background: #d32f2f;">💰 Bảng giá</a>
            </div>
        </div>

        <?php if ($viewMode == 'home'): ?>
            <div class="home-grid">
                <a href="?group=chest" class="category-card">Ngực (Chest)</a>
                <a href="?group=back" class="category-card">Lưng (Back)</a>
                <a href="?group=legs" class="category-card">Chân (Legs)</a>
                <a href="?group=shoulders" class="category-card">Vai (Shoulder)</a>
                <a href="?group=arms" class="category-card">Tay (Arms)</a>
                <a href="?group=abs" class="category-card">Bụng (Abs)</a>
            </div>

        <?php elseif ($viewMode == 'list'): ?>
            <h3 style="margin-bottom: 15px;"><?php echo $pageTitle; ?></h3>
            <div class="list-container">
                <?php foreach ($dataToShow as $item): ?>
                    <a href="index.php?id=<?php echo $item['id']; ?>" class="exercise-item">
                        <div class="ex-info"><h3><?php echo $item['name']; ?></h3><span>Nhóm: <?php echo ucfirst($item['muscle_group']); ?></span></div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($viewMode == 'detail' && $detailExercise): ?>
            <div class="detail-card">
                <?php if (!empty($detailExercise['video'])): ?>
                    <div class="video-wrapper"><iframe src="https://www.youtube.com/embed/<?php echo $detailExercise['video']; ?>" frameborder="0" allowfullscreen></iframe></div>
                <?php endif; ?>
                <div class="detail-content">
                    <h2><?php echo $detailExercise['name']; ?></h2>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="log-box">
                        <strong>📝 Ghi lại thành tích:</strong>
                        <form method="POST" class="log-form">
                            <input type="hidden" name="action" value="log_workout">
                            <input type="hidden" name="exercise_id" value="<?php echo $detailExercise['id']; ?>">
                            <input type="number" name="weight" class="log-input" placeholder="Kg" required step="0.5">
                            <input type="number" name="reps" class="log-input" placeholder="Cái" required>
                            <button type="submit" class="btn-log">Lưu</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <span class="section-header">📘 Hướng dẫn:</span>
                    <p><?php echo nl2br($detailExercise['guide']); ?></p>

                    <?php if (!empty($detailExercise['mistakes'])): ?>
                        <div class="warning-box"><strong>❌ Lưu ý lỗi sai:</strong><br><?php echo nl2br($detailExercise['mistakes']); ?></div>
                    <?php endif; ?>

                    <span class="section-header">💬 Bình luận:</span>
                    <form method="POST" style="margin-bottom: 20px;">
                        <input type="hidden" name="action" value="post_comment">
                        <input type="hidden" name="exercise_id" value="<?php echo $detailExercise['id']; ?>">
                        <textarea name="content" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ddd;" rows="2" placeholder="Viết bình luận..." required></textarea>
                        <button type="submit" style="background:#333; color:white; border:none; padding:5px 15px; border-radius:4px; margin-top:5px; cursor:pointer;">Gửi</button>
                    </form>
                    <?php foreach($commentsList as $cmt): ?>
                        <div class="comment-item"><strong><?php echo htmlspecialchars($cmt['full_name']); ?>:</strong><br><?php echo nl2br(htmlspecialchars($cmt['content'])); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>