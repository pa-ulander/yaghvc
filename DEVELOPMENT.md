# Development Guide

> For end‑user badge usage see `README.md`. This document is for contributors/self‑hosters extending or operating the service.

## Quick Start (Docker Recommended)

Prerequisites: `docker` + `docker compose`, GNU `make`.

```bash
# Start full stack (PHP FPM + Apache/Nginx proxy defined in docker-compose + MySQL)
make            # alias of `make up`

# Enter application container shell
make bash

# Run the test suite (inside container)
make test-php   # or: composer test

# Stop services
make down
```

First run will:
1. Build images.
2. Install Composer dependencies.
3. Copy `.env.example` → `.env` if missing.
4. Run migrations.

App served at: `http://localhost:8080` (adjust if you changed compose ports). Badge endpoint: `http://localhost:8080/?username=dev-user`.

## Native (Non‑Docker) Setup

Prerequisites: PHP 8.4, Composer, MySQL/MariaDB (or SQLite), Node (only if you add front‑end assets), `make` optional.

```bash
cp .env.example .env
composer install --no-interaction --prefer-dist
php artisan key:generate
# Configure DB credentials in .env then:
php artisan migrate
php artisan serve --port=8080
```

Visit: `http://127.0.0.1:8080/?username=dev-user`

## Architecture Overview

Request path (core badge endpoint):
```
GET / -> ProfileViewsRequest (validation) -> ProfileViewsRepository::findOrCreate()
         -> ProfileViews model (increments + 1s cached count)
         -> BadgeRenderService (creates base SVG via badges/poser, post-process: labelColor, logo)
         -> SVG response (no long-term cache; short max-age, ETag)
```

Key components:
- `app/Http/Requests/ProfileViewsRequest.php`: Defines & sanitizes query params; add new params only here.
- `app/Repositories/ProfileViewsRepository.php`: Central persistence + increment semantics.
- `app/Models/ProfileViews.php`: 1-second in-memory (cache) count retrieval + invalidation.
- `app/Services/BadgeRenderService.php`: Style selection, color resolution, numeric formatting, logo embedding.
- `app/Services/LogoProcessor.php`: Resolves simple‑icons slugs or validates/normalizes data URI logos; size & safety checks.

## Adding a New Query Parameter

1. Add rule + preprocessing inside `ProfileViewsRequest` (override `rules()` & `prepareForValidation()` as existing patterns show).
2. Use sanitized value in controller (already type-hinted) – extend method signature if necessary.
3. If it affects rendering, add an optional parameter to `BadgeRenderService::renderBadge(...)` (named arguments) and propagate logic.
4. Write tests:
   - Validation failure (422 JSON) for an invalid case.
   - Successful render containing expected SVG fragment.

## Adding a New Badge Style

1. Extend allowed style list in `BadgeRenderService` (constant / internal array).
2. Map style → poser style name if required.
3. Provide an asset example (optional) under `public_html/assets/` for documentation.
4. Test: request with `style=<new>` -> assert `<svg` present & style differentiator (e.g. class or geometry) appears.

## Logo Handling Basics

- Simple icons are loaded from `vendor/simple-icons/simple-icons/icons/<slug>.svg`.
- Data URI logos must be percent‑encoded (enforced at validation layer). See `README.md` for encoding helpers.
- Recoloring logic (for slugs/SVG) is intentionally narrow to remain safe; raster images ignored for recolor.
- Caching keyed by content hash + size (TTL in `config/badge.php`).

### Encoding Requirements (Logo Data URIs)

When supplying a base64 data URI via the `logo` query parameter you MUST percent‑encode the full data URI before adding it to the query string. Raw (unencoded) data URIs are frequently mangled (`+` → space, `#` treated as fragment, etc.) and will fail validation (HTTP 422) or be silently dropped at processing time.

How to encode:
* JavaScript: `encodeURIComponent('data:image/png;base64,' + b64)`
* Bash (GNU coreutils):
    ```bash
    python - <<'PY'
import urllib.parse,sys;print(urllib.parse.quote(sys.stdin.read().strip(), safe=''))
PY
    ```
* PHP: `rawurlencode($dataUri)`

If a data URI is not properly encoded the request returns 422 with a validation error prompting you to encode it.

Caching: Prepared logos may be cached (TTL in `config/badge.php`) by content hash + requested size. Cache invalidation occurs naturally if content changes (different hash) or TTL expires.

Icon catalog: https://simpleicons.org (files located locally under `vendor/simple-icons/simple-icons/icons`).

### Helper Scripts

`scripts/encode-logo.php` – produces a percent‑encoded data URI (optionally an inline Markdown snippet).

Examples:
```bash
# Basic encode to stdout (no trailing newline):
php scripts/encode-logo.php path/to/logo.png > encoded.txt

# Immediate Markdown snippet with placeholder username:
php scripts/encode-logo.php path/to/logo.png --inline

# Specify MIME manually (nonstandard extension):
php scripts/encode-logo.php blob.bin --mime=image/png

# SVG encode:
php scripts/encode-logo.php assets/icon.svg > encoded.txt

# Inline var usage:
ENC=$(php scripts/encode-logo.php assets/icon.svg); curl "http://localhost:8080/?username=you&logo=$ENC"

# Same with --inline convenience:
php scripts/encode-logo.php assets/icon.svg --inline
```

`scripts/fetch-badge.sh` – convenience fetcher & validator writing `badge.svg` (override with `--out`), reports whether an `<image>` tag was embedded.

```bash
scripts/fetch-badge.sh -u your-username --logo-slug github --style flat
scripts/fetch-badge.sh -u your-username --logo-file path/to/logo.png --label "Profile Views" --style for-the-badge
```

### Logo Troubleshooting Quick Reference

1. Always percent‑encode full data URIs (or let helper encode script do it).
2. Open resulting `badge.svg` and search for `<image` to confirm embedding.
3. Keep under size & dimension limits (`config/badge.php`).
4. For slug failures verify presence in `vendor/simple-icons/simple-icons/icons`.
5. Blurry raster? Start with a higher resolution and allow downscaling.
6. Still failing? Test with a known tiny 1x1 PNG data URI to ensure pipeline integrity.

### FAQ: Logo Disappearance Causes

| Cause                          | Symptom                           | Fix                                                            |
| ------------------------------ | --------------------------------- | -------------------------------------------------------------- |
| Not percent-encoded data URI   | Badge renders but no `<image>`    | Percent‑encode entire `logo` value (see Encoding Requirements) |
| Invalid simple-icons slug      | 422 validation or silent fallback | Use valid slug from simpleicons.org                            |
| Payload too large (bytes)      | Logo missing                      | Reduce image size/compress (< `logo_max_bytes`)                |
| Raster dimensions exceed limit | Logo missing                      | Resize within `logo_max_dimension`                             |
| MIME not allowed               | 422 or silent drop                | Use png, jpg, jpeg, gif, or svg+xml                            |
| Spaces turned plus signs       | Base64 rejected                   | Ensure `+` not converted to space; always percent‑encode       |

Validation failures produce HTTP 422 JSON. Post-validation processing failures always degrade gracefully (badge still renders, no logo).

## Caching & Performance

- Visit counts: 1 second cache window to prevent excessive DB writes on rapid reloads.
- Logo prep: optional cache (same TTL logic) to avoid reparsing identical logos.
- Response headers: `Cache-Control: public, max-age=1, s-maxage=1, stale-while-revalidate=5` currently.
- Optimization ideas (not implemented): differentiate cache lifetime when no logo supplied; CDN edge layer.

### Advanced Header Strategy (Future Option)

Differentiate cache policy by presence of a logo:

```
# No logo
Cache-Control: public, max-age=10, s-maxage=10, stale-while-revalidate=30

# With logo
Cache-Control: public, max-age=1, s-maxage=1, stale-while-revalidate=5
```

Rationale:
1. Plain (text-only) badges have higher repeat view rates – longer `max-age` improves edge hit ratios.
2. Logo variants (especially data URIs) are often unique – keep them fresh to reflect counts quickly.
3. Maintains fast feedback for users customizing logos while reducing backend load for common plain cases.

Suggested implementation outline:
1. After rendering, detect presence of `<image` (or absence of `logo` query) to classify variant.
2. Apply header profile accordingly; consider an override query like `cache=off` for debugging.
3. Instrument baseline QPS, memory, and response times before + after to validate benefit.

Leave current conservative policy unless metrics indicate strain.

## Rate Limiting

Defined in `routes/web.php` via Laravel's `RateLimiter`. Default ~60/min/IP. Adjust as needed; add environment variable indirection if operating at scale.

## Testing

All in Pest (`tests/Feature` & `tests/Unit`). Run:
```bash
composer test           # full: Pint (style) + PHPStan + Pest + coverage
composer test:lint      # style only
composer test:analyze   # PHPStan only
```

Target: 100% type coverage; do not regress coverage in PRs if possible.

### Writing Tests

Patterns:
- Feature test for each new parameter: success + failure.
- When adding abbreviation logic, reuse `BadgeRenderService::formatAbbreviatedNumber` (don’t duplicate formatting).
- For logo edge cases: oversize payload, invalid MIME, recolor attempts on raster; must degrade gracefully (badge still valid).

Example skeleton:
```php
it('rejects invalid foo parameter', function () {
    $this->get('/?username=test&foo=!!!')->assertUnprocessable();
});

it('renders with foo parameter', function () {
    $svg = $this->get('/?username=test&foo=bar')->assertOk()->getContent();
    expect($svg)->toContain('foo-derived-fragment');
});
```

## Static Analysis & Style

- Style: Laravel Pint (`composer test:lint-fix` to auto-fix).
- Static analysis: PHPStan (config in `phpstan.neon`).
- CI runs both; local pre-commit recommended.

Suggested local workflow:
```bash
composer test:lint
composer test:analyze
composer test -- --filter=YourNewFeature
```

## Troubleshooting

| Symptom              | Likely Cause                      | Where to Look                               |
| -------------------- | --------------------------------- | ------------------------------------------- |
| Logo silently absent | Size/MIME/dimension rejection     | `LogoProcessor` checks + config limits      |
| Label color ignored  | Invalid color normalized/fallback | `BadgeRenderService::getHexColor`           |
| Count stale >1s      | Cache TTL working                 | `ProfileViews::getCount`                    |
| 422 validation       | Param sanitization removed chars  | `ProfileViewsRequest::prepareForValidation` |
| Abbreviation wrong   | Rounding expectation mismatch     | `formatAbbreviatedNumber`                   |

## Self Hosting Notes

- Minimal state: only `profile_views` (or equivalent) table stores counters.
- Horizontal scaling: share DB + enable a distributed cache (Redis) if traffic significant; current 1s count cache is local—consider central store for perfect cross-instance consistency.
- Backups: simple logical dump of the counter table is usually enough.
- Security: Input validation strict; still advisable to place behind CDN (for caching + rudimentary DDoS absorption).

## Production Hardening Ideas (Optional)

- Add health endpoint (`/healthz`) returning DB connectivity + version hash.
- Introduce adaptive rate limiting (higher burst for GitHub IP ranges).
- Metrics endpoint (Prometheus) counting badge renders / validation failures.
- ETag optimization by hashing only dynamic segments, caching static left segment templates.

## Release Process

1. Ensure main branch is green (tests & coverage).
2. Update `CHANGELOG.md` (Unreleased -> version section) following Keep a Changelog style.
3. Tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z" && git push --tags`.
4. Automated deploy (see GitHub Actions) publishes updated container / artifacts.

## Contributing Checklist

1. Fork & branch (`feature/<short-name>`).
2. Add/update tests to cover new behavior.
3. Run `composer test` until green.
4. Update documentation (`README.md` and/or this file) if user-visible change.
5. Open PR describing rationale + screenshots/SVG diff snippets if UI-affecting.

## Code Reading Pointers

Start with `routes/web.php` to see entrypoints. Follow the validation class, then repository, then service objects. Keep controllers thin, prefer injecting services via constructor for testability.

## Adding a New Endpoint (JSON or Other)

1. Define route in `routes/web.php` (or `api.php` if versioned JSON API needed).
2. Create Form Request for any inputs.
3. Implement controller or invokable class.
4. Return cached response where appropriate (`Cache::remember` etc.) but do not block the main badge path.

## Environment Variables of Interest

| Variable               | Purpose                              | Default                |
| ---------------------- | ------------------------------------ | ---------------------- |
| `APP_ENV`              | Laravel environment                  | local                  |
| `APP_DEBUG`            | Debug flag                           | true (local)           |
| `DB_*`                 | Database credentials                 | varies                 |
| `LOG_CHANNEL`          | Logging driver                       | stack                  |
| `CACHE_STORE`          | Cache backend                        | file                   |
| `BADGE_LOGO_CACHE_TTL` | (If exposed) logo prep cache seconds | see `config/badge.php` |

(Expose more via config editing if/when needed.)

## Performance Profiling (Ad hoc)

Use built-in Laravel debug tools locally or add temporary timing logs around `BadgeRenderService::renderBadge` if investigating slowdowns. Avoid persistent verbose logging in production.

## Security Considerations

- All user input is query-string; only whitelisted params pass validation.
- Data URI logos are size and MIME constrained; large/complex SVGs sanitized by controlled recolor logic (no DOM execution context since we return raw SVG, but still avoid scriptable content).
- Consider adding Content Security Policy headers if embedding in iframes elsewhere (not strictly required for plain SVG images).

## FAQ (Developer Focused)

Q: Why not increment asynchronously?  
A: Simplicity + single lightweight write per unique hit (with 1s cache). Could offload later to queue for extreme scale.

Q: Can we store per-day metrics?  
A: Extend the repository to write to a `profile_views_daily` table keyed by date; keep existing flow unchanged so badges unaffected.

Q: How to invalidate a mistaken logo cache entry?  
A: Adjust logo content (salt query param) or clear cache store.

## Style & Naming Conventions

- Named arguments in service calls for clarity.
- `strict_types=1` at top of new PHP files.
- Prefer early returns over deeply nested conditionals.
- Keep public surface small; internal helper methods `private` unless tests need visibility (then `protected`).

## Future Enhancements (Backlog Candidates)

- Adaptive caching profile (logo vs no-logo).
- Daily/weekly aggregate endpoints.
- Dark mode variant style(s).
- Multi-tenant domain / vanity host support.
- Eager simple-icons slug existence cache (in-memory array warmed at boot).

---
Happy hacking! Open an issue for any clarification you wish this guide included.
