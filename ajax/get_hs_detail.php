<?php
// ajax/get_hs_detail.php - Lấy chi tiết hồ sơ với ảnh

require_once '../includes/check-session.php';
require_once '../config/database.php';
require_once '../includes/helpers/image_handler.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['maHoSo'])) {
    echo json_encode(['success' => false, 'msg' => 'Thiếu Mã Hồ Sơ']);
    exit;
}

$maHoSo = $_GET['maHoSo'];

try {
    // Get hồ sơ info
    $stmt = $conn->prepare("
        SELECT hs.MaHoSo, hs.ThoiGianViPham, hs.DiaDiemViPham, hs.TrangThai,
               pt.BienSoXe, pt.LoaiXe, pt.NhanHieu, pt.MauSac,
               cx.HoTen, cx.CCCD, cx.SoDienThoai, cx.DiaChi,
               lvp.MaLoi, lvp.TenLoi, lvp.MucPhatTien, lvp.HinhThucPhatBoSung
        FROM HoSoViPham hs
        JOIN PhuongTien pt ON hs.BienSoXe = pt.BienSoXe
        JOIN ChuXe cx ON pt.MaChuXe = cx.MaChuXe
        JOIN LoiViPham lvp ON hs.MaLoi = lvp.MaLoi
        WHERE hs.MaHoSo = ?
    ");
    $stmt->execute([$maHoSo]);
    $hs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hs) {
        echo json_encode(['success' => false, 'msg' => 'Không tìm thấy hồ sơ']);
        exit;
    }
    
    // Get evidence image
    $image = getEvidenceImage($maHoSo, $conn);
    
    echo json_encode([
        'success' => true,
        'data' => $hs,
        'image' => $image
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Lỗi: ' . $e->getMessage()]);
}
?>
