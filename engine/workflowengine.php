<?php

require_once __DIR__ . '/../nodes/workflownode.php';

class WorkflowEngine
{
  protected $nodeconfig;
  protected $globaldata;
  protected $localdata;
  protected $workflowdata;
  protected $workflownodes;
  protected $workflowedges;
  protected $callbacks;
  protected $runqueue = [];

  public function __construct($id)
  {
    $this->nodeconfig = [
      'workflow_id' => $id,
    ];
    $this->globaldata = [];
    $this->localdata = [];
    $this->workflowdata = [];
    $this->callbacks = [
      "getEdgesFromNode" => [$this, "getEdgesFromNode"],
      "getEdgesToNode" => [$this, "getEdgesToNode"],
    ];
  }

  public function start($workflowId, $inputData)
  {
    $this->workflowdata = $inputData;

    $this->globaldata = [
      'user_id' => 12345,
      'workflow_name' => 'Example Workflow'
    ];

    foreach (getallheaders() as $headerName => $headerValue) {
      if (strpos($headerName, 'aiw-') === 0) {
        $key = str_replace('aiw-', '', $headerName); // Remove 'aiw-' prefix
        $this->globaldata[$key] = $headerValue; // Add to global data
      }
    }

    var_dump("WFE START: Global Data", $this->globaldata);

    // Insert new workflow run using insertRecord
    $inputJson = json_encode($inputData);
    $status = 'running';

    $workflowRun = insertRecord(
      "INSERT INTO workflow_runs (workflow_id, status, input, created_at, modified_at)
             VALUES (?, ?, ?, NOW(), NOW())",
      [$workflowId, $status, $inputJson]
    );

    if (!$workflowRun) {
      throw new Exception("Failed to create workflow run");
    }

    // Access the first record in $workflowRun if it is an array of records
    if (is_array($workflowRun) && isset($workflowRun[0]) && is_array($workflowRun[0]) && array_key_exists('id', $workflowRun[0])) {
      $workflowRunId = $workflowRun[0]['id'];
      $this->nodeconfig['workflow_run_id'] = $workflowRunId;
    } else {
      throw new Exception("Invalid workflow run structure: " . json_encode($workflowRun));
    }

    // $this->globaldata = $inputData;

    $rows = executeSQL(
      "SELECT id, type, config FROM workflow_nodes WHERE workflow_id = ?",
      [$workflowId],
      true
    );

    $this->workflownodes = [];
    if (is_array($rows)) {
      foreach ($rows as $row) {
        if (is_array($row) && isset($row['id'], $row['type'], $row['config'])) {
          $decodedConfig = $row['config'] !== null ? json_decode($row['config'], true) : [];
          $this->workflownodes[$row['id']] = [
            'type' => $row['type'],
            'config' => $decodedConfig
          ];
        }
      }
    }

    $this->runqueue = [];
    foreach ($this->workflownodes as $nodeId => $node) {
      if (isset($node['type']) && $node['type'] === 'start') {
        $this->runqueue[] = ['prevnode' => "start", 'nextnode' => $nodeId];
      }
    }

    if (empty($this->runqueue)) {
      throw new Exception("No start node found in workflow ID: $workflowId");
    }

    $this->workflowedges = executeSQL(
      "SELECT id, from_node_id, to_node_id, if_condition FROM workflow_edges WHERE workflow_id = ?",
      [$workflowId],
      true
    );

    // Start processing the workflow
    return $this->run();
  }


  public function getEdgesFromNode($nodeId)
  {
    $edgesFromNode = [];
    if (is_array($this->workflowedges)) {
      foreach ($this->workflowedges as $edge) {
        if (isset($edge['from_node_id']) && $edge['from_node_id'] == $nodeId) {
          $edgesFromNode[] = $edge;
        }
      }
    }
    return $edgesFromNode;
  }

  public function getEdgesToNode($nodeId)
  {
    $edgesToNode = [];
    if (is_array($this->workflowedges)) {
      foreach ($this->workflowedges as $edge) {
        if (isset($edge['to_node_id']) && $edge['to_node_id'] == $nodeId) {
          $edgesToNode[] = $edge;
        }
      }
    }
    return $edgesToNode;
  }

  public function run()
  {
    var_dump("WFE RUN: Global Data", $this->globaldata);
    $workflowStatus = "running";
    while (!empty($this->runqueue)) {

      $microtimeStart = microtime(true);
      $currentNode = array_shift($this->runqueue);
      $nodeId = $currentNode['nextnode'];
      $prevNodeId = $currentNode['prevnode'];

      if (!isset($this->workflownodes[$nodeId])) {
        continue;
      }

      $node = $this->workflownodes[$nodeId];
      $nodeType = $node['type'];
      $nodeConfig = $node['config'];
      $nodeConfig['workflow_run_id'] = $this->nodeconfig['workflow_run_id'] ?? null;
      $nodeConfig['workflow_id'] = $this->nodeconfig['workflow_id'] ?? null;

      if ($nodeType === 'calculation') {
        require_once __DIR__ . '/../nodes/calculationnode.php';
        $workflowNode = new CalculationNode($nodeId, $nodeType);
      } elseif ($nodeType === 'if') {
        require_once __DIR__ . '/../nodes/ifnode.php';
        $workflowNode = new IfNode($nodeId, $nodeType);
      } elseif ($nodeType === 'llm') {
        require_once __DIR__ . '/../nodes/llmnode.php';
        $workflowNode = new LLMNode($nodeId, $nodeType);
      } elseif ($nodeType === 'api') {
        require_once __DIR__ . '/../nodes/apinode.php';
        $workflowNode = new APINode($nodeId, $nodeType);
      } else {
        $workflowNode = new WorkflowNode($nodeId, $nodeType);
      }
      $result = $workflowNode->run(
        $nodeConfig,
        $this->globaldata,
        $this->callbacks,
        $this->localdata,
        $this->workflowdata
      );

      // Modify localdata structure to be node-specific and add loop tracking
      if (!isset($this->localdata[$nodeId])) {
        $this->localdata[$nodeId] = ['loop' => 0];
      } else {
        $this->localdata[$nodeId]['loop']++;
        if ($this->localdata[$nodeId]['loop'] >= 100) {
          throw new Exception("Infinite loop detected at node $nodeId");
        }
      }

      // Update protected fields with latest data
      if (isset($result['localdata'])) {
        $this->localdata[$nodeId] = array_merge($this->localdata[$nodeId], $result['localdata']);
      }
      if (isset($result['workflowdata'])) {
        $this->workflowdata = $result['workflowdata'];
      }


      // If node signals end, stop processing
      if (isset($result['status']) && $result['status'] === 'end') {
        $workflowStatus = "done";
        break;
      }

      // Determine next nodes to run
      $nextNodeIds = [];
      if (!empty($this->localdata[$nodeId]['valid_edges']) && is_array($this->localdata[$nodeId]['valid_edges'])) {
        $nextNodeIds = $this->localdata[$nodeId]['valid_edges'];
      } else {
        $edges = $this->getEdgesFromNode($nodeId);
        foreach ($edges as $edge) {
          if (isset($edge['to_node_id'])) {
            $nextNodeIds[] = $edge['to_node_id'];
          }
        }
      }

      // Add next nodes to run queue
      foreach ($nextNodeIds as $nextNode) {
        if (is_array($nextNode)) {
          $nextNodeId = $nextNode['to_node_id'] ?? null;
        } else {
          $nextNodeId = $nextNode;
        }
        $this->runqueue[] = ['prevnode' => $nodeId, 'nextnode' => $nextNodeId];
      }

      $microtimeEnd = microtime(true);
      $executionTimeMs = round(($microtimeEnd - $microtimeStart) * 1000, 3);
      $this->updateExecutionLog([
        'node_id' => $nodeId,
        'node_type' => $nodeType,
        'prev_node_id' => $prevNodeId,
        'localdata' => $this->localdata,
        'workflowdata' => $this->workflowdata,
        'execution_time_ms' => $executionTimeMs,
        'started_at' => date('Y-m-d H:i:s', (int) $microtimeStart) . sprintf('.%03d', ($microtimeStart - floor($microtimeStart)) * 1000),
        'ended_at' => date('Y-m-d H:i:s', (int) $microtimeEnd) . sprintf('.%03d', ($microtimeEnd - floor($microtimeEnd)) * 1000),
      ]);
    }

    $this->setResult($workflowStatus = "running");

    return [
      'localdata' => $this->localdata,
      'workflowdata' => $this->workflowdata
    ];
  }

  private function setResult($status)
  {
    $workflowRunId = $this->nodeconfig['workflow_run_id'] ?? null;
    if ($workflowRunId) {
      executeSQL(
        "UPDATE workflow_runs SET status = ?, result = ? WHERE id = ?",
        [$status, json_encode($this->workflowdata), $workflowRunId]
      );
    }
  }

  private function getExecutionLog()
  {
    $workflowRunId = $this->nodeconfig['workflow_run_id'] ?? null;
    $executionLog = executeSQL(
      "SELECT execution_log FROM workflow_runs WHERE id = ?",
      [$workflowRunId],
      true
    );

    $executionLog = is_array($executionLog) && isset($executionLog[0]['execution_log'])
      ? json_decode($executionLog[0]['execution_log'], true)
      : [];

    return is_array($executionLog) ? $executionLog : [];
  }

  private function updateExecutionLog($logEntry)
  {
    $workflowRunId = $this->nodeconfig['workflow_run_id'] ?? null;
    $executionLog = $this->getExecutionLog(); // Removed the argument as getExecutionLog does not accept any.
    $executionLog[] = $logEntry;

    executeSQL(
      "UPDATE workflow_runs SET execution_log = ? WHERE id = ?",
      [json_encode($executionLog), $workflowRunId]
    );
  }

}