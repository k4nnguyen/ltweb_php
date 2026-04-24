# 📋 Tài Liệu Chi Tiết Hệ Thống Quản Lý Vi Phạm Giao Thông

## 🎯 Mục Đích Hệ Thống

Quản lý hồ sơ vi phạm giao thông, bao gồm: thêm/sửa/xóa hồ sơ, lưu trữ ảnh chứng cứ, xuất báo cáo, quản lý tài khoản người dùng và đăng nhập/đăng xuất.

---

## 📁 Cấu Trúc Thư Mục

```
admin_vi_pham/
├── admin/               # Trang quản trị (yêu cầu đăng nhập)
│   ├── login.php       # Xác thực người dùng
│   ├── manage.php      # Quản lý hồ sơ vi phạm
│   ├── logs.php        # Xem nhật ký hệ thống
│   └── ...
├── ajax/               # API endpoints (xử lý AJAX requests)
│   ├── session-info.php
│   ├── get_hs_detail.php
│   ├── export_violations.php
│   └── ...
├── includes/           # Shared PHP files & helpers
│   ├── check-session.php        # Kiểm tra session & timeout
│   ├── header.php               # HTML head & navbar
│   ├── navbar.php               # Navigation bar
│   ├── helpers/
│   │   ├── auth.php             # Xác thực & phân quyền
│   │   ├── image_handler.php    # Xử lý upload ảnh
│   │   ├── audit_log.php        # Ghi log hành động
│   │   └── ...
│   └── ...
├── config/             # Cấu hình
│   ├── database.php    # Kết nối SQL Server
│   └── ...
├── img/
│   └── evidence/       # Thư mục lưu ảnh chứng cứ
├── logs/               # Tệp log hệ thống
└── css/, js/           # Stylesheets & JavaScript
```

---

## 🔐 **Phần 1: Xác Thực & Quản Lý Phiên (Session)**

### 1. `admin/login.php`

**Mục đích:** Xác thực người dùng đăng nhập

**Chức năng chính:**

- Nhận dữ liệu POST từ form login (username, password)
- Gọi `authenticateUser()` từ `includes/helpers/auth.php` để kiểm tra thông tin
- Nếu đúng:
  - Hủy session cũ: `session_destroy()`
  - Tạo session mới an toàn: `session_regenerate_id(true)`
  - Lưu thông tin: `$_SESSION['login_time'] = time()` (dùng để tính timeout)
  - Lưu username, role (Admin/Staff)
  - Ghi log: `logAction('LOGIN', ...)`
  - Chuyển hướng tới manage.php
- Nếu sai: Hiển thị thông báo lỗi

**Cài đặt timezone:**

```php
date_default_timezone_set('Asia/Ho_Chi_Minh');  // Vietnam time
```

**Lưu ý an toàn:**

- Password được hash với Bcrypt (không lưu plain text)
- Session ID được regenerate để tránh session fixation attack
- Timeout: 30 phút tính từ lúc login

---

### 2. `includes/check-session.php`

**Mục đích:** Kiểm tra session và xử lý timeout (bao gồm tất cả trang admin)

**Chức năng chính:**

- Gọi ở **đầu tiên** của mọi trang admin (require_once)
- Kiểm tra:
  - Session đã được set? Nếu không → redirect login
  - Login time có tồn tại? Nếu không → force redirect
  - Có timeout chưa? `time() - $_SESSION['login_time'] > 1800` (30 phút)
    - Nếu có → hủy session & redirect login với message
- Tính toán & lưu thông tin hiển thị:
  - `$_SESSION['session_info']['login_time_display']` (định dạng H:i A)
  - `$_SESSION['session_info']['remaining_seconds']` (giây còn lại)
  - `$_SESSION['session_info']['remaining_time_display']` (định dạng MM:SS)

**Timeout calculation:**

```php
$SESSION_TIMEOUT = 30 * 60;  // 1800 giây
$timeFromLogin = time() - $_SESSION['login_time'];
$isTimeout = $timeFromLogin >= $SESSION_TIMEOUT;
```

**Quan trọng:** Không cập nhật `last_activity` → timeout tính từ login_time không reset

---

### 3. `ajax/session-info.php`

**Mục đích:** Cung cấp dữ liệu session real-time cho navbar (AJAX endpoint)

**Chức năng chính:**

- Nhận request từ navbar (mỗi 5 giây 1 lần)
- Tính toán thời gian còn lại tương tự `check-session.php`
- Trả về JSON:
  ```json
  {
    "remaining_seconds": 1200,
    "remaining_time_display": "20:00",
    "is_critical": false
  }
  ```
- Navbar dùng dữ liệu này để:
  - Hiển thị countdown timer
  - Đổi màu: xanh (>5min), đỏ (<5min)

**Lưu ý:** Không cập nhật session (chỉ đọc) để tránh reset timeout

---

## 📊 **Phần 2: Quản Lý Hồ Sơ Vi Phạm**

### 4. `admin/manage.php` ⭐ (FILE CHÍNH)

**Mục đích:** Quản lý toàn bộ hồ sơ vi phạm (CRUD + Export + Pagination)

**Chức năng chính:**

#### a) **Hiển thị danh sách hồ sơ (READ)**

- Lấy hồ sơ từ database với JOIN 4 bảng:
  - HoSoViPham, PhuongTien, ChuXe, LoiViPham
- Sắp xếp (Sort): 4 tùy chọn
  - Theo biển số xe
  - Theo mã hồ sơ
  - Theo trạng thái
  - Theo thời gian vi phạm (mặc định)
- **Tìm kiếm (Filter):** 4 loại
  - Biển số xe
  - Tên chủ xe (với COLLATE để so sánh tiếng Việt)
  - Số CCCD
  - Số điện thoại
- **Lọc thêm (Filter):**
  - Lỗi vi phạm
  - Trạng thái (Chưa nộp/Đã nộp)

#### b) **Phân trang (Pagination)**

- 10 hồ sơ/trang
- SQL Server syntax: `OFFSET CAST(?) AS INT ROWS FETCH NEXT CAST(?) AS INT ROWS ONLY`
- Hiển thị: "Trang 1/5 (45 hồ sơ, 10/trang)"
- Navigation links giữ nguyên sort/search parameters

#### c) **Thêm hồ sơ mới (CREATE)**

- POST action='add'
- Auto-tạo mã hồ sơ tiếp theo (HS001, HS002, ...)
- Validation:
  - Biển số xe phải tồn tại trong bảng PhuongTien
  - Các trường bắt buộc không được rỗng
  - Thời gian không được lớn hơn hiện tại
- Có thể upload ảnh chứng cứ (tuỳ chọn)
  - Gọi `uploadEvidenceImage()` từ `image_handler.php`
  - File lưu vào `/img/evidence/{number}.jpg`
  - Lưu record vào HinhAnhChungCu

#### d) **Sửa hồ sơ (UPDATE)**

- POST action='edit'
- Chỉ cho sửa **4 field:**
  - MaLoi (Lỗi vi phạm)
  - ThoiGianViPham (Thời gian)
  - DiaDiemViPham (Địa điểm)
  - TrangThai (Trạng thái)
- **Không được sửa:** BienSoXe (biển số xe)
  - HTML: Input có `readonly` attribute
  - JavaScript:
    - `disabled = true`
    - `readOnly = true`
    - `pointerEvents = 'none'` (ngăn click hoàn toàn)
    - `style.backgroundColor = '#e9ecef'` (gray styling)
  - Database: UPDATE không có BienSoXe trong SET clause
- **Có thể upload ảnh khi edit:**
  - Upload section hiển thị (không ẩn)
  - Không bắt buộc (`removeAttribute('required')`)
  - Nếu upload ảnh mới:
    - Xóa file ảnh cũ từ `/img/evidence/`
    - Xóa record cũ từ database
    - Upload ảnh mới và insert record mới

#### e) **Xóa hồ sơ (DELETE)**

- POST action='delete'
- Chỉ Admin được xóa (kiểm tra `isAdmin()`)
- Xóa hồ sơ & ảnh chứng cứ liên quan
  - Gọi `deleteEvidenceImage()` để xóa file & record

#### f) **Xuất báo cáo (EXPORT)**

- 2 format: CSV, Excel
- Gửi sang `/ajax/export_violations.php`
- Xuất dữ liệu **respecting current sort & filter**
  - Sort parameters: field, direction
  - Filter: search type/value, loiFilter, trangThaiFilter

#### g) **Xem chi tiết (VIEW DETAIL)**

- Click nút "Xem Chi Tiết" (eye icon)
- Modal hiển thị đầy đủ:
  - Thông tin hồ sơ (MaHoSo, BienSoXe, Lỗi, Mức phạt, **Hình phạt bổ sung**, etc.)
  - Thông tin chủ xe (Tên, CCCD, ĐT, Địa chỉ)
  - Thông tin xe (Loại, Nhãn hiệu, Màu sắc)
  - Ảnh chứng cứ (nếu có)
    - Hiển thị ảnh
    - Ghi chú dưới ảnh (in nghiêng, font nhỏ)

**JavaScript Functions:**

| Function               | Mục đích                                                                   |
| ---------------------- | -------------------------------------------------------------------------- |
| `resetForm()`          | Reset form add mới, enable BienSoXe, show upload section                   |
| `editHs(hsData)`       | Load dữ liệu vào form edit, disable/readonly BienSoXe, show upload section |
| `previewImage()`       | Preview ảnh, validate file size (<5MB) & format (JPG/PNG/GIF), show error  |
| `submitViPhamForm()`   | Submit form với FormData (hỗ trợ file upload)                              |
| `viewHsDetail(maHoSo)` | Fetch chi tiết hồ sơ qua AJAX & show modal                                 |
| `deleteHs(maHoSo)`     | Xóa hồ sơ (confirm trước)                                                  |
| `exportData(format)`   | Export dữ liệu hiện tại (CSV/Excel)                                        |

---

### 5. `ajax/get_hs_detail.php`

**Mục đích:** AJAX endpoint trả về chi tiết hồ sơ (với ảnh & hình phạt bổ sung)

**Nhận:** `?maHoSo=HS001`

**Trả về:** JSON

```json
{
  "success": true,
  "data": {
    "MaHoSo": "HS001",
    "BienSoXe": "51F-123.45",
    "TenLoi": "Vượt đèn đỏ",
    "MucPhatTien": "5000000",
    "HinhThucPhatBoSung": "Tước GPLX 2 tháng",
    ...
  },
  "image": {
    "URL_HinhAnh": "ha001.jpg",
    "GhiChu": "Vượt đèn đỏ - Ngã tư Điện Biên Phủ"
  }
}
```

**SQL Query:** JOIN 4 bảng để lấy đầy đủ thông tin

---

### 6. `ajax/export_violations.php`

**Mục đích:** Xuất danh sách hồ sơ thành CSV hoặc Excel

**Nhận:** GET parameters

- `format`: 'csv' hoặc 'excel'
- `sort`: tên field sắp xếp
- `dir`: 'ASC' hoặc 'DESC'
- `type`, `value`: tìm kiếm
- `loiFilter`, `trangThaiFilter`: lọc thêm

**Xử lý:**

- Xây dựng SQL với sort & filter giống manage.php
- Xuất headers HTTP:
  - `Content-Type: text/csv` (CSV) hoặc `application/vnd.ms-excel` (Excel)
  - `Content-Disposition: attachment; filename=...`
- Output dữ liệu dòng theo dòng

**CSV format:**

```
Mã Hồ Sơ,Biển Số Xe,Tên Chủ Xe,Lỗi Vi Phạm,Mức Phạt,Trạng Thái,Thời Gian
HS001,51F-123.45,Nguyễn Văn A,Vượt đèn đỏ,5000000,Chưa nộp phạt,2023-10-15 08:30
```

---

## 🛠️ **Phần 3: Xác Thực & Phân Quyền**

### 7. `includes/helpers/auth.php`

**Mục đích:** Xác thực người dùng & kiểm tra quyền hạn

**Chức năng chính:**

| Function                                 | Mục đích                                                 |
| ---------------------------------------- | -------------------------------------------------------- |
| `authenticateUser($username, $password)` | Kiểm tra username/password, return user array hoặc false |
| `isAdmin()`                              | Check role == 'Admin', return boolean                    |
| `isStaff()`                              | Check role == 'Staff', return boolean                    |
| `getCurrentUser()`                       | Lấy thông tin người dùng hiện tại từ session             |

**Từ file:** `includes/users_config.php`

```php
$users = [
    'admin' => [
        'password' => '$2y$10$...',  // Bcrypt hash
        'role' => 'Admin'
    ],
    'staff' => [
        'password' => '$2y$10$...',
        'role' => 'Staff'
    ]
];
```

**Authorization rules:**

- Admin: Được tất cả (thêm, sửa, xóa, export, xem log)
- Staff: Được xem & sửa (không xóa), không xem log

---

## 📝 **Phần 4: Ghi Log & Hình Phạt Bổ Sung**

### 8. `includes/helpers/audit_log.php`

**Mục đích:** Ghi nhật ký mọi hành động (audit trail)

**Chức năng chính:**

| Function                                 | Mục đích                   |
| ---------------------------------------- | -------------------------- |
| `logAction($action, $details, $logFile)` | Ghi log hành động vào file |

**Format file log:** `logs/system_audit.log`

```
[2024-04-24 14:30:15] [admin] [Admin] LOGIN: Đăng nhập hệ thống
[2024-04-24 14:30:45] [admin] [Admin] ADD_HS: Thêm hồ sơ HS001
[2024-04-24 14:31:20] [admin] [Admin] EDIT_HS: Sửa hồ sơ HS001 - Thay đổi trạng thái
```

**Sử dụng:**

```php
logAction('LOGIN', 'Đăng nhập hệ thống', '../logs/system_audit.log');
logAction('ADD_HS', "Thêm hồ sơ $maHoSo", $logFile);
```

---

### 9. `admin/logs.php`

**Mục đích:** Xem nhật ký hệ thống (admin only)

**Chức năng chính:**

- Hiển thị toàn bộ log từ `logs/system_audit.log`
- Parse log để extract: thời gian, tài khoản, vai trò, hành động, mô tả
- **Filters:**
  - Hành động: LOGIN, ADD_HS, EDIT_HS, DELETE_HS, EXPORT, etc.
  - Tài khoản
  - Ngày (from-to)
- **Export:** Xuất log thành CSV file
  - Format: `system_audit_log_2024-04-24_14-30-45.csv`
  - Columns: Thời gian, Tài khoản, Vai trò, Hành động, Mô tả

**Authorization:** Chỉ Admin được xem & export

---

## 🖼️ **Phần 5: Xử Lý Ảnh Chứng Cứ**

### 10. `includes/helpers/image_handler.php`

**Mục đích:** Quản lý upload & lưu trữ ảnh chứng cứ

**Chức năng chính:**

| Function                                              | Mục đích                                 | Return                                                                  |
| ----------------------------------------------------- | ---------------------------------------- | ----------------------------------------------------------------------- |
| `getNextMaHinhAnh($conn)`                             | Tạo mã ảnh tiếp theo (HA001, HA002, ...) | String: "HA010"                                                         |
| `uploadEvidenceImage($file, $maHoSo, $conn, $ghiChu)` | Upload & lưu ảnh                         | Array: `['success'=>bool, 'msg'=>string, 'MaHinhAnh'=>..., 'URL'=>...]` |
| `getEvidenceImage($maHoSo, $conn)`                    | Lấy ảnh của hồ sơ                        | Array: `['URL_HinhAnh'=>..., 'GhiChu'=>...]`                            |
| `deleteEvidenceImage($maHoSo, $conn)`                 | Xóa ảnh & record                         | Boolean                                                                 |
| `hasEvidenceImage($maHoSo, $conn)`                    | Check ảnh có tồn tại                     | Boolean                                                                 |

**Upload Validation:**

- **File type:** `image/jpeg`, `image/png`, `image/gif`
- **Max size:** 5MB
  - Frontend: Validate trước khi preview, hiển thị error alert (đỏ):
    - "Kích thước ảnh lớn! File có kích thước X.XXM B, tối đa 5MB."
    - "Định dạng không hỗ trợ! Chỉ chấp nhận JPG, PNG, GIF."
  - Backend: Validate lại, error response (JSON):
    - "Kích thước ảnh lớn! File có kích thước X.XXM B, tối đa 5MB."
    - "Định dạng không hỗ trợ! Chỉ chấp nhận JPG, PNG, GIF."
  - Nếu lỗi → Clear file selection + show alert
- **Filename format:** `ha{number}.{ext}` (e.g., `ha001.jpg`, `ha002.png`)
  - Auto-generate `MaHinhAnh` (HA001, HA002, ...)
  - Filename lưu = lowercase MaHinhAnh + extension
- **Directory:** `/img/evidence/`
- **File preview:** Hiển thị ảnh thumbnail + kích thước file (KB)

**Database Schema:**

```
HinhAnhChungCu (
  MaHinhAnh VARCHAR(50) PK,         -- HA001, HA002, ...
  MaHoSo VARCHAR(50) FK UNIQUE,     -- 1 image per record
  URL_HinhAnh VARCHAR(500),         -- ha001.jpg, ha002.jpg, ...
  GhiChu NVARCHAR(255),             -- Description/caption (in nghiêng dưới ảnh)
  NgayTaiLen DATETIME DEFAULT NOW()  -- Upload timestamp
)
```

**Filename mapping:**

- `MaHinhAnh = "HA001"` → `URL_HinhAnh = "ha001.jpg"` (lowercase, "ha" prefix)

---

## 🎨 **Phần 6: Giao Diện & Component**

### 11. `includes/header.php`

**Mục đích:** HTML document head & opening body tag

**Chứa:**

- `<!DOCTYPE html>`, `<html>`, `<head>`
- Bootstrap 5.3.2 CDN
- FontAwesome 6.4.2 CDN
- Custom CSS cho navbar (gradient, responsive)
- `padding-top: 65px` để content không bị navbar che

### 12. `includes/navbar.php`

**Mục đích:** Navigation bar cố định ở top

**Hiển thị:**

- Logo & tên hệ thống
- Tên đăng nhập & role
- **Countdown timer:**
  - Live update mỗi 5 giây (call `ajax/session-info.php`)
  - Format: "MM:SS"
  - Màu xanh (>5 phút), đỏ (<5 phút)
- Menu links:
  - Quản Lý Hồ Sơ
  - Nhật Ký Hệ Thống (Admin only)
  - Đăng Xuất

**Styling:** Gradient xanh lam, responsive layout

---

## 📊 **Phần 7: Cơ Sở Dữ Liệu**

### 13. `config/database.php`

**Mục đích:** Kết nối SQL Server

**Connection string:** SQL Server (ODBC Driver 17)

```php
$conn = new PDO("sqlsrv:Server=$serverName;Database=$databaseName", $username, $password);
```

**Charset:** UTF-8 (hỗ trợ tiếng Việt)

---

## 🔄 **Luồng Hoạt Động Chính**

### Scenario 1: Thêm Hồ Sơ Mới Với Ảnh

```
1. User click "Thêm Hồ Sơ"
   ↓
2. Form modal hiển thị (resetForm called)
   - BienSoXe enabled
   - Upload section visible
   ↓
3. User điền dữ liệu & select ảnh
   - previewImage() hiển thị preview
   ↓
4. User click "Lưu Hồ Sơ"
   - submitViPhamForm() validate & POST FormData
   ↓
5. manage.php (POST) xử lý:
   - Validate inputs
   - getNextMaHinhAnh() tạo MaHoSo
   - INSERT vào HoSoViPham
   - uploadEvidenceImage() upload ảnh
   - logAction() ghi log
   - Response: success JSON
   ↓
6. JS reload bảng danh sách
   - Show toast success
```

### Scenario 2: Sửa Hồ Sơ Hiện Có (Có Thể Thay Thế Ảnh)

```
1. User click nút "Sửa"
   ↓
2. editHs(hsData) called
   - Form modal hiển thị
   - Load dữ liệu vào fields
   - BienSoXe: readonly + disabled + pointerEvents:none (không thể click/sửa)
   - Upload section HIỂN THỊ (cho phép thay thế ảnh)
   ↓
3. User chỉnh sửa (chỉ 4 fields) & có thể:
   - Upload ảnh mới (tuỳ chọn)
   - Validate: File size <5MB, format JPG/PNG/GIF
   ↓
4. User click "Lưu Hồ Sơ"
   - submitViPhamForm() POST (action='edit') + FormData
   ↓
5. manage.php (POST) xử lý:
   - UPDATE chỉ 4 fields (NO BienSoXe)
   - Nếu upload ảnh mới:
     * deleteEvidenceImage() - xóa file cũ & record
     * uploadEvidenceImage() - upload ảnh mới & insert record
   - logAction() ghi log
   ↓
6. JS reload bảng & show success
```

### Scenario 3: Xem Chi Tiết & Ảnh Chứng Cứ

```
1. User click nút "Xem Chi Tiết"
   ↓
2. viewHsDetail(maHoSo) fetch AJAX
   - URL: /ajax/get_hs_detail.php?maHoSo=HS001
   ↓
3. get_hs_detail.php xử lý:
   - JOIN 4 bảng lấy toàn bộ info
   - getEvidenceImage() lấy ảnh
   - Response: JSON
   ↓
4. JS render modal:
   - Hiển thị bảng info (Hình phạt bổ sung có)
   - Hiển thị ảnh & GhiChu dưới (in nghiêng)
   ↓
5. Modal popup
```

---

## 🔒 **Security Features**

| Feature             | Implementation                                                      |
| ------------------- | ------------------------------------------------------------------- |
| **Authentication**  | Session-based, Bcrypt password hash                                 |
| **Authorization**   | Role-based (Admin/Staff)                                            |
| **Session Timeout** | 30 min từ login_time (không reset)                                  |
| **SQL Injection**   | Prepared statements (PDO)                                           |
| **CSRF**            | (Có thể thêm token)                                                 |
| **File Upload**     | Frontend + Backend validation (type, size <5MB), separate directory |
| **Readonly Fields** | BienSoXe: HTML readonly + JS disabled + pointerEvents:none          |
| **Audit Log**       | Ghi nhật ký mọi hành động (LOGIN, ADD_HS, EDIT_HS, DELETE_HS, etc.) |
| **Data Validation** | Server-side validation + client-side preview checks                 |

---

## 📋 **Tóm Tắt Workflows**

| Tác vụ               | File chính                           | API endpoint                  |
| -------------------- | ------------------------------------ | ----------------------------- |
| Đăng nhập            | `admin/login.php`                    | N/A                           |
| Quản lý hồ sơ (CRUD) | `admin/manage.php`                   | `/ajax/get_hs_detail.php`     |
| Export báo cáo       | `admin/manage.php`                   | `/ajax/export_violations.php` |
| Xem log              | `admin/logs.php`                     | N/A                           |
| Session countdown    | `includes/navbar.php`                | `/ajax/session-info.php`      |
| Upload ảnh           | `includes/helpers/image_handler.php` | Gọi từ `manage.php`           |

---

## 🚀 **Kết Luận**

Hệ thống được thiết kế với các **best practices:**

- ✅ MVC-like structure (View/Logic tách rời)
- ✅ Reusable helpers (auth, image, audit_log)
- ✅ Security first (session timeout, auth, validation)
- ✅ API-first (AJAX endpoints)
- ✅ Audit trail (ghi log toàn bộ)
- ✅ User-friendly (pagination, sort, filter, export)

---

**Tài liệu cập nhật:** 2024-04-24  
**Phiên bản hệ thống:** 1.0
