### Laravel 13 upgrade release

This release is primarily about upgrading the application from **Laravel 12** to **Laravel 13**.

### Highlights

- **Laravel Framework upgraded from `v12.51.0` -> `v13.7.0`**.
- Updated related Laravel ecosystem package constraints to match Laravel 13 compatibility.
- Refreshed supporting dependencies and tooling to keep the stack aligned.

### Changed

- Upgraded key dependencies in this cycle:
	- Laravel Framework: `v12.51.0` -> `v13.7.0`
	- Laravel Sanctum: `v4.3.1` -> `v4.3.1` (compatibility-validated, no version jump)
	- Laravel Tinker: `v2.11.0` -> `v3.0.2`
	- Laravel Boost: `v2.2.1` -> `v2.4.6`
	- Laravel Sail: `v1.53.0` -> `v1.58.0`
	- Laravel Pint: `v1.27.1` -> `v1.29.1`
	- simple-icons: `16.10.0` -> `16.18.0`
- Updated cache configuration defaults for current framework behavior:
	- revised cache prefix format
	- added `serializable_classes` support
- Aligned CSRF middleware naming with current Laravel conventions:
	- switched to `PreventRequestForgery`
- Refined local development and automation setup:
	- Docker ports and DNS config updates
	- Makefile/composer command adjustments
	- shell prompt cleanup
- Updated QA/tooling configuration:
	- PHPUnit schema + `cacheDirectory`
	- coverage clover path/metrics adjustments
	- PHPStan temp path cleanup

### Tests

- Added stronger assertions for label and status handling in logo embedding SVG tests.
- Removed temporary test result artifacts and refreshed test command behavior.

### Tooling

- Added MCP server configuration for laravel-boost.
- Added `node_modules` to ignore rules.

**Changelog**: https://github.com/pa-ulander/yaghvc/blob/main/CHANGELOG.md 

**Full Changelog**: https://github.com/pa-ulander/yaghvc/compare/v1.3.1...v1.4.0