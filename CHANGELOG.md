## Changelog

All notable changes to this project are documented in this file.

### [1.1.0] - 2025-10-01

#### Added
- Advanced badge customization parameters: `labelColor`, `logo`, `logoColor`, and `logoSize` with extensive validation and sanitization.
- Logo processing pipeline that normalizes simple-icons slugs, SVG assets, and raster/base64 data URIs while enforcing size and safety limits.
- Automatic logo color derivation for SVG assets plus optional recoloring support.
- Accessibility improvements that keep badge titles and aria-labels in sync with rendered content.
- Additional helper scripts for diagnosing logos and fetching rendered badges.

#### Changed
- Badge rendering flow now embeds logos before color post-processing and ensures label recoloring works across all supported Poser styles.
- Improved cache, configuration, and query sanitizer handling to better enforce defaults and type casting.

#### Fixed
- Repaired numerous edge cases in logo parsing, including loose base64 input, percent-encoded payloads, and malformed SVG headers.
- Hardened request preparation to normalize mixed-case data URIs and repair base64 payload spacing.

#### Documentation
- Expanded README and DEVELOPMENT guides with logo usage walkthroughs, new query parameter descriptions, and troubleshooting tips.
- Added coverage badges and examples illustrating color, style, and logo combinations.

#### CI / Tooling
- Raised PHPStan analysis to level 9 and registered a custom rule preventing named arguments on global functions.
- Added PHPCS sniff coverage for the new rule set and refreshed workflow/autoload configuration for diagnostics.

### [1.0.0] - 2025-09-14

Initial stable release derived from the accumulated work on `main` up to this date.

#### Added
- Dynamic SVG badge generation pipeline (Poser) tracking profile view counts.
- Database persistence for per-username counts with caching and invalidation (1s freshness window).
- (Early) rate limiting & access control refinements including GitHub Camo user agent restriction.
- Test infrastructure: Pest test suite, coverage reporting, static analysis (Larastan / PHPStan), code style (Pint / PHP_CodeSniffer) integration.
- Continuous Integration workflows (tests, coverage badge publication, dependency updates via Dependabot schedule).
- Deployment workflow configuration and environment setup (Docker, GitHub Actions deploy steps, PHP version matrix prep).

#### Changed
- Iterative refactors improving validation, cache handling, readability, and consistent use of named parameters.
- Configuration refinements (cache, tooling, composer scripts) and Dockerfile improvements.

#### Fixed
- Minor README and workflow typos.
- Workflow/composer cache identifiers adjusted for reliability.

#### Documentation
- Expanded README with setup instructions, badge usage description, and coverage/CI badges.
- Added MIT License.

#### CI / Tooling
- Automated coverage badge updates via GitHub Actions bot.
- Added coding standards and static analysis enforcement in the test pipeline.

### Historical (Pre-1.0.0) Work
Early WIP commits (March–October 2024) established the foundational Laravel application structure, initial badge endpoint, and iterative configuration/testing scaffolding before formal versioning.

[Unreleased]: https://github.com/pa-ulander/yaghvc/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.1.0
[1.0.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.0.0
