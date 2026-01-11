# Hryvinskyi_SeoRobots

Core module for SEO robots meta tags management in Magento 2.

> **Part of [hryvinskyi/magento2-seo-robots-pack](https://github.com/hryvinskyi/magento2-seo-robots-pack)** - Complete SEO Robots solution for Magento 2

## Description

This module provides the core implementation for managing robots meta tags in Magento 2. It implements the API interfaces defined in `Hryvinskyi_SeoRobotsApi` and provides configuration management for robots directives.

## Features

- Configuration implementation for robots meta tag management
- Support for X-Robots-Tag HTTP header configuration
- Robots directives list management
- Integration with Hryvinskyi SEO framework

## Available Robots Directives

- INDEX, FOLLOW
- NOINDEX, FOLLOW
- INDEX, NOFOLLOW
- NOINDEX, NOFOLLOW
- INDEX, FOLLOW, NOARCHIVE
- NOINDEX, FOLLOW, NOARCHIVE
- INDEX, NOFOLLOW, NOARCHIVE
- NOINDEX, NOFOLLOW, NOARCHIVE

## Configuration

This module provides the backend implementation for configuration. The actual admin UI is provided by `Hryvinskyi_SeoRobotsAdminUi`.

Configuration values are stored in:
- `hryvinskyi_seo/robots/enabled` - Enable/disable robots functionality
- `hryvinskyi_seo/robots/meta_robots` - URL pattern-based robots configuration
- `hryvinskyi_seo/robots/https_meta_robots` - HTTPS-specific robots settings
- `hryvinskyi_seo/robots/is_noindex_nofollow_for_no_route_index` - 404 page robots
- `hryvinskyi_seo/robots/paginated_robots` - Paginated content robots settings
- `hryvinskyi_seo/robots/paginated_robots_type` - Paginated robots type
- `hryvinskyi_seo/robots/robots_xheader_enabled` - X-Robots-Tag header enable/disable

## Dependencies

- Magento 2.4+
- hryvinskyi/magento2-seo
- hryvinskyi/magento2-seo-robots-api

## Installation

This module is typically installed as part of the `hryvinskyi/magento2-seo-robots-pack` metapackage:

```bash
composer require hryvinskyi/magento2-seo-robots-pack
php bin/magento module:enable Hryvinskyi_SeoRobotsApi Hryvinskyi_SeoRobots
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Author

**Volodymyr Hryvinskyi**
- Email: volodymyr@hryvinskyi.com

## License

Proprietary
