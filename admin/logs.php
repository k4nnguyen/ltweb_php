<?php
// admin/logs.php - System Audit Log Viewer (Admin Only)

require_once '../includes/check-session.php';
require_once '../includes/helpers/audit_log.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    die('Access denied. This page is for administrators only.');
}

// Get filter parameters
$filterAction = isset($_GET['action']) ? trim($_GET['action']) : '';
$filterUsername = isset($_GET['username']) ? trim($_GET['username']) : '';
$filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';

// Get logs
$logs = getAllLogs(1000);

// Apply filters
if ($filterAction) {
    $logs = array_filter($logs, function($log) use ($filterAction) {
        return strtoupper($log['action']) === strtoupper($filterAction);
    });
}

if ($filterUsername) {
    $logs = array_filter($logs, function($log) use ($filterUsername) {
        return stripos($log['username'], $filterUsername) !== false;
    });
}

if ($filterDate) {
    $logs = array_filter($logs, function($log) use ($filterDate) {
        return strpos($log['timestamp'], $filterDate) === 0;
    });
}

// EXPORT CSV - Nếu có request export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="system_audit_log_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Output CSV header
    echo "Thời gian,Tài khoản,Vai trò,Hành động,Mô tả\n";
    
    // Output each log entry
    foreach ($logs as $log) {
        echo sprintf(
            '"%s","%s","%s","%s","%s"' . "\n",
            str_replace('"', '""', $log['timestamp']),
            str_replace('"', '""', $log['username']),
            str_replace('"', '""', $log['role']),
            str_replace('"', '""', $log['action']),
            str_replace('"', '""', $log['description'])
        );
    }
    exit;
}

// Get unique values for filters
$allLogs = getAllLogs(5000);
$uniqueActions = [];
$uniqueUsernames = [];
$uniqueDates = [];

foreach ($allLogs as $log) {
    if (!in_array($log['action'], $uniqueActions)) {
        $uniqueActions[] = $log['action'];
    }
    if (!in_array($log['username'], $uniqueUsernames)) {
        $uniqueUsernames[] = $log['username'];
    }
    $date = substr($log['timestamp'], 0, 10);
    if (!in_array($date, $uniqueDates)) {
        $uniqueDates[] = $date;
    }
}

sort($uniqueActions);
sort($uniqueUsernames);
rsort($uniqueDates);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Log - Tra Cứu Vi Phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa;
            padding-top: 35px;
        }
        .navbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;
            border-bottom: 3px solid #3b82f6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
        }
        
        .navbar .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .navbar .nav-link:hover {
            background-color: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.session-info {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 2px solid rgba(255, 255, 255, 0.3);
            padding-left: 0.75rem !important;
            margin-left: 0.5rem;
            font-size: 0.9rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .filter-card { 
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .log-entry {
            border-left: 4px solid #667eea;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .log-entry.delete { border-left-color: #dc3545; }
        .log-entry.create { border-left-color: #28a745; }
        .log-entry.update { border-left-color: #ffc107; }
        .log-entry.login { border-left-color: #17a2b8; }
        
        .action-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .action-badge.delete { background-color: #f8d7da; color: #721c24; }
        .action-badge.create { background-color: #d4edda; color: #155724; }
        .action-badge.update { background-color: #fff3cd; color: #856404; }
        .action-badge.login { background-color: #d1ecf1; color: #0c5460; }
        .action-badge.logout { background-color: #e2e3e5; color: #383d41; }
        
        .log-timestamp { color: #667eea; font-weight: bold; }
        .log-user { color: #764ba2; }
        .log-description { color: #333; margin: 5px 0; }
        .log-id { color: #999; font-size: 12px; font-style: italic; }
        
        .stats-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php require_once '../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <h2><i class="fa-solid fa-history me-2"></i>System Audit Log</h2>
        <p>Xem lịch sử thao tác của toàn bộ cán bộ trong hệ thống</p>
    </div>
</div>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-card">
        <h5 class="mb-4"><i class="fa-solid fa-filter me-2"></i>Lọc theo</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Hành động</label>
                <select name="action" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($uniqueActions as $act): ?>
                        <option value="<?= $act ?>" <?= $filterAction === $act ? 'selected' : '' ?>>
                            <?= htmlspecialchars($act) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tài khoản</label>
                <select name="username" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($uniqueUsernames as $user): ?>
                        <option value="<?= $user ?>" <?= $filterUsername === $user ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày</label>
                <select name="date" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($uniqueDates as $d): ?>
                        <option value="<?= $d ?>" <?= $filterDate === $d ? 'selected' : '' ?>>
                            <?= date('d/m/Y', strtotime($d)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-search me-2"></i>Tìm kiếm
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stats-box">
                <div class="stats-number"><?= count($logs) ?></div>
                <div class="stats-label">Tổng bản ghi</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-box">
                <div class="stats-number"><?= count(array_filter($logs, fn($l) => $l['action'] === 'CREATE')) ?></div>
                <div class="stats-label">Thêm mới</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-box">
                <div class="stats-number"><?= count(array_filter($logs, fn($l) => $l['action'] === 'UPDATE')) ?></div>
                <div class="stats-label">Cập nhật</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-box">
                <div class="stats-number" style="color: #dc3545;"><?= count(array_filter($logs, fn($l) => $l['action'] === 'DELETE')) ?></div>
                <div class="stats-label">Xóa</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-box">
                <div class="stats-number"><?= count($uniqueUsernames) ?></div>
                <div class="stats-label">Tài khoản</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-box">
                <a href="logs.php" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-rotate-left me-1"></i>Reset
                </a>
            </div>
        </div>
    </div>

    <!-- Logs List -->
    <div class="card" style="box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Danh sách hoạt động (<?= count($logs) ?> bản ghi)</h5>
        </div>
        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    Không có bản ghi nào
                </div>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry <?= strtolower($log['action']) ?>">
                        <div class="row">
                            <div class="col-md-2">
                                <span class="log-timestamp">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    <?= $log['timestamp'] ?>
                                </span>
                            </div>
                            <div class="col-md-2">
                                <span class="log-user">
                                    <i class="fa-solid fa-user me-1"></i>
                                    <?= htmlspecialchars($log['username']) ?>
                                </span>
                                <br>
                                <small style="color: #999;"><?= htmlspecialchars($log['role']) ?></small>
                            </div>
                            <div class="col-md-8">
                                <span class="action-badge <?= strtolower($log['action']) ?>">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                                <div class="log-description">
                                    <?= htmlspecialchars($log['description']) ?>
                                </div>
                                <?php if (strpos($log['description'], 'ID:') === false && preg_match('/ID:\s*(.+?)$/', $log['description'], $matches)): ?>
                                    <span class="log-id"><?= htmlspecialchars($matches[1]) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin: 20px 0; text-align: right;">
        <a href="?export=csv" class="btn btn-success me-2" title="Export logs to CSV">
            <i class="fa-solid fa-download me-2"></i>Export CSV
        </a>
        <a href="logs.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- Session Countdown Update Script -->
<script>
    // Update countdown mỗi 5 giây
    function updateSessionCountdown() {
        fetch('/admin_vi_pham/ajax/session-info.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countdownDisplay = document.getElementById('countdown-display');
                    if (countdownDisplay) {
                        let displayText = data.remaining_time_display;
                        if (data.remaining_seconds < 300) {
                            displayText = '<span class="text-danger fw-bold">' + displayText + '</span>';
                        } else {
                            displayText = '<span class="text-success">' + displayText + '</span>';
                        }
                        countdownDisplay.innerHTML = displayText;
                    }
                }
            })
            .catch(err => console.log('Session update error:', err));
    }
    
    // Update every 5 seconds
    setInterval(updateSessionCountdown, 5000);
</script>

</body>
</html>
