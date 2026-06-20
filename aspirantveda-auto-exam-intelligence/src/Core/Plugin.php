<?php

namespace AspirantVeda\AutoExamIntelligence\Core;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Admin\AdminHooks;
use AspirantVeda\AutoExamIntelligence\Core\Database\Installer;
use AspirantVeda\AutoExamIntelligence\Core\Cron\Scheduler;
use AspirantVeda\AutoExamIntelligence\Core\API\REST\Router;
use AspirantVeda\AutoExamIntelligence\Core\Services\SourceMonitorService;
use AspirantVeda\AutoExamIntelligence\Core\Services\AIContentService;
use AspirantVeda\AutoExamIntelligence\Core\Services\NotificationService;

class Plugin {
    private Installer $installer;
    private AdminHooks $adminHooks;
    private Scheduler $scheduler;
    private Router $router;
    private SourceMonitorService $sourceMonitor;
    private AIContentService $aiContent;
    private NotificationService $notification;

    public function __construct() {
        $this->installer       = new Installer();
        $this->adminHooks      = new AdminHooks();
        $this->scheduler       = new Scheduler();
        $this->router          = new Router();
        $this->sourceMonitor   = new SourceMonitorService();
        $this->aiContent       = new AIContentService();
        $this->notification    = new NotificationService();
    }

    public function run() {
        add_action('init', [$this, 'register_post_types']);
        add_action('admin_menu', [$this->adminHooks, 'register_menus']);
        add_action('admin_post_avaei_save_settings', [$this->adminHooks, 'handle_save_settings']);
        add_action('rest_api_init', [$this->router, 'register_routes']);

        register_activation_hook(AVAEI_PLUGIN_FILE, [$this->installer, 'activate']);
        register_deactivation_hook(AVAEI_PLUGIN_FILE, [$this->installer, 'deactivate']);
        register_uninstall_hook(AVAEI_PLUGIN_FILE, ['AspirantVeda\\AutoExamIntelligence\\Core\\Database\\Installer', 'uninstall']);

        $this->scheduler->register_schedules();
        $this->scheduler->register_cron_jobs();
    }

    public function register_post_types() {
        register_post_type('avaei_notification', [
            'labels' => [
                'name' => __('AspirantVeda Notifications', 'aspirantveda-auto-exam-intelligence'),
                'singular_name' => __('Notification', 'aspirantveda-auto-exam-intelligence'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'excerpt', 'author', 'custom-fields', 'thumbnail'],
            'rewrite' => ['slug' => 'avaei-notifications'],
            'capability_type' => 'post',
        ]);
    }
}
