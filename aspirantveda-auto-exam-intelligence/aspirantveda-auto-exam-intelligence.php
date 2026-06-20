<?php
/**
 * Plugin Name: AspirantVeda Auto Exam Intelligence
 * Plugin URI:  https://example.com/aspirantveda-auto-exam-intelligence
 * Description: AI-powered monitoring and publishing system for Rajasthan government jobs, exams, results, admit cards, answer keys, scholarships, university notifications, and education news.
 * Version:     1.0.0
 * Author:      AspirantVeda
 * Author URI:  https://example.com
 * Text Domain: aspirantveda-auto-exam-intelligence
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

if (!defined('AVAEI_VERSION')) {
    define('AVAEI_VERSION', '1.0.0');
}

if (!defined('AVAEI_PLUGIN_FILE')) {
    define('AVAEI_PLUGIN_FILE', __FILE__);
}

if (!defined('AVAEI_PLUGIN_DIR')) {
    define('AVAEI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('AVAEI_PLUGIN_URL')) {
    define('AVAEI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

require_once AVAEI_PLUGIN_DIR . 'src/Autoloader.php';

use AspirantVeda\AutoExamIntelligence\Plugin;

function avaei_load_plugin() {
    $plugin = new Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'avaei_load_plugin');
