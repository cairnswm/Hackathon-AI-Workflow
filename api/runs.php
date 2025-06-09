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
    // Fetch all workflow runs for a specific workflow ID
    $query = "SELECT id, workflow_id, status, input, result, started_at FROM workflow_runs WHERE workflow_id = ?";
    $workflowRuns = fetchRecord($query, [$id]);

    if (empty($workflowRuns)) {
        http_response_code(404);
        echo json_encode(["error" => "No workflow runs found for the specified workflow ID"]);
    } else {
        // Decode 'input' and 'result' fields for each workflow run
        foreach ($workflowRuns as &$run) {
            if (isset($run['input'])) {
          $run['input'] = json_decode($run['input'], true);
            }
            if (isset($run['result'])) {
          $run['result'] = json_decode($run['result'], true);
            }
        }
        echo json_encode($workflowRuns);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
