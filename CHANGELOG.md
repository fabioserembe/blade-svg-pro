# Changelog

All notable changes to `blade-svg-pro` will be documented in this file

## 1.0.7 - 2025-09-30

### Added
- Laravel 12.x full support
- Laravel Prompts 0.2.x and 0.3.x support
- PHP 8.1+ requirement (aligned with Laravel 12)
- Config file publishing support via `php artisan vendor:publish --tag=blade-svg-pro-config`
- Added missing ext-dom and ext-libxml dependencies in composer.json
- Comprehensive type hints across all methods for better IDE support and type safety

### Changed
- **Code Quality**: Complete code optimization with strict type declarations
  - Added return types to all methods
  - Added parameter types to all method arguments
  - Improved imports with DOMDocument, SimpleXMLElement, RuntimeException
- **ServiceProvider**: Modernized with proper return types and config publishing
- **Facade**: Fixed accessor from 'svg-file-to-blade-component' to 'blade-svg-pro'
- Removed `version` field from composer.json (managed by git tags)
- Updated `laravel/prompts` constraint to support multiple versions
- Updated `spatie/image-optimizer` to ^1.7

### Fixed
- Facade accessor now correctly references 'blade-svg-pro'
- Improved code consistency and maintainability

## 1.0.6 - 2025-03-08

- Update requirements

## 1.0.5 - 2025-03-08

- Update requirements
- Add support for Laravel 12

## 1.0.4 - 2025-03-07

- Update requirements
- Update composer.json version

## 1.0.3 - 2024-11-16

- Added prompt select to choose whether to convert icons to a single file or multiple files
- Refactor and optimization of the entire core code
- Rewrite README.md and added new instructions

## 1.0.2 - 2024-11-16

- Add Flux instructions

## 1.0.1 - 2024-11-16

- Add Flux support
- Fix some bugs

## 1.0.0 - 2024-10-09

- Initial release
