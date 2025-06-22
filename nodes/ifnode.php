<?php
require_once __DIR__ . '/workflownode.php';
require_once __DIR__ . '/../utils/conditionutils.php';

class IfNode extends WorkflowNode
{
  protected function execute()
  {
    if (!isset($this->nodeconfig['field'])) {
      throw new Exception("Invalid or missing 'field' configuration for IfNode.");
    }

    $fieldname = $this->nodeconfig['field'];

    $edges = $this->getEdgesFromNode();

    $validEdges = [];

    foreach ($edges as $edge) {

      if (isset($edge['if_condition'])) {
        // Add the complete field at the start of the conditional
        $condition = $this->replaceTemplateValues($fieldname . $edge['if_condition']);

        $res = (evaluateCondition($condition, $this->workflowdata));
        if ($res) {
          $validEdges[] = $edge['to_node_id'];
          break; // Stop after finding the first valid edge
        }
      }
    }

    $this->localdata['valid_edges'] = $validEdges;
    $this->status = "ok";
  }


}
