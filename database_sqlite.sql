-- SQLite Database Schema
-- Database: ViPhamGiaoThong.db

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ==============================================
-- XÓA BẢNG CŨ (NẾU CÓ)
-- ==============================================
DROP TABLE IF EXISTS HinhAnhChungCu;
DROP TABLE IF EXISTS HoSoViPham;
DROP TABLE IF EXISTS PhuongTien;
DROP TABLE IF EXISTS ChuXe;
DROP TABLE IF EXISTS LoiViPham;

-- ==============================================
-- TẠO CẤU TRÚC BẢNG
-- ==============================================

-- Bảng Chủ Xe
CREATE TABLE ChuXe (
    MaChuXe TEXT PRIMARY KEY,
    HoTen TEXT NOT NULL,
    CCCD TEXT NOT NULL UNIQUE,
    SoDienThoai TEXT,
    DiaChi TEXT
);

-- Bảng Phương Tiện
CREATE TABLE PhuongTien (
    BienSoXe TEXT PRIMARY KEY,
    MaChuXe TEXT NOT NULL,
    LoaiXe TEXT,
    NhanHieu TEXT,
    MauSac TEXT,
    FOREIGN KEY (MaChuXe) REFERENCES ChuXe(MaChuXe)
);

-- Bảng Lỗi Vi Phạm
CREATE TABLE LoiViPham (
    MaLoi TEXT PRIMARY KEY,
    TenLoi TEXT NOT NULL,
    MucPhatTien REAL NOT NULL CHECK (MucPhatTien > 0),
    HinhThucPhatBoSung TEXT
);

-- Bảng Hồ Sơ Vi Phạm
CREATE TABLE HoSoViPham (
    MaHoSo TEXT PRIMARY KEY,
    BienSoXe TEXT NOT NULL,
    MaLoi TEXT NOT NULL,
    ThoiGianViPham DATETIME NOT NULL,
    DiaDiemViPham TEXT NOT NULL,
    TrangThai TEXT DEFAULT 'Chưa nộp phạt' CHECK (TrangThai IN ('Chưa nộp phạt', 'Đã nộp phạt')),
    FOREIGN KEY (BienSoXe) REFERENCES PhuongTien(BienSoXe),
    FOREIGN KEY (MaLoi) REFERENCES LoiViPham(MaLoi)
);

-- Bảng Hình Ảnh Chứng Cứ
CREATE TABLE HinhAnhChungCu (
    MaHinhAnh TEXT PRIMARY KEY,
    MaHoSo TEXT NOT NULL UNIQUE,
    URL_HinhAnh TEXT NOT NULL,
    GhiChu TEXT,
    NgayTaiLen DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (MaHoSo) REFERENCES HoSoViPham(MaHoSo) ON DELETE CASCADE
);

-- ==============================================
-- THÊM DỮ LIỆU MẪU
-- ==============================================

-- CHỦ XE
INSERT INTO ChuXe (MaChuXe, HoTen, CCCD, SoDienThoai, DiaChi) VALUES 
('CX001', 'Nguyễn Văn A', '079090123456', '0901234567', '123 Lê Lợi, Quận 1, TP.HCM'),
('CX002', 'Trần Thị B', '079090654321', '0912345678', '456 Trần Hưng Đạo, Quận 5, TP.HCM'),
('CX003', 'Trần Quang Đại', '001090123456', '0987654321', '15 Trần Phú, Hà Đông, Hà Nội'),
('CX004', 'Lê Hương Giang', '030190654321', '0933445566', '72 Nguyễn Trãi, Thanh Xuân, Hà Nội'),
('CX005', 'Phạm Minh Tuấn', '048090112233', '0909090909', 'KĐT Vinaconex, Nam Từ Liêm, Hà Nội');

-- PHƯƠNG TIỆN
INSERT INTO PhuongTien (BienSoXe, MaChuXe, LoaiXe, NhanHieu, MauSac) VALUES 
('51F-123.45', 'CX001', 'Ô tô con', 'Toyota', 'Trắng'),
('59T1-987.65', 'CX002', 'Xe máy', 'Honda', 'Đỏ'),
('30G-999.99', 'CX003', 'Ô tô con', 'Mazda', 'Đỏ'),
('29V1-456.78', 'CX003', 'Xe máy', 'Yamaha', 'Đen'),
('30K-111.22', 'CX004', 'Ô tô con', 'Kia', 'Trắng'),
('29H-888.88', 'CX004', 'Xe bán tải', 'Ford', 'Cam'),
('29C-333.44', 'CX005', 'Xe tải', 'Hino', 'Xanh biển'),
('59S2-111.11', 'CX001', 'Xe máy', 'Vespa', 'Trắng');

-- LỖI VI PHẠM
INSERT INTO LoiViPham (MaLoi, TenLoi, MucPhatTien, HinhThucPhatBoSung) VALUES 
('L01', 'Vượt đèn đỏ', 5000000, 'Tước GPLX 2 tháng'),
('L02', 'Chạy quá tốc độ (từ 10-20km/h)', 3000000, 'Không'),
('L03', 'Đi sai làn đường', 2000000, 'Tước GPLX 1 tháng'),
('L04', 'Không đội mũ bảo hiểm', 500000, 'Không'),
('L05', 'Đi ngược chiều trên đường có biển cấm', 1500000, 'Tước GPLX 2 tháng'),
('L06', 'Vi phạm nồng độ cồn (Vượt quá 0.4mg/l khí thở)', 7000000, 'Tước GPLX 23 tháng, Tạm giữ xe 7 ngày'),
('L07', 'Dừng đỗ xe sai quy định', 900000, 'Không');

-- HỒ SƠ VI PHẠM
INSERT INTO HoSoViPham (MaHoSo, BienSoXe, MaLoi, ThoiGianViPham, DiaDiemViPham, TrangThai) VALUES 
('HS001', '51F-123.45', 'L01', '2023-10-15 08:30:00', 'Ngã tư Điện Biên Phủ - Đinh Tiên Hoàng', 'Chưa nộp phạt'),
('HS002', '59T1-987.65', 'L02', '2023-10-20 14:15:00', 'Đại lộ Võ Văn Kiệt', 'Đã nộp phạt'),
('HS003', '30G-999.99', 'L02', '2024-01-15 10:20:00', 'Cao tốc Pháp Vân - Cầu Giẽ', 'Chưa nộp phạt'),
('HS004', '29V1-456.78', 'L04', '2024-02-10 07:45:00', 'Ngã tư Quang Trung - Lê Trọng Tấn', 'Đã nộp phạt'),
('HS005', '30K-111.22', 'L05', '2024-03-05 22:15:00', 'Đường Khuất Duy Tiến', 'Chưa nộp phạt'),
('HS006', '29C-333.44', 'L03', '2023-12-20 14:00:00', 'Vành đai 3 trên cao', 'Đã nộp phạt'),
('HS007', '29H-888.88', 'L07', '2024-04-01 09:30:00', 'Trước cổng bệnh viện Bạch Mai', 'Chưa nộp phạt'),
('HS008', '51F-123.45', 'L06', '2024-04-10 23:45:00', 'Chốt kiểm tra ngã tư Hàng Xanh', 'Chưa nộp phạt'),
('HS009', '59S2-111.11', 'L04', '2024-04-12 08:00:00', 'Đường Phạm Văn Đồng', 'Chưa nộp phạt');

-- HÌNH ẢNH CHỨNG CỨ
INSERT INTO HinhAnhChungCu (MaHinhAnh, MaHoSo, URL_HinhAnh, GhiChu) VALUES 
('HA001', 'HS001', 'ha001.jpg', 'Vượt đèn đỏ - Ngã tư Điện Biên Phủ'),
('HA002', 'HS002', 'ha002.jpg', 'Chạy quá tốc độ - Đại lộ Võ Văn Kiệt'),
('HA003', 'HS003', 'ha003.jpg', 'Chạy quá tốc độ - Cao tốc Pháp Vân'),
('HA004', 'HS004', 'ha004.jpg', 'Không đội mũ bảo hiểm'),
('HA005', 'HS005', 'ha005.jpg', 'Đi ngược chiều - Đường Khuất Duy Tiến'),
('HA006', 'HS006', 'ha006.jpg', 'Đi sai làn đường - Vành đai 3'),
('HA007', 'HS007', 'ha007.jpg', 'Dừng đỗ sai quy định - Bệnh viện Bạch Mai'),
('HA008', 'HS008', 'ha008.jpg', 'Vi phạm nồng độ cồn - Chốt kiểm tra'),
('HA009', 'HS009', 'ha009.jpg', 'Không đội mũ bảo hiểm - Đường Phạm Văn Đồng');
