<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

if (isset($_POST['plate'])) {
    $plate = trim($_POST['plate']);
    try {
        $stmt = $conn->prepare("
            SELECT cx.HoTen, cx.CCCD, cx.SoDienThoai, pt.LoaiXe, pt.NhanHieu, pt.MauSac
            FROM PhuongTien pt
            JOIN ChuXe cx ON pt.MaChuXe = cx.MaChuXe
            WHERE pt.BienSoXe = :plate
        ");
        $stmt->execute(['plate' => $plate]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($info) {
            // Convert encoding nếu cần
            $info = array_map(function($val) {
                return mb_convert_encoding($val, 'UTF-8', 'auto');
            }, $info);
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => true, 'data' => $info]);
        } else {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'msg' => 'Không tìm thấy phương tiện này']);
        }
    } catch(PDOException $e) {
        if (ob_get_length()) ob_clean();
        $msg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
        echo json_encode(['success' => false, 'msg' => 'Lỗi truy vấn: ' . $msg]);
    }
    exit;
}
?>