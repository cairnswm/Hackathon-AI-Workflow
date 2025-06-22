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

        var_dump("WFN: RUN Global Data", $this->globaldata);

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

        var_dump("Start, global data", $this->globaldata);

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
    protected function replaceTemplateValues($text)
    {
        return preg_replace_callback('/\{(.*?)\}/', function ($matches) {
            $fieldKey = $matches[1];

            // Check for deep linking in localdata
            if (str_starts_with($fieldKey, 'data.')) {
                $fieldKey = substr($fieldKey, 5); // Remove 'data.' prefix
                return $this->getNestedValue($this->localdata, $fieldKey);
            }

            // Default to workflowdata
            return $this->getNestedValue($this->workflowdata, $fieldKey);
        }, $text);
    }

    private function getNestedValue($data, $key)
    {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (is_array($data) && array_key_exists($k, $data)) {
                $data = $data[$k];
            } else {
                return null; // Key not found
            }
        }
        return $data;
    }
}