<?php
require_once __DIR__ . '/workflownode.php';

class LLMNode extends WorkflowNode
{
    protected function execute()
    {
        // var_dump("Node Confgi", $this->nodeconfig);
        // var_dump("Global Data", $this->globaldata);

        if (!isset($this->nodeconfig['prompt'])) {
            throw new Exception("Missing 'prompt' configuration for LLMNode.");
        }

        if (!isset($this->nodeconfig['format'])) {
            throw new Exception("Missing 'format' configuration for LLMNode.");
        }

        // Replace {<fieldname>} placeholders in prompt, format, and action
        $prompt = $this->replaceTemplateValues($this->nodeconfig['prompt']);
        $format = $this->replaceTemplateValues($this->nodeconfig['format']);
        $action = $this->replaceTemplateValues($this->nodeconfig['action'] ?? '');

        $model = $this->nodeconfig['model'] ?? 'gpt-4.1-mini'; // Default model
        $config = $this->nodeconfig['config'] ?? [
            'temperature' => 0.7,
            'max_tokens' => 100
        ];

        // Append format instructions to the prompt
        $formattedPrompt = $prompt . "\n\nPlease format the response as valid json, in the following JSON format: " . $format . ". Do not add any pre or post information. Only the json";

        // Call the OpenAI API
        $response = $this->callOpenAI($formattedPrompt, $model, $config, $action);

        if ($response['status'] !== 'ok') {
            throw new Exception("Error calling OpenAI API: " . $response['error']);
        }

        // Store the LLM output in workflowdata
        $fieldName = $this->nodeconfig['field'] ?? 'ai_value';
        $this->workflowdata[$fieldName] = json_decode($response['data']['output']);

        $this->status = "ok";
    }

    private function callOpenAI($prompt, $model, $config, $action)
    {
        $apiKey = $this->getApiKey();
        $apiUrl = "https://api.openai.com/v1/chat/completions"; // Updated OpenAI API endpoint
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $action]
            ],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens']
        ]);

        // var_dump($payload);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'status' => 'error',
                'error' => 'Failed to call OpenAI API. HTTP Code: ' . $httpCode
            ];
        }

        $responseData = json_decode($response, true);

        // var_dump("------------- Response Data:\n", $responseData, "\n-------------");
        return [
            'status' => 'ok',
            'data' => [
                'output' => $responseData['choices'][0]['message']['content'] ?? '',
            ],
            'metadata' => [
                'model' => $responseData['model'] ?? '',
                'tokens_used' => $responseData['usage']['total_tokens'] ?? 0
            ]
        ];
    }

    private function getApiKey()
    {
        if (!isset($this->globaldata['api_key'])) {
            // var_dump("Global Data:", $this->globaldata);
            throw new Exception("API key not found in global configuration.");
        }

        return $this->globaldata['api_key'];
    }
}
