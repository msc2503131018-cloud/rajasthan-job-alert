<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\Repository;

defined('ABSPATH') || exit;

class SourceRepository {
    public function get_active_sources(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'av_sources';
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %s", 'active'));

        return $results ?: [];
    }

    public function update_last_checked(int $source_id): void {
        global $wpdb;

        $table = $wpdb->prefix . 'av_sources';
        $wpdb->update(
            $table,
            ['last_checked' => current_time('mysql', 1)],
            ['id' => $source_id],
            ['%s'],
            ['%d']
        );
    }
}
