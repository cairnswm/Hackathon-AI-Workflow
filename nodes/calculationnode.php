<?php
require_once __DIR__ . '/workflownode.php';

class CalculationNode extends WorkflowNode
{
    protected function execute()
    {
        if (!isset($this->nodeconfig['fields']) || !is_array($this->nodeconfig['fields'])) {
            throw new Exception("Invalid or missing 'fields' configuration for CalculationNode.");
        }

        foreach ($this->nodeconfig['fields'] as $field) {
            foreach ($field as $key => $expression) {
                $evaluatedValue = $this->evaluateExpression($expression);
                $this->workflowdata[$key] = $evaluatedValue;
            }
        }

        $this->status = "ok";
    }

    private function evaluateExpression($expression)
    {
        $evaluatedExpression = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
            $variableName = $matches[1];
            return $this->workflowdata[$variableName] ?? 0;
        }, $expression);

        return eval('return ' . $evaluatedExpression . ';');
    }
}
