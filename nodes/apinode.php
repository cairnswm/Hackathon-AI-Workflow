<?php
require_once __DIR__ . '/workflownode.php';

class APINode extends WorkflowNode
{
    protected function execute()
    {
        if (!isset($this->nodeconfig['endpoint']) || !isset($this->nodeconfig['method'])) {
            throw new Exception("Missing 'endpoint' or 'method' configuration for APINode.");
        }

        $endpoint = $this->nodeconfig['endpoint'];
        $method = strtoupper($this->nodeconfig['method']);
        $headers = $this->nodeconfig['headers'] ?? [];
        $body = $this->nodeconfig['body'] ?? [];

        // Call the external API
        $response = $this->callAPI($endpoint, $method, $headers, $body);

        if ($response['status'] !== 'ok') {
            throw new Exception("Error calling external API: " . $response['error']);
        }

        // Store the API response in workflowdata
        $this->workflowdata['api_response'] = $response['data'];

        $this->status = "ok";
    }

    private function callAPI($endpoint, $method, $headers, $body)
    {
        $ch = curl_init($endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($headers)) {
            $formattedHeaders = [];
            foreach ($headers as $key => $value) {
                $formattedHeaders[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'status' => 'error',
                'error' => 'Failed to call external API. HTTP Code: ' . $httpCode
            ];
        }

        $responseData = json_decode($response, true);

        return [
            'status' => 'ok',
            'data' => $responseData
        ];
    }
}
