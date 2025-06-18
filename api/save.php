<?php

require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../utils/executesql.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid JSON in request body"
    ]);
    exit;
}

$requiredFields = ['id', 'nodes', 'edges'];
$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        $missingFields[] = $field;
    }
}
if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields",
        "missing_fields" => $missingFields
    ]);
    exit;
}

$workflowId = intval($data['id']);
$nodes = $data['nodes'];
$edges = $data['edges'];

try {
    beginTransaction();

    $nodeIds = array_map(fn($node) => $node['id'], $nodes);
    $edgeIds = array_map(fn($edge) => $edge['id'], $edges);

    deleteRecord("DELETE FROM workflow_nodes WHERE workflow_id = ? AND id NOT IN (" . implode(',', array_fill(0, count($nodeIds), '?')) . ")", array_merge([$workflowId], $nodeIds));
    deleteRecord("DELETE FROM workflow_edges WHERE workflow_id = ? AND id NOT IN (" . implode(',', array_fill(0, count($edgeIds), '?')) . ")", array_merge([$workflowId], $edgeIds));

    $nodeIdMap = [];

    foreach ($nodes as $node) {
        if (strpos($node['id'], 'X') === 0) {
            $insertedNode = insertRecord(
                "INSERT INTO workflow_nodes (workflow_id, type, name, config, display) VALUES (?, ?, ?, ?, ?)",
                [$workflowId, $node['type'], $node['name'], $node['config'], $node['display']]
            );
            $nodeIdMap[$node['id']] = $insertedNode[0]['id']; // Extract and map the actual ID
        } else {
            $updatedNode = updateRecord(
                "UPDATE workflow_nodes SET type = ?, name = ?, config = ?, display = ? WHERE id = ? AND workflow_id = ?",
                [$node['type'], $node['name'], $node['config'], $node['display'], $node['id'], $workflowId]
            );
            $nodeIdMap[$node['id']] = $node['id']; // Preserve original ID
        }
    }

    foreach ($edges as $edge) {
        $fromNodeId = $nodeIdMap[$edge['from_node_id']] ?? $edge['from_node_id'];
        $toNodeId = $nodeIdMap[$edge['to_node_id']] ?? $edge['to_node_id'];

        if (strpos($edge['id'], 'E') === 0) {
            insertRecord(
                "INSERT INTO workflow_edges (id, workflow_id, from_node_id, to_node_id, if_condition) VALUES (?, ?, ?, ?, ?)",
                [$edge['id'], $workflowId, $fromNodeId, $toNodeId, $edge['if_condition']]
            );
        } else {
            updateRecord(
                "UPDATE workflow_edges SET from_node_id = ?, to_node_id = ?, if_condition = ? WHERE id = ? AND workflow_id = ?",
                [$fromNodeId, $toNodeId, $edge['if_condition'], $edge['id'], $workflowId]
            );
        }
    }

    commitTransaction();

    http_response_code(200);
    echo json_encode(["message" => "Workflow nodes and edges updated successfully"]);
} catch (Exception $e) {
    rollbackTransaction();
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
