<?php
require_once __DIR__ . '/workflownode.php';

class WaitForNode extends WorkflowNode
{
    protected function execute()
    {
        // Get all incoming edges to this node
        $incomingEdges = $this->getEdgesToNode();

        if (empty($incomingEdges)) {
            throw new Exception("No incoming edges found for WaitForNode.");
        }

        // Check if all incoming edges have a value in localdata
        foreach ($incomingEdges as $edge) {
            $fromNodeId = $edge['from_node_id'];
            if (!isset($this->localdata[$fromNodeId]['value'])) {
                // If any incoming edge does not have a value, wait
                $this->status = "waiting";
                return;
            }
        }

        // If all incoming edges have values, proceed
        $this->status = "ok";
    }
}