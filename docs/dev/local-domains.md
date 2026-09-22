# Local domains — sharing port 80 between projects

This project serves on **`http://sylius-slider.localhost`** (port 80) without
owning port 80. A shared Traefik does, and routes to a project by the requested
hostname, so several projects can be up at the same time instead of fighting
over `:80`.

```
browser → :80 localproxy-traefik ──Host(`sylius-slider.localhost`)──→ syliusslider-nginx-1:80
                                └──Host(`traefik.localhost`)────────→ its own dashboard
          :82 ─────────────────────────────────────────────────────→ syliusslider-nginx-1:80
```

`http://localhost:82` stays as a **proxy-free bypass** to the same nginx —
useful when the proxy is down or being debugged. Plain `http://localhost`
deliberately 404s: no router matches it, which is the proof that `:80` is free
for the next project.

## Starting and stopping

```bash
make proxy-up      # docker compose -p localproxy -f docker/proxy/compose.yml up -d
make proxy-down
make proxy-logs    # the only place that explains a mysterious 404
```

`make up` runs `proxy-up` first, because this project's nginx joins the
external `web` network and the proxy compose file is what creates it. The proxy
is a **separate compose project** (`-p localproxy`) on purpose: `make down`
here must not take the proxy — and therefore every sibling project — with it.

`docker/proxy/compose.yml` lives in this repository only because this is where
it was first needed. Nothing in it is specific to the slider plugin.

## Adding a sibling project

Two things, in the project's own compose file:

```yaml
services:
    nginx:                       # whatever serves HTTP there
        networks:
            - default            # keep its project-internal aliases
            - web
        labels:
            - "traefik.enable=true"
            # nginx is on two networks — say which one Traefik should dial,
            # or it may pick the project-internal one.
            - "traefik.docker.network=web"
            - "traefik.http.routers.<project>.rule=Host(`<project>.localhost`)"
            - "traefik.http.routers.<project>.entrypoints=web"
            - "traefik.http.services.<project>.loadbalancer.server.port=80"

networks:
    web:
        external: true
        name: web
```

Router and service names must be unique across **all** projects — use the
project name. `exposedByDefault=false` means a container without
`traefik.enable=true` is never routed, so projects that have not opted in are
unaffected; they keep whatever host ports they publish, as long as it is not
`:80`.

No `/etc/hosts` entry is needed: systemd-resolved answers every `*.localhost`
name with `::1`, and browsers resolve `.localhost` themselves. The proxy
publishes `80:80`, which binds both families.

## Two traps

### Traefik must be v3.6 or newer

Every Traefik up to and including **v3.5** asks the Docker daemon for API
version **1.24**. Docker Engine 28 dropped everything below **1.40**, so the
docker provider never loads a single router and `DOCKER_API_VERSION` does not
override it — the version is compiled in. The failure is silent: *every*
request 404s, the dashboard included. `make proxy-logs` is the only place that
says why:

```
ERR Failed to retrieve information of the docker client and server host
    error="... client version 1.24 is too old. Minimum supported API version is 1.40 ..."
```

v3.1 and v3.5 were both tested against this daemon and fail; v3.6 and v3.7
negotiate and work. `docker version` shows the daemon's minimum.

### The channel hostname, and what fixtures do to it

Sylius resolves the channel from the **request host**. A channel whose
`hostname` does not match the host resolves to no channel and every shop page
404s — the admin still works, which makes this easy to misdiagnose.

The `FASHION_WEB` channel here has `hostname = NULL`, i.e. *matches any
domain*, which is what lets `sylius-slider.localhost`, the `:82` bypass and the
Behat hosts all work at once. **Reloading Sylius fixtures sets it back to
`localhost`.** After `make load-fixtures` (or a `database-reset`), clear it
again — per environment, since `sylius_dev`, `sylius_test` and `sylius_prod`
are three separate databases:

```bash
docker compose exec -T mysql \
  mysql -uroot -e "UPDATE sylius_channel SET hostname = NULL WHERE code = 'FASHION_WEB';" sylius_dev
# and the same for sylius_prod
```

Check it with:

```bash
docker compose exec -T mysql \
  mysql -uroot -N -e "SELECT code, IFNULL(hostname,'(NULL)') FROM sylius_channel;" sylius_dev
```

**In `sylius_test`, `FASHION_WEB` may stay `NULL`.** The PHPUnit functional
tests create their own `FUNCTIONAL` channel with `hostname = 'localhost'`
(`FunctionalTestCase::ensureChannel()`) and remove it again in `tearDown()`, so
the row exists only while a test runs. Behat drives the stack over the `nginx`
host.

A `FUNCTIONAL` row left by a run from before that cleanup existed is different:
`ensureChannel()` reuses a channel it did not create and never removes it.
Nulling its hostname makes every browser-kit request resolve to no channel, and
the functional tests fail with *"Channel could not be found!"*. That looks like
a plugin regression and is not one. Delete the stale row instead:

```bash
docker compose exec -T mysql mysql -uroot sylius_test -e "
  DELETE cc FROM sylius_channel_currencies cc JOIN sylius_channel c ON c.id = cc.channel_id WHERE c.code = 'FUNCTIONAL';
  DELETE cl FROM sylius_channel_locales cl JOIN sylius_channel c ON c.id = cl.channel_id WHERE c.code = 'FUNCTIONAL';
  DELETE FROM sylius_channel WHERE code = 'FUNCTIONAL';"
```

## Verifying the whole path

```bash
curl -s -o /dev/null -w "shop      -> %{http_code}\n" -L http://sylius-slider.localhost/
curl -s -o /dev/null -w "admin     -> %{http_code}\n"    http://sylius-slider.localhost/admin/login
curl -s -o /dev/null -w "bypass    -> %{http_code}\n"    http://localhost:82/
curl -s -o /dev/null -w "dashboard -> %{http_code}\n" -L http://traefik.localhost/
curl -s -o /dev/null -w "plain :80 -> %{http_code}\n" -4 http://localhost/
```

Expected: `200`, `200`, `302`, `200`, `404`. A `404` on the first two with a
working bypass means the proxy, not the app — read `make proxy-logs` and check
the router on `http://traefik.localhost/dashboard/`.
