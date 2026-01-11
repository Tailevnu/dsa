<?php
session_start();
require_once 'db.php';
require_once 'HashTable.php'; // Đảm bảo bạn vẫn giữ file này cùng thư mục

// =========================================================================
// PHẦN 1: XỬ LÝ POST (GHI NHẬT KÝ & BÌNH LUẬN)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // Lấy ID User số (integer) từ username trong session
    $current_user = $_SESSION['user_id'];
    $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $uStmt->bind_param("s", $current_user);
    $uStmt->execute();
    $uResult = $uStmt->get_result();

    if ($uResult->num_rows > 0) {
        $uID = $uResult->fetch_assoc()['id'];

        // A. Xử lý Ghi Nhật Ký (Log Workout)
        if (isset($_POST['action']) && $_POST['action'] == 'log_workout') {
            $ex_id = intval($_POST['exercise_id']);
            $weight = floatval($_POST['weight']);
            $reps = intval($_POST['reps']);
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';
            
            // SỬA LỖI: Dùng đúng định dạng "iidis" (int, int, double, int, string)
            $stmt = $conn->prepare("INSERT INTO workout_logs (user_id, exercise_id, weight, reps, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidis", $uID, $ex_id, $weight, $reps, $note);
            
            if ($stmt->execute()) {
                header("Location: index.php?id=$ex_id"); // Reload để tránh gửi lại form
                exit;
            } else {
                echo "<script>alert('Lỗi: " . $stmt->error . "');</script>";
            }
        }

        // B. Xử lý Bình luận (Comment)
        if (isset($_POST['action']) && $_POST['action'] == 'post_comment') {
            $ex_id = intval($_POST['exercise_id']);
            $content = trim($_POST['content']);
            
            if (!empty($content)) {
                $stmt = $conn->prepare("INSERT INTO comments (user_id, exercise_id, content) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $uID, $ex_id, $content);
                $stmt->execute();
            }
            header("Location: index.php?id=$ex_id");
            exit;
        }
    }
}

// =========================================================================
// PHẦN 2: KHỞI TẠO DỮ LIỆU & LOGIC HIỂN THỊ
// =========================================================================

// 1. Lấy toàn bộ bài tập từ DB
$exercises = [];
$sql = "SELECT * FROM exercises";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['video'] = $row['video_id']; // Chuẩn hóa key
        $exercises[] = $row;
    }
}

// 2. Nạp vào Hash Table (Để lọc nhóm cơ nhanh)
$gymTable = new GymHashTable(20);
foreach ($exercises as $ex) {
    $gymTable->insert($ex['muscle_group'], $ex);
}

// 3. Xác định Màn hình hiển thị (View Mode)
$viewMode = 'home';
$dataToShow = [];
$detailExercise = null;
$pageTitle = "Khám phá bài tập";
$commentsList = []; 

// A. Chế độ xem Chi tiết (Detail)
if (isset($_GET['id'])) {
    $viewMode = 'detail';
    $id = intval($_GET['id']);
    
    foreach ($exercises as $ex) {
        if ($ex['id'] == $id) {
            $detailExercise = $ex;
            $pageTitle = $ex['name'];
            
            // Lấy comment của bài này
            $cSql = "SELECT c.*, u.full_name, u.username 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.exercise_id = $id 
                     ORDER BY c.created_at DESC";
            $cRes = $conn->query($cSql);
            while($cRow = $cRes->fetch_assoc()) {
                $commentsList[] = $cRow;
            }
            break;
        }
    }
}
// B. Chế độ Tìm kiếm (Search)
elseif (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $viewMode = 'list';
    $keyword = strtolower(trim($_GET['keyword']));
    $pageTitle = "Tìm kiếm: \"" . htmlspecialchars($_GET['keyword']) . "\"";
    
    foreach ($exercises as $ex) {
        if (strpos(strtolower($ex['name']), $keyword) !== false || 
            strpos(strtolower($ex['muscle_group']), $keyword) !== false) {
            $dataToShow[] = $ex;
        }
    }
}
// C. Chế độ xem Nhóm cơ (Hash Table Filter)
elseif (isset($_GET['group'])) {
    $viewMode = 'list';
    $group = $_GET['group'];
    $dataToShow = $gymTable->search($group); // Dùng thuật toán Hash Table
    $pageTitle = ucfirst($group);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Gym Assistant</title>
    <style>
        /* CSS CHUẨN UX */
        * { box-sizing: border_box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 15px; min-height: 90vh; display: flex; flex-direction: column;}
        
        /* Header & Search Bar */
        .app-header { text-align: center; margin-bottom: 20px; }
        .logo { color: #d32f2f; font-size: 24px; font-weight: bold; text-decoration: none; display: block; margin-bottom: 15px; }
        
        .search-container { position: relative; max-width: 500px; margin: 0 auto; }
        .search-input { width: 100%; padding: 15px 20px; border-radius: 30px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05); font-size: 16px; outline: none; }
        .search-btn { position: absolute; right: 5px; top: 5px; background: #d32f2f; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }

        /* Quick Categories (Thanh nhóm cơ) */
        .quick-nav { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; justify-content: flex-start; scrollbar-width: none; }
        .quick-nav::-webkit-scrollbar { display: none; }
        .chip { white-space: nowrap; padding: 8px 16px; background: white; border: 1px solid #eee; border-radius: 20px; text-decoration: none; color: #555; font-size: 14px; font-weight: 500; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .chip:hover, .chip.active { background: #d32f2f; color: white; border-color: #d32f2f; }

        /* VIEW 1: HOME GRID */
        .home-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px; }
        .category-card { background: white; padding: 20px; border-radius: 12px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; border: 2px solid transparent;}
        .category-card:hover { border-color: #d32f2f; transform: translateY(-3px); }
        .cat-icon { font-size: 30px; display: block; margin-bottom: 10px; }
        .cat-name { font-weight: bold; font-size: 16px; }

        /* VIEW 2: LIST ITEM */
        .list-container { margin-top: 20px; }
        .exercise-item { display: flex; align-items: center; background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; text-decoration: none; color: #333; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #ddd; transition: 0.2s; }
        .exercise-item:hover { border-left-color: #d32f2f; transform: translateX(5px); }
        .ex-thumb { width: 60px; height: 60px; background: #eee; border-radius: 8px; margin-right: 15px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .ex-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .ex-info span { font-size: 13px; color: #777; background: #f0f0f0; padding: 2px 8px; border-radius: 4px; }

        /* VIEW 3: DETAIL */
        .detail-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 20px; }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; background: black; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .detail-content { padding: 20px; }
        .section-header { font-weight: bold; color: #d32f2f; margin-top: 25px; display: block; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px;}
        .warning-box { background: #fff5f5; border: 1px dashed #ffcdd2; padding: 15px; border-radius: 8px; color: #c62828; margin-top: 15px; }

        /* LOGGING & COMMENTS */
        .log-box { background: #e3f2fd; padding: 15px; border-radius: 8px; border: 1px solid #90caf9; margin-top: 20px; }
        .log-form { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .log-input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 80px; }
        .btn-log { background: #1976d2; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        .comment-item { background: #f9f9f9; padding: 10px; border-radius: 8px; margin-bottom: 10px; border-bottom: 1px solid #eee; }
        .cmt-user { font-weight: bold; color: #333; font-size: 13px; }
        .cmt-date { color: #888; font-size: 11px; float: right; }
        .cmt-text { margin-top: 5px; font-size: 14px; line-height: 1.4; color: #444; }

        /* Footer & User Bar */
        .user-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 13px; border-bottom: 1px solid #eee; margin-bottom: 20px;}
        .btn-link { color: #d32f2f; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <div class="user-bar">
            <div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_id']); ?></strong>
                <?php else: ?>
                    Chào khách tham quan!
                <?php endif; ?>
            </div>
            <div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my_logs.php" style="color: #1976d2; font-weight:bold; margin-right:10px; text-decoration:none;">📅 Nhật ký</a>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                        <a href="admin.php" class="btn-link">Admin</a> | 
                    <?php endif; ?>
                    <a href="profile.php" class="btn-link">Hồ sơ</a> | 
                    <a href="logout.php" style="color:#666; text-decoration:none;">Thoát</a>
                <?php else: ?>
                    <a href="login.php" class="btn-link">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="app-header">
            <a href="index.php" class="logo">GYM ASSISTANT 🏋️</a>
            
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="text" name="keyword" class="search-input" placeholder="Tìm bài tập (VD: Ngực, Squat...)" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>

            <div class="quick-nav">
                <a href="index.php" class="chip <?php echo $viewMode=='home'?'active':''; ?>">🏠 Tất cả</a>
                <a href="index.php?group=chest" class="chip <?php echo (isset($_GET['group']) && $_GET['group']=='chest')?'active':''; ?>">Ngực</a>
                <a href="index.php?group=back" class="chip <?php echo (isset($_GET['group']) && $_GET['group']=='back')?'active':''; ?>">Lưng</a>
                <a href="index.php?group=legs" class="chip <?php echo (isset($_GET['group']) && $_GET['group']=='legs')?'active':''; ?>">Chân</a>
                <a href="index.php?group=shoulders" class="chip <?php echo (isset($_GET['group']) && $_GET['group']=='shoulders')?'active':''; ?>">Vai</a>
                <a href="routines.php" class="chip" style="color: #fff; background: #333; border-color: #333;">📅 Lịch tập</a>
                <a href="tdee.php" class="chip" style="color: #fff; background: #28a745; border-color: #28a745;">🥗 Tính Macro</a>
                <a href="pricing.php" class="chip" style="color: #fff; background: #d32f2f; border-color: #d32f2f;">💰 Bảng giá</a>
            </div>
        </div>

        <?php if ($viewMode == 'home'): ?>
            <h3 style="margin-bottom: 10px; color: #555;">Bạn muốn tập gì hôm nay?</h3>
            <div class="home-grid">
                <a href="?group=chest" class="category-card"><span class="cat-icon">🦍</span><span class="cat-name">Ngực (Chest)</span></a>
                <a href="?group=back" class="category-card"><span class="cat-icon">🐢</span><span class="cat-name">Lưng (Back)</span></a>
                <a href="?group=legs" class="category-card"><span class="cat-icon">🦵</span><span class="cat-name">Chân (Legs)</span></a>
                <a href="?group=shoulders" class="category-card"><span class="cat-icon">🤷</span><span class="cat-name">Vai (Shoulder)</span></a>
                <a href="?group=arms" class="category-card"><span class="cat-icon">💪</span><span class="cat-name">Tay (Arms)</span></a>
                <a href="?group=abs" class="category-card"><span class="cat-icon">🍫</span><span class="cat-name">Bụng (Abs)</span></a>
            </div>

        <?php elseif ($viewMode == 'list'): ?>
            <h3 style="margin-bottom: 15px;">Kết quả: <?php echo $pageTitle; ?></h3>
            
            <?php if (empty($dataToShow)): ?>
                <div style="text-align:center; padding: 40px; color: #777;">
                    <p>😞 Không tìm thấy bài tập nào.</p>
                    <a href="index.php" class="btn-link">Quay lại trang chủ</a>
                </div>
            <?php else: ?>
                <div class="list-container">
                    <?php foreach ($dataToShow as $item): ?>
                        <a href="index.php?id=<?php echo $item['id']; ?>" class="exercise-item">
                            <div class="ex-thumb">🏋️</div>
                            <div class="ex-info">
                                <h3><?php echo $item['name']; ?></h3>
                                <span>Nhóm: <?php echo ucfirst($item['muscle_group']); ?></span>
                            </div>
                            <div style="margin-left: auto; color: #ccc;">ᐳ</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($viewMode == 'detail' && $detailExercise): ?>
            <div class="detail-card">
                <?php if (!empty($detailExercise['video'])): ?>
                    <div class="video-wrapper">
                        <iframe src="https://www.youtube.com/embed/<?php echo $detailExercise['video']; ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
                
                <div class="detail-content">
                    <h2 style="margin-top: 0; color: #333;"><?php echo $detailExercise['name']; ?></h2>
                    
                    <div class="log-box">
                        <strong>📝 Ghi lại thành tích hôm nay:</strong>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="log-form">
                                <input type="hidden" name="action" value="log_workout">
                                <input type="hidden" name="exercise_id" value="<?php echo $detailExercise['id']; ?>">
                                <input type="number" name="weight" class="log-input" placeholder="Số kg" required step="0.5">
                                <input type="number" name="reps" class="log-input" placeholder="Số cái" required>
                                <input type="text" name="note" class="log-input" placeholder="Ghi chú (Tùy chọn)" style="flex: 2;">
                                <button type="submit" class="btn-log">Lưu</button>
                            </form>
                        <?php else: ?>
                            <p style="font-size: 13px; color: #666; margin: 5px 0;">Vui lòng <a href="login.php">Đăng nhập</a> để ghi nhật ký tập.</p>
                        <?php endif; ?>
                    </div>

                    <span class="section-header">📘 Hướng dẫn thực hiện:</span>
                    <div style="line-height: 1.6; color: #444;">
                        <?php echo nl2br($detailExercise['guide']); ?>
                    </div>

                    <?php if (!empty($detailExercise['mistakes'])): ?>
                        <div class="warning-box">
                            <strong>❌ Lưu ý lỗi sai:</strong><br>
                            <?php echo nl2br($detailExercise['mistakes']); ?>
                        </div>
                    <?php endif; ?>

                    <span class="section-header">💬 Hỏi đáp & Thảo luận:</span>
                    <div class="comment-section">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <form method="POST" style="margin-bottom: 20px;">
                                <input type="hidden" name="action" value="post_comment">
                                <input type="hidden" name="exercise_id" value="<?php echo $detailExercise['id']; ?>">
                                <textarea name="content" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;" rows="2" placeholder="Bạn có thắc mắc gì không?" required></textarea>
                                <button type="submit" style="background: #333; color: white; border: none; padding: 5px 15px; border-radius: 4px; margin-top: 5px; cursor: pointer;">Gửi bình luận</button>
                            </form>
                        <?php else: ?>
                            <p style="color:#666; font-style:italic;">Đăng nhập để bình luận.</p>
                        <?php endif; ?>

                        <?php if(empty($commentsList)): ?>
                            <p style="color: #999; font-style: italic;">Chưa có bình luận nào.</p>
                        <?php else: ?>
                            <?php foreach($commentsList as $cmt): ?>
                                <div class="comment-item">
                                    <div class="cmt-user">
                                        <?php echo htmlspecialchars($cmt['full_name'] ? $cmt['full_name'] : $cmt['username']); ?>
                                        <span class="cmt-date"><?php echo date('d/m/Y', strtotime($cmt['created_at'])); ?></span>
                                    </div>
                                    <div class="cmt-text"><?php echo nl2br(htmlspecialchars($cmt['content'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 30px; text-align: center;">
                        <a href="index.php?group=<?php echo $detailExercise['muscle_group']; ?>" class="btn-link">← Xem bài khác cùng nhóm</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>