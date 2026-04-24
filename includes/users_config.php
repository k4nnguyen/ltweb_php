<?php
/**
 * User Configuration File
 * Chứa danh sách tài khoản, mật khẩu (đã hash), và role
 * 
 * Role: 'admin' = Toàn quyền, 'staff' = Chỉ thêm/xem (không xóa)
 * 
 * Mật khẩu được hash bằng password_hash()
 * Password ban đầu: admin123, staff123
 */

return [
    'admin01' => [
        'password' => '$2y$10$1pm1lt57ti8bpVAI9NUoK.l5//UjvnGhVcOqlSi4RlsmJLKvIe.rS',  // admin123
        'role' => 'admin',
        'full_name' => 'Quản lý hệ thống',
        'email' => 'admin@example.com',
        'active' => true
    ],
    'admin02' => [
        'password' => '$2y$10$1pm1lt57ti8bpVAI9NUoK.l5//UjvnGhVcOqlSi4RlsmJLKvIe.rS',  // admin123
        'role' => 'admin',
        'full_name' => 'Quản lý hệ thống 2',
        'email' => 'admin2@example.com',
        'active' => true
    ],
    'staff01' => [
        'password' => '$2y$10$8RgELyXLuqzfd5rJrcZKNunVL2AeDIgs2oU5xy0HqaBxo2oHFdySi',  // staff123
        'role' => 'staff',
        'full_name' => 'Cán bộ CSGT 1',
        'email' => 'staff1@example.com',
        'active' => true
    ],
    'staff02' => [
        'password' => '$2y$10$8RgELyXLuqzfd5rJrcZKNunVL2AeDIgs2oU5xy0HqaBxo2oHFdySi',  // staff123
        'role' => 'staff',
        'full_name' => 'Cán bộ CSGT 2',
        'email' => 'staff2@example.com',
        'active' => true
    ]
];

/**
 * HƯỚNG DẪN SINH HASH PASSWORD:
 * 
 * 1. Để generate hash cho password mới, chạy đoạn code này trong PHP:
 *    echo password_hash('mật_khẩu_của_bạn', PASSWORD_BCRYPT);
 * 
 * 2. Copy hash kết quả vào file này
 * 
 * Password mặc định:
 * - admin123 (cho admin01, admin02)
 * - staff123 (cho staff01, staff02)
 * 
 * Để đổi password:
 * - Sửa lại hash trong file này
 * - Hoặc tạo trang change password sau
 */
?>
