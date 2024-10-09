<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class BladeSVGPro extends Command
{
    // The name and description of the command
    protected $signature = 'blade-svg-pro:convert {--i=} {--o=}';
    protected $description = 'Convert SVGs into a Blade component';

    protected $file_name;

    public function handle()
    {
        // Input directory path
        $input = $this->askForInputDirectory();

        // Output directory path
        $output = $this->askForOutputDirectory();

        // File name
        $this->file_name = $this->askForFileName($output);

        // Conversion
        $this->convertSvgToBlade($input, $output, $this->file_name);

        $this->info("Conversion completed!");
    }

    private function askForInputDirectory()
    {
        $input = $this->option('i') ?? text(
            label: 'Specify the path of the SVG directory',
            required: true
        );
        if (!File::isDirectory($input)) {
            $this->error("The directory '$input' does not exist. Please try again");
            $input = $this->askForInputDirectory();
        }

        return $input;
    }

    private function askForOutputDirectory()
    {
        $directories = $this->getAllDirectories(resource_path('views'));

        $output = $this->option('o') ?? suggest(
            label: 'Specify the path where to save the file .blade.php',
            options: $directories,
            required: true,
        );

        return $output;
    }

    private function getAllDirectories($path)
    {
        $directories = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir() && !$iterator->isDot()) {
                $directories[] = $file->getPathname();
            }
        }
        return $directories;
    }

    private function askForFileName($output)
    {
        $this->file_name = text(
            label: 'Specify the name of the file',
            required: true,
            hint: "The name will convert automatically to kebab-case. (The extension '.blade.php' will be automatically added)",
            transform: fn(string $value) => str()->kebab($value)
        );

        $output_file = "$output/$this->file_name.blade.php";

        if (File::exists($output_file)) {
            $confirmed = confirm(
                label: "The file '$this->file_name' already exists, do you want to overwrite it?",
                default: false,
            );

            if($confirmed) {
                return "$this->file_name.blade.php";
            } else {
                $this->askForFileName($output);
            }
        }

        return "$this->file_name.blade.php";
    }

    private function convertToKebabCase(string $value): string
    {
        // Remove any extra spaces
        $value = trim($value);

        // Replace all dashes with spaces around them with a single dash without spaces
        $value = preg_replace('/\s*-\s*/', '-', $value);

        // Remove open and closed parentheses
        $value = preg_replace('/[()]/', '', $value);

        // Replace all remaining spaces and underscores with a dash
        $value = preg_replace('/[\s_]+/', '-', $value);

        // Convert everything to lowercase
        $value = strtolower($value);

        return $value;
    }

    private function convertSvgToBlade($input, $output, $file_name)
    {
        // Create the optimizer
        $optimizerChain = OptimizerChainFactory::create();

        // File di output
        $output_file = $output.'/'.$this->file_name;

        // Initialize the content of the blade file
        File::put($output_file, "@props(['name' => null, 'default' => 'size-4'])\n@switch(\$name)\n");

        // Get all SVG files from the directory and subdirectories
        $svgFiles = File::allFiles($input);

        // Use a progress bar to show the progress status
        $this->output->progressStart(count($svgFiles));

        // Iterate over all SVG files in the directory and subdirectories
        foreach (File::allFiles($input) as $svgFile) {
            if ($svgFile->getExtension() === 'svg') {
                // Optimize the SVG file
                $this->optimizeSvg($svgFile->getPathname(), $optimizerChain);

                // Get the icon name without extension
                $iconName = $svgFile->getFilenameWithoutExtension();

                // Convert to kebab-case
                $kebabCaseIconName = $this->convertToKebabCase($iconName);

                // Read and process the SVG content
                $svgContent = $this->processSvgContent($svgFile->getPathname());

                // Extract width and height dimensions
                [$width, $height] = $this->extractDimensions($svgFile->getPathname());

                if ($width !== $height) {
                    // Rectangle shape?
                    $viewBoxWidth = $width;
                    $viewBoxHeight = $height;
                } else {
                    // Square shape?
                    if ($width < 24 && $height < 24) {
                        $viewBoxWidth = $width;
                        $viewBoxHeight = $height;
                    } else {
                        $viewBoxWidth = 24;
                        $viewBoxHeight = 24;
                    }
                }

                $viewBoxWH = "$viewBoxWidth $viewBoxHeight";

                // Add the case to the Blade file
                File::append($output_file, "@case('$kebabCaseIconName')\n");
                File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"$width\" height=\"$height\" viewBox=\"0 0 $viewBoxWH\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
                File::append($output_file, "$svgContent\n</svg>\n");

                // Close the case
                File::append($output_file, "@break\n");
            }

            // Advance the progress bar by one step
            $this->output->progressAdvance();
        }

        // Close the progress bar
        $this->output->progressFinish();

        // Close the switch
        File::append($output_file, "@endswitch\n");
    }

    private function optimizeSvg(string $filePath, $optimizerChain)
    {
        // Execute optimization of the SVG file
        $optimizerChain->optimize($filePath);
    }

    private function processSvgContent(string $filePath): string
    {
        // Load the SVG using SimpleXML
        $svg = simplexml_load_file($filePath);

        // Remove any extra spaces and normalize attributes
        $this->normalizeAttributes($svg);

        // Get SVG dimensions
        $svgDimensions = $this->getSvgDimensions($svg);

        // Replace fill and stroke with appropriate values
        $this->replaceFillAndStroke($svg, $svgDimensions);

        // Convert SimpleXMLElement to DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($svg->asXML(), LIBXML_NOXMLDECL);

        // Remove the XML declaration
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Get the inner content of the <svg> node, excluding the <svg> tag itself
        $innerContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $innerContent .= $dom->saveXML($child);
        }

        return $innerContent;
    }

    private function normalizeAttributes(\SimpleXMLElement $element)
    {
        // Remove extra spaces from attributes
        foreach ($element->attributes() as $name => $value) {
            $value = trim(preg_replace('/\s+/', ' ', (string) $value));
            $element[$name] = $value;
        }

        // Process children recursively
        foreach ($element->children() as $child) {
            $this->normalizeAttributes($child);
        }
    }

    private function getSvgDimensions(\SimpleXMLElement $svg)
    {
        $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : null;
        $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : null;

        // If width and height are not specified, try to extract from viewBox
        if (!$width || !$height) {
            if (isset($svg['viewBox'])) {
                $viewBox = explode(' ', (string) $svg['viewBox']);
                if (count($viewBox) === 4) {
                    $width = $viewBox[2];
                    $height = $viewBox[3];
                }
            }
        }

        return ['width' => $width, 'height' => $height];
    }

    private function replaceFillAndStroke(\SimpleXMLElement $element, $svgDimensions)
    {
        // Calculate the bounding box of the element
        $elementDimensions = $this->getElementDimensions($element);

        // Determine if the element is a background
        $isSecondaryElement = $this->isSecondaryElement($elementDimensions, $svgDimensions);

        // Manage fill
        if (isset($element['fill'])) {
            $fillColor = strtolower(trim((string) $element['fill']));
            if ($fillColor !== 'none') {
                if ($isSecondaryElement) {
                    // Secondary element
                    $element['fill'] = 'currentColor';
                    $element['opacity'] = '0.3';
                } else {
                    // Primary element
                    $element['fill'] = 'currentColor';
                }
            }
        }

        // Manage stroke
        if (isset($element['stroke'])) {
            $strokeColor = strtolower(trim((string) $element['stroke']));
            if ($strokeColor === 'transparent' || $strokeColor === 'rgba(0,0,0,0)') {
                $element['stroke'] = 'none';
            } elseif ($strokeColor !== 'none') {
                // Set stroke="currentColor" only if the element is not a background
                if (!$isSecondaryElement) {
                    $element['stroke'] = 'currentColor';
                }
            }
        }

        // Process children recursively
        foreach ($element->children() as $child) {
            $this->replaceFillAndStroke($child, $svgDimensions);
        }
    }

    private function getElementDimensions(\SimpleXMLElement $element)
    {
        // Get x, y, width, height attributes
        $x = isset($element['x']) ? $this->parseDimension($element['x']) : 0;
        $y = isset($element['y']) ? $this->parseDimension($element['y']) : 0;
        $width = isset($element['width']) ? $this->parseDimension($element['width']) : null;
        $height = isset($element['height']) ? $this->parseDimension($element['height']) : null;

        // For elements like <circle> and <ellipse>, calculate width and height
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
            // For paths, we could use getBBox, but SimpleXML does not support it, return null
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
        // If we can't determine the element's dimensions, assume it's not a background
        if ($elementDimensions['width'] === null || $elementDimensions['height'] === null) {
            return false;
        }

        // Calculate the coverage percentage relative to the SVG
        $elementArea = $elementDimensions['width'] * $elementDimensions['height'];
        $svgArea = $svgDimensions['width'] * $svgDimensions['height'];

        if ($svgArea == 0) {
            return false;
        }

        $coverage = ($elementArea / $svgArea) * 100;

        // If the element covers more than 90% of the SVG area, consider it a background
        if ($coverage >= 90) {
            return true;
        }

        // Check if the element starts at the origin and has the same dimensions as the SVG
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

    private function extractDimensions(string $filePath)
    {
        // Load the SVG as SimpleXMLElement
        $svg = simplexml_load_file($filePath);

        // Extract width and height dimensions, handling different units
        $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : null;
        $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : null;

        // If width and height are not specified, try to extract from viewBox
        if (!$width || !$height) {
            if (isset($svg['viewBox'])) {
                $viewBox = explode(' ', (string) $svg['viewBox']);
                if (count($viewBox) === 4) {
                    $width = $viewBox[2];
                    $height = $viewBox[3];
                }
            }
        }

        return [$width, $height];
    }

    private function parseDimension($dimension)
    {
        // Remove any measurement units (e.g., "px", "pt", "%")
        if (preg_match('/^([0-9.]+)(px|pt|%)?$/', (string) $dimension, $matches)) {
            return floatval($matches[1]);
        }

        // Default value if no match
        return null;
    }
}