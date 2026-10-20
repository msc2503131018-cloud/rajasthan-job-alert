# AspirantVeda Auto Exam Intelligence

A WordPress plugin for enterprise-grade AI-powered monitoring and publishing of Rajasthan government jobs, exams, results, admit cards, answer keys, scholarships, university notifications, and education news.

## Installation

1. Upload the `aspirantveda-auto-exam-intelligence` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure sources and AI provider settings from the AspirantVeda admin menu.

## Features

- Source monitoring engine for RSS, XML, HTML, PDF, and sitemap feeds.
- Change detection and duplicate avoidance.
- AI content generation with Gemini, Claude, and OpenAI provider support.
- Auto post generation, category assignment, tags, featured image placeholders, internal linking, and related posts.
- Built-in public blog (`/blog`) with searchable and filterable listing, pagination, and responsive templates.
- Dedicated blog post type with categories/tags, featured image cover support, author metadata, and publish/draft lifecycle.
- Operator-safe blog management from WP Admin with publish validation and slug sanitization.
- Lightweight public-page caching (5-minute transient cache) for blog archive and blog post detail pages with automatic invalidation on blog updates.
- Lazy-loaded, async-decoded blog cover images for improved perceived performance on listing and detail screens.
- Modern React admin dashboard.
- REST API endpoints with authentication and nonce validation.
- Custom database tables with migration and uninstall cleanup.
- Custom cron schedules and queue processing.
- Email alert notifications.

## Blog Usage

1. Open **WP Admin → AspirantVeda → Blog Posts** to create and manage blog entries.
2. Fill title, content, excerpt/summary, cover image, category, and tags.
3. Publish when ready. If title/content/slug is invalid at publish time, the post is safely stored as draft with an admin notice.
4. Public users can browse `/blog`, search, filter by category/tag, and open individual post pages.
5. Public blog pages are cached for 5 minutes and automatically purged when a blog post is created, updated, deleted, or its category/tag assignment changes.

## Developer Guide

- `src/Core/Plugin.php` is the main plugin bootstrap.
- `src/Core/Database/Installer.php` handles activation, deactivation, and uninstall.
- `src/Core/Cron/Scheduler.php` registers custom cron intervals and jobs.
- `src/Core/Services` contains source monitoring, AI content generation, and repository layers.
- `src/API/REST` contains REST route and controller registration.

## Notes

This initial version is scaffolded for enterprise use and follows WordPress coding standards. Additional provider-specific AI integration, advanced scraping, and analytics can be implemented incrementally.
