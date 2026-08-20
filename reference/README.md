# Reference material

## `horex-offerte-app.html`

The working prototype the brief refers to, saved verbatim. Open it in a browser — it
runs standalone, with no build step and no back end.

Treat it as **a reference, not a blueprint.** The brief says so explicitly, and there
are places where the plugin deliberately differs (see below).

### What it settles

| | |
|---|---|
| Brand tokens | `--geel #FEC129`, `--geel-zacht #FFF3D2`, `--ink #1E1E1E`, `--canvas #FFFAED`, `--paper #FFFFFF`, `--line #EDE4CE`, `--muted #857E6C` |
| Type | Playfair Display 600/700 for headings, DM Sans 400–700 for everything else |
| Header | Dark (`--ink`), sticky, white-wordmark logo, step counter, 3px progress bar |
| Motion | Screens slide in over 0.42s; auto-advance fires 230ms after a choice |
| Step order | Computed in `stappenVoor()` from the product, exactly as the brief requires |
| Carry-over | `laatste` keeps the last colour and mesh, reapplied when the palette matches |
| Cursor fix | `updatePreview()` touches only the preview, never the inputs |

It also carries five inline SVG product illustrations and two measuring diagrams that
render before any photo loads — useful while Hor-Ex's real project photos are missing.

### Where the plugin differs, and why

- **Out-of-range measurements.** The prototype disables the add button below `MIN_MM`.
  The brief says warn, never block: "a 6.2 metre veranda is a real customer, not a
  typo." The plugin warns and lets it through.
- **Everything is configurable.** Products, colours, mesh, copy and the measuring help
  come from the settings screen rather than from constants in the file.
- **Escaping and sanitising** happen server-side, against the schema.

### Not reusable as-is

The product photos are AI-generated placeholders on a CloudFront URL that will not
outlive the prototype. The stof and doek palettes are invented stand-ins — the brief
is clear that Hor-Ex supplies the real swatch range.
