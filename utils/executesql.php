<?php

require_once __DIR__ . '/dbconfig.php';

$mysqli = new mysqli(
  $dbconfig['host'],
  $dbconfig['username'],
  $dbconfig['password'],
  $dbconfig['database']
);

if ($mysqli->connect_error) {
  die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

function executeSQL($query, $params, $fetch = false, $returnInsertId = false) {
    global $mysqli;
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // All as strings
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    if ($returnInsertId) {
        $id = $mysqli->insert_id;
        $stmt->close();
        return $id;
    }

    if ($fetch) {
        $stmt->store_result();
        $meta = $stmt->result_metadata();
        $fields = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = $field->name;
        }

        $data = [];
        $row = [];
        $bindParams = [];
        foreach ($fields as $field) {
            $bindParams[] = &$row[$field];
        }

        call_user_func_array([$stmt, 'bind_result'], $bindParams);

        while ($stmt->fetch()) {
            $data[] = array_map(function ($value) {
                return $value;
            }, $row);
        }

        $stmt->close();
        return $data;
    }

    $stmt->close();
    return true;
}

function insertRecord($query, $params) {
    $insertId = executeSQL($query, $params, false, true);
    if ($insertId) {
      // Try to extract table name from the insert query
      if (preg_match('/insert\s+into\s+([`"\[\]\w]+)/i', $query, $matches)) {
        $table = trim($matches[1], '`"[]');
        // Try to extract primary key column (assume 'id' by default)
        $fetchQuery = "SELECT * FROM `$table` WHERE id = ?";
        return executeSQL($fetchQuery, [$insertId], true);
      }
    }
    return null;
}

function updateRecord($query, $params) {
  $success = executeSQL($query, $params);
  if ($success) {
    // Try to extract table name from the update query
    if (preg_match('/update\s+([`"\[\]\w]+)/i', $query, $matches)) {
      $table = trim($matches[1], '`"[]');
      // Assume the last param is the id
      $id = end($params);
      $fetchQuery = "SELECT * FROM `$table` WHERE id = ?";
      return executeSQL($fetchQuery, [$id], true);
    }
  }
  return null;
}

function deleteRecord($query, $params) {
    $success = executeSQL($query, $params);
    return $success;
}

function fetchRecord($query, $params) {
    return executeSQL($query, $params, true);
}

function beginTransaction() {
    global $mysqli;
    if (!$mysqli->begin_transaction()) {
        throw new Exception("Failed to start transaction");
    }
}

function commitTransaction() {
    global $mysqli;
    if (!$mysqli->commit()) {
        throw new Exception("Failed to commit transaction");
    }
}

function rollbackTransaction() {
    global $mysqli;
    if (!$mysqli->rollback()) {
        throw new Exception("Failed to rollback transaction");
    }
}
