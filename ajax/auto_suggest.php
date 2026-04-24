<?php
// ajax/auto_suggest.php
require_once '../config/database.php';

if(isset($_POST['keyword'])) {
    $keyword = $_POST['keyword'];
    try {
        $stmt = $conn->prepare("SELECT BienSoXe FROM PhuongTien WHERE BienSoXe LIKE :kw COLLATE SQL_Latin1_General_CP1_CI_AI");
        $stmt->execute(['kw' => "%$keyword%"]);
        $results = $stmt->fetchAll();

        if (count($results) > 0) {
            $html = "<ul class='list-group list-group-flush mb-0'>";
            foreach($results as $row) {
                // Return items correctly
                $html .= "<li class='list-group-item list-group-item-action suggest-item px-3 py-2'><i class='fa-solid fa-car me-2 text-secondary'></i>" . $row['BienSoXe'] . "</li>";
            }
            $html .= "</ul>";
            echo $html;
        } else {
            echo "<div class='p-3 text-muted text-center'>Không tìm thấy gợi ý</div>";
        }
    } catch(PDOException $e) {
        // Tắt echo lỗi cụ thể ở Production (tránh lộ CSDL)
        echo "";
    }
}
?>