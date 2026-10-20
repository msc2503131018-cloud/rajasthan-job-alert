<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Blog;

defined('ABSPATH') || exit;

class BlogManager {
    private const POST_TYPE = 'avaei_blog';
    private const STATUS_QUERY_ARG = 'avaei_blog_notice';

    public function register_hooks(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
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

    private function set_notice(string $notice): void {
        set_transient($this->get_notice_key(), $notice, MINUTE_IN_SECONDS);
    }

    private function get_notice_key(): string {
        $userId = get_current_user_id();

        return sprintf('avaei_blog_notice_%d', $userId);
    }
}
