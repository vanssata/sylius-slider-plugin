# Adding a Stimulus controller

The plugin ships its JavaScript as a Symfony UX package
(`@vanssa/sylius-slider-plugin`, source in `assets/`). Controllers are
registered **only** through the `@symfony/stimulus-bridge` manifest, inside the
consuming application's own `startStimulusApp()`. Nothing in this plugin starts
a Stimulus application.

That design has one cost: **four** manifests must carry the same controller key
set. Today that is 14 keys — 2 shop controllers and 12 admin controllers. Get
one of them wrong and the webpack build either fails with a confusing message
or, worse, silently drops the controller from one context's bundle.

| File | Role |
| --- | --- |
| `assets/package.json` (`symfony.controllers`) | Authoritative source: `main`, `name`, `fetch`, `enabled`, `autoimport`. What Symfony Flex copies into a consumer project. |
| `assets/controllers.json` | This repo's own dev manifest. Mirrors the package defaults, all `enabled: true`. |
| `assets/admin/controllers.json` | Per-context manifest for the admin Encore build. All 14 enabled, all `eager`. |
| `assets/shop/controllers.json` | Per-context manifest for the shop Encore build. `slider` + `slide-video` enabled, the 12 admin controllers `enabled: false`. |

## How registration actually resolves

`vendor/sylius/test-application/webpack.config.js` builds four webpack configs.
The two that matter here are `app.admin` and `app.shop`. Each calls
`Encore.enableStimulusBridge()` with a **merged** manifest assembled from four
files, in this order (admin shown):

```js
[
    './assets/controllers.json',                 // test application
    '../../../assets/controllers.json',          // plugin, repo root manifest
    './assets/admin/controllers.json',           // test application, admin
    '../../../assets/admin/controllers.json',    // plugin, admin
]
```

The merge is a shallow spread **per package key**:

```js
acc.controllers = { ...acc.controllers, ...json.controllers };
```

So the `@vanssa/sylius-slider-plugin` object in `assets/admin/controllers.json`
**replaces** the one from `assets/controllers.json` wholesale. It is not a
per-controller deep merge. A controller you leave out of the per-context file is
not inherited from the root manifest — it is absent from that build.

The merged result is written to
`vendor/sylius/test-application/var/cache/webpack/controllers.merged.admin.json`
(and `...merged.shop.json`). Read those files when a controller behaves
differently in admin and shop — they are the ground truth for what webpack
compiled.

The bridge loader then, for every entry in the merged manifest:

- looks up `require('@vanssa/sylius-slider-plugin/package.json')` and reads
  `symfony.controllers[<key>]` — a key that exists in a `controllers.json` but
  not in `assets/package.json` aborts the build;
- skips the entry entirely when `enabled` is falsy;
- imports `<package>/<main>` eagerly, or wraps it in a lazy loader, according to
  `fetch` (`"eager"` when omitted, `"lazy"`, nothing else);
- registers it under `symfony.controllers[<key>].name`, which a `name` in the
  `controllers.json` entry can still override;
- imports each truthy path from the **`controllers.json` entry's** `autoimport`
  map. The `autoimport` block in `assets/package.json` is the value Flex seeds
  into a consumer's manifest; it is not what the local build reads.

## Worked example: adding `char-counter`

A new admin controller that writes a live "N characters left" hint next to a
text field. Every step below is required; the four manifest edits are one
logical change.

### 1. The controller

File naming is a convention the manifests depend on being kept consistent:
`assets/<context>/controllers/<snake_case>_controller.js`, manifest key in
`kebab-case` without the `_controller` suffix, registered identifier
`vanssa-<key>`.

`assets/admin/controllers/char_counter_controller.js`:

```js
import { Controller } from '@hotwired/stimulus';

/**
 * Live character budget for a text field.
 *
 * Markup contract: an `input` target (the field) and an `output` target (the
 * hint element). The budget comes from the `max` value.
 */
export default class extends Controller {
    static targets = ['input', 'output'];

    static values = {
        max: { type: Number, default: 120 },
    };

    connect() {
        this.render();
    }

    disconnect() {
        // Nothing to release here. A controller that adds document-level
        // listeners, timers, IntersectionObservers or object URLs MUST undo
        // that here — the admin re-parents nodes (modal portal, turbo-frame
        // preview swaps), so disconnect() runs far more often than a page load
        // would suggest.
    }

    render() {
        if (!this.hasInputTarget || !this.hasOutputTarget) {
            return;
        }

        const left = this.maxValue - this.inputTarget.value.length;
        this.outputTarget.textContent = `${left} characters left`;
        this.outputTarget.classList.toggle('text-danger', left < 0);
    }
}
```

### 2. `assets/package.json`

Add the entry to `symfony.controllers`. `main` is relative to `assets/` and the
file must exist — the build resolves it as
`@vanssa/sylius-slider-plugin/admin/controllers/char_counter_controller.js`.
Keep the file under `admin/`, `shop/` or `styles/`: those three directories are
the `files` allowlist of the published package.

```json
{
    "symfony": {
        "controllers": {
            "char-counter": {
                "main": "admin/controllers/char_counter_controller.js",
                "name": "vanssa-char-counter",
                "fetch": "lazy",
                "enabled": true
            }
        }
    }
}
```

If the controller needs a third-party package, add it to `dependencies` in the
same file (this is how `@simonwep/pickr` reaches `rgba-color-picker`), and put
any stylesheet it needs in `autoimport` rather than importing it from a template.

### 3. `assets/controllers.json`

The repo's dev manifest. Every entry here is `enabled: true` — this file stands
in for what Flex seeds into a consumer's `assets/controllers.json`, and the
bridge manifest is the only registrar, so disabling anything here disables it
outright.

```json
{
    "controllers": {
        "@vanssa/sylius-slider-plugin": {
            "char-counter": {
                "enabled": true,
                "fetch": "lazy"
            }
        }
    }
}
```

### 4. `assets/admin/controllers.json`

The admin build fetches everything eagerly.

```json
{
    "controllers": {
        "@vanssa/sylius-slider-plugin": {
            "char-counter": {
                "enabled": true,
                "fetch": "eager"
            }
        }
    }
}
```

### 5. `assets/shop/controllers.json`

The shop does not use this controller — but the entry is still mandatory,
because this file replaces the whole package object on merge. Omit it and the
build has no idea the key exists; list it as disabled and the loader skips it
before emitting any import, so it costs nothing in the shop bundle.

```json
{
    "controllers": {
        "@vanssa/sylius-slider-plugin": {
            "char-counter": {
                "enabled": false,
                "fetch": "lazy"
            }
        }
    }
}
```

### 6. The template

`stimulus_controller()` / `stimulus_target()` (symfony/stimulus-bundle) are used
across the plugin's admin templates; raw `data-*` attributes work identically.

```twig
<div {{ stimulus_controller('vanssa-char-counter', { max: 80 }) }}>
    {{ form_row(form.headline, {
        attr: {
            'data-vanssa-char-counter-target': 'input',
            'data-action': 'input->vanssa-char-counter#render'
        }
    }) }}

    <small class="form-hint" {{ stimulus_target('vanssa-char-counter', 'output') }}></small>
</div>
```

### 7. Restart the watcher, then check the browser

Manifests are merged when `webpack.config.js` is **loaded**, not when a file
changes. A running `encore dev --watch` keeps building the previous controller
set, without any warning:

```bash
make node-watch-stop
make node-watch
make node-watch-logs        # wait for the recompile to appear
docker compose restart chrome
```

`docker compose restart chrome` is not optional before a browser check — Chrome
holds compiled bundles in memory and will happily serve the previous ones.

For a controller with a visible effect on the storefront, the fast loop is:

```bash
make e2e-check SPEC=tests/e2e/shop/slider-behavior.spec.ts
```

It blocks until the compiled bundles are newer than the newest file under
`assets/`, then runs that one spec on desktop, tablet and mobile.

## The sync check

Run this from the repo root after every manifest edit. It needs only `jq` and
`diff` — no container, no PHP, no Node. `jq`'s `keys` sorts, so the comparison
is order-independent:

```bash
for f in assets/controllers.json assets/admin/controllers.json assets/shop/controllers.json; do
  diff <(jq -r '.symfony.controllers | keys[]' assets/package.json) \
       <(jq -r '.controllers["@vanssa/sylius-slider-plugin"] | keys[]' "$f") >/dev/null \
    && echo "$f: in sync" || echo "$f: OUT OF SYNC"
done
```

Two more invariants worth checking in the same breath — every `main` resolves to
a real file, and the root manifest has no disabled entry:

```bash
jq -r '.symfony.controllers[].main' assets/package.json \
  | while read -r m; do [ -f "assets/$m" ] || echo "MISSING: assets/$m"; done

jq -r '.controllers["@vanssa/sylius-slider-plugin"]
       | to_entries[] | select(.value.enabled != true) | .key' assets/controllers.json
```

A local, gitignored PostToolUse hook (`.claude/hooks/assets-guard.sh`, covered by
the `/.claude/` entry in `.gitignore`) enforces exactly these invariants plus the
entrypoint rule below whenever an agent edits a manifest, an entrypoint, a
controller or a stylesheet, and prints the watcher-restart reminder. It is local
tooling, not part of the repository contract and not present in a fresh clone —
CI does not run it, so run the commands above by hand.

## Entrypoints must never start Stimulus

`assets/admin/entrypoint.js` and `assets/shop/entrypoint.js` must not import
`@symfony/stimulus-bridge`, call `startStimulusApp()`, or `app.register(...)` a
controller.

The consuming application already starts exactly one Stimulus application — in
the test application that is `assets/{admin,shop}/bootstrap.js`, pulled in by
`app-admin-entry` / `app-shop-entry`. The plugin's entrypoints are separate
webpack entries (`plugin-admin-entry`, `plugin-shop-entry`) loaded on the same
page. If they start a second application, both applications register the same
identifiers against the same DOM, and every action and event handler on those
controllers runs **twice** per page. That is not a build error; it shows up as
double form submissions, doubled slide advances, and preview frames that reload
themselves.

This is also why every entry in `assets/controllers.json` is `enabled: true`. An
earlier version of the plugin shipped `enabled: false` on purpose, because the
entrypoints registered the controllers themselves. Now that they do not, the
bridge manifest is the only registrar and `enabled: true` is required.

What the entrypoints legitimately contain:

- `assets/shop/entrypoint.js` — comments only. The file must keep existing
  because the test application hard-codes it as the `plugin-shop-entry` webpack
  entry. Shop styles arrive through the `slider` controller's `autoimport` of
  `@vanssa/sylius-slider-plugin/shop/styles/slider.scss`.
- `assets/admin/entrypoint.js` — only what must run eagerly outside Stimulus:
  the admin stylesheet imports, the sidebar-focus behaviour, and

  ```js
  import * as Turbo from '@hotwired/turbo';

  Turbo.session.drive = false;
  ```

  That opt-out is load-bearing. The plugin uses Turbo **Frames** for the admin
  preview; Turbo **Drive** would intercept every link and form in the whole
  Sylius admin, which is not built for it.

## Renaming or removing a controller

A rename is four manifest key renames plus the file rename plus every template
reference to the identifier — `data-controller`, `data-action`, and the
`data-<identifier>-*-target` / `-value` / `-param` attributes all embed it:

```bash
grep -rn 'vanssa-char-counter' templates/ assets/ features/ tests/
```

Removal is the same four manifests plus the file plus the template markup. Note
that only the controller identifier carries the `vanssa-` prefix; unrelated
literal data attributes used as markers (for example
`data-slider-settings-*-only` or `data-vanssa-context-locale`) are matched by
string in controller code and must be renamed together with that code, not with
the identifier.

## Failure modes

| Symptom | Cause |
| --- | --- |
| `Controller "@vanssa/sylius-slider-plugin/<key>" does not exist in the package and cannot be compiled.` | A `controllers.json` lists a key that `assets/package.json` does not have. |
| `The file "@vanssa/sylius-slider-plugin/package.json" could not be found. Try running "yarn install --force".` | The package is not resolvable from the test application's `node_modules`. |
| `Invalid fetch mode "<x>" in controllers.json. Expected "eager" or "lazy".` | A typo in `fetch`. |
| `"<file>" contains a reference to the file "<ref>".` | An `autoimport` path that does not resolve. |
| Controller works in admin, dead in shop (or vice versa) | The per-context `controllers.json` for that context does not list the key at all — the shallow merge dropped it. Compare against `var/cache/webpack/controllers.merged.<context>.json` in the test application. |
| Every handler fires twice | An entrypoint started a second Stimulus application. |
| Edit compiles but the page runs the old code | Chrome cached the bundle — `docker compose restart chrome`. |
| Manifest edit has no effect at all | The watcher was not restarted. |
| Controller edit has no effect, entrypoint/SCSS edits do | The `file:` dependency copy trap: yarn classic copies `assets/` into `node_modules/@vanssa/sylius-slider-plugin` instead of linking it, and the entrypoints are referenced by absolute path while controllers resolve through the copy. `make node-watch` replaces that copy with a symlink on first start. |

## Consumers overriding a controller

A project that wants to replace one of the plugin's controllers sets that entry
to `"enabled": false` in **its own** `assets/controllers.json` and registers its
own class under the same identifier (for example `vanssa-slider`) in its own
Stimulus application. Templates and sibling controllers only ever reference the
identifier, so they keep working against the replacement. See
[../FLEX_RECIPE.md](../FLEX_RECIPE.md) for what Flex seeds into that file on
`composer require`.
