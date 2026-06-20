<?php

namespace AspirantVeda\AutoExamIntelligence\Core\API\REST;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\API\REST\Controller\AIController;
use AspirantVeda\AutoExamIntelligence\Core\API\REST\Controller\SettingsController;

class Router {
    public function register_routes() {
        register_rest_route('avaei/v1', '/generate-content', [
            'methods' => 'POST',
            'callback' => [new AIController(), 'generate_content'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('avaei/v1', '/settings', [
            [
                'methods' => 'GET',
                'callback' => [new SettingsController(), 'get_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [new SettingsController(), 'save_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    public function permission_check() {
        return current_user_can('manage_options');
    }
}
