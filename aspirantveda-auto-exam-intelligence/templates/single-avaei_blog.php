<?php

defined('ABSPATH') || exit;

get_header();
?>
<main class="avaei-blog avaei-blog--single">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('avaei-blog-post'); ?>>
            <header class="avaei-blog-post__header">
                <p class="avaei-blog-post__meta">
                    <?php
                    printf(
                        /* translators: 1 published date, 2 updated date, 3 author display name */
                        esc_html__('Published %1$s · Updated %2$s · By %3$s', 'aspirantveda-auto-exam-intelligence'),
                        esc_html(get_the_date()),
                        esc_html(get_the_modified_date()),
                        esc_html(get_the_author())
                    );
                    ?>
                </p>
                <h1><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="avaei-blog-post__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <figure class="avaei-blog-post__cover">
                    <?php the_post_thumbnail('large'); ?>
                </figure>
            <?php endif; ?>

            <div class="avaei-blog-post__content">
                <?php the_content(); ?>
            </div>

            <?php
            $tags = get_the_tags();
            if (!empty($tags)) :
                ?>
                <footer class="avaei-blog-post__footer">
                    <h2><?php esc_html_e('Tags', 'aspirantveda-auto-exam-intelligence'); ?></h2>
                    <ul class="avaei-blog-post__tags">
                        <?php foreach ($tags as $tag) : ?>
                            <li>
                                <a href="<?php echo esc_url(get_term_link($tag)); ?>">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </footer>
            <?php endif; ?>

            <p class="avaei-blog-post__back">
                <a href="<?php echo esc_url(get_post_type_archive_link('avaei_blog')); ?>">
                    <?php esc_html_e('← Back to blog', 'aspirantveda-auto-exam-intelligence'); ?>
                </a>
            </p>
        </article>
    <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
