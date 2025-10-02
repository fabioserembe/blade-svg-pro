<?php

namespace FabioSerembe\BladeSVGPro;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class BladeSVGPro extends Command
{
    protected $signature = 'blade-svg-pro:convert {--i=} {--o=} {--flux} {--inline} {--preserve-contrast}';
    protected $description = 'Convert SVGs into a Blade component';

    public function handle()
    {
        $flux = $this->option('flux');
        $inline = $this->option('inline');

        if ($inline) {
            $this->handleInlineConversion($flux);
        } else {
            $input = $this->askForInputDirectory();
            $output = $flux ? resource_path('views/flux/icon') : $this->askForOutputDirectory();

            $type = $flux ? 'multiple' : select(
                label: 'Do you want to convert icons into a single or multiple files?',
                options: [
                    'single' => 'Single file',
                    'multiple' => 'Multiple files',
                ]
            );

            $file_name = ($type === 'single' && !$flux) ? $this->askForFileName($output) : null;

            $this->convertSvgToBlade($input, $output, $file_name, $flux, $type);

            $this->info("\nConversion completed!");
        }
    }

    private function askForInputDirectory(): string
    {
        $input = $this->option('i') ?? text(
            label: 'Specify the path of the SVG directory',
            required: true
        );

        $input = str_replace('\ ', ' ', $input);

        if (!File::isDirectory($input)) {
            $this->error("The directory '$input' does not exist. Please try again");
            return $this->askForInputDirectory();
        }

        return $input;
    }

    private function askForOutputDirectory(): string
    {
        $directories = $this->getAllDirectories(resource_path('views'));

        return $this->option('o') ?? suggest(
            label: 'Specify the path where to save the .blade.php files',
            options: $directories,
            required: true,
        );
    }

    private function getAllDirectories(string $path): array
    {
        if (!File::isDirectory($path)) {
            return [];
        }

        $directories = [];
        $items = File::directories($path);

        foreach ($items as $item) {
            $directories[] = $item;
            $directories = array_merge($directories, $this->getAllDirectories($item));
        }

        return $directories;
    }

    private function askForFileName(string $output): string
    {
        $file_name = text(
            label: 'Specify the name of the file',
            required: true,
            hint: "The name will be automatically converted to kebab-case. (The extension '.blade.php' will be automatically added)",
            transform: fn(string $value) => Str::kebab(preg_replace('/[()]/', '', trim($value)))
        );

        $output_file = "$output/$file_name.blade.php";

        if (File::exists($output_file)) {
            $confirmed = confirm(
                label: "The file '$file_name' already exists, do you want to overwrite it?",
                default: false,
            );

            if (!$confirmed) {
                return $this->askForFileName($output);
            }
        }

        return "$file_name.blade.php";
    }

    private function convertSvgToBlade(string $input, string $output, ?string $file_name = null, bool $flux = false, string $type = 'single'): void
    {
        if (!File::isDirectory($output)) {
            File::makeDirectory($output, 0755, true);
        }

        $optimizerChain = OptimizerChainFactory::create();

        $this->info("Start conversion");

        $svgFiles = File::allFiles($input);
        $this->output->progressStart(count($svgFiles));

        if ($type === 'multiple') {
            foreach ($svgFiles as $svgFile) {
                $data = $this->processSvgFile($svgFile->getPathname(), $optimizerChain);

                if ($data) {
                    $this->writeMultipleFile($output, $data, $flux);
                }

                $this->output->progressAdvance();
            }
        } else {
            $output_file = $output . '/' . $file_name;

            $this->initializeSingleOutputFile($output_file);

            foreach ($svgFiles as $svgFile) {
                $data = $this->processSvgFile($svgFile->getPathname(), $optimizerChain);

                if ($data) {
                    $this->appendToSingleOutputFile($output_file, $data);
                }

                $this->output->progressAdvance();
            }

            $this->finalizeSingleOutputFile($output_file);
        }

        $this->output->progressFinish();
    }

    private function processSvgFile($svgFilePath, $optimizerChain)
    {
        if (pathinfo($svgFilePath, PATHINFO_EXTENSION) !== 'svg') {
            return null;
        }

        $this->optimizeSvg($svgFilePath, $optimizerChain);
        $this->normalizeViewBoxTo24x24($svgFilePath);

        $svg = simplexml_load_file($svgFilePath);

        $iconName = pathinfo($svgFilePath, PATHINFO_FILENAME);
        $kebabCaseIconName = $this->convertToKebabCase($iconName);

        $this->normalizeAttributes($svg);

        $svgDimensions = $this->extractDimensionsFromSvg($svg);

        $preserveAttributes = ['fill', 'stroke-width', 'overflow'];
        $preservedAttributesString = '';

        foreach ($preserveAttributes as $attr) {
            if (isset($svg[$attr])) {
                $value = (string)$svg[$attr];
                if ($attr === 'fill' && $value !== 'none') {
                    continue;
                }
                $preservedAttributesString .= " $attr=\"$value\"";
            }
        }

        $preserveContrast = $this->option('preserve-contrast') || $this->hasWhiteColorsForContrast($svg);
        $this->replaceFillAndStroke($svg, $svgDimensions, false, $preserveContrast);

        $viewBox = $this->getViewBoxFromSvg($svg);
        [$width, $height] = [$svgDimensions['width'], $svgDimensions['height']];

        $svgContent = $this->getInnerSvgContent($svg);

        return compact('kebabCaseIconName', 'svgContent', 'viewBox', 'width', 'height', 'preservedAttributesString');
    }

    private function writeMultipleFile($output, $data, $flux)
    {
        $output_file = $output . '/' . $data['kebabCaseIconName'] . '.blade.php';

        if ($flux) {
            File::put($output_file, "@php \$attributes = \$unescapedForwardedAttributes ?? \$attributes; @endphp\n\n");
            File::append($output_file, "@props([\n\t'variant' => 'outline',\n])\n\n");
            File::append($output_file, "@php\n\$classes = Flux::classes('shrink-0')\n->add(match(\$variant) {\n\t'outline' => '[:where(&)]:size-6',\n\t'solid' => '[:where(&)]:size-6',\n\t'mini' => '[:where(&)]:size-5',\n\t'micro' => '[:where(&)]:size-4',\n});\n@endphp\n\n");

            File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\"" . $data['preservedAttributesString'] . " viewBox=\"{$data['viewBox']}\" {{ \$attributes->class(\$classes) }} data-flux-icon aria-hidden=\"true\">\n");
            File::append($output_file, "{$data['svgContent']}\n</svg>\n");
        } else {
            File::put($output_file, "@props(['name' => null, 'default' => 'size-4'])\n\n");
            File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\"" . $data['preservedAttributesString'] . " viewBox=\"{$data['viewBox']}\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
            File::append($output_file, "{$data['svgContent']}\n</svg>\n");
        }
    }


    private function initializeSingleOutputFile($output_file)
    {
        File::put($output_file, "@props(['name' => null, 'default' => 'size-4'])\n@switch(\$name)\n");
    }

    private function appendToSingleOutputFile($output_file, $data)
    {
        File::append($output_file, "@case('{$data['kebabCaseIconName']}')\n");
        File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\"" . $data['preservedAttributesString'] . " viewBox=\"{$data['viewBox']}\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
        File::append($output_file, "{$data['svgContent']}\n</svg>\n");
        File::append($output_file, "@break\n");
    }

    private function finalizeSingleOutputFile($output_file)
    {
        File::append($output_file, "@endswitch\n");
    }

    private function getViewBoxFromSvg(SimpleXMLElement $svg): string
    {
        if (isset($svg['viewBox'])) {
            return (string)$svg['viewBox'];
        } else {
            $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : 0;
            $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : 0;
            return "0 0 $width $height";
        }
    }

    private function optimizeSvg(string $filePath, $optimizerChain): void
    {
        $optimizerChain->optimize($filePath);
    }

    private function getInnerSvgContent(SimpleXMLElement $svg): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($svg->asXML(), LIBXML_NOXMLDECL | LIBXML_NOBLANKS);

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $innerContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $innerContent .= $dom->saveXML($child);
        }

        return $innerContent;
    }

    private function normalizeAttributes(SimpleXMLElement $element): void
    {
        foreach ($element->attributes() as $name => $value) {
            $value = trim(preg_replace('/\s+/', ' ', (string)$value));
            $element[$name] = $value;
        }

        foreach ($element->children() as $child) {
            $this->normalizeAttributes($child);
        }
    }

    private function replaceFillAndStroke(SimpleXMLElement $element, array $svgDimensions, bool $parentHasFillNone = false, bool $preserveContrast = false): void
    {
        $elementDimensions = $this->getElementDimensions($element);
        $isSecondaryElement = $this->isSecondaryElement($elementDimensions, $svgDimensions);
        $currentHasFillNone = isset($element['fill']) && strtolower(trim((string)$element['fill'])) === 'none';

        $isWhiteColor = function($color) {
            $color = strtolower(trim($color));
            return in_array($color, ['white', '#fff', '#ffffff', 'rgb(255,255,255)', 'rgba(255,255,255,1)']);
        };

        if (isset($element['fill'])) {
            $fillColor = strtolower(trim((string)$element['fill']));
            if ($fillColor !== 'none') {
                if ($preserveContrast && $isWhiteColor($fillColor)) {
                    // Keep white color for contrast
                } else {
                    if ($isSecondaryElement) {
                        $element['fill'] = 'currentColor';
                        $element['opacity'] = '0.3';
                    } else {
                        $element['fill'] = 'currentColor';
                    }
                }
            }
        } else {
            $elementName = $element->getName();
            $hasStroke = isset($element['stroke']);

            if (!$parentHasFillNone && !$hasStroke && !in_array($elementName, ['defs', 'clipPath', 'mask', 'pattern', 'linearGradient', 'radialGradient', 'filter', 'g'])) {
                if ($isSecondaryElement) {
                    $element['fill'] = 'currentColor';
                    $element['opacity'] = '0.3';
                } else {
                    $element['fill'] = 'currentColor';
                }
            }
        }

        if (isset($element['stroke'])) {
            $strokeColor = strtolower(trim((string)$element['stroke']));
            if ($strokeColor === 'transparent' || $strokeColor === 'rgba(0,0,0,0)') {
                $element['stroke'] = 'none';
            } elseif ($strokeColor !== 'none') {
                if ($preserveContrast && $isWhiteColor($strokeColor)) {
                    // Keep white color for contrast
                } else {
                    if (!$isSecondaryElement) {
                        $element['stroke'] = 'currentColor';
                    }
                }
            }
        }

        foreach ($element->children() as $child) {
            $this->replaceFillAndStroke($child, $svgDimensions, $currentHasFillNone || $parentHasFillNone, $preserveContrast);
        }
    }

    private function getElementDimensions(SimpleXMLElement $element): array
    {
        $x = isset($element['x']) ? $this->parseDimension($element['x']) : 0;
        $y = isset($element['y']) ? $this->parseDimension($element['y']) : 0;
        $width = isset($element['width']) ? $this->parseDimension($element['width']) : null;
        $height = isset($element['height']) ? $this->parseDimension($element['height']) : null;

        if ($element->getName() === 'circle') {
            $cx = isset($element['cx']) ? $this->parseDimension($element['cx']) : 0;
            $cy = isset($element['cy']) ? $this->parseDimension($element['cy']) : 0;
            $r = isset($element['r']) ? $this->parseDimension($element['r']) : 0;
            $x = $cx - $r;
            $y = $cy - $r;
            $width = $r * 2;
            $height = $r * 2;
        } elseif ($element->getName() === 'ellipse') {
            $cx = isset($element['cx']) ? $this->parseDimension($element['cx']) : 0;
            $cy = isset($element['cy']) ? $this->parseDimension($element['cy']) : 0;
            $rx = isset($element['rx']) ? $this->parseDimension($element['rx']) : 0;
            $ry = isset($element['ry']) ? $this->parseDimension($element['ry']) : 0;
            $x = $cx - $rx;
            $y = $cy - $ry;
            $width = $rx * 2;
            $height = $ry * 2;
        } elseif ($element->getName() === 'path') {
            $width = null;
            $height = null;
        }

        return [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function isSecondaryElement(array $elementDimensions, array $svgDimensions): bool
    {
        if ($elementDimensions['width'] === null || $elementDimensions['height'] === null) {
            return false;
        }

        $elementArea = $elementDimensions['width'] * $elementDimensions['height'];
        $svgArea = $svgDimensions['width'] * $svgDimensions['height'];

        if ($svgArea == 0) {
            return false;
        }

        $coverage = ($elementArea / $svgArea) * 100;

        if ($coverage >= 90) {
            return true;
        }

        if (
            $elementDimensions['x'] == 0 &&
            $elementDimensions['y'] == 0 &&
            $elementDimensions['width'] == $svgDimensions['width'] &&
            $elementDimensions['height'] == $svgDimensions['height']
        ) {
            return true;
        }

        return false;
    }

    private function extractDimensionsFromSvg(SimpleXMLElement $svg): array
    {
        $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : null;
        $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : null;

        if (!$width || !$height) {
            if (isset($svg['viewBox'])) {
                $viewBox = explode(' ', (string)$svg['viewBox']);
                if (count($viewBox) === 4) {
                    $width = $viewBox[2];
                    $height = $viewBox[3];
                }
            }
        }

        return ['width' => $width, 'height' => $height];
    }

    private function parseDimension($dimension): ?float
    {
        if (preg_match('/^([0-9.]+)(px|pt|%)?$/', (string)$dimension, $matches)) {
            return floatval($matches[1]);
        }

        return null;
    }

    private function convertToKebabCase(string $value): string
    {
        $value = preg_replace('/[()]/', '', trim($value));

        return Str::kebab($value);
    }

    private function hasWhiteColorsForContrast(SimpleXMLElement $element): bool
    {
        $isWhiteColor = function($color) {
            $color = strtolower(trim($color));
            return in_array($color, ['white', '#fff', '#ffffff', 'rgb(255,255,255)', 'rgba(255,255,255,1)']);
        };

        if (isset($element['fill']) && $isWhiteColor((string)$element['fill'])) {
            return true;
        }

        if (isset($element['stroke']) && $isWhiteColor((string)$element['stroke'])) {
            return true;
        }

        foreach ($element->children() as $child) {
            if ($this->hasWhiteColorsForContrast($child)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeViewBoxTo24x24(string $svgFilePath): void
    {
        $svg = simplexml_load_file($svgFilePath);

        $currentViewBox = $this->getViewBoxFromSvg($svg);
        $viewBoxParts = array_map('floatval', explode(' ', $currentViewBox));

        if (count($viewBoxParts) !== 4) {
            return;
        }

        [$minX, $minY, $width, $height] = $viewBoxParts;

        if ($width == 24 && $height == 24 && $minX == 0 && $minY == 0) {
            return;
        }

        $scale = 24 / max($width, $height);
        $scaledWidth = $width * $scale;
        $scaledHeight = $height * $scale;
        $offsetX = (24 - $scaledWidth) / 2;
        $offsetY = (24 - $scaledHeight) / 2;

        $dom = new DOMDocument();
        $dom->loadXML($svg->asXML());
        $svgElement = $dom->documentElement;

        $group = $dom->createElement('g');
        $transformValue = "translate($offsetX $offsetY) scale($scale)";
        $group->setAttribute('transform', $transformValue);

        $children = [];
        foreach ($svgElement->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $children[] = $child;
            }
        }

        foreach ($children as $child) {
            $svgElement->removeChild($child);
            $group->appendChild($child);
        }

        $svgElement->appendChild($group);
        $svgElement->setAttribute('viewBox', '0 0 24 24');
        $svgElement->setAttribute('width', '24');
        $svgElement->setAttribute('height', '24');
        $dom->save($svgFilePath);
    }

    private function handleInlineConversion(bool $flux): void
    {
        $svgContent = $this->option('i') ?? textarea(
            label: 'Paste the SVG code',
            required: true,
            hint: 'Press Ctrl+D when finished'
        );

        $output = $flux ? resource_path('views/flux/icon') : $this->askForOutputDirectory();

        $type = $flux ? 'multiple' : select(
            label: 'Do you want to convert the icon into a single or multiple files?',
            options: [
                'single' => 'Single file',
                'multiple' => 'Multiple files',
            ]
        );

        $iconName = text(
            label: 'Specify the name of the icon',
            required: true,
            hint: 'The name will be automatically converted to kebab-case'
        );

        $kebabCaseIconName = $this->convertToKebabCase($iconName);

        if (!File::isDirectory($output)) {
            File::makeDirectory($output, 0755, true);
        }

        $this->info("Start conversion");

        $data = $this->processInlineSvg($svgContent, $kebabCaseIconName);

        if ($data) {
            if ($type === 'multiple') {
                $this->writeMultipleFile($output, $data, $flux);
            } else {
                $file_name = $this->askForFileName($output);
                $output_file = $output . '/' . $file_name;

                $this->initializeSingleOutputFile($output_file);
                $this->appendToSingleOutputFile($output_file, $data);
                $this->finalizeSingleOutputFile($output_file);
            }

            $this->info("\nConversion completed!");
        } else {
            $this->error("\nFailed to process the SVG content.");
        }
    }

    private function processInlineSvg(string $svgContent, string $iconName)
    {
        $tempFile = sys_get_temp_dir() . '/' . uniqid('svg_') . '.svg';

        try {
            File::put($tempFile, $svgContent);

            $optimizerChain = OptimizerChainFactory::create();
            $this->optimizeSvg($tempFile, $optimizerChain);
            $this->normalizeViewBoxTo24x24($tempFile);

            $svg = simplexml_load_file($tempFile);

            if (!$svg) {
                return null;
            }

            $kebabCaseIconName = $this->convertToKebabCase($iconName);
            $this->normalizeAttributes($svg);
            $svgDimensions = $this->extractDimensionsFromSvg($svg);

            $preserveAttributes = ['fill', 'stroke-width', 'overflow'];
            $preservedAttributesString = '';

            foreach ($preserveAttributes as $attr) {
                if (isset($svg[$attr])) {
                    $value = (string)$svg[$attr];
                    if ($attr === 'fill' && $value !== 'none') {
                        continue;
                    }
                    $preservedAttributesString .= " $attr=\"$value\"";
                }
            }

            $preserveContrast = $this->option('preserve-contrast') || $this->hasWhiteColorsForContrast($svg);
            $this->replaceFillAndStroke($svg, $svgDimensions, false, $preserveContrast);

            $viewBox = $this->getViewBoxFromSvg($svg);
            [$width, $height] = [$svgDimensions['width'], $svgDimensions['height']];

            $svgContent = $this->getInnerSvgContent($svg);

            return compact('kebabCaseIconName', 'svgContent', 'viewBox', 'width', 'height', 'preservedAttributesString');
        } finally {
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }
        }
    }
}