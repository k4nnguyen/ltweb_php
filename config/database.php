<?php
// config/database.php - SQLite Configuration
// Database file: data/ViPhamGiaoThong.db

try {
    // Mảng cấu hình PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Đường dẫn database SQLite
    $dbPath = __DIR__ . '/../data/ViPhamGiaoThong.db';
    
    // Tạo folder 'data' nếu chưa có
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    // Khởi tạo kết nối SQLite
    $conn = new PDO("sqlite:$dbPath", null, null, $options);
    
    // Bật Foreign Keys (quan trọng cho SQLite)
    $conn->exec("PRAGMA foreign_keys = ON");
    
} catch(PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>