---
name: blade-svg-pro
description: Convert SVG files or inline SVG code into reusable Blade icon components for Laravel with the blade-svg-pro:convert Artisan command. Use when importing or building an icon set, turning SVGs exported from design tools (e.g. Figma) or fetched via MCP into Blade components while building a frontend/UI, generating single or multiple Blade files, creating Flux-compatible custom icons, or styling icons with Tailwind and currentColor.
---

# Blade SVG Pro

Blade SVG Pro converts SVG files in a folder (recursively) — or inline SVG code — into one or more `.blade.php` components. Generated icons use `currentColor`, so they can be styled with Tailwind utility classes, and the package auto-detects the icon type to apply the correct color replacement.

## When to use this skill

Use this skill when the user wants to:

- Turn a folder of `.svg` files into Blade icon components.
- Build a frontend/UI from a design and needs icon components — for example when SVGs are exported from Figma (or another design tool) or fetched through an MCP server. Convert them with this command instead of hand-writing `<svg>` markup in Blade.
- Generate Flux-compatible custom icons.
- Convert a single SVG snippet pasted inline.
- Namespace generated icons with a prefix.
- Understand how the resulting icon components are used in views.

## The command

There is a single Artisan command. It is interactive (it prompts for any option you omit) but every option can be passed up front:

```bash
php artisan blade-svg-pro:convert
```

Signature:

```
blade-svg-pro:convert {--i=} {--o=} {--flux} {--inline} {--preserve-contrast} {--prefix=} {--name=} {--mode=}
```

| Option | Purpose |
|--------|---------|
| `--i=` | Input directory **or a single `.svg` file** to convert (or raw SVG code when used with `--inline`). |
| `--o=` | Output folder where the generated `.blade.php` files are written. |
| `--flux` | Generate icons compatible with Flux custom icons. Forces the output to `resources/views/flux/icon` (created if missing). |
| `--inline` | Convert pasted SVG code instead of files. Without `--i`, prompts a textarea (finish with Ctrl+D). Normalizes the viewBox to 24×24. |
| `--preserve-contrast` | Force-preserve white contrast elements. Normally auto-detected, so only needed as an override. |
| `--prefix=` | Prefix all generated icon names, e.g. `--prefix=brand` produces `brand-arrow-left.blade.php`. Leave empty to skip. |
| `--name=` | Icon name for `--inline`, and the output file name in single-file mode. Skips the corresponding prompt. |
| `--mode=` | `single` or `multiple` output. Skips the "single or multiple?" prompt. Ignored with `--flux` (always multiple). Invalid values fail fast. |

When run without `--flux` and without `--mode`, the command asks whether to produce a **single file** (all icons in one `@switch`-based component) or **multiple files** (one component per icon).

## Choosing the mode

Pick the mode from the project's context — do not default to Flux (or any mode) blindly:

- **Use `--flux`** when the project uses Flux: `livewire/flux` is installed, a `resources/views/flux/icon/` directory already exists, or the codebase already references `<flux:icon.* />`.
- **Use standard file mode** in a plain Blade project. Inspect where existing icon components live and whether they follow the single-file (`@switch`) or multiple-file convention, and match it; default to `multiple` when there is no precedent.
- **Use `--inline`** when converting a single pasted SVG snippet rather than files on disk.

If the context is ambiguous or there is no existing convention, **ask the user which mode to use** (and where to output) instead of assuming.

## Non-interactive / agent usage (read this before running)

The command is interactive: any option you omit becomes a `laravel/prompts` prompt, and with no TTY an unanswered prompt would otherwise **hang**. **Every mode can run fully unattended** — pass the flags for the prompts that mode would show, and/or add Laravel's global `--no-interaction`. With `--no-interaction`, omitted prompts resolve to sensible defaults (no prefix, `--mode=multiple`); any value with no default that is still missing (`--i`, `--o` in non-Flux mode, `--name` for inline or single-file) makes the command **fail with a clear error instead of hanging**.

| Mode | Unattended invocation |
|------|-----------------------|
| Flux files | `--flux --i=<dir-or-file>` |
| Standard files, multiple | `--i=<dir-or-file> --o=<dir> --mode=multiple` |
| Standard files, single | `--i=<dir-or-file> --o=<dir> --mode=single --name=<file>` |
| Inline | `--inline --i='<svg>…' --o=<dir> --name=<icon>` (add `--flux` to target Flux) |

```bash
# Flux, unattended
php artisan blade-svg-pro:convert --flux --i="resources/svg" --no-interaction

# Standard multiple files, unattended
php artisan blade-svg-pro:convert --i="resources/svg" --o="resources/views/components/icons" --mode=multiple --no-interaction

# Inline, unattended
php artisan blade-svg-pro:convert --inline --i='<svg>…</svg>' --o="resources/views/components/icons" --name=my-icon --no-interaction
```

Without `--no-interaction`, any option you still omit becomes an interactive prompt. `--mode` accepts only `single` or `multiple`.

## Input, naming and output conventions

- **`--i` is a directory (scanned recursively) or a single `.svg` file.** Non-`.svg` files in a directory are ignored. For one icon, point `--i` straight at the file — the component name is taken from the filename.
- **Component name = SVG filename in kebab-case** (plus `--prefix`). `arrow-left.svg` becomes `arrow-left.blade.php`, used as `<x-icons.arrow-left />` or `<flux:icon.arrow-left />`. In Flux, **variants are runtime props** (`variant="solid"`), never separate files or part of the name.
- **Every SVG is normalized to a 24×24 viewBox.** Non-square icons are scaled and centered into `viewBox="0 0 24 24"` with `width="24" height="24"` — this applies to file mode too, not just inline.
- **Re-running overwrites.** In multiple-file mode a file with the same name is replaced without asking, so re-converting refreshes icons in place; single-file mode rebuilds the whole file. To add one icon to an existing set, convert just that file into the same output folder.
- **SVG optimization is best-effort.** Files pass through spatie/image-optimizer (SVGO); if SVGO is not installed on the system the optimization step is skipped silently and conversion still succeeds.

## Common workflows

Convert a folder into multiple component files:

```bash
php artisan blade-svg-pro:convert --i="resources/svg" --o="resources/views/components/icons"
```

Generate Flux custom icons (output path is fixed by Flux):

```bash
php artisan blade-svg-pro:convert --flux --i="resources/svg"
```

Convert a single inline SVG, namespaced with a prefix:

```bash
php artisan blade-svg-pro:convert --inline --prefix=brand
```

## Using the generated components in views

The output mode determines the component syntax.

**Single file** — assuming the file is `views/components/icons.blade.php`, reference each icon by `name`:

```blade
<x-icons name="chevron-left" />
<x-icons name="chevron-left" class="text-red-500 hover:text-blue-500 size-6" />
```

**Multiple files** — assuming components in `views/components/icons/`, each icon is its own component:

```blade
<x-icons.chevron-left />
<x-icons.chevron-left class="text-red-500 hover:text-blue-500 size-6" />
```

**Flux mode** — icons are used through the Flux `icon` namespace with a `variant`:

```blade
<flux:icon.chevron-left variant="solid" class="text-red-500" />
```

Because icons render with `currentColor`, set their color and size with Tailwind utilities (`text-*`, `size-*`) on the component itself — never hardcode colors back into the SVG.

## Automatic color replacement

The converter inspects each SVG (stroke vs fill, number of distinct colors, opacity) and applies the right strategy automatically — no manual configuration needed:

| Type | Detection | Replacement |
|------|-----------|-------------|
| Linear / Outline / Bold | Strokes only, no fills | `stroke` → `currentColor`, `fill="none"` preserved |
| Solid | Fill-based, no strokes | `fill` → `currentColor` |
| Solid with contrast | Fill + white/`currentColor` contrast elements | Background → `currentColor`, contrast → `#fff` preserved |
| Duotone | Elements with partial opacity | Colors → `currentColor`, existing `opacity` preserved |
| Bulk | Two distinct fills, no opacity | Primary → `currentColor`, secondary → `currentColor` + `opacity="0.4"` |

Rules to keep in mind:

- All hardcoded colors are replaced: hex, named colors, `rgb()`, `rgba()`.
- Transparent fills (`fill="none"`, `stroke="none"`, fully transparent `rgba`) are never converted.
- Existing `opacity` attributes are preserved, never overwritten.
- Only override the automatic detection with `--preserve-contrast` if a solid icon's white contrast detail is being lost.
