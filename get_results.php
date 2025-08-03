<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'test_website';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::EXCEPTION);
    
    $type = $_GET['type'] ?? 'device';
    $deviceId = $_GET['deviceId'] ?? '';
    
    if ($type === 'top10') {
        // Get top 10 scores globally
        $sql = "SELECT student_name, education_level, subject, score_percentage, time_taken, completed_at 
                FROM test_results 
                ORDER BY score_percentage DESC, time_taken ASC 
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
    } else if ($type === 'device' && $deviceId) {
        // Get results for specific device
        $sql = "SELECT student_name, education_level, subject, test_type, score_percentage, time_taken, completed_at 
                FROM test_results 
                WHERE device_id = :device_id 
                ORDER BY completed_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':device_id' => $deviceId]);
        
    } else {
        throw new Exception('Invalid request parameters');
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>