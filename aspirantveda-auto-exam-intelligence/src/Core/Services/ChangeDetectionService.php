<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services;

defined('ABSPATH') || exit;

use AspirantVeda\AutoExamIntelligence\Core\Services\Repository\ChangeHistoryRepository;

class ChangeDetectionService {
    private ChangeHistoryRepository $historyRepository;

    public function __construct() {
        $this->historyRepository = new ChangeHistoryRepository();
    }

    public function compute_hash(array $item): string {
        return sha1(wp_json_encode($item));
    }

    public function record_change(int $notification_id, string $change_type, ?string $old_value, ?string $new_value): void {
        $this->historyRepository->add_change($notification_id, $change_type, $old_value, $new_value);
    }
}
