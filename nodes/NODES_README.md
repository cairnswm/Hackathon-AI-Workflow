# Workflow Nodes Configuration

This document details the expected `nodeconfig` for each node in the workflow system.

## APINode

### Expected `nodeconfig`
- **endpoint** (string, required): The URL of the API endpoint to call.
- **method** (string, optional): The HTTP method to use (default: `GET`).
- **headers** (array, optional): Key-value pairs for HTTP headers.
- **body** (array, optional): Data to send in the request body (used for `POST` or `PUT` methods).

## CalculationNode

### Expected `nodeconfig`
- **fields** (array, required): An array of key-value pairs where the key is the field name and the value is an expression to evaluate. Expressions can reference other workflow data using `{fieldname}`.

## IfNode

### Expected `nodeconfig`
- **field** (string, required): The field name to use in conditional expressions.

### Additional Information
- Conditions are defined in the edges connected to the node. Each edge can have an `if_condition` property.
- The `if_condition` is evaluated using the `field` and other workflow data.

## WorkflowNode

### Templated Values

The `WorkflowNode` class now supports templated values in configuration fields. Templated values are placeholders in the format `{fieldname}` that are dynamically replaced with data from the workflow during execution. 

#### Deep Linking

Templated values can reference nested fields using dot notation. For example:

- `{data.fieldname}`: Refers to a field in the `localdata` array.
- `{fieldname}`: Refers to a field in the `workflowdata` array.

The system resolves these placeholders by traversing the nested structure of the respective data array. If a field is not found, the placeholder is replaced with `null`.

---

## LLMNode

### Updated Configuration

- **prompt** (string, required): The prompt to send to the language model.
- **format** (string, required): Specifies the JSON format for the response. The prompt will include instructions to format the output as valid JSON.
- **action** (string, optional): Additional instructions or context for the language model.
- **model** (string, optional): The model to use (default: `gpt-4.1-mini`).
- **config** (array, optional): Configuration for the model, including:
  - **temperature** (float, default: `0.7`): Controls randomness.
  - **max_tokens** (integer, default: `100`): Maximum number of tokens to generate.

### Key Details

- The `LLMNode` retrieves the API key from the `globaldata` array under the key `api_key`. If the `api_key` is not set, an exception is thrown with the message "API key not found in global configuration."
- The `LLMNode` now appends format instructions to the prompt, ensuring the response is structured as valid JSON.
- The `action` field allows for additional user-defined instructions to be sent to the language model.
