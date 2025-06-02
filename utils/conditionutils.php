<?php
function evaluateCondition($condition, $data) {
    // Trim the condition
    $condition = trim($condition);

    // If the condition is empty or has no comparison, return true
    if (!preg_match('/[=<>!]/', $condition) && strpos($condition, ' in ') === false) {
        return true;
    }

    // Replace variable placeholders like {value} with actual values from $data
    $condition = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($data) {
        $key = $matches[1];
        if (isset($data[$key])) {
            $val = $data[$key];
            if (is_bool($val)) return $val ? 'true' : 'false';
            if (is_string($val)) return "'" . addslashes($val) . "'";
            return $val;
        } else {
            return 'null';
        }
    }, $condition);

    // Handle "x in [a, b, c]" condition
    if (preg_match('/^(.+)\s+in\s+\[(.+)\]$/', $condition, $matches)) {
        $left = trim($matches[1]);
        $list = array_map('trim', explode(',', $matches[2]));
        $leftValue = eval("return $left;");
        $val = in_array($leftValue, $list);
            return $val;
    }

    // Supported comparison operators
    $operators = ['==', '!=', '>=', '<=', '>', '<', '='];
    foreach ($operators as $op) {
        $escapedOp = preg_quote($op, '/');
        if (preg_match("/^(.+)\s*$escapedOp\s*(.+)$/", $condition, $matches)) {
            $left = trim($matches[1]);
            $right = trim($matches[2]);

            // Support = as ==
            if ($op === '=') $op = '==';

            // Construct safe PHP expression
            $expression = "\$__result = ($left $op $right); return \$__result;";

            // Evaluate and return result
            $val = eval($expression);
            return $val;
        }
    }

    // If it's a literal value like true or a string
    $val = eval("return ($condition);");
    return $val;
}
