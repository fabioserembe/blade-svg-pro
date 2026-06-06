<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

$fixturesPath = __DIR__ . '/../fixtures';

beforeEach(function () use ($fixturesPath) {
    $this->outputDir = sys_get_temp_dir() . '/blade-svg-pro-ni-' . uniqid();
    File::makeDirectory($this->outputDir, 0755, true);

    $this->fixturesPath = $fixturesPath;
    $this->originalFixtures = $fixturesPath . '-ni-originals';

    if (!File::isDirectory($this->originalFixtures)) {
        File::makeDirectory($this->originalFixtures, 0755, true);
    }

    foreach (File::files($fixturesPath) as $file) {
        File::copy($file->getPathname(), $this->originalFixtures . '/' . $file->getFilename());
    }

    $this->svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#000" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';
});

afterEach(function () {
    File::deleteDirectory($this->outputDir);

    if (File::isDirectory($this->originalFixtures)) {
        foreach (File::files($this->originalFixtures) as $file) {
            File::copy($file->getPathname(), $this->fixturesPath . '/' . $file->getFilename());
        }
        File::deleteDirectory($this->originalFixtures);
    }

    File::deleteDirectory(resource_path('views/flux'));
});

describe('--name option (inline)', function () {
    it('uses --name as the inline icon name without prompting', function () {
        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $this->svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
            '--mode' => 'multiple',
            '--name' => 'hexagon',
        ])->assertSuccessful();

        expect(File::exists($this->outputDir . '/hexagon.blade.php'))->toBeTrue();
        expect(File::get($this->outputDir . '/hexagon.blade.php'))->toContain('currentColor');
    });
});

describe('--mode option', function () {
    it('uses --mode=multiple for files without prompting', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
            '--mode' => 'multiple',
        ])->assertSuccessful();

        expect(File::exists($this->outputDir . '/arrow-left.blade.php'))->toBeTrue()
            ->and(File::exists($this->outputDir . '/chevron-right.blade.php'))->toBeTrue();
    });

    it('uses --mode=single with --name as the file name without prompting', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
            '--mode' => 'single',
            '--name' => 'my-icons',
        ])->assertSuccessful();

        $file = $this->outputDir . '/my-icons.blade.php';
        expect(File::exists($file))->toBeTrue();
        expect(File::get($file))
            ->toContain('@switch($name)')
            ->toContain("@case('arrow-left')")
            ->toContain('@endswitch');
    });

    it('fails with a clear error on an invalid --mode value', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
            '--mode' => 'foo',
        ])
            ->expectsOutputToContain("Invalid value for --mode")
            ->assertFailed();
    });

    it('still prompts for mode interactively when --mode is omitted', function () {
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

describe('--i accepts a single file', function () {
    it('accepts a single .svg file path as --i', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath . '/arrow-left.svg',
            '--o' => $this->outputDir,
            '--prefix' => '',
            '--mode' => 'multiple',
        ])->assertSuccessful();

        expect(File::exists($this->outputDir . '/arrow-left.blade.php'))->toBeTrue()
            ->and(File::exists($this->outputDir . '/chevron-right.blade.php'))->toBeFalse();
    });
});

describe('--no-interaction support', function () {
    it('runs file conversion non-interactively defaulting to multiple', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--no-interaction' => true,
        ])->assertSuccessful();

        expect(File::exists($this->outputDir . '/arrow-left.blade.php'))->toBeTrue();
    });

    it('runs inline conversion non-interactively with --name', function () {
        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $this->svgCode,
            '--o' => $this->outputDir,
            '--name' => 'hexagon',
            '--no-interaction' => true,
        ])->assertSuccessful();

        expect(File::exists($this->outputDir . '/hexagon.blade.php'))->toBeTrue();
    });

    it('runs flux conversion non-interactively', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--flux' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        expect(File::exists(resource_path('views/flux/icon/arrow-left.blade.php')))->toBeTrue();
    });

    it('fails non-interactively when --i is missing', function () {
        artisan('blade-svg-pro:convert', [
            '--o' => $this->outputDir,
            '--no-interaction' => true,
        ])->assertFailed();
    });

    it('fails non-interactively in file mode when --o is missing (no flux)', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--no-interaction' => true,
        ])->assertFailed();
    });

    it('fails non-interactively for inline conversion without --name', function () {
        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $this->svgCode,
            '--o' => $this->outputDir,
            '--no-interaction' => true,
        ])->assertFailed();
    });

    it('fails non-interactively in single mode without --name', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--mode' => 'single',
            '--no-interaction' => true,
        ])->assertFailed();
    });
});
