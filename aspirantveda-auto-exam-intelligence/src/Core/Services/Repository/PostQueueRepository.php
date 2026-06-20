<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class PostQueueRepository {
    public function get_pending_queue(int $limit = 10): array {
        global $wpdb;

        $table = $wpdb->prefix . 'av_post_queue';
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE post_status = %s ORDER BY created_at ASC LIMIT %d", 'pending', $limit));

        return $results ?: [];
    }

    public function create_queue_item(int $notificationId, string $postStatus = 'pending', ?int $assignedCategory = null, ?string $assignedTags = null, ?int $featuredImageId = null): int {
        global $wpdb;

        $table = $wpdb->prefix . 'av_post_queue';
        $wpdb->insert(
            $table,
            [
                'notification_id' => $notificationId,
                'post_status' => sanitize_text_field($postStatus),
                'assigned_category' => $assignedCategory,
                'assigned_tags' => sanitize_text_field($assignedTags),
                'featured_image_id' => $featuredImageId,
                'retries' => 0,
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1),
            ],
            ['%d', '%s', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update_queue_status(int $queueId, string $status, string $note = ''): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'av_post_queue';
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET post_status = %s, last_attempt = %s, retries = retries + 1 WHERE id = %d",
            sanitize_text_field($status),
            current_time('mysql', 1),
            $queueId
        ));

        return (bool) $updated;
    }
}
