<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

$fixturesPath = __DIR__ . '/../fixtures/color-replacement';

beforeEach(function () use ($fixturesPath) {
    $this->outputDir = sys_get_temp_dir() . '/blade-svg-pro-color-test-' . uniqid();
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

describe('Linear/Outline icon color replacement', function () {
    it('replaces stroke colors with currentColor', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path stroke="#292D32" stroke-width="1.5" d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10z"/><path stroke="#292D32" stroke-width="1.5" d="M8 12h8"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'linear-icon')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/linear-icon.blade.php');

        expect($content)
            ->toContain('stroke="currentColor"')
            ->not->toMatch('/stroke="#[0-9a-fA-F]+"/');
    });

    it('preserves fill="none" in linear icons', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/linear.blade.php');

        expect($content)
            ->toContain('stroke="currentColor"')
            ->not->toMatch('/stroke="#[0-9a-fA-F]+"/');
    });
});

describe('Bold icon color replacement', function () {
    it('replaces stroke colors with currentColor in bold icons', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/bold.blade.php');

        expect($content)
            ->toContain('stroke="currentColor"')
            ->not->toMatch('/stroke="#[0-9a-fA-F]+"/');
    });
});

describe('Solid icon color replacement', function () {
    it('replaces fill colors with currentColor', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/solid.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toMatch('/fill="#292[dD]32"/');
    });

    it('replaces any hex color in solid icons', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#3B82F6" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'blue-solid')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/blue-solid.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toContain('#3B82F6')
            ->not->toContain('#3b82f6');
    });

    it('replaces named color fills with currentColor', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="black" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'named-color')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/named-color.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toContain('fill="black"');
    });
});

describe('Solid icon with automatic contrast preservation', function () {
    it('preserves white fill for contrast automatically', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/solid-contrast.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/fill="(#fff|#ffffff|white)"/');
    });

    it('preserves white stroke for contrast in solid icons', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#000" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/><path fill="none" stroke="white" stroke-width="2" d="M9 12l2 2 4-4"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'shield-white-stroke')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/shield-white-stroke.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/stroke="(white|#fff|#ffffff)"/');
    });

    it('does not require --preserve-contrast flag', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#292D32" d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81v8.37C2 19.83 4.17 22 7.81 22h8.37c3.64 0 5.81-2.17 5.81-5.81V7.81C22 4.17 19.83 2 16.19 2z"/><path fill="#ffffff" d="M10.58 15.58l-2.83-2.83 1.06-1.06 2.3 2.3 5.14-5.14 1.06 1.06-5.67 5.67z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'auto-contrast')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/auto-contrast.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/fill="(#fff|#ffffff|white)"/');
    });
});

describe('Duotone icon color replacement', function () {
    it('replaces colors with currentColor and preserves existing opacity', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/duotone.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/opacity="\.?0?\.4"/')
            ->not->toMatch('/fill="#[0-9a-fA-F]+"/');
    });

    it('does not overwrite existing opacity values', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path opacity="0.6" fill="#292D32" d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81v8.37C2 19.83 4.17 22 7.81 22h8.37c3.64 0 5.81-2.17 5.81-5.81V7.81C22 4.17 19.83 2 16.19 2z"/><path fill="#292D32" d="M10.58 15.58l-2.83-2.83 1.06-1.06 2.3 2.3 5.14-5.14 1.06 1.06-5.67 5.67z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'duotone-custom-opacity')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/duotone-custom-opacity.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/opacity="\.?0?\.6"/')
            ->not->toContain('opacity="0.3"')
            ->not->toContain('opacity=".3"');
    });
});

describe('Bulk icon color replacement', function () {
    it('replaces primary color with currentColor and adds opacity to secondary', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/bulk.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toMatch('/opacity="\.?0?\.4"/')
            ->not->toMatch('/fill="#[0-9a-fA-F]+"/');
    });

    it('handles bulk icons with multiple secondary elements', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#292D32" d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81v8.37C2 19.83 4.17 22 7.81 22h8.37c3.64 0 5.81-2.17 5.81-5.81V7.81C22 4.17 19.83 2 16.19 2z"/><path fill="#C9CDD4" d="M8 12h8"/><path fill="#C9CDD4" d="M12 8v8"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'bulk-multi')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/bulk-multi.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toMatch('/fill="#[0-9a-fA-F]+"/');

        preg_match_all('/opacity="\.?0?\.4"/', $content, $matches);
        expect(count($matches[0]))->toBeGreaterThanOrEqual(1);
    });
});

describe('General color replacement rules', function () {
    it('does not touch fill="none"', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill="none" stroke="#292D32" stroke-width="1.5" d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'fill-none-test')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/fill-none-test.blade.php');

        expect($content)->not->toMatch('/fill="none"[^>]*fill="currentColor"/');
    });

    it('does not touch stroke="none"', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#000" stroke="none" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'stroke-none-test')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/stroke-none-test.blade.php');

        expect($content)->not->toContain('stroke="currentColor"');
    });

    it('replaces rgb() color values', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="rgb(41,45,50)" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'rgb-color')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/rgb-color.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toContain('rgb(');
    });

    it('replaces rgba() color values', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="rgba(41,45,50,1)" d="M12 2L2 7v10l10 5 10-5V7L12 2z"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'rgba-color')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/rgba-color.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->not->toContain('rgba(');
    });

    it('treats rgba with alpha=0 as transparent', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="rgba(255,255,255,0)" d="M0 0h24v24H0Z"/><path fill="none" stroke="#8998a6" stroke-width="2" d="m3 3 18 18"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'transparent-bg')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/transparent-bg.blade.php');

        expect($content)
            ->toContain('stroke="currentColor"')
            ->not->toContain('fill="currentColor"');
    });
});

describe('Solid icons with existing currentColor elements', function () {
    it('converts existing currentColor to white when background has hardcoded color', function () {
        artisan('blade-svg-pro:convert', [
            '--i' => $this->fixturesPath,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert icons into a single or multiple files?', 'multiple')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/solid-currentcolor-contrast.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toContain('fill="#fff"')
            ->toContain('stroke="#fff"')
            ->not->toContain('fill="#ed3035"');
    });

    it('converts fill=currentColor to white for inline SVGs with colored background', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle fill="#edad00" cx="12" cy="12" r="10"/><path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 7v5l3 3"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'clock-solid')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/clock-solid.blade.php');

        expect($content)
            ->toContain('fill="currentColor"')
            ->toContain('stroke="#fff"')
            ->not->toContain('fill="#edad00"');
    });

    it('does not convert currentColor to white when no hardcoded colors exist', function () {
        $svgCode = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2"/></svg>';

        artisan('blade-svg-pro:convert', [
            '--inline' => true,
            '--i' => $svgCode,
            '--o' => $this->outputDir,
            '--prefix' => '',
        ])
            ->expectsQuestion('Do you want to convert the icon into a single or multiple files?', 'multiple')
            ->expectsQuestion('Specify the name of the icon', 'pure-currentcolor')
            ->assertSuccessful();

        $content = File::get($this->outputDir . '/pure-currentcolor.blade.php');

        expect($content)
            ->not->toContain('stroke="#fff"')
            ->not->toContain('fill="#fff"');
    });
});
