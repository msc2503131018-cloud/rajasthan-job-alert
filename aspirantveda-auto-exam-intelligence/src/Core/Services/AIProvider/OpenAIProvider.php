<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider;

defined('ABSPATH') || exit;

class OpenAIProvider implements AIProviderInterface {
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model) {
        $this->apiKey = trim($apiKey);
        $this->model = sanitize_text_field($model ?: 'gpt-4.1-mini');
    }

    public function generate(string $prompt): array {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $body = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an AI assistant that generates structured SEO content for government exam notifications.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1100,
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

        if ($statusCode !== 200 || empty($decoded['choices'][0]['message']['content'])) {
            return ['error' => __('OpenAI provider rejected the request.', 'aspirantveda-auto-exam-intelligence'), 'raw' => $responseBody];
        }

        return ['text' => $decoded['choices'][0]['message']['content'], 'raw' => $decoded];
    }
}
