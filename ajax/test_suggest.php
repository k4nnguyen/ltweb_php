<?php
// Test endpoint for debugging auto_suggest
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Test 1: Check connection
    $test1 = $conn->query("SELECT COUNT(*) as total FROM PhuongTien");
    $totalVehicles = $test1->fetch()['total'];
    
    // Test 2: Get sample data
    $test2 = $conn->query("SELECT TOP 5 BienSoXe FROM PhuongTien ORDER BY BienSoXe");
    $sampleData = $test2->fetchAll(PDO::FETCH_COLUMN);
    
    // Test 3: Search for "29"
    $stmt = $conn->prepare("
        SELECT TOP 10 BienSoXe 
        FROM PhuongTien 
        WHERE BienSoXe LIKE :kw COLLATE SQL_Latin1_General_CP1_CI_AI
        ORDER BY BienSoXe ASC
    ");
    $stmt->execute(['kw' => "%29%"]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'total_vehicles' => $totalVehicles,
        'sample_data' => $sampleData,
        'search_results_for_29' => $searchResults
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
