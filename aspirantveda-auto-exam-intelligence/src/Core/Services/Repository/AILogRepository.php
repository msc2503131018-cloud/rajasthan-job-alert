<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class AILogRepository {
    public function log_request(string $provider, string $request_body, string $response_body, string $status): int {
        global $wpdb;

        $table = $wpdb->prefix . 'av_ai_logs';
        $wpdb->insert(
            $table,
            [
                'provider' => sanitize_text_field($provider),
                'model' => sanitize_text_field('default'),
                'request_body' => wp_json_encode($request_body),
                'response_body' => wp_json_encode($response_body),
                'status' => sanitize_text_field($status),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
