## BladeSVGPro
Simplify the implementation of custom icons and use them in your Laravel project by using TailwindCSS classes for styling.

**BladeSVGPro** is a package that simplifies the conversion of SVG files into Blade components for Laravel projects. It allows you to convert SVG icons from an input folder into a single or multiple `.blade.php` files. Additionally, it offers support for custom icons compatible with the Flux package.

## Requirements
Ensure you have the following requirements to use BladeSVGPro:

- PHP: ^8.0
- PHP Extension: `ext-simplexml`
- Laravel core: >= 10.x
- Additional Packages:
    - `laravel/prompts`: ^0.1.25
    - `spatie/image-optimizer`: ^1.7

Make sure all required components are correctly installed in your environment to ensure the proper functioning of the package.

## Installation
Run the following command to install the package:
```bash
composer require fabioserembe/blade-svg-pro --dev
```

## Usage
BladeSVGPro offers a straightforward interface to convert your SVG files:

#### Basic Usage Example
Run the command and follow the prompts:
```php
php artisan blade-svg-pro:convert
```
#### Usage with Parameters
You can specify the input and output directories using the `--i` and `--o` options:
```bash
php artisan blade-svg-pro:convert --i="path/to/svg/directory" --o="path/to/output/directory"
```
#### Available Options
- `--i`: Specifies the path to the folder containing the SVGs to be converted (or SVG code when used with `--inline`).
- `--o`: Specifies the path to the folder where the generated .blade.php files will be saved.
- `--flux`: Enables support for custom icons compatible with the Flux package.
- `--inline`: Enables inline SVG conversion mode, allowing you to paste SVG code directly instead of using files.
- `--preserve-contrast`: Manually forces preservation of white colors for contrast elements (auto-detected by default).

---
#### Inline SVG Conversion
Convert SVG code directly without needing a file by using the `--inline` option:
```bash
php artisan blade-svg-pro:convert --inline
```
When prompted, paste your SVG code and press **Ctrl+D** to finish.

You can also pass the SVG code directly via the `--i` option:
```bash
php artisan blade-svg-pro:convert --inline --i='<svg>...</svg>'
```

**Features**:
- Works with both standard and `--flux` modes
- Automatically normalizes viewBox to 24x24 standard size
- Supports multi-line SVG code via textarea prompt
- Applies the same optimizations and transformations as file-based conversion

**Example with Flux**:
```bash
php artisan blade-svg-pro:convert --inline --flux
```

---
#### Flux support
To convert icons into a format compatible with Flux custom icons, use the `--flux` parameter:
```bash
php artisan blade-svg-pro:convert --flux
```
**Note**: When using `--flux`, the output directory is automatically set to `resources/views/flux/icon` as required by the [Flux documentation](https://fluxui.dev/components/icon#custom-icons). If the path does not exist, it will be created automatically.

---
#### Converting into Single or Multiple .blade files
When running the command without the `--flux` option, you will be prompted to choose whether you want to convert the icons into a single file or multiple files:
- Single File: All icons are inserted into one `.blade.php` file.
- Multiple Files: Each icon is saved in a separate `.blade.php` file.
#### Example for Single file conversion
```bash
php artisan blade-svg-pro:convert --i="path/to/svg/directory" --o="path/to/output/directory"
```
Follow the prompts and choose "Single file" when asked.
#### Example for Multiple file conversion
```bash
php artisan blade-svg-pro:convert --i="path/to/svg/directory" --o="path/to/output/directory"
```
Follow the prompts and choose "Multiple file" when asked.

---
### Output example
#### Single .blade file
If you choose to convert into a single file, the output will look similar to this:
```php
@props(['name' => null, 'default' => 'size-4'])
@switch($name)
@case('chevron-left')
    <svg xmlns="http://www.w3.org/2000/svg" width="5" height="8.746" viewBox="0 0 5 8.746" {{ $attributes->merge(['class' => $default]) }}>
        <path fill="currentColor" d="m1.507 4.371 3.309-3.307a.625.625 0 0 0-.885-.883L.182 3.928a.624.624 0 0 0-.018.862l3.765 3.773a.625.625 0 1 0 .885-.883Z" />
    </svg>
@break
// More cases...
@endswitch
```
#### Use the blade component in a view
Let us assume that we have exported the icons within a blade file named `icons` located in the `views/components` directory.
Within the view blade, we can use this file generated with:
```html
<x-icons name="chevron-left" />
```
You can customize the icon by adding your preferred Tailwind classes:
```html
<x-icons name="chevron-left" class="text-red-500 hover:text-blue-500 ..." />
```

---
#### Multiple .blade files
If you choose to convert into multiple files, each icon will have its own `.blade.php` file.
#### Example for the `chevron-left.blade.php` icon
```php
@props(['name' => null, 'default' => 'size-4'])
<svg xmlns="http://www.w3.org/2000/svg" width="5" height="8.746" viewBox="0 0 5 8.746" {{ $attributes->merge(['class' => $default]) }}>
    <path fill="currentColor" d="M1.507 4.371L4.816 1.064a.625.625 0 0 0-.885-.883L.182 3.928a.624.624 0 0 0-.018.862l3.765 3.773a.625.625 0 1 0 .885-.883Z"/>
</svg>
```
#### Using blade component in a view
Let us assume that we have exported the blade files in the `views/components/icons` directory.
```html
<x-icons.chevron-left />
```
You can customize the icon by adding your preferred Tailwind classes:
```html
<x-icons.chevron-left class="text-red-500 hover:text-blue-500 ..." />
```

___
### Flux support
When you use the `--flux` option, the icons are generated in a format compatible with the [Flux](https://fluxui.dev) package.
#### Example of a generated file
```php
@php $attributes = $unescapedForwardedAttributes ?? $attributes; @endphp

@props([
    'variant' => 'outline',
])

@php
$classes = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => '[:where(&)]:size-6',
        'solid' => '[:where(&)]:size-6',
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
    });
@endphp

<svg xmlns="http://www.w3.org/2000/svg" width="5" height="8.746" viewBox="0 0 5 8.746" {{ $attributes->class($classes) }} data-flux-icon aria-hidden="true">
    <path fill="currentColor" d="M1.507 4.371L4.816 1.064a.625.625 0 0 0-.885-.883L.182 3.928a.624.624 0 0 0-.018.862l3.765 3.773a.625.625 0 1 0 .885-.883Z"/>
</svg>
```
#### Using the blade component with Flux in a view
```html
<flux:icon.chevron-left variant="solid" class="text-red-500 hover:text-blue-500 ..." />
```

___
### Smart White Color Preservation
BladeSVGPro automatically detects and preserves white colors used for contrast in solid icons (e.g., checkmarks on shields, crosses on badges).

**How it works:**
- The converter automatically scans SVG files for white colors (`white`, `#fff`, `#ffffff`)
- When detected, these colors are preserved instead of being converted to `currentColor`
- This ensures solid icons with contrast elements display correctly

**Example:**
```svg
<!-- Original SVG with white stroke for contrast -->
<svg>
  <path fill="currentColor" d="...shield path..." />
  <path fill="none" stroke="white" d="...checkmark path..." />
</svg>
```

After conversion, the white stroke is automatically preserved:
```php
<svg>
  <path fill="currentColor" d="...shield path..." />
  <path fill="none" stroke="white" d="...checkmark path..." />
</svg>
```

**Manual override (optional):**
If you need to force preservation of white colors, use the `--preserve-contrast` flag:
```bash
php artisan blade-svg-pro:convert --inline --flux --preserve-contrast
```

**Note:** This feature works automatically for both file-based and inline conversions.

___
### Currently supported icon types
- Linear
- Bold
- Duotone
- Bulk
- Solid (with automatic white contrast preservation)

---
## Issues and bugs
Please report any issues or bugs in the [issues section](https://github.com/fabioserembe/blade-svg-pro/issues).

## Suggestions
If you have any suggestions, write to me at [fabio.serembe@gmail.com](mailto:fabio.serembe@gmail.com).

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security
If you discover any security related issues, please email fabio.serembe@gmail.com instead of using the issue tracker.

## Credits
-   [Fabio Serembe](https://github.com/fabioserembe)

## Do you like this package?
If you like this package and find it useful, please [Buy me a beer 🍺](https://buymeacoffee.com/fabioserembe)

Thanks for your support! 🤙🏻