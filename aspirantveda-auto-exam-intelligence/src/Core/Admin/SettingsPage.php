<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Admin;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;

class SettingsPage {
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->settingsManager = new SettingsManager();
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aspirantveda-auto-exam-intelligence'));
        }

        $settings = $this->settingsManager->get_settings();
        $message = '';

        if (!empty($_GET['avaei_message'])) {
            $message = sanitize_text_field(wp_unslash($_GET['avaei_message']));
        }

        echo '<div class="wrap"><h1>' . esc_html__('AspirantVeda Intelligence Settings', 'aspirantveda-auto-exam-intelligence') . '</h1>';

        if ($message) {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('avaei_save_settings', 'avaei_settings_nonce');
        echo '<input type="hidden" name="action" value="avaei_save_settings" />';

        echo '<table class="form-table">';

        $this->render_field('provider', __('AI Provider', 'aspirantveda-auto-exam-intelligence'), sprintf(
            '<select name="provider"><option value="openai"%s>OpenAI</option><option value="claude"%s>Claude</option><option value="gemini"%s>Gemini</option></select>',
            selected($settings['provider'], 'openai', false),
            selected($settings['provider'], 'claude', false),
            selected($settings['provider'], 'gemini', false)
        ));

        $this->render_field('language', __('Content Language', 'aspirantveda-auto-exam-intelligence'), sprintf(
            '<select name="language"><option value="english"%s>English</option><option value="hindi"%s>Hindi</option><option value="hinglish"%s>Hinglish</option></select>',
            selected($settings['language'], 'english', false),
            selected($settings['language'], 'hindi', false),
            selected($settings['language'], 'hinglish', false)
        ));

        $this->render_section_title(__('OpenAI Settings', 'aspirantveda-auto-exam-intelligence'));
        $this->render_text_field('openai_api_key', __('OpenAI API Key', 'aspirantveda-auto-exam-intelligence'), $settings['openai_api_key']);
        $this->render_text_field('openai_model', __('OpenAI Model', 'aspirantveda-auto-exam-intelligence'), $settings['openai_model']);

        $this->render_section_title(__('Claude Settings', 'aspirantveda-auto-exam-intelligence'));
        $this->render_text_field('claude_api_key', __('Claude API Key', 'aspirantveda-auto-exam-intelligence'), $settings['claude_api_key']);
        $this->render_text_field('claude_model', __('Claude Model', 'aspirantveda-auto-exam-intelligence'), $settings['claude_model']);

        $this->render_section_title(__('Gemini Settings', 'aspirantveda-auto-exam-intelligence'));
        $this->render_text_field('gemini_api_key', __('Gemini API Key', 'aspirantveda-auto-exam-intelligence'), $settings['gemini_api_key']);
        $this->render_text_field('gemini_model', __('Gemini Model', 'aspirantveda-auto-exam-intelligence'), $settings['gemini_model']);

        $this->render_section_title(__('Notification Settings', 'aspirantveda-auto-exam-intelligence'));
        $this->render_checkbox_field('enable_email', __('Enable Email Alerts', 'aspirantveda-auto-exam-intelligence'), $settings['enable_email']);
        $this->render_text_field('email_recipients', __('Email Recipients', 'aspirantveda-auto-exam-intelligence'), $settings['email_recipients']);
        $this->render_checkbox_field('enable_telegram', __('Enable Telegram Alerts', 'aspirantveda-auto-exam-intelligence'), $settings['enable_telegram']);
        $this->render_text_field('telegram_bot_token', __('Telegram Bot Token', 'aspirantveda-auto-exam-intelligence'), $settings['telegram_bot_token']);
        $this->render_text_field('telegram_chat_id', __('Telegram Chat ID', 'aspirantveda-auto-exam-intelligence'), $settings['telegram_chat_id']);
        $this->render_checkbox_field('enable_discord', __('Enable Discord Alerts', 'aspirantveda-auto-exam-intelligence'), $settings['enable_discord']);
        $this->render_text_field('discord_webhook_url', __('Discord Webhook URL', 'aspirantveda-auto-exam-intelligence'), $settings['discord_webhook_url']);
        $this->render_checkbox_field('enable_whatsapp_webhook', __('Enable WhatsApp Webhook', 'aspirantveda-auto-exam-intelligence'), $settings['enable_whatsapp_webhook']);
        $this->render_text_field('whatsapp_webhook_url', __('WhatsApp Webhook URL', 'aspirantveda-auto-exam-intelligence'), $settings['whatsapp_webhook_url']);

        $this->render_section_title(__('Post Settings', 'aspirantveda-auto-exam-intelligence'));
        $this->render_text_field('default_post_status', __('Default Post Status', 'aspirantveda-auto-exam-intelligence'), $settings['default_post_status']);

        echo '</table>';
        submit_button(__('Save Settings', 'aspirantveda-auto-exam-intelligence'));
        echo '</form></div>';
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized request', 'aspirantveda-auto-exam-intelligence'));
        }

        if (!isset($_POST['avaei_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avaei_settings_nonce'])), 'avaei_save_settings')) {
            wp_die(__('Invalid security token.', 'aspirantveda-auto-exam-intelligence'));
        }

        $sanitized = [];
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field(wp_unslash($value));
            }
        }

        $this->settingsManager->save_settings($sanitized);

        $redirect = add_query_arg(
            'avaei_message',
            rawurlencode(__('Settings saved successfully.', 'aspirantveda-auto-exam-intelligence')),
            wp_get_referer() ?: admin_url('admin.php?page=avaei-settings')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    private function render_field(string $name, string $label, string $html) {
        echo '<tr><th scope="row"><label for="avaei_' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>' . $html . '</td></tr>';
    }

    private function render_text_field(string $name, string $label, string $value) {
        $this->render_field($name, $label, sprintf(
            '<input type="text" id="avaei_%1$s" name="%1$s" value="%2$s" class="regular-text" />',
            esc_attr($name),
            esc_attr($value)
        ));
    }

    private function render_checkbox_field(string $name, string $label, string $checked) {
        $this->render_field($name, $label, sprintf(
            '<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
            esc_attr($name),
            checked($checked, '1', false),
            esc_html__('Enabled', 'aspirantveda-auto-exam-intelligence')
        ));
    }

    private function render_section_title(string $title) {
        echo '<tr><th colspan="2"><h2>' . esc_html($title) . '</h2></th></tr>';
    }
}
