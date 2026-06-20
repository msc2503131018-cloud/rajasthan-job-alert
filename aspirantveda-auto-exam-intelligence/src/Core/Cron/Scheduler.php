<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Cron;

defined('ABSPATH') || exit;

class Scheduler {
    public function register_schedules() {
        add_filter('cron_schedules', [$this, 'add_custom_intervals']);
    }

    public function add_custom_intervals(array $schedules) {
        $schedules['avaei_five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'aspirantveda-auto-exam-intelligence'),
        ];

        $schedules['avaei_fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'aspirantveda-auto-exam-intelligence'),
        ];

        $schedules['avaei_thirty_minutes'] = [
            'interval' => 1800,
            'display' => __('Every 30 Minutes', 'aspirantveda-auto-exam-intelligence'),
        ];

        $schedules['avaei_hourly'] = [
            'interval' => 3600,
            'display' => __('Every Hour', 'aspirantveda-auto-exam-intelligence'),
        ];

        $schedules['avaei_four_hours'] = [
            'interval' => 14400,
            'display' => __('Every 4 Hours', 'aspirantveda-auto-exam-intelligence'),
        ];

        $schedules['avaei_daily'] = [
            'interval' => DAY_IN_SECONDS,
            'display' => __('Daily', 'aspirantveda-auto-exam-intelligence'),
        ];

        return $schedules;
    }

    public function register_cron_jobs() {
        add_action('avaei_monitor_sources', [$this, 'run_source_monitor']);
        add_action('avaei_process_queue', [$this, 'run_post_queue_processor']);
    }

    public function run_source_monitor() {
        $monitor = new \AspirantVeda\AutoExamIntelligence\Core\Services\SourceMonitorService();
        $monitor->process_sources();
    }

    public function run_post_queue_processor() {
        $queue = new \AspirantVeda\AutoExamIntelligence\Core\Services\PostQueueService();
        $queue->process_pending_queue();
    }
}
