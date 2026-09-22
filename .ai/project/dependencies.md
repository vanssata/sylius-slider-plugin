# Dependencies

<!-- Not a copy of the lockfile. Only what an agent must know before touching
     something. -->

## Runtime platform

<!-- Language version, framework version, and the constraint that pins them.
     KNOWN FACT with the manifest. -->

## Dependencies that carry business behaviour

<!-- Payment SDKs, tax engines, PDF generators, ERP clients — anything whose
     upgrade changes what customers see. Version, why it is pinned, what breaks. -->

## Patched or forked packages

<!-- What is patched, where the patch lives, how it is applied, why it exists.
     A patch nobody remembers is a landmine. -->

## Frontend build

<!-- Bundler, asset pipeline, whether the built assets are committed. -->

## Upgrade landmines

<!-- RISK entries: the upgrade everyone postpones, and what it would break. -->
