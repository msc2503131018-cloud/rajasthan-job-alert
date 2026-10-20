<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Blog;

defined('ABSPATH') || exit;

class BlogManager {
    private const POST_TYPE = 'avaei_blog';
    private const STATUS_QUERY_ARG = 'avaei_blog_notice';
    private const CACHE_INDEX_OPTION = 'avaei_blog_cache_keys';
    private const CACHE_STATS_OPTION = 'avaei_blog_cache_stats';
    private const FILTER_CATEGORIES_TRANSIENT = 'avaei_blog_filter_categories';
    private const FILTER_TAGS_TRANSIENT = 'avaei_blog_filter_tags';
    private const CACHE_TTL = 300;
    private const FILTER_CACHE_TTL = 1800;

    public function register_hooks(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'output_resource_hints'], 1);
        add_action('template_redirect', [$this, 'maybe_serve_or_buffer_cache'], 0);
        add_action('admin_post_avaei_purge_blog_cache', [$this, 'handle_manual_purge']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'flush_public_cache']);
        add_action('before_delete_post', [$this, 'flush_cache_on_delete']);
        add_action('set_object_terms', [$this, 'flush_cache_on_term_change'], 10, 6);
        add_action('created_category', [$this, 'flush_filter_cache']);
        add_action('edited_category', [$this, 'flush_filter_cache']);
        add_action('delete_category', [$this, 'flush_filter_cache']);
        add_action('created_post_tag', [$this, 'flush_filter_cache']);
        add_action('edited_post_tag', [$this, 'flush_filter_cache']);
        add_action('delete_post_tag', [$this, 'flush_filter_cache']);
        add_filter('template_include', [$this, 'template_include']);
        add_filter('pre_get_posts', [$this, 'filter_archive_query']);
        add_filter('wp_insert_post_data', [$this, 'validate_post_data'], 10, 2);
        add_filter('redirect_post_location', [$this, 'add_notice_to_redirect'], 10, 2);
        add_action('admin_notices', [$this, 'render_admin_notices']);
    }

    public function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Blog Posts', 'aspirantveda-auto-exam-intelligence'),
                'singular_name' => __('Blog Post', 'aspirantveda-auto-exam-intelligence'),
                'add_new_item' => __('Add New Blog Post', 'aspirantveda-auto-exam-intelligence'),
                'edit_item' => __('Edit Blog Post', 'aspirantveda-auto-exam-intelligence'),
                'new_item' => __('New Blog Post', 'aspirantveda-auto-exam-intelligence'),
                'view_item' => __('View Blog Post', 'aspirantveda-auto-exam-intelligence'),
                'search_items' => __('Search Blog Posts', 'aspirantveda-auto-exam-intelligence'),
                'not_found' => __('No blog posts found.', 'aspirantveda-auto-exam-intelligence'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'has_archive' => 'blog',
            'rewrite' => ['slug' => 'blog', 'with_front' => false],
            'supports' => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions'],
            'taxonomies' => ['category', 'post_tag'],
            'menu_icon' => 'dashicons-welcome-write-blog',
            'capability_type' => 'post',
        ]);
    }

    public function register_admin_menu(): void {
        add_submenu_page(
            'avaei-dashboard',
            __('Manage Blog Posts', 'aspirantveda-auto-exam-intelligence'),
            __('Blog Posts', 'aspirantveda-auto-exam-intelligence'),
            'edit_posts',
            'edit.php?post_type=' . self::POST_TYPE
        );

        add_submenu_page(
            'avaei-dashboard',
            __('Blog Performance', 'aspirantveda-auto-exam-intelligence'),
            __('Blog Performance', 'aspirantveda-auto-exam-intelligence'),
            'manage_options',
            'avaei-blog-performance',
            [$this, 'render_performance_page']
        );
    }

    public function enqueue_assets(): void {
        if (!is_post_type_archive(self::POST_TYPE) && !is_singular(self::POST_TYPE)) {
            return;
        }

        wp_enqueue_style(
            'avaei-blog-style',
            AVAEI_PLUGIN_URL . 'assets/css/blog.css',
            [],
            AVAEI_VERSION
        );
    }

    public function template_include(string $template): string {
        if (is_post_type_archive(self::POST_TYPE)) {
            $archiveTemplate = AVAEI_PLUGIN_DIR . 'templates/archive-avaei_blog.php';
            if (file_exists($archiveTemplate)) {
                return $archiveTemplate;
            }
        }

        if (is_singular(self::POST_TYPE)) {
            $singleTemplate = AVAEI_PLUGIN_DIR . 'templates/single-avaei_blog.php';
            if (file_exists($singleTemplate)) {
                return $singleTemplate;
            }
        }

        return $template;
    }

    public function filter_archive_query(\WP_Query $query): void {
        if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive(self::POST_TYPE)) {
            return;
        }

        $query->set('post_status', 'publish');
        $query->set('posts_per_page', 9);
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
        $query->set('ignore_sticky_posts', true);
        $query->set('cache_results', true);
        $query->set('update_post_meta_cache', true);
        $query->set('update_post_term_cache', true);
        $query->set('lazy_load_term_meta', true);

        if (isset($_GET['blog_search']) && is_string($_GET['blog_search'])) {
            $query->set('s', sanitize_text_field(wp_unslash($_GET['blog_search'])));
        }

        if (isset($_GET['blog_category']) && is_numeric($_GET['blog_category'])) {
            $query->set('cat', (int) $_GET['blog_category']);
        }

        if (isset($_GET['blog_tag']) && is_string($_GET['blog_tag'])) {
            $query->set('tag', sanitize_title(wp_unslash($_GET['blog_tag'])));
        }
    }

    public function validate_post_data(array $data, array $postarr): array {
        if (($data['post_type'] ?? '') !== self::POST_TYPE) {
            return $data;
        }

        if (($data['post_status'] ?? '') === 'auto-draft') {
            return $data;
        }

        $rawTitle = sanitize_text_field($postarr['post_title'] ?? $data['post_title'] ?? '');
        $rawContent = trim(wp_kses_post($postarr['post_content'] ?? $data['post_content'] ?? ''));
        $slugSource = $postarr['post_name'] ?? $data['post_name'] ?? $rawTitle;
        $slug = sanitize_title($slugSource);

        if ($slug === '') {
            $this->set_notice('invalid_slug');
            $data['post_status'] = 'draft';
        } else {
            $postId = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
            $data['post_name'] = wp_unique_post_slug(
                $slug,
                $postId,
                $data['post_status'] ?? 'draft',
                self::POST_TYPE,
                $data['post_parent'] ?? 0
            );
        }

        if (($data['post_status'] ?? '') === 'publish') {
            if ($rawTitle === '' || $rawContent === '') {
                $this->set_notice('missing_required');
                $data['post_status'] = 'draft';
            }
        }

        return $data;
    }

    public function add_notice_to_redirect(string $location, int $postId): string {
        if ($postId <= 0 || get_post_type($postId) !== self::POST_TYPE) {
            return $location;
        }

        $notice = get_transient($this->get_notice_key());
        if ($notice === false) {
            return $location;
        }

        delete_transient($this->get_notice_key());

        return add_query_arg(self::STATUS_QUERY_ARG, rawurlencode((string) $notice), $location);
    }

    public function render_admin_notices(): void {
        if (!is_admin() || !isset($_GET[self::STATUS_QUERY_ARG])) {
            return;
        }

        $notice = sanitize_text_field(wp_unslash($_GET[self::STATUS_QUERY_ARG]));
        $messages = [
            'missing_required' => __('Title and content are required before publishing a blog post. The post was saved as draft.', 'aspirantveda-auto-exam-intelligence'),
            'invalid_slug' => __('A valid slug is required. The post was saved as draft.', 'aspirantveda-auto-exam-intelligence'),
        ];

        if (!isset($messages[$notice])) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($messages[$notice])
        );
    }

    public function maybe_serve_or_buffer_cache(): void {
        if (!$this->is_cacheable_request()) {
            return;
        }

        $cacheKey = $this->build_cache_key();
        $cachedContent = get_transient($cacheKey);
        if (is_string($cachedContent) && $cachedContent !== '') {
            $this->record_cache_stat('hits');
            echo $cachedContent;
            exit;
        }

        $this->record_cache_stat('misses');
        ob_start(function (string $buffer) use ($cacheKey): string {
            if (is_404() || $buffer === '') {
                return $buffer;
            }

            set_transient($cacheKey, $buffer, self::CACHE_TTL);
            $this->register_cache_key($cacheKey, strlen($buffer));

            return $buffer;
        });
    }

    public function flush_public_cache(...$args): void {
        $cacheIndex = get_option(self::CACHE_INDEX_OPTION, []);
        if (!is_array($cacheIndex) || empty($cacheIndex)) {
            $this->record_cache_flush();
            return;
        }

        foreach ($cacheIndex as $indexKey => $cacheValue) {
            $cacheKey = is_string($indexKey) ? $indexKey : (is_string($cacheValue) ? $cacheValue : '');
            if ($cacheKey === '') {
                continue;
            }

            delete_transient($cacheKey);
        }

        update_option(self::CACHE_INDEX_OPTION, [], false);
        $this->record_cache_flush();
    }

    public function flush_cache_on_delete(int $postId): void {
        if ($postId <= 0 || get_post_type($postId) !== self::POST_TYPE) {
            return;
        }

        $this->flush_public_cache();
    }

    public function flush_cache_on_term_change($objectId, $terms, $ttIds, $taxonomy, $append, $oldTtIds): void {
        if (get_post_type((int) $objectId) !== self::POST_TYPE) {
            return;
        }

        if (!in_array($taxonomy, ['category', 'post_tag'], true)) {
            return;
        }

        $this->flush_public_cache();
    }

    public function render_performance_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aspirantveda-auto-exam-intelligence'));
        }

        $metrics = $this->get_cache_metrics();
        $notice = isset($_GET['avaei_perf_notice']) ? sanitize_text_field(wp_unslash($_GET['avaei_perf_notice'])) : '';

        echo '<div class="wrap"><h1>' . esc_html__('Blog Performance', 'aspirantveda-auto-exam-intelligence') . '</h1>';
        if ($notice === 'cache_purged') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Blog cache purged successfully.', 'aspirantveda-auto-exam-intelligence') . '</p></div>';
        }

        echo '<table class="widefat striped" style="max-width: 720px;"><tbody>';
        $this->render_metric_row(__('Cache TTL', 'aspirantveda-auto-exam-intelligence'), sprintf(__('%d seconds', 'aspirantveda-auto-exam-intelligence'), self::CACHE_TTL));
        $this->render_metric_row(__('Cache entries', 'aspirantveda-auto-exam-intelligence'), (string) $metrics['entries']);
        $this->render_metric_row(__('Estimated cache size', 'aspirantveda-auto-exam-intelligence'), size_format((int) $metrics['bytes']));
        $this->render_metric_row(__('Cache hits', 'aspirantveda-auto-exam-intelligence'), (string) $metrics['hits']);
        $this->render_metric_row(__('Cache misses', 'aspirantveda-auto-exam-intelligence'), (string) $metrics['misses']);
        $this->render_metric_row(__('Last cache flush', 'aspirantveda-auto-exam-intelligence'), $metrics['last_flush_human']);
        echo '</tbody></table>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-top:16px;">';
        echo '<input type="hidden" name="action" value="avaei_purge_blog_cache" />';
        wp_nonce_field('avaei_purge_blog_cache', 'avaei_purge_blog_cache_nonce');
        submit_button(__('Purge Blog Cache', 'aspirantveda-auto-exam-intelligence'), 'delete', 'submit', false);
        echo '</form></div>';
    }

    public function handle_manual_purge(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized request', 'aspirantveda-auto-exam-intelligence'));
        }

        if (
            !isset($_POST['avaei_purge_blog_cache_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avaei_purge_blog_cache_nonce'])), 'avaei_purge_blog_cache')
        ) {
            wp_die(__('Invalid security token.', 'aspirantveda-auto-exam-intelligence'));
        }

        $this->flush_public_cache();

        wp_safe_redirect(
            add_query_arg(
                'avaei_perf_notice',
                'cache_purged',
                admin_url('admin.php?page=avaei-blog-performance')
            )
        );
        exit;
    }

    public function output_resource_hints(): void {
        if (!is_post_type_archive(self::POST_TYPE) && !is_singular(self::POST_TYPE)) {
            return;
        }

        $criticalImageUrl = $this->get_critical_image_url();
        if ($criticalImageUrl !== '') {
            printf(
                '<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
                esc_url($criticalImageUrl)
            );
        }

        if (is_post_type_archive(self::POST_TYPE)) {
            $nextPage = get_next_posts_page_link();
            if (is_string($nextPage) && $nextPage !== '') {
                printf(
                    '<link rel="prefetch" href="%s" />' . "\n",
                    esc_url($nextPage)
                );
            }
            return;
        }

        if (is_singular(self::POST_TYPE)) {
            $archiveUrl = get_post_type_archive_link(self::POST_TYPE);
            if (is_string($archiveUrl) && $archiveUrl !== '') {
                printf(
                    '<link rel="prefetch" href="%s" />' . "\n",
                    esc_url($archiveUrl)
                );
            }
        }
    }

    public function flush_filter_cache(...$args): void {
        delete_transient(self::FILTER_CATEGORIES_TRANSIENT);
        delete_transient(self::FILTER_TAGS_TRANSIENT);
    }

    public static function get_archive_filter_options(): array {
        $categories = get_transient(self::FILTER_CATEGORIES_TRANSIENT);
        if (!is_array($categories)) {
            $categoryTerms = get_categories([
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);

            $categories = array_map(static function (\WP_Term $term): array {
                return [
                    'id' => (int) $term->term_id,
                    'name' => $term->name,
                ];
            }, $categoryTerms);
            set_transient(self::FILTER_CATEGORIES_TRANSIENT, $categories, self::FILTER_CACHE_TTL);
        }

        $tags = get_transient(self::FILTER_TAGS_TRANSIENT);
        if (!is_array($tags)) {
            $tagTerms = get_tags([
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);

            $tags = array_map(static function (\WP_Term $term): array {
                return [
                    'slug' => $term->slug,
                    'name' => $term->name,
                ];
            }, $tagTerms);
            set_transient(self::FILTER_TAGS_TRANSIENT, $tags, self::FILTER_CACHE_TTL);
        }

        return [
            'categories' => $categories,
            'tags' => $tags,
        ];
    }

    private function set_notice(string $notice): void {
        set_transient($this->get_notice_key(), $notice, MINUTE_IN_SECONDS);
    }

    private function get_notice_key(): string {
        $userId = get_current_user_id();

        return sprintf('avaei_blog_notice_%d', $userId);
    }

    private function is_cacheable_request(): bool {
        if (is_admin() || is_user_logged_in()) {
            return false;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            return false;
        }

        return is_post_type_archive(self::POST_TYPE) || is_singular(self::POST_TYPE);
    }

    private function build_cache_key(): string {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        $requestUri = $requestUri !== '' ? $requestUri : '/';

        return 'avaei_blog_page_' . md5($requestUri);
    }

    private function register_cache_key(string $cacheKey, int $bytes): void {
        $cacheIndex = get_option(self::CACHE_INDEX_OPTION, []);
        if (!is_array($cacheIndex)) {
            $cacheIndex = [];
        }

        $normalized = [];
        foreach ($cacheIndex as $indexKey => $cacheValue) {
            if (is_string($indexKey) && is_array($cacheValue)) {
                $normalized[$indexKey] = [
                    'bytes' => (int) ($cacheValue['bytes'] ?? 0),
                    'stored_at' => (int) ($cacheValue['stored_at'] ?? time()),
                ];
                continue;
            }

            if (is_string($cacheValue)) {
                $normalized[$cacheValue] = [
                    'bytes' => 0,
                    'stored_at' => time(),
                ];
            }
        }

        $normalized[$cacheKey] = [
            'bytes' => max(0, $bytes),
            'stored_at' => time(),
        ];

        if (count($normalized) > 500) {
            uasort($normalized, static function (array $left, array $right): int {
                return ((int) $left['stored_at']) <=> ((int) $right['stored_at']);
            });
            $normalized = array_slice($normalized, -500, null, true);
        }

        update_option(self::CACHE_INDEX_OPTION, $normalized, false);
    }

    private function record_cache_stat(string $type): void {
        $stats = $this->get_cache_stats();
        if (!in_array($type, ['hits', 'misses'], true)) {
            return;
        }

        $stats[$type] = (int) $stats[$type] + 1;
        update_option(self::CACHE_STATS_OPTION, $stats, false);
    }

    private function record_cache_flush(): void {
        $stats = $this->get_cache_stats();
        $stats['last_flush'] = time();
        $stats['purges'] = (int) $stats['purges'] + 1;
        update_option(self::CACHE_STATS_OPTION, $stats, false);
    }

    private function get_cache_metrics(): array {
        $cacheIndex = get_option(self::CACHE_INDEX_OPTION, []);
        if (!is_array($cacheIndex)) {
            $cacheIndex = [];
        }

        $bytes = 0;
        $entries = 0;
        foreach ($cacheIndex as $indexKey => $cacheValue) {
            if (is_string($indexKey) && is_array($cacheValue)) {
                $entries++;
                $bytes += (int) ($cacheValue['bytes'] ?? 0);
                continue;
            }

            if (is_string($cacheValue)) {
                $entries++;
            }
        }

        $stats = $this->get_cache_stats();
        $lastFlushHuman = __('Never', 'aspirantveda-auto-exam-intelligence');
        if ((int) $stats['last_flush'] > 0) {
            $lastFlushHuman = sprintf(
                /* translators: %s date time */
                __('%s', 'aspirantveda-auto-exam-intelligence'),
                wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $stats['last_flush'])
            );
        }

        return [
            'entries' => $entries,
            'bytes' => $bytes,
            'hits' => (int) $stats['hits'],
            'misses' => (int) $stats['misses'],
            'last_flush_human' => $lastFlushHuman,
        ];
    }

    private function get_cache_stats(): array {
        $stats = get_option(self::CACHE_STATS_OPTION, []);
        if (!is_array($stats)) {
            $stats = [];
        }

        return wp_parse_args($stats, [
            'hits' => 0,
            'misses' => 0,
            'purges' => 0,
            'last_flush' => 0,
        ]);
    }

    private function render_metric_row(string $label, string $value): void {
        printf(
            '<tr><th scope="row" style="width:40%%;">%1$s</th><td>%2$s</td></tr>',
            esc_html($label),
            esc_html($value)
        );
    }

    private function get_critical_image_url(): string {
        if (is_singular(self::POST_TYPE)) {
            $postId = get_queried_object_id();
            if ($postId > 0) {
                $thumbnailId = get_post_thumbnail_id($postId);
                if ($thumbnailId > 0) {
                    $image = wp_get_attachment_image_src($thumbnailId, 'large');
                    if (is_array($image) && isset($image[0])) {
                        return (string) $image[0];
                    }
                }
            }
            return '';
        }

        if (is_post_type_archive(self::POST_TYPE)) {
            global $wp_query;
            if (
                isset($wp_query->posts[0]) &&
                $wp_query->posts[0] instanceof \WP_Post
            ) {
                $thumbnailId = get_post_thumbnail_id($wp_query->posts[0]->ID);
                if ($thumbnailId > 0) {
                    $image = wp_get_attachment_image_src($thumbnailId, 'large');
                    if (is_array($image) && isset($image[0])) {
                        return (string) $image[0];
                    }
                }
            }
        }

        return '';
    }
}
