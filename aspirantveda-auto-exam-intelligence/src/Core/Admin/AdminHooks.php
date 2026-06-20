<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Admin;

defined('ABSPATH') || exit;

class AdminHooks {
    public function register_hooks() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_post_avaei_save_settings', [$this, 'handle_save_settings']);
    }

    public function register_menus() {
        add_menu_page(
            __('AspirantVeda Intelligence', 'aspirantveda-auto-exam-intelligence'),
            __('AspirantVeda', 'aspirantveda-auto-exam-intelligence'),
            'manage_options',
            'avaei-dashboard',
            [new Dashboard(), 'render_dashboard'],
            'dashicons-laptop',
            6
        );

        add_submenu_page(
            'avaei-dashboard',
            __('Settings', 'aspirantveda-auto-exam-intelligence'),
            __('Settings', 'aspirantveda-auto-exam-intelligence'),
            'manage_options',
            'avaei-settings',
            [new SettingsPage(), 'render_settings_page']
        );
    }

    public function handle_save_settings() {
        (new SettingsPage())->save_settings();
    }
}
