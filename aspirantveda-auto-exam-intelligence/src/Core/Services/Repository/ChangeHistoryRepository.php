<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class ChangeHistoryRepository {
    public function add_change(int $notification_id, string $change_type, ?string $old_value, ?string $new_value): int {
        global $wpdb;

        $table = $wpdb->prefix . 'av_change_history';
        $wpdb->insert(
            $table,
            [
                'notification_id' => $notification_id,
                'change_type' => sanitize_text_field($change_type),
                'old_value' => wp_kses_post($old_value),
                'new_value' => wp_kses_post($new_value),
            ],
            ['%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
