<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\SourceRepository;
use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\NotificationRepository;
use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\PostQueueRepository;
use AspirantVeda\AutoExamIntelligence\Core\Services\ChangeDetectionService;
use AspirantVeda\AutoExamIntelligence\Core\Services\NotificationService;
use AspirantVeda\AutoExamIntelligence\Core\Settings\SettingsManager;

class SourceMonitorService {
    private SourceRepository $sourceRepository;
    private NotificationRepository $notificationRepository;
    private PostQueueRepository $postQueueRepository;
    private ChangeDetectionService $changeDetection;
    private NotificationService $notificationService;
    private SettingsManager $settingsManager;

    public function __construct() {
        $this->sourceRepository = new SourceRepository();
        $this->notificationRepository = new NotificationRepository();
        $this->postQueueRepository = new PostQueueRepository();
        $this->changeDetection = new ChangeDetectionService();
        $this->notificationService = new NotificationService();
        $this->settingsManager = new SettingsManager();
    }

    public function process_sources() {
        $settings = $this->settingsManager->get_settings();
        $sources = $this->sourceRepository->get_active_sources();

        foreach ($sources as $source) {
            $items = $this->fetch_source_items($source);

            foreach ($items as $item) {
                $hash = $this->changeDetection->compute_hash($item);

                if ($this->notificationRepository->is_duplicate($hash, $source->id)) {
                    continue;
                }

                $notification_id = $this->notificationRepository->create_notification($source->id, $item, $hash);
                $this->changeDetection->record_change($notification_id, 'new_notification', null, wp_json_encode($item));
                $this->postQueueRepository->create_queue_item(
                    $notification_id,
                    $settings['default_post_status'],
                    $this->create_tags((string) $item['title']),
                    $this->get_category_id_for_source($source),
                    null
                );
                $this->notificationService->send_alert([
                    'title' => $item['title'],
                    'message' => $item['description'] ?? '',
                    'url' => $item['link'] ?? '',
                ]);
            }

            $this->sourceRepository->update_last_checked($source->id);
        }
    }

    private function fetch_source_items(object $source): array {
        $items = [];
        switch ($source->source_type) {
            case 'rss':
                $items = $this->fetch_rss_feed($source->source_url);
                break;
            case 'xml':
                $items = $this->fetch_xml_feed($source->source_url);
                break;
            case 'html':
                $items = $this->fetch_html_page($source->source_url);
                break;
            case 'sitemap':
                $items = $this->fetch_sitemap($source->source_url);
                break;
            case 'pdf':
                $items = $this->fetch_pdf_notifications($source->source_url);
                break;
            default:
                $items = [];
                break;
        }

        return array_filter($items);
    }

    private function fetch_rss_feed(string $url): array {
        $response = wp_remote_get(esc_url_raw($url), ['timeout' => 20]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml || !isset($xml->channel->item)) {
            return [];
        }

        $items = [];
        foreach ($xml->channel->item as $item) {
            $items[] = [
                'title' => sanitize_text_field((string) $item->title),
                'link' => esc_url_raw((string) $item->link),
                'description' => wp_kses_post((string) $item->description),
                'pubDate' => sanitize_text_field((string) $item->pubDate),
            ];
        }

        return $items;
    }

    private function fetch_xml_feed(string $url): array {
        return $this->fetch_rss_feed($url);
    }

    private function fetch_html_page(string $url): array {
        $response = wp_remote_get(esc_url_raw($url), ['timeout' => 20]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        preg_match_all('/<a[^>]*href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $body, $matches, PREG_SET_ORDER);

        $items = [];
        foreach ($matches as $match) {
            $link = esc_url_raw($this->normalize_url($url, $match[1]));
            $items[] = [
                'title' => sanitize_text_field(trim(strip_tags($match[2]))),
                'link' => $link,
                'description' => '',
                'pubDate' => '',
            ];
        }

        return $items;
    }

    private function fetch_sitemap(string $url): array {
        $response = wp_remote_get(esc_url_raw($url), ['timeout' => 20]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml || !isset($xml->url)) {
            return [];
        }

        $items = [];
        foreach ($xml->url as $node) {
            $items[] = [
                'title' => sanitize_text_field((string) $node->loc),
                'link' => esc_url_raw((string) $node->loc),
                'description' => '',
                'pubDate' => sanitize_text_field((string) $node->lastmod),
            ];
        }

        return $items;
    }

    private function fetch_pdf_notifications(string $url): array {
        $response = wp_remote_get(esc_url_raw($url), ['timeout' => 20]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        preg_match_all('/href="([^"]+\.pdf)"/i', $body, $matches, PREG_SET_ORDER);

        $items = [];
        foreach ($matches as $match) {
            $link = esc_url_raw($this->normalize_url($url, $match[1]));
            $items[] = [
                'title' => sanitize_text_field(basename($link)),
                'link' => $link,
                'description' => __('PDF notification discovered. Please inspect the source for details.', 'aspirantveda-auto-exam-intelligence'),
                'pubDate' => '',
            ];
        }

        return $items;
    }

    private function normalize_url(string $base, string $relative): string {
        if (strpos($relative, 'http') === 0) {
            return $relative;
        }

        return rtrim($base, '/') . '/' . ltrim($relative, '/');
    }

    private function create_tags(string $title): string {
        $words = preg_split('/\s+/', strip_tags($title));
        $tags = array_slice(array_unique(array_filter(array_map('sanitize_text_field', $words))), 0, 5);

        return implode(', ', $tags);
    }

    private function get_category_id_for_source(object $source): ?int {
        return null;
    }
}
