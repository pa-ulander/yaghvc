# Yet Another Github Visitor Counter

[![Tests](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml)
[![Test Coverage](./code_coverage_badge.svg)](https://github.com/pa-ulander/ghvc)
[![Deploy](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml)
![](https://ghvc.kabelkultur.se?username=pa-ulander&label=Repository%20visits&color=brightgreen&style=flat&repository=yaghvc)

A Laravel-based GitHub profile visitor counter and github repository visitor counter that generates customizable SVG badges to display on your GitHub profile or in a repository's README.<br> 
Made only for fun and to try out new latest laravel and testing features. I just wanted to have a visitorcounter on my github profile.

## Usage

Add this to your profile page README.md to show a visitor counter badge:

```markdown
![](https://ghvc.kabelkultur.se?username=your-username)
```

It will generate a visitor counter badge that looks like this:

![](./public_html/assets/default.svg) 


## Usage per repository

Add this to your repository README.md to show a visitor counter badge for a specific repository:

```markdown
![](https://ghvc.kabelkultur.se/?username=your-username&repository=my-repository&label=my-repository%20Views)
```

![](./public_html/assets/repository.svg) 


## Badge Customization Options

The visitor counter badge can be customized with the following URL parameters:

| Parameter     | Description                                             | Default       | Example Values                                                       |
| ------------- | ------------------------------------------------------- | ------------- | -------------------------------------------------------------------- |
| `username`    | GitHub username (required)                              | -             | `username=your-username`                                             |
| `label`       | Text label displayed on the badge                       | Visits        | `label=Profile Views`                                                |
| `color`       | Badge right-side (value) color                          | blue          | `color=green`, `color=red`, `color=ff5500`                           |
| `labelColor`  | Left-side label background color (named or hex w/out #) | blue          | `labelColor=red`, `labelColor=ffd700`                                |
| `style`       | Badge style                                             | for-the-badge | `style=flat`, `style=flat-square`, `style=plastic`                   |
| `base`        | Starting count value added to stored counter            | 0             | `base=100`                                                           |
| `abbreviated` | Abbreviate large numbers (1.2K, 3.4M)                   | false         | `abbreviated=true`                                                   |
| `repository`  | Count visits in a repository (scopes counter)           | (none)        | `repository=my-repo`                                                 |
| `logo`        | Data URI image (png,jpg,gif,svg) OR simple-icons slug   | (none)        | `logo=github`, `logo=laravel`, `logo=data:image/png;base64,iVBOR...` |
| `logoSize`    | Logo sizing: 'auto' (SVG adapt) or fixed px (8-64)      | 14            | `logoSize=auto`, `logoSize=32`                                       |
| `logoColor`   | Recolor SVG/simple-icon logo (named or hex, no #)       | (none)        | `logoColor=red`, `logoColor=ff8800`, `logoColor=brightgreen`         |


## Examples

### Different Styles

| Style           | Example                                           | Markdown                                                                      |
| --------------- | ------------------------------------------------- | ----------------------------------------------------------------------------- |
| `for-the-badge` | ![](./public_html/assets/style-for-the-badge.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&style=for-the-badge)` |
| `flat`          | ![](./public_html/assets/style-flat.svg)          | `![](https://ghvc.kabelkultur.se?username=your-username&style=flat)`          |
| `flat-square`   | ![](./public_html/assets/style-flat-square.svg)   | `![](https://ghvc.kabelkultur.se?username=your-username&style=flat-square)`   |
| `plastic`       | ![](./public_html/assets/style-plastic.svg)       | `![](https://ghvc.kabelkultur.se?username=your-username&style=plastic)`       |


### Custom Colors

| **Named Color** | Example                                    | Markdown                                                                    |
| --------------- | ------------------------------------------ | --------------------------------------------------------------------------- |
| `brightgreen`   | ![](./public_html/assets/color-green.svg)  | `![](https://ghvc.kabelkultur.se?username=your-username&color=brightgreen)` |
| `red`           | ![](./public_html/assets/color-red.svg)    | `![](https://ghvc.kabelkultur.se?username=your-username&color=red)`         |
| `orange`        | ![](./public_html/assets/color-orange.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=orange)`      |
| `yellow`        | ![](./public_html/assets/color-yellow.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=yellow)`      |



| **Hex Color** | Example                                  | Markdown                                                               |
| ------------- | ---------------------------------------- | ---------------------------------------------------------------------- |
| `ffd700`      | ![](./public_html/assets/hex-ffd700.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=ffd700)` |
| `e34234`      | ![](./public_html/assets/hex-e34234.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=e34234)` |
| `6a0dad`      | ![](./public_html/assets/hex-6a0dad.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=6a0dad)` |
| `00b7eb`      | ![](./public_html/assets/hex-00b7eb.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=00b7eb)` |


> **Note:** You must specify hex colors without the `#` prefix (e.g., `f000ff` instead of `#f000ff`).

| **Custom Label**      | Example                                 | Markdown                                                                            |
| --------------------- | --------------------------------------- | ----------------------------------------------------------------------------------- |
| `Profile%20Visitors`  | ![](./public_html/assets/label-pfv.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Profile%20Visitors)`  |
| `Chocolate%20Cookies` | ![](./public_html/assets/label-cho.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Chocolate%20Cookies)` |
| `Horsepowers`         | ![](./public_html/assets/label-hp.svg)  | `![](https://ghvc.kabelkultur.se?username=your-username&label=Horsepowers)`         |


### Number Abbreviation

Display large numbers in abbreviated format (1K, 1.5M, etc.):

```markdown
![](https://ghvc.kabelkultur.se?username=your-username&abbreviated=true)
```
![](./public_html/assets/abbr.svg) 


### Full Customization Example

```markdown
![](https://ghvc.kabelkultur.se?username=your-username&label=Visitors&color=orange&style=for-the-badge&abbreviated=true)
```
![](./public_html/assets/full.svg) 

### Label Background Color (labelColor)

You can style the left label segment independently from the value segment using `labelColor`:

````markdown
![](https://ghvc.kabelkultur.se?username=your-username&labelColor=red)
````

### Logo Usage (Slug vs Base64 Data URI)

The `logo` parameter supports either a simple-icons slug (preferred for brevity) or a full base64 data URI.

Slug examples:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=github)
![](https://ghvc.kabelkultur.se?username=your-username&logo=laravel)
````

Small PNG via data URI:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==)
````

![](https://ghvc.kabelkultur.se?username=your-username&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==)


SVG with automatic aspect scaling:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=<your-encoded-svg-data-uri>&logoSize=auto)
````

Sizing:
````
logoSize=auto   # scale width to maintain intrinsic aspect ratio at target height
logoSize=32     # fixed square size (clamped to configured max)
````

Security & Limits:
- MIME types accepted: png, jpeg, jpg, gif, svg+xml
- Max decoded bytes: `config('badge.logo_max_bytes')` (default 10000)
- Max raster dimension: `config('badge.logo_max_dimension')`
- Invalid / oversize input: silently ignored (badge still renders)

### Logo Color (logoColor)

`logoColor` lets you recolor supported SVG logos (simple-icons slugs or inline SVG data URIs). It is ignored for raster images (png/jpg/gif) and for SVGs where a safe unified replacement cannot be determined. If you omit `logoColor` for a simple-icons slug, a subtle neutral default `f5f5f5` is applied automatically.

Accepted formats mirror `color` / `labelColor`:

```
logoColor=red
logoColor=ff8800
logoColor=brightgreen
```

Behavior:
* If the SVG uses `currentColor`, a `fill` is added to the root `<svg>` and `currentColor` tokens are replaced.
* Otherwise existing solid `fill="#XXXXXX"` values (not `none` / gradients) are replaced uniformly.
* If no fills are found, a `fill` is injected into the first `<path>` element.
* Fails safely (returns original logo) on parse anomalies — never breaks the badge.

Notes:
* Does NOT attempt to recolor gradients, masks, or more complex paint servers.
* Raster logos (PNG/JPG/GIF) are unaffected (silently ignored).
* Default for simple-icons when omitted: `f5f5f5`.
* Works well with monochrome simple-icons whose original paths are single-color.

Example:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=github&logoColor=orange)
````

`logoColor=auto`

Use `auto` to automatically select a contrasting monochrome color for the logo relative to the label background:

Algorithm:
1. Determine base background: explicit `labelColor` if provided; otherwise the existing label segment color (initially `#555`), else the message segment color.
2. Convert to RGB and compute perceived brightness: `0.299*R + 0.587*G + 0.114*B`.
3. Brightness < 128 → light logo `f5f5f5`; otherwise dark logo `333333`.

Happens before applying the simple-icons default so `auto` always overrides the neutral fallback.

Additional examples:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=github&logoColor=auto)
![](https://ghvc.kabelkultur.se?username=your-username&logo=github&labelColor=yellow&logoColor=auto)
````

Chaining with size:
````markdown
![](https://ghvc.kabelkultur.se?username=your-username&logo=github&logoSize=auto&logoColor=ff0000)
````

#### Encoding Requirements (Important)

When supplying a base64 data URI as the `logo` parameter you MUST percent‑encode (URL encode) the entire value before adding it to the query string. Raw (unencoded) data URIs can be corrupted by shells, markdown renderers, or HTTP clients (notably `+` may be turned into a space) causing the logo to be rejected.

Incorrect (raw, may break):
````markdown
![](https://ghvc.kabelkultur.se?username=you&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ...)
````

Correct (percent‑encoded):
````markdown
![](https://ghvc.kabelkultur.se?username=you&logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ...)
````

How to encode:
* JavaScript: `encodeURIComponent('data:image/png;base64,' + b64)`
* Bash (GNU coreutils): `python - <<'PY'\nimport urllib.parse,sys;print(urllib.parse.quote(sys.stdin.read().strip(), safe=''))\nPY`
* PHP: `rawurlencode($dataUri)`

If you supply an unencoded data URI, validation will now return HTTP 422 with a message indicating encoding is required.

Caching: Prepared logos may be cached (TTL in `config/badge.php`) by content hash + size.

Icon catalog: https://simpleicons.org (files located in `vendor/simple-icons/simple-icons/icons`).

### FAQ

#### Why did my logo disappear?
Common causes:
| Cause                          | Symptom                           | Fix                                                            |
| ------------------------------ | --------------------------------- | -------------------------------------------------------------- |
| Not percent-encoded data URI   | Badge renders but no `<image>`    | URL‑encode entire `logo` value (see Encoding Requirements)     |
| Invalid simple-icons slug      | 422 validation or silent fallback | Use a valid slug from simpleicons.org                          |
| Payload too large (bytes)      | Logo missing                      | Reduce image size or compress (limit: `logo_max_bytes`)        |
| Raster dimensions exceed limit | Logo missing                      | Resize to within `logo_max_dimension`                          |
| MIME not allowed               | 422 or silent drop                | Use png, jpg, jpeg, gif, or svg+xml                            |
| Spaces turned plus signs       | Base64 rejected                   | Ensure tool didn’t convert `+` to space; always percent‑encode |

If validation fails you’ll get HTTP 422 with a JSON body. If validation succeeds but the logo is invalid at processing time it degrades silently to avoid breaking the badge.

### Helper: Encoding a Logo Locally

You can generate a percent‑encoded data URI using the provided helper script:

```bash
php scripts/encode-logo.php path/to/logo.png > encoded.txt
cat encoded.txt  # paste after &logo=
```

Want a ready-to-paste Markdown snippet (with a username placeholder) in one step? Use `--inline`:

```bash
php scripts/encode-logo.php path/to/logo.png --inline
# Outputs: ![](https://ghvc.kabelkultur.se?username=<your-username>&logo=data%3Aimage%2F...)
```

Specify MIME manually if the extension is non-standard:

```bash
php scripts/encode-logo.php path/to/custom-image.bin --mime=image/png
```

For SVG:
```bash
php scripts/encode-logo.php path/to/icon.svg > encoded.txt
```

The script outputs ONLY the encoded string (no newline) so you can inline it:

```bash
ENC=$(php scripts/encode-logo.php assets/icon.svg); curl "https://ghvc.kabelkultur.se?username=you&logo=$ENC"

Or directly with `--inline`:

```bash
php scripts/encode-logo.php assets/icon.svg --inline
```

### Quick Local Fetch & Verification

For rapid local experimentation (ensuring an `<image>` actually embeds) use the helper fetch script:

```bash
scripts/fetch-badge.sh -u your-username --logo-slug github --style flat
scripts/fetch-badge.sh -u your-username --logo-file path/to/logo.png --label "Profile Views" --style for-the-badge
```

It writes `badge.svg` (override with `--out`) and reports whether an `<image>` tag was found. The script will percent‑encode a file‑derived data URI automatically.

### Logo Troubleshooting (Condensed)

1. Always percent‑encode full data URIs (or let the helper scripts do it).
2. Verify locally: open the resulting `badge.svg` and search for `<image`.
3. Keep size under configured byte & dimension limits (see `config/badge.php`).
4. If a slug fails, confirm it exists in `vendor/simple-icons/simple-icons/icons`.
5. Raster looks blurry? Supply a higher resolution and rely on downscaling.

Still stuck? Reduce to a 1x1 PNG data URI (shown above) to confirm the pipeline, then substitute your real asset.
```

### Rate Limiting & Caching Strategy

To remain robust under high traffic:
- Per-IP rate limit (default 60 req/min) via Laravel `RateLimiter` (`routes/web.php`).
- Short-lived caching semantics: response includes `Cache-Control: public, max-age=1, s-maxage=1, stale-while-revalidate=5` and an `ETag` for conditional requests.
- Clients can revalidate quickly; counts remain fresh while repeated identical badge views avoid recomputing within a one‑second window.

You may tune these by editing the limiter definition or exposing env-driven limits (e.g. `BADGE_RATE_LIMIT`).

#### Potential Caching Header Tuning (With vs Without Logos)

Possible future optimization differentiates cache lifetimes:

* **No logo provided** – badge is purely text/geometry; identical requests are common. Use a slightly longer `max-age` (e.g. 5–10s) plus generous `stale-while-revalidate` to reduce backend load.
* **Logo provided** – especially data URI logos (unique per user) have low re-use. Keep conservative 1s freshness to reflect counts quickly.

Illustrative example:
```
# No logo
Cache-Control: public, max-age=10, s-maxage=10, stale-while-revalidate=30

# With logo
Cache-Control: public, max-age=1, s-maxage=1, stale-while-revalidate=5
```

Benefits:
1. Improves CDN edge hit ratio for high-volume plain badges.
2. Avoids over-caching many near-unique logo variants.
3. Preserves fast count visibility for logo variants.

Implementation outline:
1. After rendering, check if `<image` exists (or if `logo` query absent) and choose header profile.
2. Optionally allow `cache=off` query for debugging zero-cache responses.
3. Measure baseline QPS and hit ratios before enabling to validate improvement.

Current policy is intentionally conservative; only pursue tuning if traffic profile shows backend strain.

## Self-hosting

This is a Laravel-based application that can be self-hosted. See the project structure and Docker configuration for details on how to set up your own instance.

## Acknowledgments
- [Badges](https://github.com/badges) for the cool [Poser](https://github.com/badges/poser) library. A php library that creates badges.

- [Awesome Badges](https://github.com/badges/awesome-badges) a curated list of awesome badge things. 


## License

[MIT](LICENSE)

