<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Blog;

defined('ABSPATH') || exit;

class BlogManager {
    private const POST_TYPE = 'avaei_blog';
    private const STATUS_QUERY_ARG = 'avaei_blog_notice';
    private const CACHE_INDEX_OPTION = 'avaei_blog_cache_keys';
    private const CACHE_TTL = 300;

    public function register_hooks(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('template_redirect', [$this, 'maybe_serve_or_buffer_cache'], 0);
        add_action('save_post_' . self::POST_TYPE, [$this, 'flush_public_cache']);
        add_action('before_delete_post', [$this, 'flush_cache_on_delete']);
        add_action('set_object_terms', [$this, 'flush_cache_on_term_change'], 10, 6);
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
            echo $cachedContent;
            exit;
        }

        ob_start(function (string $buffer) use ($cacheKey): string {
            if (is_404() || $buffer === '') {
                return $buffer;
            }

            set_transient($cacheKey, $buffer, self::CACHE_TTL);
            $this->register_cache_key($cacheKey);

            return $buffer;
        });
    }

    public function flush_public_cache(): void {
        $cacheKeys = get_option(self::CACHE_INDEX_OPTION, []);
        if (!is_array($cacheKeys) || empty($cacheKeys)) {
            return;
        }

        foreach ($cacheKeys as $cacheKey) {
            if (!is_string($cacheKey) || $cacheKey === '') {
                continue;
            }

            delete_transient($cacheKey);
        }

        update_option(self::CACHE_INDEX_OPTION, [], false);
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

    private function register_cache_key(string $cacheKey): void {
        $cacheKeys = get_option(self::CACHE_INDEX_OPTION, []);
        if (!is_array($cacheKeys)) {
            $cacheKeys = [];
        }

        if (!in_array($cacheKey, $cacheKeys, true)) {
            $cacheKeys[] = $cacheKey;
        }

        if (count($cacheKeys) > 500) {
            $cacheKeys = array_slice($cacheKeys, -500);
        }

        update_option(self::CACHE_INDEX_OPTION, array_values(array_unique($cacheKeys)), false);
    }
}
