<?php
class WorkflowNode
{
    protected $id;
    protected $type;
    protected $status = "ok";
    protected $nodeconfig;
    protected $globaldata;
    protected $localdata;
    protected $workflowdata;
    protected $callbacks;

    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
        $this->nodeconfig = [];
        $this->globaldata = [];
        $this->localdata = [];
        $this->workflowdata = [];
        $this->callbacks = [];
    }

    public function run($config, $globaldata, $callbacks, $localdata, $workflowdata)
    {
        $this->nodeconfig = $config;
        $this->globaldata = $globaldata;
        $this->callbacks = $callbacks;
        $this->localdata = $localdata;
        $this->workflowdata = $workflowdata;

        $this->start();

        $this->execute();

        return $this->end();
    }

    protected function getEdgesFromNode() {
        if (isset($this->callbacks['getEdgesFromNode'])) {
            return call_user_func($this->callbacks['getEdgesFromNode'], $this->id);
        }
    }
    protected function getEdgesToNode() {
        if (isset($this->callbacks['getEdgesToNode'])) {
            return call_user_func($this->callbacks['getEdgesToNode'], $this->id);
        }
    }

    protected function start()
    {
        // Parent start logic: validate inputs
        if (!isset($this->nodeconfig['workflow_run_id']) || !isset($this->nodeconfig['workflow_id']) || empty($this->nodeconfig)) {
            throw new Exception("Missing required configuration data.");
        }

        // Allow child classes to extend this method with additional validation or setup
    }

    protected function execute()
    {
        // Default execution - pass through the workflow data
        $this->status = "ok";
    }

    protected function end( )
    {

        return [
            'status' => $this->status,
            "localdata" => $this->localdata,
            "workflowdata" => $this->workflowdata,
        ];
    }
}