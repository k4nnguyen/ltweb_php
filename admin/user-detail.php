<?php
// admin/user-detail.php

// Check session and timeout
require_once '../includes/check-session.php';

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Lấy ID người dùng
$userId = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($userId)) {
    header('Location: users.php');
    exit;
}

// Lấy thông tin chủ xe
try {
    $stmt = $conn->prepare("SELECT * FROM ChuXe WHERE MaChuXe = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: users.php');
        exit;
    }
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    exit;
}

// Lấy thông tin phương tiện và vi phạm của chủ xe
try {
    $query = "
        SELECT 
            pt.BienSoXe,
            pt.LoaiXe,
            pt.NhanHieu,
            pt.MauSac,
            COUNT(hs.MaHoSo) as TongViPham,
            SUM(CASE WHEN hs.TrangThai = N'Đã nộp phạt' THEN 1 ELSE 0 END) as ViPhamDaNop,
            SUM(CASE WHEN hs.TrangThai = N'Chưa nộp phạt' THEN 1 ELSE 0 END) as ViPhamChuaNop
        FROM PhuongTien pt
        LEFT JOIN HoSoViPham hs ON pt.BienSoXe = hs.BienSoXe
        WHERE pt.MaChuXe = :userId
        GROUP BY pt.BienSoXe, pt.LoaiXe, pt.NhanHieu, pt.MauSac
        ORDER BY pt.BienSoXe
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['userId' => $userId]);
    $vehicles = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    $vehicles = [];
}
?>

<div class="container-fluid" style="padding-top: 100px; padding-bottom: 40px;">
    <div class="container">
        <!-- Back Button & Header -->
        <div class="row mb-4">
            <div class="col-md-12">
                <a href="users.php" class="btn btn-secondary mb-3">
                    <i class="fa-solid fa-arrow-left me-1"></i>Quay Lại
                </a>
                <h2><i class="fa-solid fa-user-circle me-2"></i>Chi Tiết Người Dùng</h2>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-id-card me-2"></i>Thông Tin Cá Nhân</h5>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Họ Tên:</strong></div>
                            <div class="col-md-8"><?= htmlspecialchars($user['HoTen']) ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>CCCD:</strong></div>
                            <div class="col-md-8"><?= htmlspecialchars($user['CCCD']) ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Số Điện Thoại:</strong></div>
                            <div class="col-md-8"><?= htmlspecialchars($user['SoDienThoai']) ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><strong>Ngày Sinh:</strong></div>
                            <div class="col-md-8"><?= isset($user['NgaySinh']) ? htmlspecialchars($user['NgaySinh']) : 'N/A' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-chart-bar me-2"></i>Thống Kê Vi Phạm</h5>
                        <hr>
                        <?php
                        $totalViolations = 0;
                        $totalPaid = 0;
                        $totalUnpaid = 0;
                        foreach ($vehicles as $v) {
                            $totalViolations += $v['TongViPham'];
                            $totalPaid += $v['ViPhamDaNop'] ?? 0;
                            $totalUnpaid += $v['ViPhamChuaNop'] ?? 0;
                        }
                        ?>
                        <div class="text-center">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="p-3 bg-warning text-white rounded">
                                        <h3><?= $totalViolations ?></h3>
                                        <small>Tổng Vi Phạm</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-success text-white rounded">
                                        <h3><?= $totalPaid ?></h3>
                                        <small>Đã Nộp</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-danger text-white rounded">
                                        <h3><?= $totalUnpaid ?></h3>
                                        <small>Chưa Nộp</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicles Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="fa-solid fa-car me-2"></i>Danh Sách Phương Tiện</h5>
                
                <?php if (empty($vehicles)): ?>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle me-2"></i>Người dùng này chưa có phương tiện
                    </div>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-primary text-white">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <strong class="fs-5"><?= htmlspecialchars($vehicle['BienSoXe']) ?></strong>
                                        <small class="ms-3"><?= htmlspecialchars($vehicle['NhanHieu']) ?> - <?= htmlspecialchars($vehicle['LoaiXe']) ?></small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <span class="badge bg-warning me-2"><?= $vehicle['TongViPham'] ?> Vi Phạm</span>
                                        <span class="badge bg-success me-2"><?= $vehicle['ViPhamDaNop'] ?? 0 ?> Đã Nộp</span>
                                        <span class="badge bg-danger"><?= $vehicle['ViPhamChuaNop'] ?? 0 ?> Chưa Nộp</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Loại Xe:</strong> <?= htmlspecialchars($vehicle['LoaiXe']) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Nhãn Hiệu:</strong> <?= htmlspecialchars($vehicle['NhanHieu']) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Màu Xe:</strong> <?= htmlspecialchars($vehicle['MauSac']) ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="manage.php?type=BienSoXe&search=<?= urlencode($vehicle['BienSoXe']) ?>" class="btn btn-sm btn-info">
                                            <i class="fa-solid fa-list me-1"></i>Xem Vi Phạm
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
