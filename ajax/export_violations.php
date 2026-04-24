<?php
// ajax/export_violations.php
// Export violation records to Excel/CSV

session_start();
require_once '../includes/check-session.php';
require_once '../config/database.php';

// Get export format (csv or xlsx)
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

// Get filter parameters
$searchType = isset($_GET['searchType']) ? trim($_GET['searchType']) : '';
$searchValue = isset($_GET['searchValue']) ? trim($_GET['searchValue']) : '';
$loiFilter = isset($_GET['loiFilter']) ? trim($_GET['loiFilter']) : '';
$trangThaiFilter = isset($_GET['trangThaiFilter']) ? trim($_GET['trangThaiFilter']) : '';

// Get sort parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'thoiGian';
$sortDir = isset($_GET['dir']) && strtoupper($_GET['dir']) === 'ASC' ? 'ASC' : 'DESC';

try {
    // Build query
    $query = "SELECT 
        hs.MaHoSo,
        hs.BienSoXe,
        cx.HoTen,
        cx.CCCD,
        cx.SoDienThoai,
        pt.LoaiXe,
        pt.NhanHieu,
        pt.MauSac,
        lv.TenLoi,
        lv.MucPhatTien,
        hs.ThoiGianViPham,
        hs.DiaDiemViPham,
        hs.TrangThai
    FROM HoSoViPham hs
    JOIN PhuongTien pt ON hs.BienSoXe = pt.BienSoXe
    JOIN ChuXe cx ON pt.MaChuXe = cx.MaChuXe
    JOIN LoiViPham lv ON hs.MaLoi = lv.MaLoi
    WHERE 1=1";
    
    // Apply filters
    if ($searchType && $searchValue) {
        if ($searchType === 'BienSoXe') {
            $query .= " AND hs.BienSoXe LIKE ?";
        } elseif ($searchType === 'HoTen') {
            $query .= " AND cx.HoTen LIKE ?";
        } elseif ($searchType === 'CCCD') {
            $query .= " AND cx.CCCD LIKE ?";
        } elseif ($searchType === 'SoDienThoai') {
            $query .= " AND cx.SoDienThoai LIKE ?";
        }
    }
    
    if ($loiFilter) {
        $query .= " AND hs.MaLoi = ?";
    }
    
    if ($trangThaiFilter) {
        $query .= " AND hs.TrangThai = ?";
    }
    
    // Dynamic ORDER BY based on sort parameter
    $orderBy = "hs.ThoiGianViPham " . $sortDir; // default
    
    if ($sort == 'bienSo') {
        $orderBy = "pt.BienSoXe " . $sortDir;
    } elseif ($sort == 'maHS') {
        $orderBy = "hs.MaHoSo " . $sortDir;
    } elseif ($sort == 'trangThai') {
        $orderBy = "hs.TrangThai " . $sortDir;
    } elseif ($sort == 'thoiGian') {
        $orderBy = "hs.ThoiGianViPham " . $sortDir;
    }
    
    $query .= " ORDER BY " . $orderBy;
    
    // Build parameters
    $params = [];
    if ($searchType && $searchValue) {
        $params[] = "%$searchValue%";
    }
    if ($loiFilter) {
        $params[] = $loiFilter;
    }
    if ($trangThaiFilter) {
        $params[] = $trangThaiFilter;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Generate CSV content
    $csv = "Mã Hồ Sơ,Biển Số Xe,Họ Tên,CCCD,Số Điện Thoại,Loại Xe,Nhãn Hiệu,Màu Sắc,Lỗi Vi Phạm,Mức Phạt,Thời Gian,Địa Điểm,Trạng Thái\n";
    
    foreach ($records as $row) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%d","%s","%s","%s"' . "\n",
            str_replace('"', '""', $row['MaHoSo']),
            str_replace('"', '""', $row['BienSoXe']),
            str_replace('"', '""', $row['HoTen']),
            str_replace('"', '""', $row['CCCD']),
            str_replace('"', '""', $row['SoDienThoai']),
            str_replace('"', '""', $row['LoaiXe']),
            str_replace('"', '""', $row['NhanHieu']),
            str_replace('"', '""', $row['MauSac']),
            str_replace('"', '""', $row['TenLoi']),
            (int)$row['MucPhatTien'],
            str_replace('"', '""', $row['ThoiGianViPham']),
            str_replace('"', '""', $row['DiaDiemViPham']),
            str_replace('"', '""', $row['TrangThai'])
        );
    }
    
    // Send file headers
    $filename = 'vi_pham_' . date('Y-m-d_H-i-s');
    
    if ($format === 'xlsx') {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        
        // For XLSX, we need to convert CSV to XLSX format
        // For simplicity, we'll create an HTML table and let browser save as Excel
        $html = "<table border='1'><tr>";
        $headers = ["Mã Hồ Sơ", "Biển Số Xe", "Họ Tên", "CCCD", "Số Điện Thoại", "Loại Xe", "Nhãn Hiệu", "Màu Sắc", "Lỗi Vi Phạm", "Mức Phạt", "Thời Gian", "Địa Điểm", "Trạng Thái"];
        foreach ($headers as $h) {
            $html .= "<th>" . htmlspecialchars($h) . "</th>";
        }
        $html .= "</tr>";
        
        foreach ($records as $row) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($row['MaHoSo']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['BienSoXe']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['HoTen']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['CCCD']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['SoDienThoai']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['LoaiXe']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['NhanHieu']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['MauSac']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['TenLoi']) . "</td>";
            $html .= "<td>" . number_format($row['MucPhatTien'], 0, ',', '.') . " ₫</td>";
            $html .= "<td>" . htmlspecialchars($row['ThoiGianViPham']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['DiaDiemViPham']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['TrangThai']) . "</td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
        
        echo $html;
    } else {
        // CSV format
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM for UTF-8
        echo $csv;
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => 'Lỗi: ' . $e->getMessage()]);
}
exit;
?>
