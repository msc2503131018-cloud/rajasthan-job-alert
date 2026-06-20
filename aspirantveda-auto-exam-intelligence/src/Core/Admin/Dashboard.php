<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Admin;

defined('ABSPATH') || exit;

class Dashboard {
    public function register_menu() {
        add_menu_page(
            __('AspirantVeda Intelligence', 'aspirantveda-auto-exam-intelligence'),
            __('AspirantVeda', 'aspirantveda-auto-exam-intelligence'),
            'manage_options',
            'avaei-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-laptop',
            6
        );
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aspirantveda-auto-exam-intelligence'));
        }

        echo '<div class="wrap"><h1>' . esc_html__('AspirantVeda Auto Exam Intelligence', 'aspirantveda-auto-exam-intelligence') . '</h1>';
        echo '<div id="avaei-dashboard-root"></div>';
        echo '</div>';

        $this->enqueue_assets();
    }

    private function enqueue_assets() {
        wp_enqueue_style('avaei-admin-style', AVAEI_PLUGIN_URL . 'assets/css/admin.css', [], AVAEI_VERSION);
        wp_enqueue_script('avaei-admin-script', AVAEI_PLUGIN_URL . 'assets/js/admin.js', ['wp-element', 'wp-api-fetch'], AVAEI_VERSION, true);
        wp_localize_script('avaei-admin-script', 'avaeiAdminConfig', [
            'restUrl' => esc_url_raw(rest_url('avaei/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
