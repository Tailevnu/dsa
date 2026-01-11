<?php
session_start();       // Khởi động session để biết đang hủy cái gì
session_unset();       // Xóa các biến session (user_id, role,...)
session_destroy();     // Hủy hoàn toàn phiên làm việc

// Chuyển hướng về trang chủ
header("Location: index.php");
exit;
?>