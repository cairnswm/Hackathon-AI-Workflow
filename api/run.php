<?php

require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../utils/executesql.php';

// Check if 'id' parameter is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'id' parameter"]);
    exit;
}

$id = intval($_GET['id']);

try {
    // Fetch workflow run from the database
    $query = "SELECT * FROM workflow_runs WHERE id = ?";
    $workflowRun = fetchRecord($query, [$id]);

    if (empty($workflowRun)) {
        http_response_code(404);
        echo json_encode(["error" => "Workflow run not found"]);
    } else {
        echo json_encode($workflowRun);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
