<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider;

defined('ABSPATH') || exit;

class ClaudeProvider implements AIProviderInterface {
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model) {
        $this->apiKey = trim($apiKey);
        $this->model = sanitize_text_field($model ?: 'claude-3.5-sonic');
    }

    public function generate(string $prompt): array {
        $endpoint = 'https://api.anthropic.com/v1/complete';
        $body = [
            'model' => $this->model,
            'prompt' => sprintf("%s\n\nAssistant:", $prompt),
            'max_tokens_to_sample' => 1100,
            'temperature' => 0.7,
            'return_likelihoods' => 'NONE',
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($responseBody, true);

        if ($statusCode !== 200 || empty($decoded['completion'])) {
            return ['error' => __('Claude provider rejected the request.', 'aspirantveda-auto-exam-intelligence'), 'raw' => $responseBody];
        }

        return ['text' => $decoded['completion'], 'raw' => $decoded];
    }
}
