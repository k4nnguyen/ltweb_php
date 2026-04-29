<?php
// admin/manage.php

// Check session and timeout
require_once '../includes/check-session.php';
require_once '../includes/helpers/audit_log.php';

// ===== BLOCK XỬ LÝ AJAX =====
// Đặt header JSON để trình duyệt hiểu đây là data, không phải HTML
if (isset($_GET['ajax_delete'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once '../config/database.php';
    
    // Permission check: Only admin can delete
    if (!isAdmin()) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Bạn không có quyền xóa hồ sơ!']);
        exit;
    }
    
    try {
        $maHoSo = $_GET['ajax_delete'];
        $stmt = $conn->prepare("DELETE FROM HoSoViPham WHERE MaHoSo = :ma");
        $stmt->execute(['ma' => $maHoSo]);
        
        // Log the action
        logAction(
            LOG_ACTION_DELETE,
            'Đã xóa hồ sơ vi phạm',
            $maHoSo
        );
        
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'msg' => 'Đã xóa hồ sơ vi phạm thành công!']);
    } catch(PDOException $e) {
        if (ob_get_length()) ob_clean();
        $msg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
        echo json_encode(['success' => false, 'msg' => 'Lỗi khi xóa: ' . $msg]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once '../config/database.php';
    
    // Permission check: Both admin and staff can add/edit
    if (!isAdmin() && !isStaff()) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'msg' => 'Bạn không có quyền thực hiện hành động này!']);
        exit;
    }
    
    // Nếu là thêm mới, tự động sinh MaHoSo từ MAX
    if ($_POST['ajax_action'] == 'add' && (empty($_POST['maHoSo']) || $_POST['maHoSo'] == '')) {
        try {
            $stmtMax = $conn->query("SELECT MAX(CAST(SUBSTRING(MaHoSo, 3, LEN(MaHoSo)) AS INT)) as MaxNum FROM HoSoViPham WHERE MaHoSo LIKE 'HS%'");
            $rowMax = $stmtMax->fetch();
            $maxNum = ($rowMax && $rowMax['MaxNum']) ? (int)$rowMax['MaxNum'] : 0;
            $_POST['maHoSo'] = 'HS' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
        } catch(PDOException $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'msg' => 'Lỗi sinh Mã Hồ Sơ: ' . $e->getMessage()]);
            exit;
        }
    }
    
    $maHoSo = !empty($_POST['maHoSo']) ? trim($_POST['maHoSo']) : '';
    $bienSoXe = trim($_POST['bienSoXe']);
    $maLoi = trim($_POST['maLoi']);
    
    // Chuẩn hóa Datetime cho SQL Server
    $thoiGian = trim($_POST['thoiGian']); // Trim first
    $thoiGian = str_replace('T', ' ', $thoiGian); 
    
    // Ensure format YYYY-MM-DD HH:mm:ss
    if (strlen($thoiGian) == 16) {
        $thoiGian .= ':00';
    }
    
    error_log("DEBUG thoiGian: '" . $thoiGian . "' (length: " . strlen($thoiGian) . ")");
    
    $diaDiem = trim($_POST['diaDiem']);
    $trangThai = trim($_POST['trangThai']);
    $action = $_POST['ajax_action'];

    try {
        if ($action == 'add') {
            // Validate required fields
            if (empty($bienSoXe) || empty($maLoi) || empty($thoiGian) || empty($diaDiem)) {
                echo json_encode(['success' => false, 'msg' => 'Các trường bắt buộc không được để trống']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO HoSoViPham (MaHoSo, BienSoXe, MaLoi, ThoiGianViPham, DiaDiemViPham, TrangThai) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$maHoSo, $bienSoXe, $maLoi, $thoiGian, $diaDiem, $trangThai]);
            
            // Log the action
            logAction(
                LOG_ACTION_CREATE,
                'Đã thêm mới hồ sơ vi phạm',
                $maHoSo
            );
            
            // Handle image upload if provided
            $imageMsg = '';
            if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
                require_once '../includes/helpers/image_handler.php';
                $imageResult = uploadEvidenceImage(
                    $_FILES['evidence_image'],
                    $maHoSo,
                    $conn,
                    'Ảnh chứng cứ hồ sơ ' . $maHoSo
                );
                if ($imageResult['success']) {
                    $imageMsg = ' (ảnh: ' . $imageResult['url'] . ')';
                } else {
                    error_log("Image upload failed: " . $imageResult['msg']);
                }
            }
            
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => true, 'msg' => 'Thêm mới hồ sơ thành công!' . $imageMsg]);
        } elseif ($action == 'edit') {
            // EDIT: Chỉ được sửa 4 fields - MaLoi, ThoiGianViPham, DiaDiemViPham, TrangThai
            // KHÔNG được sửa: BienSoXe (biển số xe)
            $stmt = $conn->prepare("UPDATE HoSoViPham SET MaLoi=?, ThoiGianViPham=?, DiaDiemViPham=?, TrangThai=? WHERE MaHoSo=?");
            $stmt->execute([$maLoi, $thoiGian, $diaDiem, $trangThai, $maHoSo]);
            
            // Log the action
            logAction(
                LOG_ACTION_UPDATE,
                'Đã cập nhật hồ sơ vi phạm',
                $maHoSo
            );
            
            // Handle image upload if provided (replace old image)
            $imageMsg = '';
            if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
                require_once '../includes/helpers/image_handler.php';
                
                // Xóa ảnh cũ nếu tồn tại
                deleteEvidenceImage($maHoSo, $conn);
                
                // Upload ảnh mới
                $imageResult = uploadEvidenceImage(
                    $_FILES['evidence_image'],
                    $maHoSo,
                    $conn,
                    'Ảnh chứng cứ hồ sơ ' . $maHoSo
                );
                if ($imageResult['success']) {
                    $imageMsg = ' (ảnh: ' . $imageResult['url'] . ')';
                } else {
                    error_log("Image upload failed: " . $imageResult['msg']);
                }
            }
            
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => true, 'msg' => 'Cập nhật hồ sơ thành công!' . $imageMsg]);
        }
    } catch(PDOException $e) {
        if (ob_get_length()) ob_clean();
        $msg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto');
        echo json_encode(['success' => false, 'msg' => 'Lỗi dữ liệu: ' . $msg]);
    }
    exit;
}
// ==============================================

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Get data for Search
$search = $_GET['search'] ?? '';
$searchType = $_GET['type'] ?? 'BienSoXe';
$sort = $_GET['sort'] ?? 'thoiGian'; // Default sort by thời gian
$sortDir = $_GET['dir'] ?? 'DESC'; // ASC or DESC

$sql = "SELECT hs.MaHoSo, hs.ThoiGianViPham, hs.DiaDiemViPham, hs.TrangThai,
               pt.BienSoXe, cx.HoTen, cx.CCCD, cx.SoDienThoai, 
               lvp.MaLoi, lvp.TenLoi, lvp.MucPhatTien 
        FROM HoSoViPham hs
        JOIN PhuongTien pt ON hs.BienSoXe = pt.BienSoXe
        JOIN ChuXe cx ON pt.MaChuXe = cx.MaChuXe
        JOIN LoiViPham lvp ON hs.MaLoi = lvp.MaLoi 
        WHERE 1=1 ";

$params = [];
if (!empty($search)) {
    if ($searchType == 'BienSoXe') {
        $sql .= " AND pt.BienSoXe LIKE ?";
    } elseif ($searchType == 'HoTen') {
        $sql .= " AND cx.HoTen LIKE ? COLLATE SQL_Latin1_General_CP1_CI_AI";
    } elseif ($searchType == 'CCCD') {
        $sql .= " AND cx.CCCD LIKE ?";
    } elseif ($searchType == 'SoDienThoai') {
        $sql .= " AND cx.SoDienThoai LIKE ?";
    }
    $params[] = "%" . $search . "%";
}

// Thêm ORDER BY dựa vào sort parameter
$orderBy = "hs.ThoiGianViPham DESC";
if ($sort == 'bienSo') {
    $orderBy = "pt.BienSoXe " . $sortDir;
} elseif ($sort == 'maHS') {
    $orderBy = "hs.MaHoSo " . $sortDir;
} elseif ($sort == 'trangThai') {
    $orderBy = "hs.TrangThai " . $sortDir;
} elseif ($sort == 'thoiGian') {
    $orderBy = "hs.ThoiGianViPham " . $sortDir;
}

$sql .= " ORDER BY " . $orderBy;

// ===== PAGINATION =====
$itemsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;

// Get total count
$countSql = "SELECT COUNT(*) as total FROM HoSoViPham hs
             JOIN PhuongTien pt ON hs.BienSoXe = pt.BienSoXe
             JOIN ChuXe cx ON pt.MaChuXe = cx.MaChuXe
             JOIN LoiViPham lvp ON hs.MaLoi = lvp.MaLoi 
             WHERE 1=1 ";
if (!empty($search)) {
    if ($searchType == 'BienSoXe') {
        $countSql .= " AND pt.BienSoXe LIKE ?";
    } elseif ($searchType == 'HoTen') {
        $countSql .= " AND cx.HoTen LIKE ? COLLATE SQL_Latin1_General_CP1_CI_AI";
    } elseif ($searchType == 'CCCD') {
        $countSql .= " AND cx.CCCD LIKE ?";
    } elseif ($searchType == 'SoDienThoai') {
        $countSql .= " AND cx.SoDienThoai LIKE ?";
    }
}

$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetch()['total'];
$totalPages = ceil($totalRecords / $itemsPerPage);

// Reset page if out of range
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;
$sql .= " OFFSET CAST(? AS INT) ROWS FETCH NEXT CAST(? AS INT) ROWS ONLY";

$stmt = $conn->prepare($sql);
$paramsWithPagination = array_merge($params, [(int)$offset, (int)$itemsPerPage]);
$stmt->execute($paramsWithPagination);
$hoSoList = $stmt->fetchAll();

// Get Lỗi Vi Phạm list for Dropdown
$loiList = $conn->query("SELECT * FROM LoiViPham")->fetchAll();
?>

<div class="container mt-4 mb-5">
    <h2 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-list-check me-2"></i>Quản Lý Hồ Sơ Vi Phạm</h2>
    
    <div class="row align-items-center mb-4">
        <div class="col-md-3">
            <button class="btn btn-success fw-bold w-100" data-bs-toggle="modal" data-bs-target="#viPhamModal" onclick="resetForm()">
                <i class="fa-solid fa-plus me-2"></i>Thêm Hồ Sơ
            </button>
        </div>
        <div class="col-md-6">
            <form id="searchFormLocal" method="GET" class="d-flex gx-2">
                <select name="type" class="form-select me-2" style="width: 200px;">
                    <option value="BienSoXe" <?= $searchType=='BienSoXe'?'selected':'' ?>>Biển Số Xe</option>
                    <option value="HoTen" <?= $searchType=='HoTen'?'selected':'' ?>>Tên Chủ Xe</option>
                    <option value="CCCD" <?= $searchType=='CCCD'?'selected':'' ?>>Căn Cước/CMND</option>
                    <option value="SoDienThoai" <?= $searchType=='SoDienThoai'?'selected':'' ?>>Số Điện Thoại</option>
                </select>
                <input type="text" name="search" class="form-control me-2" placeholder="Nhập từ khóa tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-dark"><i class="fa-solid fa-filter"></i> Lọc</button>
                <?php if($search): ?>
                    <a href="manage.php" class="btn btn-outline-secondary ms-2">Xóa Lọc</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-3">
            <form method="GET" class="d-flex gap-2">
                <!-- Giữ lại các parameter tìm kiếm -->
                <input type="hidden" name="type" value="<?= $searchType ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <!-- Sort Select -->
                <select name="sort" class="form-select me-2" style="flex: 1;" onchange="this.form.submit()">
                    <option value="thoiGian" <?= $sort=='thoiGian'?'selected':'' ?>>Thời Gian</option>
                    <option value="maHS" <?= $sort=='maHS'?'selected':'' ?>>Mã Hồ Sơ</option>
                    <option value="bienSo" <?= $sort=='bienSo'?'selected':'' ?>>Biển Số Xe</option>
                    <option value="trangThai" <?= $sort=='trangThai'?'selected':'' ?>>Trạng Thái</option>
                </select>
                
                <!-- Direction Select -->
                <select name="dir" class="form-select" style="width: 120px;" onchange="this.form.submit()">
                    <option value="DESC" <?= $sortDir=='DESC'?'selected':'' ?>>↓ Giảm</option>
                    <option value="ASC" <?= $sortDir=='ASC'?'selected':'' ?>>↑ Tăng</option>
                </select>
            </form>
        </div>
        <div class="col-md-2">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="exportData('csv')" title="Xuất CSV">
                    <i class="fa-solid fa-file-csv me-1"></i>CSV
                </button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="exportData('xlsx')" title="Xuất Excel">
                    <i class="fa-solid fa-file-excel me-1"></i>Excel
                </button>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060" id="toastContainer"></div>

    <div class="card shadow-sm border-0" id="tableContent">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Mã HS</th>
                        <th>Biển Số - Chủ Xe</th>
                        <th>Chi Tiết Lỗi</th>
                        <th>Thời Gian / Địa Điểm</th>
                        <th>Trạng Thái</th>
                        <th class="text-center pe-3">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($hoSoList) == 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Không tìm thấy dữ liệu phù hợp</td></tr>
                    <?php endif; ?>
                    <?php foreach($hoSoList as $hs): ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?= $hs['MaHoSo'] ?></td>
                            <td>
                                <div class="text-primary fw-bold"><?= $hs['BienSoXe'] ?></div>
                                <small class="text-muted"><i class="fa-solid fa-user me-1"></i><?= $hs['HoTen'] ?> - <?= $hs['SoDienThoai'] ?></small>
                            </td>
                            <td>
                                <div><?= $hs['TenLoi'] ?></div>
                                <span class="badge bg-danger"><?= number_format($hs['MucPhatTien'],0,',','.') ?> ₫</span>
                            </td>
                            <td>
                                <div><i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($hs['ThoiGianViPham'])) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= $hs['DiaDiemViPham'] ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $hs['TrangThai']=='Đã nộp phạt'?'success':'warning text-dark' ?>"><?= $hs['TrangThai'] ?></span>
                            </td>
                            <td class="text-center pe-3">
                                <button class="btn btn-sm btn-secondary" onclick='viewHsDetail("<?= $hs['MaHoSo'] ?>")' title="Xem chi tiết"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-sm btn-info text-white" onclick='editHs(<?= json_encode($hs) ?>)' title="Sửa"><i class="fa-solid fa-pen"></i></button>
                                <?php if (isAdmin()): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteHs('<?= $hs['MaHoSo'] ?>')" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="Pagination" class="d-flex justify-content-center mt-4">
                <ul class="pagination">
                    <!-- Previous Button -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page-1) ?>&type=<?= urlencode($searchType) ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sortDir) ?>">
                            <i class="fa-solid fa-chevron-left"></i> Trước
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    // Show max 5 page numbers
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&type=<?= urlencode($searchType) ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sortDir) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                    endif;
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= urlencode($searchType) ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sortDir) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor;
                    
                    if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $totalPages ?>&type=<?= urlencode($searchType) ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sortDir) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($totalPages, $page+1) ?>&type=<?= urlencode($searchType) ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sortDir) ?>">
                            Sau <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="text-center text-muted small mb-4">
                Trang <?= $page ?> / <?= $totalPages ?> (<?= $totalRecords ?> hồ sơ, <?= $itemsPerPage ?>/trang)
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: Chi Tiết Hồ Sơ & Ảnh Chứng Cứ -->
<div class="modal fade" id="hsDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-info text-white rounded-top-4">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-lines me-2"></i>Chi Tiết Hồ Sơ Vi Phạm
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viPhamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title" id="modalTitle"><i class="fa-solid fa-file-circle-plus me-2"></i>Lập Hồ Sơ Vi Phạm</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="viPhamForm" onsubmit="return false;">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="frmAction" value="add">
                    <input type="hidden" name="maHoSo" id="frmMaHoSo" value="">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Biển Số Xe <span class="text-danger">*</span></label>
                            <input type="text" name="bienSoXe" id="frmBienSoXe" class="form-control" placeholder="Nhập để tìm xe..." required autocomplete="off" readonly>
                            <div id="viPhamSuggest" class="autocomplete-list d-none position-absolute bg-white border shadow-sm z-3" style="width: 250px;"></div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Thông tin phương tiện (Auto-fill)</label>
                            <div class="p-2 border rounded bg-light" id="autoFillBox" style="min-height: 40px; font-size: 0.9rem;">
                                <span class="text-muted fst-italic">Nhập biển số hợp lệ để hiển thị thông tin...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Lỗi Vi Phạm <span class="text-danger">*</span></label>
                            <select name="maLoi" id="frmMaLoi" class="form-select" required>
                                <option value="">-- Chọn lỗi vi phạm --</option>
                                <?php foreach($loiList as $loi): ?>
                                    <option value="<?= $loi['MaLoi'] ?>"><?= $loi['TenLoi'] ?> (<?= number_format($loi['MucPhatTien'],0,',','.') ?> ₫)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Thời Gian Vi Phạm <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="thoiGian" id="frmThoiGian" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Trạng Thái</label>
                            <select name="trangThai" id="frmTrangThai" class="form-select">
                                <option value="Chưa nộp phạt">Chưa nộp phạt</option>
                                <option value="Đã nộp phạt">Đã nộp phạt</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Địa Điểm Chụp Hình Camera <span class="text-danger">*</span></label>
                        <input type="text" name="diaDiem" id="frmDiaDiem" class="form-control" required placeholder="Ghi rõ ngã tư, tuyến đường...">
                    </div>
                    
                    <!-- Upload Evidence Image (khi thêm mới) -->
                    <div id="uploadImageSection" class="mb-3">
                        <label class="form-label fw-bold">Ảnh Chứng Cứ (không bắt buộc)</label>
                        <input type="file" name="evidence_image" id="frmEvidenceImage" class="form-control" accept="image/*" onchange="previewImage()">
                        <small class="text-muted">Định dạng: JPG, PNG, GIF. Kích thước tối đa: 5MB</small>
                        <div id="imagePreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy Bỏ</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btnSubmitForm" onclick="submitViPhamForm();">
                        <i class="fa-solid fa-save me-1"></i> Lưu Hồ Sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ===== IMAGE UPLOAD FUNCTIONS =====
function previewImage() {
    const input = document.getElementById('frmEvidenceImage');
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        // Check file size
        if (file.size > maxSize) {
            preview.innerHTML = '<div class="alert alert-danger alert-sm mb-0" role="alert">' +
                '<i class="fa-solid fa-exclamation-circle me-2"></i>' +
                '<strong>Kích thước ảnh lớn!</strong> Tệp có kích thước ' + (file.size / (1024*1024)).toFixed(2) + 'MB, tối đa 5MB.' +
                '</div>';
            input.value = '';  // Clear selection
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            preview.innerHTML = '<div class="alert alert-danger alert-sm mb-0" role="alert">' +
                '<i class="fa-solid fa-exclamation-circle me-2"></i>' +
                '<strong>Định dạng không hỗ trợ!</strong> Chỉ chấp nhận JPG, PNG, GIF.' +
                '</div>';
            input.value = '';  // Clear selection
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 150px;" class="img-thumbnail">' +
                '<div class="mt-2 small text-muted">Kích thước: ' + (file.size / 1024).toFixed(2) + ' KB</div>';
        };
        reader.readAsDataURL(file);
    }
}

function editHs(hsData) {
    // Không dùng reset() vì nó sẽ reset disabled attribute
    // Clear fields individually
    document.getElementById('frmMaHoSo').value = '';
    document.getElementById('frmEvidenceImage').value = '';
    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('viPhamSuggest').innerHTML = '';
    document.getElementById('viPhamSuggest').classList.add('d-none');
    
    document.getElementById('frmAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa Hồ Sơ Vi Phạm';
    document.getElementById('frmMaHoSo').value = hsData.MaHoSo;
    document.getElementById('frmBienSoXe').value = hsData.BienSoXe;
    document.getElementById('frmBienSoXe').setAttribute('readonly', 'readonly');  // Set readonly attribute
    document.getElementById('frmBienSoXe').disabled = true;
    document.getElementById('frmBienSoXe').style.backgroundColor = '#e9ecef';
    document.getElementById('frmBienSoXe').style.cursor = 'not-allowed';
    document.getElementById('frmBienSoXe').style.pointerEvents = 'none';  // Ngăn click hoàn toàn
    
    document.getElementById('frmMaLoi').value = hsData.MaLoi;
    document.getElementById('frmThoiGian').value = hsData.ThoiGianViPham;
    document.getElementById('frmDiaDiem').value = hsData.DiaDiemViPham;
    document.getElementById('frmTrangThai').value = hsData.TrangThai;
    
    // ✅ SHOW upload section khi edit (cho phép thay thế ảnh)
    document.getElementById('uploadImageSection').style.display = 'block';
    document.getElementById('frmEvidenceImage').removeAttribute('required');  // Không bắt buộc khi edit
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('viPhamModal'));
    modal.show();
}

function resetForm() {
    document.getElementById('frmAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-file-circle-plus me-2"></i>Lập Hồ Sơ Vi Phạm';
    document.getElementById('viPhamForm').reset();
    document.getElementById('frmBienSoXe').removeAttribute('readonly');  // Enable nhập
    document.getElementById('frmBienSoXe').disabled = false;
    document.getElementById('frmBienSoXe').style.backgroundColor = '';
    document.getElementById('frmBienSoXe').style.cursor = 'auto';
    document.getElementById('frmBienSoXe').style.pointerEvents = 'auto';
    document.getElementById('uploadImageSection').style.display = 'block';
    document.getElementById('imagePreview').innerHTML = '';
}

// View hồ sơ chi tiết với ảnh
function viewHsDetail(maHoSo) {
    fetch('/admin_vi_pham/ajax/get_hs_detail.php?maHoSo=' + encodeURIComponent(maHoSo))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Lỗi: ' + data.msg);
                return;
            }
            
            const hs = data.data;
            const img = data.image;
            
            let detailHtml = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Thông Tin Hồ Sơ</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Mã Hồ Sơ:</th>
                                <td><strong>${hs.MaHoSo}</strong></td>
                            </tr>
                            <tr>
                                <th>Biển Số Xe:</th>
                                <td><strong>${hs.BienSoXe}</strong></td>
                            </tr>
                            <tr>
                                <th>Lỗi Vi Phạm:</th>
                                <td><strong>${hs.TenLoi}</strong></td>
                            </tr>
                            <tr>
                                <th>Mức Phạt:</th>
                                <td><strong>${parseInt(hs.MucPhatTien).toLocaleString('vi-VN')}₫</strong></td>
                            </tr>
                            <tr>
                                <th>Hình Phạt Bổ Sung:</th>
                                <td><small>${hs.HinhThucPhatBoSung || 'Không'}</small></td>
                            </tr>
                            <tr>
                                <th>Thời Gian:</th>
                                <td>${hs.ThoiGianViPham}</td>
                            </tr>
                            <tr>
                                <th>Địa Điểm:</th>
                                <td>${hs.DiaDiemViPham}</td>
                            </tr>
                            <tr>
                                <th>Trạng Thái:</th>
                                <td>
                                    <span class="badge bg-${hs.TrangThai=='Đã nộp phạt'?'success':'warning text-dark'}">
                                        ${hs.TrangThai}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Thông Tin Chủ Xe</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Chủ Xe:</th>
                                <td><strong>${hs.HoTen}</strong></td>
                            </tr>
                            <tr>
                                <th>CCCD:</th>
                                <td>${hs.CCCD}</td>
                            </tr>
                            <tr>
                                <th>Điện Thoại:</th>
                                <td>${hs.SoDienThoai}</td>
                            </tr>
                            <tr>
                                <th>Địa Chỉ:</th>
                                <td>${hs.DiaChi}</td>
                            </tr>
                        </table>
                        
                        <h6 class="text-muted mb-3 mt-4">Thông Tin Phương Tiện</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Loại Xe:</th>
                                <td>${hs.LoaiXe}</td>
                            </tr>
                            <tr>
                                <th>Nhãn Hiệu:</th>
                                <td>${hs.NhanHieu}</td>
                            </tr>
                            <tr>
                                <th>Màu Sắc:</th>
                                <td>${hs.MauSac}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
            
            // Add evidence image if exists
            if (img) {
                detailHtml += `
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">
                                <i class="fa-solid fa-image me-2"></i>Ảnh Chứng Cứ
                            </h6>
                            <div class="text-center">
                                <img src="/admin_vi_pham/img/evidence/${img.URL_HinhAnh}" 
                                     alt="Ảnh chứng cứ" 
                                     style="max-width: 100%; max-height: 400px;" 
                                     class="img-fluid border rounded">
                                <div class="mt-2">
                                    <p class="mb-0" style="font-size: 0.85rem; font-style: italic; color: #6c757d;">
                                        ${img.GhiChu || 'Ảnh chứng cứ'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                detailHtml += `
                    <div class="alert alert-warning mt-4">
                        <i class="fa-solid fa-exclamation-circle me-2"></i>
                        Hồ sơ này chưa có ảnh chứng cứ
                    </div>
                `;
            }
            
            document.getElementById('detailContent').innerHTML = detailHtml;
            const modal = new bootstrap.Modal(document.getElementById('hsDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi khi tải chi tiết!');
        });
}

function submitViPhamForm() {
    const form = document.getElementById('viPhamForm');
    const action = document.getElementById('frmAction').value;
    let formData;
    
    // Khi edit: re-enable bienSoXe để submit được (nó disabled cho readonly visual only)
    if (action === 'edit') {
        const bienSoXeField = document.getElementById('frmBienSoXe');
        if (bienSoXeField) {
            bienSoXeField.disabled = false;
            // Tạo FormData mới sau khi enable field
            formData = new FormData(form);
            // Disable lại sau khi lấy dữ liệu
            bienSoXeField.disabled = true;
        }
    } else {
        formData = new FormData(form);
    }
    
    formData.append('ajax_action', action);
    
    // Kiểm tra required fields
    // Khi ADD: cần bienSoXe, maLoi, thoiGian, diaDiem
    // Khi EDIT: chỉ cần maLoi, thoiGian, diaDiem (bienSoXe là readonly)
    if (action === 'add') {
        if (!formData.get('bienSoXe') || !formData.get('maLoi') || !formData.get('thoiGian') || !formData.get('diaDiem')) {
            alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
            // Restore disabled state nếu edit
            if (document.getElementById('frmBienSoXe')) {
                document.getElementById('frmBienSoXe').disabled = true;
            }
            return;
        }
    } else if (action === 'edit') {
        if (!formData.get('maLoi') || !formData.get('thoiGian') || !formData.get('diaDiem')) {
            alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
            // Restore disabled state
            if (document.getElementById('frmBienSoXe')) {
                document.getElementById('frmBienSoXe').disabled = true;
            }
            return;
        }
        // Restore disabled state cho visual
        if (document.getElementById('frmBienSoXe')) {
            document.getElementById('frmBienSoXe').disabled = true;
        }
    }
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.msg);
            bootstrap.Modal.getInstance(document.getElementById('viPhamModal')).hide();
            location.reload();
        } else {
            alert('Lỗi: ' + data.msg);
            // Restore disabled state nếu edit
            if (action === 'edit' && document.getElementById('frmBienSoXe')) {
                document.getElementById('frmBienSoXe').disabled = true;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Lỗi khi xử lý yêu cầu!');
        // Restore disabled state nếu edit
        if (action === 'edit' && document.getElementById('frmBienSoXe')) {
            document.getElementById('frmBienSoXe').disabled = true;
        }
    });
}

// ===== EXPORT & OTHER FUNCTIONS =====
// Export data to CSV or Excel
function exportData(format) {
    // Get current filter parameters from form
    const searchType = document.querySelector('select[name="type"]').value;
    const searchValue = document.querySelector('input[name="search"]').value;
    
    // Get current sort parameters (nếu có hidden fields)
    const sortField = document.querySelector('select[name="sort"]')?.value || 'thoiGian';
    const sortDir = document.querySelector('select[name="dir"]')?.value || 'DESC';
    
    // Build export URL with all parameters
    let url = '/admin_vi_pham/ajax/export_violations.php?format=' + format;
    if (searchType) url += '&searchType=' + encodeURIComponent(searchType);
    if (searchValue) url += '&searchValue=' + encodeURIComponent(searchValue);
    url += '&sort=' + encodeURIComponent(sortField);
    url += '&dir=' + encodeURIComponent(sortDir);
    
    // Trigger download
    window.location.href = url;
}
</script>

<?php require_once '../includes/footer.php'; ?>