## Documentation layout

- `docs/usage/` — for someone using the plugin in their shop
  (`getting-started`, `admin-guide`, `style-presets`, `storefront`,
  `options-reference`).
- `docs/dev/` — for someone extending it (`architecture`,
  `adding-a-stimulus-controller`, `extending`, `style-presets`,
  `color-picker-type`, `testing`, `docs-media`, `contributing`).
- `docs/FLEX_RECIPE.md` stays where it is. `README.md` is a short index.
- Screenshots and GIFs under `docs/screenshots/` and `docs/media/` are
  **generated** — regenerate with `make docs-media`, never hand-edit
  (`docs/dev/docs-media.md`).

## Repository maintenance guides

- **CLEANUP_GUIDE.md** — cleaning up and organizing plugin code
- **RENAME_GUIDE.md** — renaming the plugin and its components
- **COMPATIBILITY_GUIDE.md** — compatibility across Sylius versions
