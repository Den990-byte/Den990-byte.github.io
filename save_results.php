<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    // Generate device ID from browser fingerprint
    $deviceId = $input['deviceId'] ?? 'unknown';
    
    $pdo->beginTransaction();
    
    // Insert test session
    $sessionSql = "INSERT INTO test_sessions (student_name, education_level, subject, test_type, end_time, status) 
                   VALUES (:name, :level, :subject, :test_type, NOW(), 'completed')";
    $sessionStmt = $pdo->prepare($sessionSql);
    $sessionStmt->execute([
        ':name' => $input['studentName'],
        ':level' => $input['educationLevel'],
        ':subject' => $input['subject'],
        ':test_type' => $input['testType']
    ]);
    
    $sessionId = $pdo->lastInsertId();
    
    // Insert test results with device ID
    $resultSql = "INSERT INTO test_results (session_id, student_name, education_level, subject, test_type, 
                  total_questions, correct_answers, wrong_answers, unanswered, score_percentage, time_taken, device_id) 
                  VALUES (:session_id, :name, :level, :subject, :test_type, :total, :correct, :wrong, :unanswered, :score, :time, :device_id)";
    
    $resultStmt = $pdo->prepare($resultSql);
    $resultStmt->execute([
        ':session_id' => $sessionId,
        ':name' => $input['studentName'],
        ':level' => $input['educationLevel'],
        ':subject' => $input['subject'],
        ':test_type' => $input['testType'],
        ':total' => 20,
        ':correct' => $input['correctAnswers'],
        ':wrong' => $input['wrongAnswers'],
        ':unanswered' => $input['unanswered'],
        ':score' => $input['scorePercentage'],
        ':time' => $input['timeTaken'],
        ':device_id' => $deviceId
    ]);
    
    // Save individual question responses
    if (isset($input['questionResponses'])) {
        $responseSql = "INSERT INTO question_responses (session_id, question_id, student_answer, is_correct) 
                        VALUES (:session_id, :question_id, :answer, :is_correct)";
        $responseStmt = $pdo->prepare($responseSql);
        
        foreach ($input['questionResponses'] as $response) {
            $responseStmt->execute([
                ':session_id' => $sessionId,
                ':question_id' => $response['questionId'],
                ':answer' => $response['userAnswer'] >= 0 ? chr(65 + $response['userAnswer']) : null,
                ':is_correct' => $response['isCorrect'] ? 1 : 0
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'message' => 'Results saved successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>