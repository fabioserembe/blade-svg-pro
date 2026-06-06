<?php

use Illuminate\Support\Facades\File;

$skillPath = __DIR__ . '/../../resources/boost/skills/blade-svg-pro/SKILL.md';
$guidelinesPath = __DIR__ . '/../../resources/boost/guidelines/core.blade.php';

beforeEach(function () use ($skillPath, $guidelinesPath) {
    $this->skillPath = $skillPath;
    $this->guidelinesPath = $guidelinesPath;
});

it('pubblica la skill Boost nel percorso previsto dal pacchetto', function () {
    expect(File::exists($this->skillPath))->toBeTrue();
});

it('espone un frontmatter YAML valido con i campi obbligatori name e description', function () {
    $content = File::get($this->skillPath);

    expect($content)->toStartWith('---');

    preg_match('/^---\s*\n(.*?)\n---/s', $content, $matches);
    expect($matches)->not->toBeEmpty();

    $frontmatter = $matches[1];

    expect($frontmatter)->toMatch('/^name:\s*blade-svg-pro\s*$/m');
    expect($frontmatter)->toMatch('/^description:\s*\S.+$/m');
});

it('documenta il comando reale e tutte le sue opzioni', function () {
    $content = File::get($this->skillPath);

    expect($content)->toContain('blade-svg-pro:convert');

    foreach (['--i', '--o', '--flux', '--inline', '--preserve-contrast', '--prefix'] as $option) {
        expect($content)->toContain($option);
    }
});

it('mantiene la skill allineata alla signature effettiva del comando', function () {
    $command = File::get(__DIR__ . '/../../src/BladeSVGPro.php');

    preg_match('/\$signature\s*=\s*[\'"]([^\'"]+)[\'"]/', $command, $matches);
    expect($matches)->not->toBeEmpty();

    preg_match_all('/\{--([a-z-]+)=?\}/', $matches[1], $options);

    $skill = File::get($this->skillPath);

    foreach ($options[1] as $option) {
        expect($skill)->toContain('--' . $option);
    }
});

it('include esempi di utilizzo dei componenti generati nelle viste', function () {
    $content = File::get($this->skillPath);

    expect($content)->toContain('<x-icons');
    expect($content)->toContain('<flux:icon.');
    expect($content)->toContain('currentColor');
});

it('dichiara i trigger di attivazione per design-to-code (Figma/MCP)', function () {
    $content = File::get($this->skillPath);

    preg_match('/^description:\s*(.+)$/m', $content, $matches);
    $description = strtolower($matches[1] ?? '');

    expect($description)->toContain('figma');
    expect($description)->toContain('mcp');
});

it('avverte sull’uso non interattivo per evitare che il comando si blocchi sui prompt', function () {
    $content = File::get($this->skillPath);

    expect($content)->toContain('Non-interactive');
    expect($content)->toContain('--prefix=');
    expect(strtolower($content))->toContain('hang');
    expect($content)->toContain('single or multiple');
});

it('documenta che ogni modalità è eseguibile in modo non interattivo', function () {
    $content = File::get($this->skillPath);

    // Le nuove opzioni che rendono ogni modalità automatizzabile
    expect($content)->toContain('--no-interaction');
    expect($content)->toContain('--mode');
    expect($content)->toContain('--name');

    // In assenza di un valore obbligatorio, fallisce invece di bloccarsi
    expect($content)->toContain('fail with a clear error');
});

it('documenta le convenzioni reali di input, naming e output', function () {
    $content = File::get($this->skillPath);

    // Input = directory ricorsiva
    expect(strtolower($content))->toContain('directory');
    expect(strtolower($content))->toContain('recursively');

    // Naming = filename kebab-case, varianti Flux come prop (anti-allucinazione)
    expect($content)->toContain('filename in kebab-case');
    expect($content)->toContain('variants are runtime props');

    // Normalizzazione 24x24 e sovrascrittura
    expect($content)->toContain('24×24 viewBox');
    expect(strtolower($content))->toContain('overwrite');

    // SVGO opzionale
    expect($content)->toContain('SVGO');
});

it('pubblica le AI guidelines core nel percorso previsto da Boost', function () {
    expect(File::exists($this->guidelinesPath))->toBeTrue();
});

it('fornisce guidelines concise che richiamano comando e skill', function () {
    $content = File::get($this->guidelinesPath);

    expect($content)->toContain('blade-svg-pro:convert');
    expect($content)->toContain('blade-svg-pro');

    foreach (['@verbatim', '@endverbatim', '<code-snippet'] as $token) {
        expect($content)->toContain($token);
    }
});
