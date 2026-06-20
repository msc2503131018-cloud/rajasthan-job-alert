<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\AILogRepository;
use AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider\AIProviderFactory;
use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;

class AIContentService {
    private AILogRepository $logRepository;
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->logRepository = new AILogRepository();
        $this->settingsManager = new SettingsManager();
    }

    public function generate_post_content(array $payload): array {
        $settings = $this->settingsManager->get_settings();
        $provider = sanitize_text_field($payload['provider'] ?? $settings['provider']);
        $language = sanitize_text_field($payload['language'] ?? $settings['language']);
        $title = sanitize_text_field($payload['title'] ?? '');
        $description = sanitize_textarea_field($payload['description'] ?? '');

        $providerAdapter = AIProviderFactory::create($provider, $this->settingsManager);
        $prompt = $this->build_prompt($title, $description, $language);
        $apiResponse = $providerAdapter->generate($prompt);

        $this->logRepository->log_request($provider, $prompt, $apiResponse['raw'] ?? [], isset($apiResponse['error']) ? 'failed' : 'success');

        if (!empty($apiResponse['error'])) {
            return [
                'title' => $title,
                'meta_description' => '',
                'slug' => sanitize_title($title),
                'content' => sprintf('<p>%s</p>', esc_html($description)),
                'faq_schema' => [],
                'breadcrumb_schema' => [],
                'article_schema' => [],
                'error' => sanitize_text_field($apiResponse['error']),
            ];
        }

        return $this->parse_response($apiResponse['text'] ?? '');
    }

    private function build_prompt(string $title, string $description, string $language): string {
        $language_text = ucfirst($language);
        return sprintf(
            "Create a structured SEO-optimized article in %s for a Rajasthan government exam notification. Use title: %s. Use description: %s. Return JSON with keys: title, meta_description, slug, content, faq_schema, breadcrumb_schema, article_schema.",
            $language_text,
            $title,
            $description
        );
    }

    private function parse_response(string $text): array {
        $decoded = json_decode($text, true);

        if (is_array($decoded) && isset($decoded['title'])) {
            return [
                'title' => sanitize_text_field($decoded['title']),
                'meta_description' => sanitize_text_field($decoded['meta_description'] ?? ''),
                'slug' => sanitize_text_field($decoded['slug'] ?? sanitize_title($decoded['title'])),
                'content' => wp_kses_post($decoded['content'] ?? ''),
                'faq_schema' => $decoded['faq_schema'] ?? [],
                'breadcrumb_schema' => $decoded['breadcrumb_schema'] ?? [],
                'article_schema' => $decoded['article_schema'] ?? [],
            ];
        }

        return [
            'title' => sanitize_text_field($text),
            'meta_description' => '',
            'slug' => sanitize_title($text),
            'content' => wp_kses_post($text),
            'faq_schema' => [],
            'breadcrumb_schema' => [],
            'article_schema' => [],
        ];
    }
}
