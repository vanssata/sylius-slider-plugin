---
dirs: [assets]
---

## Asset watch mode

Use a **watcher**, not one-off builds:

```bash
docker compose --profile watch up -d nodejs-watch     # start   (= make node-watch)
docker compose --profile watch logs -f nodejs-watch   # follow  (= make node-watch-logs)
docker compose --profile watch rm -sf nodejs-watch    # stop    (= make node-watch-stop)
```

The `nodejs-watch` service runs `encore dev --watch` and, on first start, replaces the yarn copy with a symlink to the real `assets/` tree — which is what makes controller edits visible to the watcher and retires the `yarn install --force` step. It sits behind the `watch` compose profile, so a plain `docker compose up -d` never starts it. `yarn build` and `yarn watch` are both `encore dev` in this app, so watch output is identical to what a one-off build produces.

Two things a running watcher does **not** handle:

- **Manifest changes need a restart.** `webpack.config.js` merges the `controllers.json` files into `var/cache/webpack/controllers.merged.*.json` at config-load time only. Edit any `controllers.json` or `assets/package.json` and the watcher keeps building the old controller set, silently — restart it.
- **Chrome caches bundles in memory.** The `chrome` service (Behat's `@javascript` leg) keeps compiled bundles in memory; restart it before a browser check: `docker compose restart chrome`. Playwright is unaffected — `make e2e` starts a fresh browser context each run.

If file events don't reach the watcher (edits never trigger a recompile), start it with `WATCHPACK_POLLING=true`.

**Fast visual loop while editing SCSS/CSS/JS:**

```bash
make node-watch                                              # once per task
make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
```

`make e2e-check` blocks until the compiled bundles are newer than the newest
`assets/` source (i.e. the watcher caught up), then runs that one spec on
desktop + tablet + mobile. Seconds, not a full suite.

Drive the watcher with the commands above — there is no wrapper script and no dedicated subagent, so starting it, waiting for the recompile to appear in `logs`, and stopping it when the task ends are all manual steps. Nothing stops it at session end either; a forgotten `nodejs-watch` keeps running until `rm -sf`.

Clean-room fallback when no watcher is running (e.g. reproducing a CI build):

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force"
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
docker compose restart chrome
```
