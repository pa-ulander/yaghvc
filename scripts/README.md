# scripts/ dev utils

Information intended for self hosters and code fiddlers :)  
This folder `./scripts` contains some dev and troubleshooting utils.

## Overview

| File                      | Type        | Keep?                     | Purpose                                                                                                                                                    |
| ------------------------- | ----------- | ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `encode-logo.php`         | PHP CLI     | Yes                       | Converts an image (PNG/JPG/GIF/SVG) into a **percent‑encoded data URI** ready for `logo=` query param usage. Avoids manual base64 + URL encoding mistakes. |
| `fetch-badge.sh`          | Bash script | Yes (if you do manual QA) | Convenience wrapper around `curl` to fetch a badge with parameters, write `badge.svg`, and (optionally) inspect for `<image>` embedding.                   |
| `diagnose_raw_logo.php`   | PHP CLI     | Optional                  | Ad‑hoc parser / normalization diagnostic for raw base64 or data URI inputs. Useful only when debugging edge cases. Can be removed if not needed.           |
| `diagnose_raw_output.svg` | Artifact    | Removable                 | Example / leftover output from a diagnostic run. Not used anywhere; safe to delete.                                                                        |

---
## `encode-logo.php`
Helper for producing a fully percent‑encoded data URI (the preferred + fastest path for the service). Prevents issues like:
* `+` becoming space in query strings
* `#` truncating the URL
* Missed `data:image/...;base64,` prefix

### Usage
```bash
php scripts/encode-logo.php path/to/logo.png > encoded.txt

# Inline convenience snippet
php scripts/encode-logo.php path/to/logo.svg --inline
```

### Output Modes
* Raw percent‑encoded data URI (default)
* Markdown snippet when `--inline` flag is supplied

### Typical Integration
```bash
ENC=$(php scripts/encode-logo.php assets/icon.svg)
curl "http://localhost:8080/?username=dev-user&logo=$ENC"
```

---
## `fetch-badge.sh`
Quickly fetches and stores a badge for inspection. Helps validate geometry adjustments and logo embedding without writing longer curl commands.

### Basic Example
```bash
scripts/fetch-badge.sh -u dev-user --logo-slug github --style flat
open badge.svg  # (macOS) or xdg-open badge.svg (Linux)
```

### With Custom Data URI Logo
```bash
ENC=$(php scripts/encode-logo.php assets/icon.svg)
scripts/fetch-badge.sh -u dev-user --logo "$ENC" --label "Profile Views" --style for-the-badge
```

### Why Use It?
* Normalizes repeated testing steps
* Ensures consistent parameters across runs
* Makes it easy to diff rendered SVGs after code changes

---
## `diagnose_raw_logo.php`
Low‑level probe script. Use only when investigating *why* a logo failed validation or wasn’t embedded.

### Typical Workflow
1. Copy a failing `logo` query param value (raw or encoded)
2. Pipe / pass it to the script
3. Inspect parsed MIME, size, sanitation steps

If you rarely debug icon edge cases, feel free to delete this script.

### Potential Future Replacement
If diagnostic needs grow, consider a dedicated `artisan` command (e.g. `php artisan badge:diagnose --logo <value>`), which would integrate better with Laravel’s environment.

---
## `diagnose_raw_output.svg`
A sample SVG produced during a diagnostic run. Not referenced; safe to remove. Add similar transient outputs to `.gitignore` if you regenerate them frequently.

---
## Removal / Cleanup Guidelines
You can trim this folder to only the two commonly useful scripts:
```bash
git rm scripts/diagnose_raw_logo.php scripts/diagnose_raw_output.svg
```
Tests and application runtime will remain unaffected.

---
## Troubleshooting Cheat Sheet (Logos)
| Symptom                        | Suspect                       | Script to Use                                            |
| ------------------------------ | ----------------------------- | -------------------------------------------------------- |
| Badge renders but no `<image>` | Validation drop or size limit | `fetch-badge.sh` + inspect; then `diagnose_raw_logo.php` |
| 422 validation error           | Bad data URI encoding         | `encode-logo.php` to regenerate                          |
| Wrong color (recolor ignored)  | Raster image                  | Use SVG instead (encode with `encode-logo.php`)          |

---
