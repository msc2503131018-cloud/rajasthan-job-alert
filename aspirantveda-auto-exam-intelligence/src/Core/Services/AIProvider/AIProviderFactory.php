<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;

class AIProviderFactory {
    public static function create(string $provider, SettingsManager $settings): AIProviderInterface {
        switch (strtolower($provider)) {
            case 'claude':
                return new ClaudeProvider(
                    $settings->get_settings()['claude_api_key'],
                    $settings->get_settings()['claude_model']
                );
            case 'gemini':
                return new GeminiProvider(
                    $settings->get_settings()['gemini_api_key'],
                    $settings->get_settings()['gemini_model']
                );
            case 'openai':
            default:
                return new OpenAIProvider(
                    $settings->get_settings()['openai_api_key'],
                    $settings->get_settings()['openai_model']
                );
        }
    }
}
