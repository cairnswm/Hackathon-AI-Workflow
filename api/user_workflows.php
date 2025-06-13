<?php

require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../utils/executesql.php';

// Check if 'id' parameter is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'id' parameter"]);
    exit;
}

$id = $_GET['id'];

try {
    // Fetch all workflows a specific user owns
    $query = "SELECT * FROM workflows WHERE created_by = ?";
    $userWorkflows = fetchRecord($query, [$id]);

    if (empty($userWorkflows)) {
        http_response_code(404);
        echo json_encode(["error" => "No workflows found for the specified user ID"]);
    } else {
        echo json_encode($userWorkflows);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
