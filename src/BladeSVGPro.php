<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class BladeSVGPro extends Command
{
    // Il nome e la descrizione del command
    protected $signature = 'blade-svg-pro:convert {--i=} {--o=}';
    protected $description = 'Converte file SVG massivamente in un componente Blade';

    public function handle()
    {
        // Input directory path
        $input = $this->askForInputDirectory();

        // Output directory path
        $output = $this->askForOutputDirectory();

        // File name
        $file_name = $this->askForFileName();

        // Conversion
        $this->convertSvgToBlade($input, $output, $file_name);

        $this->info("Conversion completed!");
    }

    private function askForInputDirectory()
    {
        $input = $this->option('i') ?? text(
            label: 'Specify the path of the SVG directory',
            required: true
        );
        if (!File::isDirectory($input)) {
            $this->error("La directory $input non esiste, riprovare.");
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

    private function askForFileName()
    {
        $file_name = text(
            label: 'Specify the name of the file .blade.php',
            required: true,
            hint: 'The name will convert automatically to kebab-case',
            transform: fn(string $value) => str()->kebab($value),
        );

        return "$file_name.blade.php";
    }

    private function convertSvgToBlade($input, $output, $file_name)
    {
        // Crea l'ottimizzatore
        $optimizerChain = OptimizerChainFactory::create();

        // File di output
        $output_file = $output.'/'.$file_name;

        // Inizializza il contenuto del file blade
        File::put($output_file, "@props(['name' => null, 'default' => 'size-4'])\n@switch(\$name)\n");

        // Scorre tutti i file SVG nella directory e nelle sottodirectory
        foreach (File::allFiles($input) as $svgFile) {
            if ($svgFile->getExtension() === 'svg') {
                // Ottimizza il file SVG
                $this->optimizeSvg($svgFile->getPathname(), $optimizerChain);

                // Ottieni il nome dell'icona senza estensione
                $iconName = $svgFile->getFilenameWithoutExtension();

                // Trasformalo in kebab-case
                $kebabCaseIconName = str()->kebab($iconName);

                // Leggi e processa il contenuto dell'SVG
                $svgContent = $this->processSvgContent($svgFile->getPathname());

                // Estrai le dimensioni width e height
                [$width, $height] = $this->extractDimensions($svgFile->getPathname());

                if ($width !== $height) {
                    // Forma a rettangolo?
                    $viewBoxWidth = $width;
                    $viewBoxHeight = $height;
                } else {
                    // Forma a quadrato?
                    if ($width < 24 && $height < 24) {
                        $viewBoxWidth = $width;
                        $viewBoxHeight = $height;
                    } else {
                        $viewBoxWidth = 24;
                        $viewBoxHeight = 24;
                    }
                }

                $viewBoxWH = "$viewBoxWidth $viewBoxHeight";

                // Aggiungi il case al file Blade
                File::append($output_file, "@case('$kebabCaseIconName')\n");
                File::append($output_file, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"$width\" height=\"$height\" viewBox=\"0 0 $viewBoxWH\" {{ \$attributes->merge(['class' => \$default]) }}>\n");
                File::append($output_file, "$svgContent\n</svg>\n");

                // Chiudi il case
                File::append($output_file, "@break\n");
            }
        }

        // Chiudi lo switch
        File::append($output_file, "@endswitch\n");
    }

    private function optimizeSvg(string $filePath, $optimizerChain)
    {
        // Esegui l'ottimizzazione del file SVG
        $optimizerChain->optimize($filePath);
    }

    private function processSvgContent(string $filePath): string
    {
        // Carica l'SVG usando SimpleXML
        $svg = simplexml_load_file($filePath);

        // Rimuove eventuali spazi extra e normalizza gli attributi
        $this->normalizeAttributes($svg);

        // Ottieni le dimensioni dell'SVG
        $svgDimensions = $this->getSvgDimensions($svg);

        // Sostituisce fill e stroke con valori appropriati
        $this->replaceFillAndStroke($svg, $svgDimensions);

        // Converti SimpleXMLElement in DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($svg->asXML(), LIBXML_NOXMLDECL);

        // Rimuove la dichiarazione XML
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Ottieni il contenuto interno del nodo <svg>, escludendo il tag <svg> stesso
        $innerContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $innerContent .= $dom->saveXML($child);
        }

        return $innerContent;
    }

    private function normalizeAttributes(\SimpleXMLElement $element)
    {
        // Rimuove spazi extra dagli attributi
        foreach ($element->attributes() as $name => $value) {
            $value = trim(preg_replace('/\s+/', ' ', (string) $value));
            $element[$name] = $value;
        }

        // Processa i figli ricorsivamente
        foreach ($element->children() as $child) {
            $this->normalizeAttributes($child);
        }
    }

    private function getSvgDimensions(\SimpleXMLElement $svg)
    {
        $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : null;
        $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : null;

        // Se width e height non sono specificati, prova a estrarre dal viewBox
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
        // Calcola il bounding box dell'elemento
        $elementDimensions = $this->getElementDimensions($element);

        // Determina se l'elemento è uno sfondo
        $isSecondaryElement = $this->isSecondaryElement($elementDimensions, $svgDimensions);

        // Gestione del fill
        if (isset($element['fill'])) {
            $fillColor = strtolower(trim((string) $element['fill']));
            if ($fillColor !== 'none') {
                if ($isSecondaryElement) {
                    // Elemento secondario
                    $element['fill'] = 'currentColor';
                    $element['opacity'] = '0.3';
                } else {
                    // Elemento primario
                    $element['fill'] = 'currentColor';
                }
            }
        }

        // Gestione dello stroke
        if (isset($element['stroke'])) {
            $strokeColor = strtolower(trim((string) $element['stroke']));
            if ($strokeColor === 'transparent' || $strokeColor === 'rgba(0,0,0,0)') {
                $element['stroke'] = 'none';
            } elseif ($strokeColor !== 'none') {
                // Imposta stroke="currentColor" solo se l'elemento non è uno sfondo
                if (!$isSecondaryElement) {
                    $element['stroke'] = 'currentColor';
                }
            }
        }

        // Processa i figli ricorsivamente
        foreach ($element->children() as $child) {
            $this->replaceFillAndStroke($child, $svgDimensions);
        }
    }

    private function getElementDimensions(\SimpleXMLElement $element)
    {
        // Ottieni gli attributi x, y, width, height
        $x = isset($element['x']) ? $this->parseDimension($element['x']) : 0;
        $y = isset($element['y']) ? $this->parseDimension($element['y']) : 0;
        $width = isset($element['width']) ? $this->parseDimension($element['width']) : null;
        $height = isset($element['height']) ? $this->parseDimension($element['height']) : null;

        // Per alcuni elementi come <circle> ed <ellipse>, calcola width e height
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
            // Per i path, potremmo utilizzare getBBox, ma SimpleXML non lo supporta ritorno null
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
        // Se non possiamo determinare le dimensioni dell'elemento, assumiamo che non sia uno sfondo
        if ($elementDimensions['width'] === null || $elementDimensions['height'] === null) {
            return false;
        }

        // Calcola la percentuale di copertura rispetto all'SVG
        $elementArea = $elementDimensions['width'] * $elementDimensions['height'];
        $svgArea = $svgDimensions['width'] * $svgDimensions['height'];

        if ($svgArea == 0) {
            return false;
        }

        $coverage = ($elementArea / $svgArea) * 100;

        // Se l'elemento copre più del 90% dell'area dell'SVG, consideralo uno sfondo
        if ($coverage >= 90) {
            return true;
        }

        // Controlla se l'elemento inizia all'origine e ha le stesse dimensioni dell'SVG
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
        // Carica l'SVG come SimpleXMLElement
        $svg = simplexml_load_file($filePath);

        // Estrai le dimensioni width e height, gestendo unità diverse
        $width = isset($svg['width']) ? $this->parseDimension($svg['width']) : null;
        $height = isset($svg['height']) ? $this->parseDimension($svg['height']) : null;

        // Se width e height non sono specificati, prova a estrarre dal viewBox
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
        // Rimuove eventuali unità di misura (es. "px", "pt", "%")
        if (preg_match('/^([0-9.]+)(px|pt|%)?$/', (string) $dimension, $matches)) {
            return floatval($matches[1]);
        }

        // Valore predefinito se non corrisponde
        return null;
    }
}