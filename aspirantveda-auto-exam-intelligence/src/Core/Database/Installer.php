<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Database;

defined('ABSPATH') || exit;

class Installer {
    public function activate() {
        $this->create_tables();
        $this->schedule_cron_jobs();
    }

    public function deactivate() {
        $this->clear_scheduled_cron_jobs();
    }

    public static function uninstall() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $tables = [
            $wpdb->prefix . 'av_sources',
            $wpdb->prefix . 'av_notifications',
            $wpdb->prefix . 'av_ai_logs',
            $wpdb->prefix . 'av_post_queue',
            $wpdb->prefix . 'av_api_usage',
            $wpdb->prefix . 'av_change_history',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('avaei_cron_last_run');
        delete_option('avaei_enabled_providers');
        delete_option('avaei_source_monitor_settings');
    }

    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = [];

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_sources (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_name varchar(255) NOT NULL,
            source_url text NOT NULL,
            source_type varchar(100) NOT NULL,
            last_checked datetime DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'active',
            settings longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_type (source_type),
            KEY status (status)
        ) {$charset_collate}";

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_notifications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_id bigint(20) unsigned NOT NULL,
            notification_title varchar(255) NOT NULL,
            source_url text NOT NULL,
            notification_summary longtext DEFAULT NULL,
            notification_hash varchar(255) NOT NULL,
            notification_type varchar(100) NOT NULL,
            detected_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            published_post_id bigint(20) unsigned DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'new',
            metadata longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY source_id (source_id),
            KEY notification_hash (notification_hash),
            KEY status (status)
        ) {$charset_collate}";

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_ai_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(100) NOT NULL,
            model varchar(100) NOT NULL,
            request_body longtext NOT NULL,
            response_body longtext DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider (provider),
            KEY status (status)
        ) {$charset_collate}";

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_post_queue (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) unsigned NOT NULL,
            post_status varchar(50) NOT NULL DEFAULT 'pending',
            assigned_category bigint(20) unsigned DEFAULT NULL,
            assigned_tags text DEFAULT NULL,
            featured_image_id bigint(20) unsigned DEFAULT NULL,
            retries int(11) NOT NULL DEFAULT 0,
            last_attempt datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY notification_id (notification_id),
            KEY post_status (post_status)
        ) {$charset_collate}";

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_api_usage (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(100) NOT NULL,
            endpoint varchar(255) NOT NULL,
            request_payload longtext DEFAULT NULL,
            response_payload longtext DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'success',
            used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider (provider),
            KEY used_at (used_at)
        ) {$charset_collate}";

        $tables[] = "CREATE TABLE {$wpdb->prefix}av_change_history (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) unsigned NOT NULL,
            change_type varchar(100) NOT NULL,
            old_value longtext DEFAULT NULL,
            new_value longtext DEFAULT NULL,
            detected_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY notification_id (notification_id),
            KEY change_type (change_type)
        ) {$charset_collate}";

        foreach ($tables as $table_sql) {
            dbDelta($table_sql);
        }
    }

    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('avaei_monitor_sources')) {
            wp_schedule_event(time(), 'avaei_five_minutes', 'avaei_monitor_sources');
        }

        if (!wp_next_scheduled('avaei_process_queue')) {
            wp_schedule_event(time(), 'avaei_fifteen_minutes', 'avaei_process_queue');
        }
    }

    private function clear_scheduled_cron_jobs() {
        wp_clear_scheduled_hook('avaei_monitor_sources');
        wp_clear_scheduled_hook('avaei_process_queue');
    }
}
