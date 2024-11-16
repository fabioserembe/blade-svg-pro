<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class BladeSVGPro extends Command
{
    protected $signature = 'blade-svg-pro:convert {--i=} {--o=} {--flux}';
    protected $description = 'Convert SVGs into a Blade component';

    public function handle()
    {
        $flux = $this->option('flux');

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

    private function askForInputDirectory()
    {
        $input = $this->option('i') ?? text(
            label: 'Specify the path of the SVG directory',
            required: true
        );

        if (!File::isDirectory($input)) {
            $this->error("The directory '$input' does not exist. Please try again");
            return $this->askForInputDirectory();
        }

        return $input;
    }

    private function askForOutputDirectory()
    {
        $directories = $this->getAllDirectories(resource_path('views'));

        $output = $this->option('o') ?? suggest(
            label: 'Specify the path where to save the .blade.php files',
            options: $directories,
            required: true,
        );

        return $output;
    }

    private function getAllDirectories($path)
    {
        $directories = [];

        if (!File::isDirectory($path)) {
            return $directories;
        }

        $items = File::directories($path);

        foreach ($items as $item) {
            $directories[] = $item;
            $subDirectories = $this->getAllDirectories($item);
            $directories = array_merge($directories, $subDirectories);
        }

        return $directories;
    }

    private function askForFileName($output)
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

            if ($confirmed) {
                return "$file_name.blade.php";
            } else {
                return $this->askForFileName($output);
            }
        }

        return "$file_name.blade.php";
    }

    private function convertSvgToBlade($input, $output, $file_name = null, $flux = false, $type = 'single')
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

        $svg = simplexml_load_file($svgFilePath);

        $iconName = pathinfo($svgFilePath, PATHINFO_FILENAME);
        $kebabCaseIconName = $this->convertToKebabCase($iconName);

        $this->normalizeAttributes($svg);

        $svgDimensions = $this->extractDimensionsFromSvg($svg);

        $this->replaceFillAndStroke($svg, $svgDimensions);

        $viewBox = $this->getViewBoxFromSvg($svg);
        [$width, $height] = [$svgDimensions['width'], $svgDimensions['height']];

        $svgContent = $this->getInnerSvgContent($svg);

        return compact('kebabCaseIconName', 'svgContent', 'viewBox', 'width', 'height');
    }

    private function writeMultipleFile($output, $data, $flux)
    {
        $output_file = $output . '/' . $data['kebabCaseIconName'] . '.blade.php';

        if ($flux) {
            File::put($output_file, "@php \$attributes = \$unescapedForwardedAttributes ?? \$attributes; @endphp\n\n");
            File::append($output_file, "@props([\n\t'variant' => 'outline',\n])\n\n");
            File::append($output_file, "@php\n\$classes = Flux::classes('shrink-0')\n->add(match(\$variant) {\n\t'outline' => '[:where(&)]:size-6',\n\t'solid' => '[:where(&)]:size-6',\n\t'mini' => '[:where(&)]:size-5',\n\t'micro' => '[:where(&)]:size-4',\n});\n@endphp\n\n");

            File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\" viewBox=\"{$data['viewBox']}\" {{ \$attributes->class(\$classes) }} data-flux-icon aria-hidden=\"true\">\n");
            File::append($output_file, "{$data['svgContent']}\n</svg>\n");
        } else {
            File::put($output_file, "@props(['name' => null, 'default' => 'size-4'])\n\n");
            File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\" viewBox=\"{$data['viewBox']}\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
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
        File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$data['width']}\" height=\"{$data['height']}\" viewBox=\"{$data['viewBox']}\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
        File::append($output_file, "{$data['svgContent']}\n</svg>\n");
        File::append($output_file, "@break\n");
    }

    private function finalizeSingleOutputFile($output_file)
    {
        File::append($output_file, "@endswitch\n");
    }

    private function getViewBoxFromSvg(\SimpleXMLElement $svg)
    {
        if (isset($svg['viewBox'])) {
            return (string)$svg['viewBox'];
        } else {
            $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : 0;
            $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : 0;
            return "0 0 $width $height";
        }
    }

    private function optimizeSvg(string $filePath, $optimizerChain)
    {
        $optimizerChain->optimize($filePath);
    }

    private function getInnerSvgContent(\SimpleXMLElement $svg)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($svg->asXML(), LIBXML_NOXMLDECL | LIBXML_NOBLANKS);

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $innerContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $innerContent .= $dom->saveXML($child);
        }

        return $innerContent;
    }

    private function normalizeAttributes(\SimpleXMLElement $element)
    {
        foreach ($element->attributes() as $name => $value) {
            $value = trim(preg_replace('/\s+/', ' ', (string)$value));
            $element[$name] = $value;
        }

        foreach ($element->children() as $child) {
            $this->normalizeAttributes($child);
        }
    }

    private function replaceFillAndStroke(\SimpleXMLElement $element, $svgDimensions)
    {
        $elementDimensions = $this->getElementDimensions($element);

        $isSecondaryElement = $this->isSecondaryElement($elementDimensions, $svgDimensions);

        if (isset($element['fill'])) {
            $fillColor = strtolower(trim((string)$element['fill']));
            if ($fillColor !== 'none') {
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
                if (!$isSecondaryElement) {
                    $element['stroke'] = 'currentColor';
                }
            }
        }

        foreach ($element->children() as $child) {
            $this->replaceFillAndStroke($child, $svgDimensions);
        }
    }

    private function getElementDimensions(\SimpleXMLElement $element)
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

    private function isSecondaryElement($elementDimensions, $svgDimensions)
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

    private function extractDimensionsFromSvg(\SimpleXMLElement $svg)
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

    private function parseDimension($dimension)
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
}