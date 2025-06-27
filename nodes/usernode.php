<?php
require_once __DIR__ . '/workflownode.php';

class UserNode extends WorkflowNode
{
    protected function execute()
    {
        // Check if user input field is defined in nodeconfig
        if (!isset($this->nodeconfig['field']) || !isset($this->nodeconfig['prompt'])) {
            throw new Exception("Missing 'field' or 'prompt' configuration for UserNode.");
        }

        $fieldName = $this->nodeconfig['field'];
        $prompt = $this->nodeconfig['prompt'];

        // Check if user input is available in workflowdata
        if (!isset($this->workflowdata[$fieldName])) {
            // Pause the workflow if user input is not available
            $this->localdata['prompt'] = $prompt; // Store the prompt for external use
            $this->status = "paused";

            // Insert into workflow_run_paused table
            $workflowRunId = $this->nodeconfig['workflow_run_id'] ?? null;
            $workflowId = $this->nodeconfig['workflow_id'] ?? null;
            $nodeId = $this->id;

            executeSQL(
                "INSERT INTO workflow_run_paused (workflow_id, workflow_run_id, prompt, field, rules, created_at, modified_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [$workflowId, $workflowRunId, $prompt, $fieldName, json_encode(['node_id' => $nodeId])]
            );

            return;
        }

        // Process the user input
        $userInput = $this->workflowdata[$fieldName];
        $this->localdata['processed_input'] = $userInput; // Example processing

        // Mark the node as completed
        $this->status = "ok";
    }
}