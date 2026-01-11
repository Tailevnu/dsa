<?php
session_start();
require_once 'db.php';

// 1. BẢO MẬT: Kiểm tra role = 1
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    die("Bạn không có quyền truy cập trang này!");
}

// XỬ LÝ THÊM BÀI TẬP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_exercise'])) {
    $name = $_POST['name'];
    $group = $_POST['muscle_group'];
    $guide = $_POST['guide'];
    $mistake = $_POST['mistakes'];
    $video = $_POST['video_id'];

    $stmt = $conn->prepare("INSERT INTO exercises (name, muscle_group, guide, mistakes, video_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $group, $guide, $mistake, $video);
    
    if($stmt->execute()) echo "<script>alert('Thêm thành công!'); window.location.href='admin_exercises.php';</script>";
}

// XỬ LÝ XÓA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM exercises WHERE id = $id");
    header("Location: admin_exercises.php");
    exit;
}

$result = $conn->query("SELECT * FROM exercises ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý bài tập</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .container { display: flex; gap: 20px; flex-wrap: wrap;}
        .form-box { background: white; padding: 20px; width: 300px; height: fit-content; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .list-box { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); min-width: 300px; }
        
        input, textarea, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { width: 100%; background: #d32f2f; color: white; padding: 10px; border: none; cursor: pointer; border-radius: 4px;}
        
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; }
        .btn-del { color: red; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <a href="admin.php" style="text-decoration:none; color:#333; font-weight:bold; margin-bottom:15px; display:inline-block;">← Quay lại Dashboard Admin</a>
    
    <div class="container">
        <div class="form-box">
            <h3>➕ Thêm bài tập mới</h3>
            <form method="POST">
                <label>Tên bài tập:</label>
                <input type="text" name="name" required>

                <label>Nhóm cơ:</label>
                <select name="muscle_group">
                    <option value="chest">Ngực (Chest)</option>
                    <option value="back">Lưng (Back)</option>
                    <option value="legs">Chân (Legs)</option>
                    <option value="shoulders">Vai (Shoulders)</option>
                    <option value="arms">Tay (Arms)</option>
                    <option value="abs">Bụng (Abs)</option>
                </select>

                <label>Hướng dẫn:</label>
                <textarea name="guide" rows="4" required></textarea>

                <label>Lưu ý / Sai lầm:</label>
                <textarea name="mistakes" rows="3"></textarea>

                <label>Youtube ID (VD: rT7DgCr-3pg):</label>
                <input type="text" name="video_id">

                <button type="submit" name="add_exercise">Lưu bài tập</button>
            </form>
        </div>

        <div class="list-box">
            <h3>Danh sách bài tập hiện có</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Tên bài</th>
                    <th>Nhóm</th>
                    <th>Hành động</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo ucfirst($row['muscle_group']); ?></td>
                    <td>
                        <a href="admin_exercises.php?delete=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Xóa bài này?')">Xóa</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

</body>
</html>