# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fabioserembe/svg-file-to-blade-component.svg?style=flat-square)](https://packagist.org/packages/fabioserembe/svg-file-to-blade-component)
[![Total Downloads](https://img.shields.io/packagist/dt/fabioserembe/svg-file-to-blade-component.svg?style=flat-square)](https://packagist.org/packages/fabioserembe/svg-file-to-blade-component)
![GitHub Actions](https://github.com/fabioserembe/svg-file-to-blade-component/actions/workflows/main.yml/badge.svg)

# BladeSVGPro
BladeSVGPro is a simple package to convert SVG files into Blade component.

## Installation
Run the following command to install the package:
```bash
composer require fabioserembe/blade-svg-pro --dev
```

## Usage
The usage is very simple, just run the command and follow the prompts:
```php
php artisan blade-svg-pro:convert
```
or use the command adding the input and output directories:
```bash
php artisan blade-svg-pro:convert --i="path/to/svg/directory" --o="path/to/output/directory"
```
The package will parse all SVGs within the input directory (even if they are nested within subdirectories) and insert all SVGs within the blade file, after optimising the file code, removing unnecessary attributes and other improvements.

### Output example
```php
@props(['name' => null, 'default' => 'size-4'])
@switch($name)
@case('chevron-left')
    <svg xmlns="http://www.w3.org/2000/svg" width="5" height="8.746" viewBox="0 0 5 8.746" {{ $attributes->merge(['class' => $default]) }}>
        <path fill="currentColor" d="m1.507 4.371 3.309-3.307a.625.625 0 0 0-.885-.883L.182 3.928a.624.624 0 0 0-.018.862l3.765 3.773a.625.625 0 1 0 .885-.883Z" data-name="Icon ionic-ios-arrow-back"/>
    </svg>
@break
@endswitch
```
#### Use the blade component inside a view
Let us assume that we have exported the icons within a blade file named 'icons' located in the 'views/components' directory.
Within the view blade, we can use this file generated with:
```html
    <x-icons name="chevron-left" />
```
Now you can customise the icon by inserting your preferred Tailwind classes, e.g.
```html
   <x-icons name="chevron-left" class="text-red-500 hover:text-blue-500 ..." />
```

### Icon types actually supported
- Linear
- Bold
- Duotone
- Bulk

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
[Buy me a beer 🍺](https://buymeacoffee.com/fabioserembe)