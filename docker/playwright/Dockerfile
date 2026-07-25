# Playwright runner for the e2e suite and the docs-media generators.
#
# The image tag MUST track the `@playwright/test` version pinned in the root
# package.json: a runner whose bundled browsers predate (or postdate) the test
# library is the classic cause of "works locally, fails in the container"
# breakage, and Playwright refuses to start when the drift is large enough.
ARG PLAYWRIGHT_VERSION=1.61.1
FROM mcr.microsoft.com/playwright:v${PLAYWRIGHT_VERSION}-noble

# ffmpeg assembles the docs GIFs from screenshot frame sequences
# (palettegen/paletteuse). Playwright's own .webm recording is not used:
# its frame timing is variable, so the same run produces a different GIF
# every time — useless for a committed doc asset.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# The service runs as the host UID (see compose.override.dist.yml) so
# test-results/, playwright-report/ and regenerated docs media stay writable
# on the host. That user has no real home, so point HOME and the npm cache at
# a world-writable location.
ENV HOME=/tmp \
    npm_config_cache=/tmp/.npm \
    PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

WORKDIR /srv/sylius
