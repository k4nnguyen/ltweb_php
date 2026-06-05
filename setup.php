<?php
/**
 * setup.php - Tự động import dữ liệu vào SQLite
 * Chỉ cần mở: http://localhost:8080/ltweb_php/setup.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/data/ViPhamGiaoThong.db';
$dataDir = __DIR__ . '/data';
$sqlFile = __DIR__ . '/database_sqlite.sql';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="setup-card">
        <h2 class="mb-4">🔧 Setup Database</h2>
        
        <?php
        // Kiểm tra xem file SQL tồn tại không
        if (!file_exists($sqlFile)) {
            echo '<div class="alert alert-danger">❌ Lỗi: File database_sqlite.sql không tìm thấy!</div>';
            exit;
        }

        try {
            // Tạo folder data nếu chưa có
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
                echo '<div class="alert alert-info">✓ Tạo folder /data thành công</div>';
            }

            // Xóa database cũ (nếu muốn reset)
            if (file_exists($dbPath)) {
                unlink($dbPath);
                echo '<div class="alert alert-info">✓ Xóa database cũ</div>';
            }

            // Kết nối SQLite (tự động tạo file nếu chưa có)
            $conn = new PDO("sqlite:$dbPath");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="alert alert-info">✓ Kết nối database thành công</div>';

            // Đọc file SQL
            $sql = file_get_contents($sqlFile);
            
            // Loại bỏ comment (dòng bắt đầu bằng --)
            $lines = explode("\n", $sql);
            $cleanSql = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed) && !preg_match('/^--/', $trimmed)) {
                    $cleanSql .= $line . "\n";
                }
            }

            // Execute toàn bộ SQL script
            try {
                $conn->exec($cleanSql);
                $successCount = 1;
                echo '<div class="alert alert-info">✓ Thực thi SQL script thành công</div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-warning"><small>⚠ ' . htmlspecialchars($e->getMessage()) . '</small></div>';
                $successCount = 0;
            }

            // Kiểm tra dữ liệu
            $result = $conn->query("SELECT COUNT(*) as total FROM HoSoViPham")->fetch();
            $count = $result['total'];

            echo '<div class="alert alert-success">';
            echo '<h4>✅ Setup hoàn tất!</h4>';
            echo '<p>Database: <strong>' . $dbPath . '</strong></p>';
            echo '<p>Số lệnh SQL thực thi: <strong>' . $successCount . '</strong></p>';
            echo '<p>Số hồ sơ vi phạm: <strong>' . $count . '</strong></p>';
            echo '</div>';

            echo '<div class="alert alert-info">';
            echo '<strong>📝 Thông tin tài khoản:</strong><br>';
            echo '• Tài khoản: <code>admin01</code><br>';
            echo '• Mật khẩu: <code>admin123</code><br>';
            echo '• Hoặc dùng: <code>staff01</code> / <code>staff123</code>';
            echo '</div>';

            // Button quay lại
            echo '<a href="http://localhost:8080/ltweb_php/admin/login.php" class="btn btn-primary btn-lg btn-block mt-3">
                    🚀 Đi tới trang Login
                  </a>';

        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">';
            echo '<h4>❌ Lỗi kết nối:</h4>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
