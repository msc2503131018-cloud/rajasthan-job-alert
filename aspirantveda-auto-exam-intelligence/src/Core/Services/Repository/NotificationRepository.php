<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class NotificationRepository {
    public function is_duplicate(string $hash, int $source_id): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'av_notifications';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE notification_hash = %s AND source_id = %d", $hash, $source_id));

        return (bool) $count;
    }

    public function create_notification(int $source_id, array $item, string $hash): int {
        global $wpdb;

        $table = $wpdb->prefix . 'av_notifications';
        $wpdb->insert(
            $table,
            [
                'source_id' => $source_id,
                'notification_title' => sanitize_text_field($item['title'] ?? ''),
                'source_url' => esc_url_raw($item['link'] ?? ''),
                'notification_summary' => wp_kses_post($item['description'] ?? ''),
                'notification_hash' => $hash,
                'notification_type' => sanitize_text_field($item['type'] ?? 'notification'),
                'status' => 'new',
                'metadata' => wp_json_encode($item),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function get_notification_by_id(int $notificationId): ?array {
        global $wpdb;

        $table = $wpdb->prefix . 'av_notifications';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $notificationId), ARRAY_A);

        return $result ?: null;
    }

    public function mark_notification_as_published(int $notificationId, int $postId): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'av_notifications';
        return (bool) $wpdb->update(
            $table,
            ['published_post_id' => $postId, 'status' => 'published'],
            ['id' => $notificationId],
            ['%d', '%s'],
            ['%d']
        );
    }
}
