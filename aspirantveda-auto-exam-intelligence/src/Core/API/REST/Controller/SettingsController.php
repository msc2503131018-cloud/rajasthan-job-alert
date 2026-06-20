<?php

namespace AspirantVeda\AutoExamIntelligence\Core\API\REST\Controller;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;
use WP_REST_Request;
use WP_REST_Response;

class SettingsController {
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->settingsManager = new SettingsManager();
    }

    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $this->settingsManager->get_settings(),
        ]);
    }

    public function save_settings(WP_REST_Request $request): WP_REST_Response {
        $body = $request->get_json_params();

        if (!isset($body['provider'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Provider selection is required.', 'aspirantveda-auto-exam-intelligence'),
            ], 422);
        }

        $this->settingsManager->save_settings($body);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Settings saved successfully.', 'aspirantveda-auto-exam-intelligence'),
        ]);
    }
}
