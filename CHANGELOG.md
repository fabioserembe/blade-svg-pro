# Changelog

All notable changes to `blade-svg-pro` will be documented in this file

## 1.2.4 - 2026-08-24

### Changed
- Dependency maintenance update: `laravel/prompts` (0.3.16 → 0.3.23), `spatie/image-optimizer` (1.8.1 → 1.10.0), `orchestra/testbench` (11.1.0 → 11.2.0), `pestphp/pest` (4.6.3 → 4.7.8), `phpunit/phpunit` (12.5.23 → 12.5.33)
- No changes to the version constraints in `composer.json`: Laravel 8 → 13 compatibility is unchanged

### Fixed
- The `<flux:icon.* />` reference in the Boost guideline is now wrapped in `@verbatim`. Boost renders `core.blade.php` through `Blade::render()` and neutralizes only `<x-...>` tags, so a bare `<flux:...>` tag could be compiled as a real component. Rendered output is unchanged
- Added a regression test ensuring no component tag is left outside `@verbatim` in the guideline

## 1.2.3 - 2026-06-06

### Changed
- The Laravel Boost skill and guideline now instruct the agent to **choose the conversion mode from the project context** (use `--flux` when the project uses Flux, otherwise standard file mode) and to **ask the user when the context is ambiguous**, instead of defaulting to a single mode

## 1.2.2 - 2026-06-06

### Added
- **Laravel Boost integration**: the package now ships resources for AI agents, installed automatically with `php artisan boost:install` in projects that use Boost
  - AI guideline (`resources/boost/guidelines/core.blade.php`) loaded upfront with a package overview
  - Agent skill (`resources/boost/skills/blade-svg-pro/SKILL.md`) activated on-demand, covering options, naming conventions, 24×24 normalization and color replacement
  - The skill is designed to activate when building a frontend from SVG assets (e.g. icons exported from Figma or fetched via MCP)
- **`--name` option**: sets the icon name in `--inline` mode and the output file name in single-file mode, skipping the related prompt
- **`--mode` option**: sets the output to `single` or `multiple`, skipping the choice prompt (ignored with `--flux`, which is always multiple); invalid values fail fast with a clear error
- **Support for `--i` with a single `.svg` file** in addition to directories, to convert one icon without placing it in a folder
- **Fully non-interactive execution** via Laravel's global `--no-interaction` flag: omitted prompts fall back to sensible defaults (no prefix, `--mode=multiple`); any required value still missing (`--i`, `--o` without `--flux`, `--name` for inline/single-file) makes the command fail with a clear error instead of blocking while waiting for input

### Changed
- All command modes (`--flux`, standard files, `--inline`) are now automatable in CI, scripts and AI agents by passing the corresponding options

## 1.2.1 - 2026-04-20

### Added
- Laravel 13 compatibility

## 1.2.0 - 2026-04-18

### Added
- Laravel 13 support (`illuminate/*: ^13.0`)
- `orchestra/testbench: ^11.0` support for testing on Laravel 13

## 1.1.0 - 2026-04-02

### Added
- **Automatic icon type detection** based on structural analysis of the SVG (stroke vs fill, number of distinct colors, presence of opacity)
  - **Linear/Outline**: stroke → `currentColor`, `fill="none"` preserved
  - **Bold**: same treatment as outline icons
  - **Solid**: fill → `currentColor`, with automatic white preservation for contrast
  - **Duotone**: colors → `currentColor`, existing opacity never overwritten
  - **Bulk**: primary color → `currentColor`, secondary color → `currentColor` with `opacity="0.4"`
- **Automatic conversion of pre-existing `currentColor` to white** for solid icons with a colored background and inner details in `currentColor` (e.g. clock hands, percent symbol, checkmark)
- **Full replacement of every hardcoded color format**: hex (`#fff`, `#000`, `#3B82F6`), named colors (`white`, `black`, `red`), `rgb()`, `rgba()`
- **Recognition of `rgba(...,0)` as transparent**: fills with alpha=0 (e.g. `rgba(255,255,255,0)`) are no longer converted to `currentColor`

### Changed
- The `--preserve-contrast` flag is no longer required: contrast detection is now fully automatic
- Complete refactoring of `replaceFillAndStroke()` with type-based logic
- Removed the `getElementDimensions()` and `isSecondaryElement()` methods (replaced by the new detection system)

### Fixed
- Solid icons with inner details in `currentColor` (e.g. badge-percent, clock) no longer render as colored blocks with no visible details
- Outline icons with `fill="rgba(255,255,255,0)"` (transparent hit-area) no longer produce an opaque `currentColor` block

## 1.0.10 - 2026-04-02

### Added
- **Icon prefix** with `--prefix` option to namespace generated icon names
  - Adds a custom prefix to all generated icon filenames (e.g. `brandname-icon-name.blade.php`)
  - Available via `--prefix=` parameter or interactive prompt
  - Works with all modes: standard, `--flux`, `--inline`, single and multiple file conversion
  - Prefix is automatically converted to kebab-case

## 1.0.9.1 - 2026-01-31

## 1.0.9 - 2025-10-05

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
