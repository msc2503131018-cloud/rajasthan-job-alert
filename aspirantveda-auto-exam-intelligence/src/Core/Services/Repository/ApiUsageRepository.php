<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class ApiUsageRepository {
    public function log_usage(string $provider, string $endpoint, array $requestPayload, array $responsePayload, string $status): int {
        global $wpdb;

        $table = $wpdb->prefix . 'av_api_usage';
        $wpdb->insert(
            $table,
            [
                'provider' => sanitize_text_field($provider),
                'endpoint' => sanitize_text_field($endpoint),
                'request_payload' => wp_json_encode($requestPayload),
                'response_payload' => wp_json_encode($responsePayload),
                'status' => sanitize_text_field($status),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
