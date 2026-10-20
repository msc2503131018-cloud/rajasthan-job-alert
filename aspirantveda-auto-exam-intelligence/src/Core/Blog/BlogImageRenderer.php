<?php

namespace AspirantVeda\AutoExamIntelligence\Core\Blog;

defined('ABSPATH') || exit;

class BlogImageRenderer {
    public static function render_thumbnail(int $postId, string $size, array $attributes = []): string {
        $thumbnailId = get_post_thumbnail_id($postId);
        if ($thumbnailId <= 0) {
            return '';
        }

        $attributes = wp_parse_args($attributes, [
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);

        $fallback = wp_get_attachment_image($thumbnailId, $size, false, $attributes);
        if (!is_string($fallback) || $fallback === '') {
            return '';
        }

        $imageSource = wp_get_attachment_image_src($thumbnailId, $size);
        if (!is_array($imageSource) || !isset($imageSource[0])) {
            return $fallback;
        }

        $baseUrl = (string) $imageSource[0];
        $sources = [];

        $avif = self::build_variant_url_if_exists($baseUrl, 'avif');
        if ($avif !== '') {
            $sources[] = sprintf('<source srcset="%s" type="image/avif">', esc_url($avif));
        }

        $webp = self::build_variant_url_if_exists($baseUrl, 'webp');
        if ($webp !== '') {
            $sources[] = sprintf('<source srcset="%s" type="image/webp">', esc_url($webp));
        }

        if (empty($sources)) {
            return $fallback;
        }

        return '<picture>' . implode('', $sources) . $fallback . '</picture>';
    }

    private static function build_variant_url_if_exists(string $imageUrl, string $extension): string {
        $uploads = wp_get_upload_dir();
        if (
            empty($uploads['baseurl']) ||
            empty($uploads['basedir']) ||
            strpos($imageUrl, (string) $uploads['baseurl']) !== 0
        ) {
            return '';
        }

        $relativePath = ltrim(substr($imageUrl, strlen((string) $uploads['baseurl'])), '/');
        $filePath = trailingslashit((string) $uploads['basedir']) . $relativePath;
        $variantPath = preg_replace('/\.[a-zA-Z0-9]+$/', '.' . $extension, $filePath);
        if (!is_string($variantPath) || !file_exists($variantPath)) {
            return '';
        }

        $variantUrl = preg_replace('/\.[a-zA-Z0-9]+$/', '.' . $extension, $imageUrl);
        if (!is_string($variantUrl)) {
            return '';
        }

        return $variantUrl;
    }
}
