<?php
require_once __DIR__ . '/utils/dbconfig.php';
require_once __DIR__ . '/utils/executesql.php';
require_once __DIR__ . '/engine/WorkflowEngine.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'GET method is not allowed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Read and decode JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Extract workflow ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid workflow ID in query string']);
    exit;
}

$workflowId = intval($_GET['id']);

// Check if the workflow exists
$workflow = executeSQL("SELECT id FROM workflows WHERE id = ?", [$workflowId], true);
if (!$workflow) {
    http_response_code(404);
    echo json_encode(['error' => 'Workflow not found']);
    exit;
}

// Start the workflow engine
$engine = new WorkflowEngine($workflowId);
$out = $engine->start($workflowId, $data);
echo json_encode($out["workflowdata"]);