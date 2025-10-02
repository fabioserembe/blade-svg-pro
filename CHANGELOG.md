# Changelog

All notable changes to `blade-svg-pro` will be documented in this file

## 1.0.8 - 2025-10-02

### Added
- **Inline SVG conversion** with `--inline` option to convert SVG code directly without requiring a file
  - Support for `textarea()` prompt to paste multi-line SVG code
  - Works with both standard and `--flux` modes
- **Automatic viewBox normalization** to 24x24 standard size
  - Automatically scales and centers icons to fit 24x24 viewBox
  - Maintains aspect ratio and vector quality
  - Improves consistency across icon sets
- **Smart white color preservation** for solid icons with contrast elements
  - Automatically detects white colors (`white`, `#fff`, `#ffffff`) used for contrast in solid icons
  - Preserves white fills and strokes instead of converting to `currentColor`
  - Ensures proper visibility of contrast elements (e.g., checkmarks on shields, crosses on badges)
  - Optional `--preserve-contrast` flag for manual override when needed

### Fixed
- **Fill/Stroke detection** for stroke-only icons
  - Icons with `stroke` attribute no longer receive unwanted `fill="currentColor"`
  - Properly handles `fill="none"` inheritance from parent elements
  - Fixes issue where outline icons appeared filled
- **Missing fill attribute** handling
  - Automatically adds `fill="currentColor"` to elements without explicit fill (when appropriate)
  - Ensures icons respond to text color classes (e.g., `text-indigo-500`)
- **Solid icons with white contrast elements** now display correctly
  - White strokes and fills are preserved automatically
  - No more "all black" solid icons with invisible contrast elements

### Changed
- Enhanced `replaceFillAndStroke()` logic to distinguish between fill-based and stroke-based icons
- Improved attribute inheritance for better SVG rendering
- Code cleanup: removed unnecessary comments and translated Italian comments to English

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
