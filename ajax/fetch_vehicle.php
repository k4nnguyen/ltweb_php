<?php
// ajax/fetch_vehicle.php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once '../config/database.php';

if(isset($_POST['plate'])) {
    $plate = trim($_POST['plate']);
    try {
        $stmt = $conn->prepare("
            SELECT hs.MaHoSo, lvp.TenLoi, hs.ThoiGianViPham, hs.DiaDiemViPham, lvp.MucPhatTien, hs.TrangThai 
            FROM HoSoViPham hs 
            JOIN LoiViPham lvp ON hs.MaLoi = lvp.MaLoi 
            WHERE hs.BienSoXe = :plate
        ");
        $stmt->execute(['plate' => $plate]);
        $results = $stmt->fetchAll();

        if (count($results) > 0) {
            $html = "<div class='card shadow border-0'><div class='card-header bg-danger text-white'><h5 class='mb-0'><i class='fa-solid fa-triangle-exclamation me-2'></i>Kết Quả Tra Cứu: $plate</h5></div><div class='card-body'><div class='table-responsive'><table class='table table-hover table-bordered'><thead><tr><th>Mã HS</th><th>Lỗi</th><th>Thời Gian</th><th>Địa Điểm</th><th>Phạt Tiền</th><th>Trạng Thái</th></tr></thead><tbody>";
            foreach($results as $row) {
                $statusType = ($row['TrangThai'] == 'Đã nộp phạt') ? 'success' : 'warning';
                $html .= "<tr>
                            <td>{$row['MaHoSo']}</td>
                            <td>{$row['TenLoi']}</td>
                            <td>{$row['ThoiGianViPham']}</td>
                            <td>{$row['DiaDiemViPham']}</td>
                            <td class='text-danger fw-bold'>" . number_format($row['MucPhatTien'], 0, ',', '.') . " ₫</td>
                            <td><span class='badge bg-{$statusType}'>{$row['TrangThai']}</span></td>
                          </tr>";
            }
            $html .= "</tbody></table></div></div></div>";
            echo $html;
        } else {
            echo "<div class='alert alert-success border-0 shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>Chúc mừng! Không tìm thấy lỗi vi phạm nào cho biển số <strong>$plate</strong>.</div>";
        }
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Lỗi truy vấn: " . $e->getMessage() . "</div>";
    }
}
?>