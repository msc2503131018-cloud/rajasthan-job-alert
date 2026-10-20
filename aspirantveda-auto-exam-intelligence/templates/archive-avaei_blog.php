<?php

defined('ABSPATH') || exit;

get_header();

$searchValue = '';
if (isset($_GET['blog_search']) && is_string($_GET['blog_search'])) {
    $searchValue = sanitize_text_field(wp_unslash($_GET['blog_search']));
}

$selectedCategory = isset($_GET['blog_category']) ? (int) $_GET['blog_category'] : 0;
$selectedTag = '';
if (isset($_GET['blog_tag']) && is_string($_GET['blog_tag'])) {
    $selectedTag = sanitize_title(wp_unslash($_GET['blog_tag']));
}

$categories = get_categories(['hide_empty' => false]);
$tags = get_tags(['hide_empty' => false]);
?>
<main class="avaei-blog avaei-blog--archive">
    <section class="avaei-blog__hero">
        <h1><?php esc_html_e('AspirantVeda Blog', 'aspirantveda-auto-exam-intelligence'); ?></h1>
        <p><?php esc_html_e('Latest updates, explainers, and exam guidance for Rajasthan aspirants.', 'aspirantveda-auto-exam-intelligence'); ?></p>
    </section>

    <form class="avaei-blog__filters" action="<?php echo esc_url(get_post_type_archive_link('avaei_blog')); ?>" method="get" aria-label="<?php esc_attr_e('Filter blog posts', 'aspirantveda-auto-exam-intelligence'); ?>">
        <label>
            <span><?php esc_html_e('Search', 'aspirantveda-auto-exam-intelligence'); ?></span>
            <input type="search" name="blog_search" value="<?php echo esc_attr($searchValue); ?>" placeholder="<?php esc_attr_e('Search blog posts…', 'aspirantveda-auto-exam-intelligence'); ?>">
        </label>

        <label>
            <span><?php esc_html_e('Category', 'aspirantveda-auto-exam-intelligence'); ?></span>
            <select name="blog_category">
                <option value="0"><?php esc_html_e('All categories', 'aspirantveda-auto-exam-intelligence'); ?></option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr((string) $category->term_id); ?>" <?php selected($selectedCategory, (int) $category->term_id); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span><?php esc_html_e('Tag', 'aspirantveda-auto-exam-intelligence'); ?></span>
            <select name="blog_tag">
                <option value=""><?php esc_html_e('All tags', 'aspirantveda-auto-exam-intelligence'); ?></option>
                <?php foreach ($tags as $tag) : ?>
                    <option value="<?php echo esc_attr($tag->slug); ?>" <?php selected($selectedTag, $tag->slug); ?>>
                        <?php echo esc_html($tag->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit"><?php esc_html_e('Apply', 'aspirantveda-auto-exam-intelligence'); ?></button>
    </form>

    <?php if (have_posts()) : ?>
        <div class="avaei-blog__grid">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('avaei-blog-card'); ?>>
                    <a class="avaei-blog-card__media" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php
                            the_post_thumbnail('large', [
                                'loading' => 'lazy',
                                'decoding' => 'async',
                            ]);
                            ?>
                        <?php else : ?>
                            <span><?php esc_html_e('No cover image', 'aspirantveda-auto-exam-intelligence'); ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="avaei-blog-card__body">
                        <p class="avaei-blog-card__meta">
                            <?php
                            echo esc_html(get_the_date());
                            echo ' · ';
                            printf(
                                /* translators: %s author display name */
                                esc_html__('By %s', 'aspirantveda-auto-exam-intelligence'),
                                esc_html(get_the_author())
                            );
                            ?>
                        </p>
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p><?php echo esc_html(get_the_excerpt()); ?></p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <nav class="avaei-blog__pagination" aria-label="<?php esc_attr_e('Blog pagination', 'aspirantveda-auto-exam-intelligence'); ?>">
            <?php
            echo wp_kses_post((string) paginate_links([
                'prev_text' => __('← Previous', 'aspirantveda-auto-exam-intelligence'),
                'next_text' => __('Next →', 'aspirantveda-auto-exam-intelligence'),
            ]));
            ?>
        </nav>
    <?php else : ?>
        <section class="avaei-blog__empty" aria-live="polite">
            <h2><?php esc_html_e('No blog posts found', 'aspirantveda-auto-exam-intelligence'); ?></h2>
            <p><?php esc_html_e('Try changing your search or filters, or check back later for new updates.', 'aspirantveda-auto-exam-intelligence'); ?></p>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
