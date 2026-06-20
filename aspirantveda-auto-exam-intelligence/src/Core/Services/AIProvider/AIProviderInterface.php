<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Services\AIProvider;

defined('ABSPATH') || exit;

interface AIProviderInterface {
    public function generate(string $prompt): array;
}
