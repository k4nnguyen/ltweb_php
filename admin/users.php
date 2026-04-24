<?php
// admin/users.php

// Check session and timeout
require_once '../includes/check-session.php';

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Lấy danh sách chủ xe với thống kê
try {
    $query = "
        SELECT 
            cx.MaChuXe,
            cx.HoTen, 
            cx.CCCD, 
            cx.SoDienThoai,
            COUNT(DISTINCT pt.BienSoXe) as SoPhuongTien,
            COUNT(DISTINCT hs.MaHoSo) as TongViPham,
            SUM(CASE WHEN hs.TrangThai = N'Đã nộp phạt' THEN 1 ELSE 0 END) as ViPhamDaNop,
            SUM(CASE WHEN hs.TrangThai = N'Chưa nộp phạt' THEN 1 ELSE 0 END) as ViPhamChuaNop
        FROM ChuXe cx
        LEFT JOIN PhuongTien pt ON cx.MaChuXe = pt.MaChuXe
        LEFT JOIN HoSoViPham hs ON pt.BienSoXe = hs.BienSoXe
        GROUP BY cx.MaChuXe, cx.HoTen, cx.CCCD, cx.SoDienThoai
        ORDER BY cx.HoTen
    ";
    
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    $users = [];
}
?>

<div class="container-fluid" style="padding-top: 100px; padding-bottom: 40px;">
    <div class="container">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fa-solid fa-users me-2"></i>Thông Tin Người Dùng</h2>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Họ Tên</th>
                            <th>CCCD</th>
                            <th>Số Điện Thoại</th>
                            <th class="text-center">Số Phương Tiện</th>
                            <th class="text-center">Tổng Vi Phạm</th>
                            <th class="text-center">Đã Nộp</th>
                            <th class="text-center">Chưa Nộp</th>
                            <th class="text-center">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox me-2"></i>Không có dữ liệu
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['HoTen']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['CCCD']) ?></td>
                                    <td><?= htmlspecialchars($user['SoDienThoai']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $user['SoPhuongTien'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning"><?= $user['TongViPham'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= $user['ViPhamDaNop'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $user['ViPhamChuaNop'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="user-detail.php?id=<?= $user['MaChuXe'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-eye me-1"></i>Xem Chi Tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
