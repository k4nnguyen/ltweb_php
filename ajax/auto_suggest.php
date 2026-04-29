<?php
// ajax/auto_suggest.php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

if(isset($_POST['keyword'])) {
    $keyword = trim($_POST['keyword']);
    if (empty($keyword)) {
        echo "<div class='p-3 text-muted text-center'>Vui lòng nhập biển số xe</div>";
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT TOP 10 BienSoXe 
            FROM PhuongTien 
            WHERE BienSoXe LIKE :kw COLLATE SQL_Latin1_General_CP1_CI_AI
            ORDER BY BienSoXe ASC
        ");
        $stmt->execute(['kw' => "%$keyword%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $html = "<ul class='list-group list-group-flush mb-0'>";
            foreach($results as $row) {
                $bienSoXe = trim(htmlspecialchars($row['BienSoXe'], ENT_QUOTES, 'UTF-8'));
                $html .= "<li class='list-group-item list-group-item-action suggest-item px-3 py-2' data-plate='" . $bienSoXe . "'><i class='fa-solid fa-car me-2 text-secondary'></i>" . $bienSoXe . "</li>";
            }
            $html .= "</ul>";
            echo $html;
        } else {
            echo "<div class='p-3 text-muted text-center'>Không tìm thấy gợi ý</div>";
        }
    } catch(PDOException $e) {
        echo "<div class='p-3 text-danger text-center small'>Lỗi: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
    }
} else {
    echo "";
}
?>