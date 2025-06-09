<?php

require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../utils/executesql.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetWorkflow();
        break;
    case 'POST':
        handleCreateWorkflow();
        break;
    case 'PUT':
        handleUpdateWorkflow();
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
}

function handleGetWorkflow() {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'id' parameter"]);
        exit;
    }

    $id = intval($_GET['id']);

    try {
        $query = "
          SELECT 
            w.id,
            w.name,
            w.description,
            w.created_at,
            w.modified_at,
            COALESCE(
              (
                SELECT JSON_ARRAYAGG(
                  JSON_OBJECT(
                    'id', n.id,
                    'name', n.name,
                    'type', n.type,
                    'config', n.config,
                    'x', n.x,
                    'y', n.y
                  )
                )
                FROM workflow_nodes n
                WHERE n.workflow_id = w.id
              ), JSON_ARRAY()
            ) AS nodes,
            COALESCE(
              (
                SELECT JSON_ARRAYAGG(
                  JSON_OBJECT(
                    'id', e.id,
                    'from_node_id', e.from_node_id,
                    'to_node_id', e.to_node_id,
                    'if_condition', e.if_condition
                  )
                )
                FROM workflow_edges e
                WHERE e.workflow_id = w.id
              ), JSON_ARRAY()
            ) AS edges
          FROM workflows w
          WHERE w.id = ?
          LIMIT 1
        ";

        $workflow = fetchRecord($query, [$id]);

        if (empty($workflow)) {
            http_response_code(404);
            echo json_encode(["error" => "Workflow not found"]);
        } else {
            function is_assoc(array $arr) {
                if ([] === $arr) return false;
                return array_keys($arr) !== range(0, count($arr) - 1);
            }

            $workflows = is_assoc($workflow) ? [$workflow] : $workflow;

            foreach ($workflows as &$wf) {
                $wf['nodes'] = json_decode($wf['nodes'], true);
                $wf['edges'] = json_decode($wf['edges'], true);
            }

            echo json_encode(is_assoc($workflow) ? $workflows[0] : $workflows);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
}

function handleCreateWorkflow() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name']) || !isset($data['description'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    try {
        $query = "INSERT INTO workflows (name, description, created_at, modified_at) VALUES (?, ?, NOW(), NOW())";
        $workflowId = insertRecord($query, [$data['name'], $data['description']]);

        http_response_code(201);
        echo json_encode(["message" => "Workflow created successfully", "id" => $workflowId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
}

function handleUpdateWorkflow() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'id' field"]);
        exit;
    }

    $id = intval($data['id']);
    unset($data['id']);

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(["error" => "No fields to update"]);
        exit;
    }

    try {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $query = "UPDATE workflows SET " . implode(", ", $fields) . ", modified_at = NOW() WHERE id = ?";
        updateRecord($query, $values);

        http_response_code(200);
        echo json_encode(["message" => "Workflow updated successfully"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
    }
}