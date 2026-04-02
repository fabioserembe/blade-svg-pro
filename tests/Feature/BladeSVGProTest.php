<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

$fixturesPath = __DIR__ . '/../fixtures';

beforeEach(function () use ($fixturesPath) {
    $this->outputDir = sys_get_temp_dir() . '/blade-svg-pro-test-' . uniqid();
    File::makeDirectory($this->outputDir, 0755, true);

    $this->fixturesPath = $fixturesPath;
    $this->originalFixtures = $fixturesPath . '-originals';

    if (!File::isDirectory($this->originalFixtures)) {
        File::makeDirectory($this->originalFixtures, 0755, true);
    }

    foreach (File::files($fixturesPath) as $file) {
        File::copy($file->getPathname(), $this->originalFixtures . '/' . $file->getFilename());
    }
});

afterEach(function () {
    File::deleteDirectory($this->outputDir);

    if (File::isDirectory($this->originalFixtures)) {
        foreach (File::files($this->originalFixtures) as $file) {
            File::copy($file->getPathname(), $this->fixturesPath . '/' . $file->getFilename());
        }
        File::deleteDirectory($this->originalFixtures);
    }
});

describe('Multiple file conversion', function () {
    it('converts SVG files into multiple blade files', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/arrow-left.blade.php'))->toBeTrue()
            ->and(File::exists($this->outputDir . '/chevron-right.blade.php'))->toBeTrue();
    });

    it('generates valid blade content in multiple mode', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/arrow-left.blade.php');

        expect($content)
            ->toContain("@props(['name' => null, 'default' => 'size-4'])")
            ->toContain('currentColor')
            ->toContain('<svg')
            ->toContain('</svg>');
    });
});

describe('Single file conversion', function () {
    it('converts SVG files into a single blade file', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'single')
            ->expectsQuestion('Specify the name of the file', 'my-icons')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/my-icons.blade.php'))->toBeTrue();

        $content = File::get($this->outputDir . '/my-icons.blade.php');

        expect($content)
            ->toContain('@switch($name)')
            ->toContain("@case('arrow-left')")
            ->toContain("@case('chevron-right')")
            ->toContain('@endswitch');
    });
});

describe('Flux mode', function () {
    it('converts SVG files in flux format', function () {
        $fluxOutputDir = resource_path('views/flux/icon');

        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--flux' => true,
            '--prefix' => '',
        ])->assertSuccessful();

        expect(File::isDirectory($fluxOutputDir))->toBeTrue();

        $files = File::files($fluxOutputDir);
        expect(count($files))->toBeGreaterThanOrEqual(2);

        $content = File::get($fluxOutputDir . '/arrow-left.blade.php');
        expect($content)
            ->toContain('$unescapedForwardedAttributes')
            ->toContain("'variant' => 'outline'")
            ->toContain('Flux::classes')
            ->toContain('data-flux-icon');

        File::deleteDirectory(resource_path('views/flux'));
    });
});

describe('Inline conversion', function () {
    it('converts inline SVG code into a blade file', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#000" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'hexagon')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/hexagon.blade.php'))->toBeTrue();

        $content = File::get($this->outputDir . '/hexagon.blade.php');
        expect($content)->toContain('currentColor');
    });
});

describe('Prefix option', function () {
    it('applies prefix to filenames in multiple mode', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => 'brandname',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/brandname-arrow-left.blade.php'))->toBeTrue()
            ->and(File::exists($this->outputDir . '/brandname-chevron-right.blade.php'))->toBeTrue();
    });

    it('applies prefix to case names in single mode', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => 'brandname',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'single')
            ->expectsQuestion('Specify the name of the file', 'icons')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/icons.blade.php');

        expect($content)
            ->toContain("@case('brandname-arrow-left')")
            ->toContain("@case('brandname-chevron-right')");
    });

    it('applies prefix to inline conversion', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#000" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => 'brandname',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'hexagon')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/brandname-hexagon.blade.php'))->toBeTrue();
    });

    it('applies prefix in flux mode', function () {
        $fluxOutputDir = resource_path('views/flux/icon');

        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--flux' => true,
            '--prefix' => 'brandname',
        ])->assertSuccessful();

        expect(File::exists($fluxOutputDir . '/brandname-arrow-left.blade.php'))->toBeTrue()
            ->and(File::exists($fluxOutputDir . '/brandname-chevron-right.blade.php'))->toBeTrue();

        File::deleteDirectory(resource_path('views/flux'));
    });

    it('converts prefix to kebab-case', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => 'MyBrand',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/my-brand-arrow-left.blade.php'))->toBeTrue();
    });

    it('skips prefix when empty string is provided', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        expect(File::exists($this->outputDir . '/arrow-left.blade.php'))->toBeTrue();
    });
});

describe('White color contrast preservation', function () {
    it('preserves white colors automatically', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/shield-check.blade.php');

        expect($content)
            ->toMatch('/stroke="(white|#fff|#ffffff)"/');
    });
});

describe('ViewBox normalization', function () {
    it('normalizes viewBox to 24x24', function () {
        $customSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><path fill="#000" d="M24 4L4 14v20l20 10 20-10V14L24 4z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $customSvg,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'custom')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/custom.blade.php');

        expect($content)->toContain('viewBox="0 0 24 24"');
    });
});
