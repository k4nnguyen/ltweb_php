<?php
// admin/index.php

// Check session and timeout
require_once '../includes/check-session.php';

// Dashboard và Tra cứu vi phạm cho Admin
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Lấy thống kê cơ bản
try {
    // Tổng số hồ sơ vi phạm
    $stmt1 = $conn->query("SELECT COUNT(MaHoSo) as Total FROM HoSoViPham");
    $totalRecords = $stmt1->fetch()['Total'];

    // Tổng số tiền chưa nộp (TrangThai = 'Chưa nộp phạt')
    $stmt2 = $conn->query("SELECT SUM(lvp.MucPhatTien) as Unpaid FROM HoSoViPham hs JOIN LoiViPham lvp ON hs.MaLoi = lvp.MaLoi WHERE hs.TrangThai = N'Chưa nộp phạt'");
    $unpaidFines = $stmt2->fetch()['Unpaid'] ?? 0;
} catch (PDOException $e) {
    $totalRecords = 0;
    $unpaidFines = 0;
}
?>

<div class="container main-content mt-4 mb-5" style="min-height: calc(100vh - 200px);">
    <h2 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-chart-line me-2"></i>Tổng Quan Hệ Thống</h2>
    
    <!-- Dashboard Cards -->
    <div class="row mb-5">
        <div class="col-md-6 mb-3">
            <div class="card bg-info text-white shadow-sm border-0 h-100 rounded-4">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4"><i class="fa-solid fa-file-signature fa-4x opacity-50"></i></div>
                    <div>
                        <h5 class="card-title fw-semibold">Tổng Số Hồ Sơ Khởi Tạo</h5>
                        <h2 class="display-5 fw-bold mb-0"><?= number_format($totalRecords, 0, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-danger text-white shadow-sm border-0 h-100 rounded-4">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4"><i class="fa-solid fa-sack-dollar fa-4x opacity-50"></i></div>
                    <div>
                        <h5 class="card-title fw-semibold">Phạt Tiền Chưa Thu (Dự Kiến)</h5>
                        <h2 class="display-5 fw-bold mb-0"><?= number_format($unpaidFines, 0, ',', '.') ?> ₫</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5 border-secondary opacity-25">

    <!-- Tra cứu phương tiện -->
    <h3 class="fw-bold mb-4"><i class="fa-solid fa-magnifying-glass me-2"></i>Tra Cứu Khẩn Phương Tiện</h3>
    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4">
                    <form id="searchForm">
                        <div class="mb-4 position-relative">
                            <label for="plateNumber" class="form-label fw-semibold text-dark"><i class="fa-solid fa-id-card me-2"></i>Nhập Biển Số Xe Tình Nghi</label>
                            <input type="text" class="form-control form-control-lg border-2" id="plateNumber" placeholder="VD: 51F-123.45" required autocomplete="off">
                            <div id="plateSuggest" class="autocomplete-list d-none position-absolute w-100 bg-white border rounded shadow-sm z-3"></div>
                        </div>
                        <button type="button" id="btnSearch" class="btn btn-dark btn-lg w-100 fw-bold shadow-sm">
                            <i class="fa-solid fa-search me-2"></i>Tìm Kiếm
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <!-- Kết quả AJAX -->
            <div id="resultArea">
                <div class="alert alert-secondary border-0 shadow-sm d-flex align-items-center">
                    <i class="fa-solid fa-circle-info fa-2x me-3"></i>
                    <div>Nhập biển kiểm soát bên cạnh để xem ngay lịch sử vi phạm, mức phạt quy định và trích xuất bằng chứng (hình ảnh/video).</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
