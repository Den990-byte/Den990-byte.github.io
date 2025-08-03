<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$host = 'https://dbwebsitepi-dennisluisky228-a11d.d.aivencloud.com'; //:21061
$dbname = 'testwebsite'; // Your database name
$username = 'avnadmin'; // Your database username
$password = 'AVNS_EAXcIoeolpK4AaywvrL'; // Your database password

$message = "testwebsite";
echo "<script type='text/javascript'>alert('$message');</script>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    alert($pdo);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get parameters
$subject = $_GET['subject'] ?? '';
$education_level = $_GET['level'] ?? '';

if (empty($subject) || empty($education_level)) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject and education_level are required']);
    exit;
}

try {
    // Fetch questions from database
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE subject = ? AND education_level = ? ORDER BY RAND() LIMIT 20");
    $stmt->execute([$subject, $education_level]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform data to match frontend format
    $formattedQuestions = [];
    foreach ($questions as $q) {
        $formattedQuestions[] = [
            'id' => $q['id'],
            'question' => $q['question_text'],
            'options' => [
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d']
            ],
            'correct' => ord(strtoupper($q['correct_answer'])) - ord('A') // Convert A,B,C,D to 0,1,2,3
        ];
    }
    
    echo json_encode([
        'success' => true,
        'questions' => $formattedQuestions,
        'total' => count($formattedQuestions)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>
