<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;

class NotificationService {
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->settingsManager = new SettingsManager();
    }

    public function send_admin_notice(string $message): void {
        if (!is_admin()) {
            return;
        }

        add_action('admin_notices', function() use ($message) {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
        });
    }

    public function send_alert(array $payload): void {
        $settings = $this->settingsManager->get_settings();
        $message = sanitize_text_field($payload['message'] ?? '');
        $title = sanitize_text_field($payload['title'] ?? __('AspirantVeda Alert', 'aspirantveda-auto-exam-intelligence'));
        $url = esc_url_raw($payload['url'] ?? '');

        if ($settings['enable_email'] === '1') {
            $this->send_email_alert([
                'to' => $settings['email_recipients'],
                'subject' => $title,
                'message' => $message . "\n\n" . $url,
            ]);
        }

        if ($settings['enable_telegram'] === '1') {
            $this->send_telegram_alert($title, $message, $settings['telegram_bot_token'], $settings['telegram_chat_id']);
        }

        if ($settings['enable_discord'] === '1') {
            $this->send_discord_alert($title, $message, $settings['discord_webhook_url']);
        }

        if ($settings['enable_whatsapp_webhook'] === '1') {
            $this->send_whatsapp_alert($title, $message, $settings['whatsapp_webhook_url']);
        }
    }

    public function send_email_alert(array $payload): void {
        $to = sanitize_email($payload['to'] ?? get_option('admin_email'));
        $subject = sanitize_text_field($payload['subject'] ?? 'AspirantVeda Alert');
        $message = sanitize_textarea_field($payload['message'] ?? '');

        wp_mail($to, $subject, $message);
    }

    private function send_telegram_alert(string $title, string $message, string $token, string $chatId): void {
        if (empty($token) || empty($chatId)) {
            return;
        }

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', rawurlencode($token));
        wp_remote_post($endpoint, [
            'body' => [
                'chat_id' => $chatId,
                'text' => sprintf('%s\n\n%s', $title, $message),
            ],
            'timeout' => 20,
        ]);
    }

    private function send_discord_alert(string $title, string $message, string $webhookUrl): void {
        if (empty($webhookUrl)) {
            return;
        }

        wp_remote_post($webhookUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'username' => 'AspirantVeda Bot',
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => $message,
                        'timestamp' => gmdate('c'),
                    ],
                ],
            ]),
            'timeout' => 20,
        ]);
    }

    private function send_whatsapp_alert(string $title, string $message, string $webhookUrl): void {
        if (empty($webhookUrl)) {
            return;
        }

        wp_remote_post($webhookUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'title' => $title,
                'message' => $message,
            ]),
            'timeout' => 20,
        ]);
    }
}
