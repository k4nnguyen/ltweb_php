<?php
/**
 * Session Helper - Kiểm tra và quản lý session admin
 * Include file này ở đầu các trang admin để bảo vệ
 */

// Set timezone cho Vietnam (múi giờ Đông Dương)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bắt đầu session nếu chưa bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth helpers
require_once __DIR__ . '/helpers/auth.php';

// Timeout session (tính bằng giây)
// Hiện tại: 30 phút = 1800 giây
// Bạn có thể đổi: 
//   - 5 * 60   = 5 phút
//   - 15 * 60  = 15 phút
//   - 30 * 60  = 30 phút
//   - 60 * 60  = 1 giờ
$SESSION_TIMEOUT = 30 * 60;  // ← ĐỔI CON SỐ NÀY ĐỂ THAY ĐỔI TIMEOUT

// Kiểm tra xem admin đã login chưa
if (!isset($_SESSION['admin_id'])) {
    // Chưa login -> redirect đến login page
    header('Location: /admin_vi_pham/admin/login.php');
    exit;
}

// Kiểm tra session timeout (tính từ login_time, không phải last_activity)
if (isset($_SESSION['login_time'])) {
    $timeFromLogin = time() - $_SESSION['login_time'];
    
    if ($timeFromLogin > $SESSION_TIMEOUT) {
        // Session hết hạn -> destroy và redirect
        session_destroy();
        header('Location: /admin_vi_pham/admin/login.php?timeout=1');
        exit;
    }
}

// Kiểm tra xem có logout request không
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin_vi_pham/admin/login.php?logout=1');
    exit;
}

// ===== Tính toán thông tin hiển thị trên navbar =====

// KIỂM TRA: Nếu login_time không tồn tại, điều này có nghĩa là session bị lỗi
if (!isset($_SESSION['login_time'])) {
    // Debug: Ghi log khi login_time không tồn tại
    error_log("WARNING: \$_SESSION['login_time'] không được set! Login ID: " . $_SESSION['admin_id']);
    // Force redirect về login lại
    session_destroy();
    header('Location: /admin_vi_pham/admin/login.php?error=session');
    exit;
}

// Thời gian đăng nhập
$login_time = $_SESSION['login_time'];
$login_time_formatted = date('H:i A', $login_time); // VD: 10:30 AM

// Thời gian còn lại (tính từ login_time)
$current_time = time();
$timeFromLogin = $current_time - $_SESSION['login_time'];
$time_remaining = $SESSION_TIMEOUT - $timeFromLogin;

// Format thời gian còn lại
$minutes_remaining = floor($time_remaining / 60);
$seconds_remaining = $time_remaining % 60;
$session_remaining_formatted = "$minutes_remaining phút $seconds_remaining giây"; // VD: 28 phút 45 giây

// Lưu vào biến global để navbar sử dụng
$_SESSION['session_info'] = [
    'login_time_display' => $login_time_formatted,
    'remaining_time_display' => $session_remaining_formatted,
    'remaining_seconds' => $time_remaining
];
?>
