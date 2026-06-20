<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;
use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\NotificationRepository;
use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\PostQueueRepository;

class PostQueueService {
    private PostQueueRepository $queueRepository;
    private NotificationRepository $notificationRepository;
    private AIContentService $contentService;
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->queueRepository = new PostQueueRepository();
        $this->notificationRepository = new NotificationRepository();
        $this->contentService = new AIContentService();
        $this->settingsManager = new SettingsManager();
    }

    public function process_pending_queue(): void {
        $queueItems = $this->queueRepository->get_pending_queue();

        foreach ($queueItems as $item) {
            $notification = $this->notificationRepository->get_notification_by_id((int) $item->notification_id);
            if (!$notification) {
                $this->queueRepository->update_queue_status((int) $item->id, 'failed', 'Missing notification reference');
                continue;
            }

            $successful = $this->create_post_from_notification((array) $item, (array) $notification);
            $status = $successful ? 'completed' : 'failed';
            $this->queueRepository->update_queue_status((int) $item->id, $status);
        }
    }

    private function create_post_from_notification(array $queueItem, array $notification): bool {
        $settings = $this->settingsManager->get_settings();
        $payload = [
            'provider' => $settings['provider'],
            'language' => $settings['language'],
            'title' => $notification['notification_title'] ?? '',
            'description' => $notification['notification_summary'] ?? '',
        ];

        $aiResult = $this->contentService->generate_post_content($payload);
        $postContent = $aiResult['content'] ?: wp_kses_post($notification['notification_summary'] ?? '');
        $postTitle = $aiResult['title'] ?: sanitize_text_field($notification['notification_title'] ?? '');

        $postData = [
            'post_title' => $postTitle,
            'post_content' => $postContent,
            'post_status' => sanitize_text_field($queueItem['post_status'] ?? $settings['default_post_status']),
            'post_type' => 'avaei_notification',
            'post_name' => sanitize_title($aiResult['slug'] ?: $postTitle),
        ];

        $postId = wp_insert_post($postData, true);

        if (is_wp_error($postId) || $postId <= 0) {
            return false;
        }

        if (!empty($queueItem['assigned_category'])) {
            wp_set_post_categories((int) $postId, [(int) $queueItem['assigned_category']], true);
        }

        if (!empty($queueItem['assigned_tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $queueItem['assigned_tags'])));
            wp_set_post_tags((int) $postId, array_map('sanitize_text_field', $tags));
        }

        if (!empty($queueItem['featured_image_id'])) {
            set_post_thumbnail((int) $postId, (int) $queueItem['featured_image_id']);
        }

        update_post_meta((int) $postId, '_avaei_notification_hash', sanitize_text_field($notification['notification_hash'] ?? ''));
        update_post_meta((int) $postId, '_avaei_ai_generated', '1');

        $this->notificationRepository->mark_notification_as_published((int) $notification['id'], (int) $postId);

        return true;
    }
}
