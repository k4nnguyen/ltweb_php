<!-- includes/navbar.php -->
<?php 
// Kiểm tra login status
$isLoggedIn = isset($_SESSION['admin_id']) ? true : false;
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid px-4">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="/admin_vi_pham/admin/index.php">
            <i class="fa-solid fa-user-shield me-2"></i><span>Vi Phạm Admin</span>
        </a>
        
        <!-- Toggler Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Main Navigation -->
            <ul class="navbar-nav me-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin_vi_pham/admin/index.php" title="Xem thống kê và tra cứu vi phạm">
                            <i class="fa-solid fa-chart-pie me-1"></i> Thống Kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin_vi_pham/admin/manage.php" title="Quản lý hồ sơ vi phạm">
                            <i class="fa-solid fa-list-check me-1"></i> Quản Lý
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin_vi_pham/admin/users.php" title="Xem thông tin người dùng">
                            <i class="fa-solid fa-users me-1"></i> Người Dùng
                        </a>
                    </li>
                    <?php require_once __DIR__ . '/helpers/auth.php'; ?>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin_vi_pham/admin/logs.php" title="Xem nhật ký hoạt động hệ thống">
                            <i class="fa-solid fa-history me-1"></i> System Log
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <!-- User Info & Session Status -->
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- User Name -->
                    <li class="nav-item">
                        <span class="nav-link text-light d-flex align-items-center" title="Tài khoản đang đăng nhập">
                            <i class="fa-solid fa-user-circle me-1"></i>
                            <small class="d-none d-md-inline"><?= $adminName ?></small>
                        </span>
                    </li>
                    
                    <!-- Divider -->
                    <li class="nav-item">
                        <span class="nav-link" style="color: rgba(255,255,255,0.3);">|</span>
                    </li>
                    
                    <!-- Login Time -->
                    <li class="nav-item session-info">
                        <span class="nav-link text-warning d-flex align-items-center" title="Thời gian đăng nhập">
                            <i class="fa-solid fa-clock me-1"></i>
                            <small>
                                <?php 
                                if (isset($_SESSION['session_info'])) {
                                    echo $_SESSION['session_info']['login_time_display'];
                                } else {
                                    echo "Không xác định";
                                }
                                ?>
                            </small>
                        </span>
                    </li>
                    
                    <!-- Session Countdown -->
                    <li class="nav-item session-info">
                        <span class="nav-link d-flex align-items-center" title="Thời gian còn lại trước khi tự động logout">
                            <i class="fa-solid fa-hourglass-end me-1"></i>
                            <small id="countdown-display">
                                <?php 
                                if (isset($_SESSION['session_info'])) {
                                    if ($_SESSION['session_info']['remaining_seconds'] < 300) {
                                        echo '<span class="text-danger fw-bold">' . $_SESSION['session_info']['remaining_time_display'] . '</span>';
                                    } else {
                                        echo '<span class="text-success">' . $_SESSION['session_info']['remaining_time_display'] . '</span>';
                                    }
                                }
                                ?>
                            </small>
                        </span>
                    </li>
                    
                    <!-- Logout Button -->
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="index.php?logout=1" title="Đăng xuất khỏi hệ thống">
                            <i class="fa-solid fa-right-from-bracket me-1"></i><span class="d-none d-md-inline">Đăng Xuất</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- JavaScript để update countdown -->
<script>
    // Update countdown mỗi giây
    function updateCountdown() {
        fetch('/admin_vi_pham/ajax/session-info.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && document.getElementById('countdown-display')) {
                    const remaining = data.remaining_seconds;
                    let displayText = data.remaining_time_display;
                    
                    if (remaining < 300) {
                        displayText = '<span class="text-danger fw-bold">' + displayText + '</span>';
                    } else {
                        displayText = '<span class="text-success">' + displayText + '</span>';
                    }
                    
                    document.getElementById('countdown-display').innerHTML = displayText;
                }
            })
            .catch(err => console.log('Countdown update error:', err));
    }
    
    // Update mỗi 5 giây
    setInterval(updateCountdown, 5000);
</script>
