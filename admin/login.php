<?php
// admin/login.php

// Set timezone cho Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

require_once '../includes/helpers/auth.php';

// Redirect nếu đã login
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Kiểm tra xem có logout từ timeout hay click logout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Phiên đăng nhập hết hạn! Vui lòng đăng nhập lại.';
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $error = 'Đã đăng xuất thành công.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Authenticate using config file (password is hashed)
    $user = authenticateUser($username, $password);
    
    if ($user !== null) {
        // Destroy old session để tránh session fixation
        session_destroy();
        session_start();
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        // Login successful - set session variables MỚI
        $_SESSION['admin_id'] = $username;
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];  // Store user role
        $_SESSION['admin_email'] = $user['email'];
        
        // Set login time - KHÔNG được update lại sau này (bắt buộc)
        $_SESSION['login_time'] = time();
        
        // Log đăng nhập
        require_once '../includes/helpers/audit_log.php';
        logAction('LOGIN', "User {$username} đã đăng nhập", $_SESSION['admin_id']);
        
        // Redirect based on role (optional)
        header('Location: index.php');
        exit;
    } else {
        $error = 'Tên tài khoản hoặc mật khẩu không đúng!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin - Tra Cứu Vi Phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border: none;
        }
        .login-card h3 {
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card login-card p-5" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4"><i class="fa-solid fa-shield me-2"></i>Đăng Nhập Admin</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Tài Khoản</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Nhập tên tài khoản" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Mật Khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                <i class="fa-solid fa-sign-in-alt me-2"></i>Đăng Nhập
            </button>
        </form>
        
        <hr>
        <p class="text-muted small text-center">
            <i class="fa-solid fa-info-circle me-1"></i>
            Demo: admin / admin123
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>