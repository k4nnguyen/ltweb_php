<?php
// config/database.php
$serverName = "IT-PTIT-99-99-02\\SQLEXPRESS"; 
$database = "ViPhamGiaoThong";
$uid = "sa"; 
$pwd = "123456"; 

try {
    // Mảng cấu hình PDO, bao gồm việc ép kiểu UTF-8
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ];

    // Khởi tạo kết nối (Đã xóa CharacterSet khỏi chuỗi DSN, thay bằng $options)
    if (empty($uid) && empty($pwd)) {
        $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", "", "", $options);
    } else {
        $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $uid, $pwd, $options);
    }
    
} catch(PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>