<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider;

defined('ABSPATH') || exit;

class GeminiProvider implements AIProviderInterface {
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model) {
        $this->apiKey = trim($apiKey);
        $this->model = sanitize_text_field($model ?: 'gemini-1.5-pro');
    }

    public function generate(string $prompt): array {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta2/models/' . rawurlencode($this->model) . ':generateText';
        $body = [
            'prompt' => [
                'text' => $prompt,
            ],
            'temperature' => 0.7,
            'maxOutputTokens' => 1100,
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
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

        if ($statusCode !== 200 || empty($decoded['candidates'][0]['output'])) {
            return ['error' => __('Gemini provider rejected the request.', 'aspirantveda-auto-exam-intelligence'), 'raw' => $responseBody];
        }

        return ['text' => $decoded['candidates'][0]['output'], 'raw' => $decoded];
    }
}
