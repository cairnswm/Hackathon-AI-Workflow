# Workflow Engine Documentation

## Overview

The Workflow Engine is a PHP-based system designed to execute workflows consisting of interconnected nodes. Each node performs specific tasks, and the engine processes these nodes sequentially or conditionally based on the workflow configuration.

## Key Components

### 1. Workflow Nodes

Workflow nodes represent individual tasks or operations within a workflow. Each node has a type (e.g., `start`, `calculation`, `if`, `llm`, `api`) and a configuration that defines its behavior.

### 2. Workflow Edges

Workflow edges define the connections between nodes. They specify the flow of execution from one node to another, optionally including conditions for transitions.

### 3. Run Queue

The run queue is a list of nodes that are scheduled for execution. The engine processes nodes from this queue until the workflow is complete.

## Data Structures

### 1. Global Data

Global data is shared across all nodes in the workflow. It typically contains information that is relevant to the entire workflow, such as API keys, user IDs, and workflow metadata.

#### Example:

```php
$this->globaldata = [
    'api_key' => 'your-api-key-here',
    'user_id' => 12345,
    'workflow_name' => 'Example Workflow'
];
```

### 2. Local Data

Local data is specific to individual nodes. It tracks node-specific information, such as intermediate results and loop counters.

#### Example:

```php
$this->localdata[$nodeId] = ['loop' => 0];
```

### 3. Node Configuration (`nodeconfig`)

Node configuration contains metadata about the workflow and the current run. It includes the workflow ID and the workflow run ID.

#### Example:

```php
$this->nodeconfig = [
    'workflow_id' => $id,
    'workflow_run_id' => $workflowRunId
];
```

## Workflow Execution

### 1. Initialization

The workflow engine is initialized with a workflow ID. It loads the nodes and edges from the database and prepares the run queue.

### 2. Node Execution

Nodes are processed one by one from the run queue. The engine determines the next nodes to execute based on the edges and conditions.

### 3. Infinite Loop Prevention

The engine tracks the number of times each node is executed to prevent infinite loops. If a node exceeds 100 executions, an exception is thrown.

### 4. Logging

Execution logs are maintained for each node, including execution time, input/output data, and timestamps.

### 5. Completion

The workflow ends when a node signals the `end` status or the run queue is empty. The final results are stored in the database.

## Node Types

### 1. Start Node

The entry point of the workflow. It initializes the workflow execution.

### 2. Calculation Node

Performs mathematical or logical calculations.

### 3. If Node

Evaluates conditions and determines the next nodes to execute.

### 4. LLM Node

Interacts with a language model for AI-based tasks.

### 5. API Node

Makes API calls to external services.

### 6. Generic Node

Handles custom or undefined node types.

## Database Interaction

The engine interacts with the database to:

- Insert workflow runs
- Retrieve nodes and edges
- Update execution logs
- Store final results

## Error Handling

The engine includes error handling for:

- Missing start nodes
- Invalid workflow configurations
- Infinite loops
- Database operation failures

## Interaction with `start.php`

The `start.php` file serves as the entry point for triggering workflows using the Workflow Engine. It handles HTTP requests, validates input, and initializes the engine.

### Workflow Initialization

When a POST request is made to `start.php`, the following steps occur:

1. **Input Validation**:
   - The script checks if the request method is POST.
   - It validates the JSON input and ensures a valid workflow ID is provided in the query string.

2. **Workflow Engine Interaction**:
   - The workflow ID is passed to the `WorkflowEngine` constructor.
   - The `start` method of the engine is called with the workflow ID and input data.

### Setting Global Data

Global data is set within the `WorkflowEngine` during the `start` method. This data can be customized by modifying the input data passed to `start.php`.

#### Example

```php
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$engine = new WorkflowEngine($workflowId);
$out = $engine->start($workflowId, $data);
```

The `data` array can include any global parameters required by the workflow, such as API keys, user information, or workflow-specific settings.

### Error Handling

`start.php` includes robust error handling to ensure:

- Invalid JSON input results in a 400 response.
- Missing or invalid workflow IDs result in a 400 response.
- Non-existent workflows result in a 404 response.

### Example Usage

To trigger a workflow:

1. Make a POST request to `start.php` with the workflow ID in the query string.
2. Include the required input data as JSON in the request body.

#### Example Request

```bash
curl -X POST -H "Content-Type: application/json" \
     -d '{"api_key": "your-api-key", "user_id": 12345}' \
     "http://localhost/workflow/start.php?id=1"
```
