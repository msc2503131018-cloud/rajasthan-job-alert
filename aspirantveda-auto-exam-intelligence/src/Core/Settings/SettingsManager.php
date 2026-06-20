<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Settings;

defined('ABSPATH') || exit;

class SettingsManager {
    public const SETTINGS_KEY = 'avaei_settings';

    public function get_settings(): array {
        return wp_parse_args(
            get_option(self::SETTINGS_KEY, []),
            $this->defaults()
        );
    }

    public function save_settings(array $data): bool {
        $settings = [
            'provider' => sanitize_text_field($data['provider'] ?? 'openai'),
            'language' => sanitize_text_field($data['language'] ?? 'english'),
            'openai_api_key' => sanitize_text_field($data['openai_api_key'] ?? ''),
            'openai_model' => sanitize_text_field($data['openai_model'] ?? 'gpt-4.1-mini'),
            'claude_api_key' => sanitize_text_field($data['claude_api_key'] ?? ''),
            'claude_model' => sanitize_text_field($data['claude_model'] ?? 'claude-3.5-sonic'),
            'gemini_api_key' => sanitize_text_field($data['gemini_api_key'] ?? ''),
            'gemini_model' => sanitize_text_field($data['gemini_model'] ?? 'gemini-1.5-pro'),
            'email_recipients' => sanitize_text_field($data['email_recipients'] ?? get_option('admin_email')),
            'enable_email' => isset($data['enable_email']) ? '1' : '0',
            'telegram_bot_token' => sanitize_text_field($data['telegram_bot_token'] ?? ''),
            'telegram_chat_id' => sanitize_text_field($data['telegram_chat_id'] ?? ''),
            'enable_telegram' => isset($data['enable_telegram']) ? '1' : '0',
            'discord_webhook_url' => esc_url_raw($data['discord_webhook_url'] ?? ''),
            'enable_discord' => isset($data['enable_discord']) ? '1' : '0',
            'whatsapp_webhook_url' => esc_url_raw($data['whatsapp_webhook_url'] ?? ''),
            'enable_whatsapp_webhook' => isset($data['enable_whatsapp_webhook']) ? '1' : '0',
            'default_post_status' => sanitize_text_field($data['default_post_status'] ?? 'draft'),
        ];

        return update_option(self::SETTINGS_KEY, $settings);
    }

    private function defaults(): array {
        return [
            'provider' => 'openai',
            'language' => 'english',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4.1-mini',
            'claude_api_key' => '',
            'claude_model' => 'claude-3.5-sonic',
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-1.5-pro',
            'email_recipients' => get_option('admin_email'),
            'enable_email' => '1',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'enable_telegram' => '0',
            'discord_webhook_url' => '',
            'enable_discord' => '0',
            'whatsapp_webhook_url' => '',
            'enable_whatsapp_webhook' => '0',
            'default_post_status' => 'draft',
        ];
    }
}
