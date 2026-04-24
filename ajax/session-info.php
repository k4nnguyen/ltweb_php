<?php
/**
 * AJAX endpoint để lấy thông tin session hiện tại
 * Không update last_activity để cho countdown chạy liên tục
 */

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Not logged in']);
    exit;
}

$SESSION_TIMEOUT = 30 * 60; // 30 phút (match với check-session.php)

// Tính thời gian còn lại từ login_time (không phải last_activity)
if (isset($_SESSION['login_time'])) {
    $timeFromLogin = time() - $_SESSION['login_time'];
    $time_remaining = $SESSION_TIMEOUT - $timeFromLogin;
    
    if ($time_remaining < 0) {
        $time_remaining = 0;
    }
} else {
    $time_remaining = $SESSION_TIMEOUT;
}

$minutes = floor($time_remaining / 60);
$seconds = $time_remaining % 60;

echo json_encode([
    'success' => true,
    'remaining_seconds' => $time_remaining,
    'remaining_formatted' => "$minutes phút $seconds giây",
    'is_critical' => $time_remaining < 300 // < 5 phút
]);
exit;
?>
