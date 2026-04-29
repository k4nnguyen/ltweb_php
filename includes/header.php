<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Tra Cứu Vi Phạm Giao Thông</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS (with cache bust) -->
    <link rel="stylesheet" href="/admin_vi_pham/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light d-flex flex-column min-vh-100" style="padding-top: 65px;">
    <style>
        /* Navbar styling */
        .navbar.fixed-top {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;
            border-bottom: 3px solid #3b82f6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
        }
        
        .navbar .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .navbar .nav-link:hover {
            background-color: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.active {
            background-color: rgba(59, 130, 246, 0.3);
            border-bottom: 2px solid #3b82f6;
        }
        
        /* Session info styling */
        .navbar .nav-link.session-info {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 2px solid rgba(255, 255, 255, 0.3);
            padding-left: 0.75rem !important;
            margin-left: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Responsive navbar */
        @media (max-width: 991px) {
            .navbar-nav {
                background-color: rgba(0, 0, 0, 0.2);
                border-radius: 8px;
                padding: 10px;
                margin-top: 10px;
            }
            
            .navbar .nav-link {
                padding: 0.75rem 0.5rem !important;
                border-radius: 4px;
            }
        }
    </style>