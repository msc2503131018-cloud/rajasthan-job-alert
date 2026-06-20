<?php

namespace AspirantVeda\AutoExamIntelligence\Core\API\REST\Controller;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Services\AIContentService;
use WP_REST_Request;
use WP_REST_Response;

class AIController {
    public function generate_content(WP_REST_Request $request): WP_REST_Response {
        $body = $request->get_json_params();

        if (empty($body['title']) || empty($body['description'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Title and description are required.', 'aspirantveda-auto-exam-intelligence'),
            ], 422);
        }

        $contentService = new AIContentService();
        $result = $contentService->generate_post_content($body);

        return new WP_REST_Response([
            'success' => true,
            'data' => $result,
        ]);
    }
}
