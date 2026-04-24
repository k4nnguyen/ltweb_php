<?php
/**
 * Image Handler - Quản lý upload ảnh chứng cứ
 */

/**
 * Lấy MaHinhAnh tiếp theo (HA001, HA002, ...)
 */
function getNextMaHinhAnh($conn)
{
    try {
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(MaHinhAnh, 3, LEN(MaHinhAnh)) AS INT)) as MaxNum FROM HinhAnhChungCu WHERE MaHinhAnh LIKE 'HA%'");
        $row = $stmt->fetch();
        $maxNum = ($row && $row['MaxNum']) ? (int)$row['MaxNum'] : 0;
        return 'HA' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        error_log("Error getting next MaHinhAnh: " . $e->getMessage());
        return null;
    }
}

/**
 * Upload ảnh và insert vào database
 * @param $file - $_FILES['image']
 * @param $maHoSo - Mã hồ sơ
 * @param $conn - PDO connection
 * @param $ghiChu - Ghi chú (optional)
 * @return ['success' => bool, 'msg' => string, 'maHinhAnh' => string, 'url' => string]
 */
function uploadEvidenceImage($file, $maHoSo, $conn, $ghiChu = '')
{
    // Validate file
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'msg' => 'Lỗi upload file!'];
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'msg' => 'Định dạng không hỗ trợ! Chỉ chấp nhận JPG, PNG, GIF.'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $sizeMB = number_format($file['size'] / (1024 * 1024), 2);
        return ['success' => false, 'msg' => 'Kích thước ảnh lớn! File có kích thước ' . $sizeMB . 'MB, tối đa 5MB.'];
    }
    
    try {
        // Get next MaHinhAnh
        $maHinhAnh = getNextMaHinhAnh($conn);
        if (!$maHinhAnh) {
            return ['success' => false, 'msg' => 'Lỗi sinh mã hình ảnh!'];
        }
        
        // Generate filename: ha001.jpg, ha002.jpg, ...
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = strtolower($maHinhAnh) . '.' . $extension; // ha001.jpg
        
        // Upload directory
        $uploadDir = __DIR__ . '/../../img/evidence/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'msg' => 'Lỗi lưu file!'];
        }
        
        // Insert into database
        $url = strtolower($maHinhAnh) . '.' . $extension;
        $stmt = $conn->prepare("
            INSERT INTO HinhAnhChungCu (MaHinhAnh, MaHoSo, URL_HinhAnh, GhiChu)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$maHinhAnh, $maHoSo, $url, $ghiChu]);
        
        return [
            'success' => true,
            'msg' => 'Upload ảnh thành công!',
            'maHinhAnh' => $maHinhAnh,
            'url' => $url,
            'filename' => $filename
        ];
    } catch (Exception $e) {
        error_log("Image upload error: " . $e->getMessage());
        return ['success' => false, 'msg' => 'Lỗi database: ' . $e->getMessage()];
    }
}

/**
 * Lấy ảnh chứng cứ của một hồ sơ
 */
function getEvidenceImage($maHoSo, $conn)
{
    try {
        $stmt = $conn->prepare("SELECT * FROM HinhAnhChungCu WHERE MaHoSo = ?");
        $stmt->execute([$maHoSo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get evidence image error: " . $e->getMessage());
        return null;
    }
}

/**
 * Xóa ảnh chứng cứ
 */
function deleteEvidenceImage($maHoSo, $conn)
{
    try {
        // Get image info
        $image = getEvidenceImage($maHoSo, $conn);
        if (!$image) {
            return ['success' => false, 'msg' => 'Không tìm thấy ảnh!'];
        }
        
        // Delete file from disk
        $filepath = __DIR__ . '/../../img/evidence/' . $image['URL_HinhAnh'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM HinhAnhChungCu WHERE MaHoSo = ?");
        $stmt->execute([$maHoSo]);
        
        return ['success' => true, 'msg' => 'Xóa ảnh thành công!'];
    } catch (Exception $e) {
        error_log("Delete evidence image error: " . $e->getMessage());
        return ['success' => false, 'msg' => 'Lỗi xóa ảnh: ' . $e->getMessage()];
    }
}

/**
 * Kiểm tra hồ sơ có ảnh hay không
 */
function hasEvidenceImage($maHoSo, $conn)
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM HinhAnhChungCu WHERE MaHoSo = ?");
        $stmt->execute([$maHoSo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (Exception $e) {
        return false;
    }
}
?>
