<?php
// Include database connection
require_once '../utils/executesql.php';

header('Content-Type: application/json');

// Helper function to generate a unique token
function generateToken() {
    return bin2hex(random_bytes(16));
}

// Handle the request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle POST request to create a new token
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    $workflow_id = $data['workflow_id'] ?? null;
    $run_id = $data['run_id'] ?? null;

    if (!$user_id || !$workflow_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters: user_id and workflow_id']);
        exit;
    }

    $token = generateToken();
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Example expiration time

    $query = "INSERT INTO workflow_tokens (token, user_id, workflow_id, workflow_run_id, expires_at) 
              VALUES (?, ?, ?, ?, ?)";
    $params = [$token, $user_id, $workflow_id, $run_id, $expires_at];

    try {
        executeSQL($query, $params);
        echo json_encode(['token' => $token]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create token', 'details' => $e->getMessage()]);
    }
} elseif ($method === 'GET') {
    // Handle GET request to retrieve token details
    $token = $_GET['token'] ?? null;

    if (!$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameter: token']);
        exit;
    }

    $query = "SELECT user_id, workflow_id, workflow_run_id FROM workflow_tokens WHERE token = ?";
    $params = [$token];

    try {
        $result = fetchRecord($query, $params);
        if (!empty($result)) {
            echo json_encode($result[0]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Token not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve token details', 'details' => $e->getMessage()]);
    }
} else {
    // Handle unsupported methods
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>