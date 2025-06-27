<?php
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../engine/workflowengine.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workflowId = $_POST['workflow_id'] ?? null;
    $workflowRunId = $_POST['workflow_run_id'] ?? null;
    $userInput = $_POST['user_input'] ?? null;

    if (!$workflowId || !$workflowRunId || !$userInput) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing workflow_id, workflow_run_id, or user_input']);
        exit;
    }

    try {
        // Check for paused workflow input
        $pausedInput = executeSQL(
            "SELECT * FROM workflow_run_paused WHERE workflow_run_id = ? AND value = '' LIMIT 1",
            [$workflowRunId]
        );

        if (empty($pausedInput)) {
            throw new Exception("No paused input found for workflow_run_id: $workflowRunId");
        }

        $pausedInput = $pausedInput[0];
        $fieldName = $pausedInput['field'];
        $nodeId = json_decode($pausedInput['rules'], true)['node_id'] ?? null;

        if (!$fieldName || !$nodeId) {
            throw new Exception("Invalid paused input configuration");
        }

        // Update the paused input value
        executeSQL(
            "UPDATE workflow_run_paused SET value = ? WHERE id = ?",
            [$userInput, $pausedInput['id']]
        );

        // Load the paused workflow
        $workflowEngine = new WorkflowEngine($workflowId);

        // Update workflow data with user input
        $workflowEngine->updateWorkflowData($fieldName, $userInput);

        // Resume the workflow
        $result = $workflowEngine->run();

        echo json_encode(['status' => 'resumed', 'result' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
