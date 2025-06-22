<?php
require_once __DIR__ . '/workflownode.php';

class LLMNode extends WorkflowNode
{
    protected function execute()
    {
        if (!isset($this->nodeconfig['prompt'])) {
            throw new Exception("Missing 'prompt' configuration for LLMNode.");
        }

        if (!isset($this->nodeconfig['format'])) {
            throw new Exception("Missing 'format' configuration for LLMNode.");
        }

        $prompt = $this->nodeconfig['prompt'];
        $format = $this->nodeconfig['format'];
        $model = $this->nodeconfig['model'] ?? 'gpt-4.1-nano'; // Default model
        $config = $this->nodeconfig['config'] ?? [
            'temperature' => 0.7,
            'max_tokens' => 100
        ];

        // Append format instructions to the prompt
        $formattedPrompt = $prompt . "\n\nPlease respond only in the following JSON format: " . $format;

        // Call the OpenAI API
        $response = $this->callOpenAI($formattedPrompt, $model, $config);

        if ($response['status'] !== 'ok') {
            throw new Exception("Error calling OpenAI API: " . $response['error']);
        }

        // Store the LLM output in workflowdata
        $this->workflowdata['ai_output'] = $response['data']['output'];
        $this->workflowdata['metadata'] = $response['metadata'];

        $this->status = "ok";
    }

    private function callOpenAI($prompt, $model, $config)
    {
        $apiKey = $this->getApiKey();
        $apiUrl = "https://api.openai.com/v1/completions"; // OpenAI API endpoint
        $payload = json_encode([
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens']
        ]);

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

        return [
            'status' => 'ok',
            'data' => [
                'output' => $responseData['choices'][0]['text'] ?? '',
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
            throw new Exception("API key not found in global configuration.");
        }

        return $this->globaldata['api_key'];
    }
}
