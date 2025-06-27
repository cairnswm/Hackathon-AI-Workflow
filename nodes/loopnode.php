<?php
require_once __DIR__ . '/workflownode.php';

class LoopNode extends WorkflowNode
{
    protected function execute()
    {
        if (!isset($this->nodeconfig['max_iterations'])) {
            throw new Exception("Missing 'max_iterations' configuration for LoopNode.");
        }

        $maxIterations = $this->nodeconfig['max_iterations'];
        $iterations = 0;

        $validEdges = [];
        $loopType = isset($this->nodeconfig['while']) ? 'while' : (isset($this->nodeconfig['for']) ? 'for' : 'array');

        switch ($loopType) {
            case 'while':
                $condition = $this->replaceTemplateValues($this->nodeconfig['while']);
                while (evaluateCondition($condition, $this->workflowdata) && $iterations < $maxIterations) {
                    $validEdges = $this->getEdgesFromNode();
                    $iterations++;
                }
                break;

            case 'for':
                $from = $this->replaceTemplateValues($this->nodeconfig['for']['from']);
                $to = $this->replaceTemplateValues($this->nodeconfig['for']['to']);
                for ($i = $from; $i <= $to && $iterations < $maxIterations; $i++) {
                    $this->localdata['current_iteration'] = $i;
                    $validEdges = $this->getEdgesFromNode();
                    $iterations++;
                }
                break;

            case 'array':
                $array = $this->replaceTemplateValues($this->nodeconfig['array']);
                if (!is_array($array)) {
                    throw new Exception("Invalid 'array' configuration for LoopNode.");
                }
                foreach ($array as $item) {
                    if ($iterations >= $maxIterations) {
                        break;
                    }
                    $this->localdata['current_item'] = $item;
                    $validEdges = $this->getEdgesFromNode();
                    $iterations++;
                }
                break;

            default:
                throw new Exception("Invalid loop type configuration for LoopNode.");
        }

        $this->localdata['valid_edges'] = $validEdges;
        $this->status = "ok";
    }
}
