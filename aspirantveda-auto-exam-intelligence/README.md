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
- Modern React admin dashboard.
- REST API endpoints with authentication and nonce validation.
- Custom database tables with migration and uninstall cleanup.
- Custom cron schedules and queue processing.
- Email alert notifications.

## Developer Guide

- `src/Core/Plugin.php` is the main plugin bootstrap.
- `src/Core/Database/Installer.php` handles activation, deactivation, and uninstall.
- `src/Core/Cron/Scheduler.php` registers custom cron intervals and jobs.
- `src/Core/Services` contains source monitoring, AI content generation, and repository layers.
- `src/API/REST` contains REST route and controller registration.

## Notes

This initial version is scaffolded for enterprise use and follows WordPress coding standards. Additional provider-specific AI integration, advanced scraping, and analytics can be implemented incrementally.
