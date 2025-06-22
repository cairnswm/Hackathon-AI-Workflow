# Copilot Rules for This Repository

## General Guidelines
1. Always use the `executeSQL` function for database operations.
2. Avoid using `$stmt->get_result` as it is not supported by the server.
3. Ensure all SQL queries are parameterized to prevent SQL injection.
4. Use `insertRecord`, `updateRecord`, `deleteRecord`, and `fetchRecord` helper functions for CRUD operations.

## Using `executeSQL`
The `executeSQL` function is defined in `utils/executesql.php`. It supports the following operations:

### Parameters
- `$query`: The SQL query string.
- `$params`: An array of parameters to bind to the query.
- `$fetch`: Boolean indicating whether to fetch results.
- `$returnInsertId`: Boolean indicating whether to return the last inserted ID.

### Example Usage
#### Fetching Records
```php
$query = "SELECT * FROM users WHERE email = ?";
$params = ["example@example.com"];
$result = executeSQL($query, $params, true);
print_r($result);
```

#### Inserting Records
```php
$query = "INSERT INTO users (name, email) VALUES (?, ?)";
$params = ["John Doe", "john@example.com"];
$insertedRecord = insertRecord($query, $params);
print_r($insertedRecord);
```

#### Updating Records
```php
$query = "UPDATE users SET name = ? WHERE id = ?";
$params = ["Jane Doe", 1];
$updatedRecord = updateRecord($query, $params);
print_r($updatedRecord);
```

#### Deleting Records
```php
$query = "DELETE FROM users WHERE id = ?";
$params = [1];
$success = deleteRecord($query, $params);
// var_dump($success);
```

### Notes
- For fetching results, the function uses `$stmt->store_result()` and `$stmt->bind_result()` to retrieve data.
- Avoid using `$stmt->get_result()` as it is not supported.
- Always close the statement after execution using `$stmt->close()`.

## Additional Rules
- Use `dbconfig.php` for database configuration.
- Ensure proper error handling for database operations.
- Follow the workspace structure and keep utility functions in the `utils/` directory.
