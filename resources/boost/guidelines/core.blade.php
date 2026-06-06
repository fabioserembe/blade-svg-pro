## Blade SVG Pro

Blade SVG Pro converts SVG files (recursively) or inline SVG code into reusable Blade icon components. Generated icons render with `currentColor`, so they are styled with Tailwind utility classes (`text-*`, `size-*`) rather than hardcoded colors.

When you need icon components — including SVGs exported from a design tool such as Figma or fetched via an MCP server — convert them with this command instead of hand-writing `<svg>` markup in Blade. The command is interactive, but **every mode runs unattended** by passing the relevant flags and adding `--no-interaction`. `--i` is a directory (recursive) or a single `.svg` file. Use `--mode=single|multiple` to skip the file-type prompt and `--name=` for the inline/single-file name:

@verbatim
<code-snippet name="Convert SVGs into Flux icon components (unattended)" lang="bash">
php artisan blade-svg-pro:convert --flux --i="resources/svg" --no-interaction
</code-snippet>
@endverbatim

Component names come from the SVG filename in kebab-case (`--prefix=brand` namespaces them); in Flux, variants are props (`variant="solid"`), not separate files. Every SVG is normalized to a 24×24 viewBox.

- Use the generated components in views, styling color and size with Tailwind:

@verbatim
<code-snippet name="Use a generated icon component" lang="blade">
<x-icons.chevron-left class="text-red-500 size-6" />
</code-snippet>
@endverbatim

Icon type detection and color replacement (linear, solid, duotone, bulk, contrast preservation) are automatic — no manual configuration. Use the `blade-svg-pro` skill for the full option list, single vs. multiple file output, Flux usage, and color-replacement details.
