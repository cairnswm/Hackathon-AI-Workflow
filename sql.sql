-- Workflows
CREATE TABLE workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    created_by varchar(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(created_by)
);

-- Workflow Nodes
CREATE TABLE workflow_nodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT,
    type VARCHAR(50), -- e.g., start, end, api_call, decision, etc.
    name VARCHAR(255),
    config TEXT, -- JSON string for node-specific configuration
    x INT, -- Optional: position on canvas (for UI)
    y INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(workflow_id)
);

-- Edges between nodes
CREATE TABLE workflow_edges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT,
    from_node_id INT,
    to_node_id INT,
    if_condition TEXT, -- Only used if from_node is a decision node
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(workflow_id),
    INDEX(from_node_id),
    INDEX(to_node_id)
);

-- Workflow Runs (each execution instance of a workflow)
CREATE TABLE workflow_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT,
    status VARCHAR(50), -- running, completed, failed, paused, etc.
    input_data JSON,
    result_data JSON,
    execution_log JSON, -- Full trace log as a large object
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(workflow_id),
    INDEX(status)
);

-- Workflow Steps (each node executed in a run)
CREATE TABLE workflow_run_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_run_id INT,
    node_id INT,
    node_type VARCHAR(50),
    step_index INT, -- Optional order
    input_data JSON,
    output_data JSON,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(workflow_run_id),
    INDEX(node_id)
);

-- Access Tokens
CREATE TABLE workflow_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) UNIQUE,
    token_type ENUM('edit', 'audit', 'run'),
    workflow_id INT DEFAULT NULL,
    workflow_run_id INT DEFAULT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX(token),
    INDEX(workflow_id),
    INDEX(workflow_run_id),
    INDEX(token_type)
);
