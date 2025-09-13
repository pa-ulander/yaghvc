## Changelog

All notable changes to this project are documented in this file.

The format follows Keep a Changelog and the project aims to follow [Semantic Versioning](https://semver.org/).

### [Unreleased]

### [1.0.0] - 2025-09-08

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

[Unreleased]: https://github.com/pa-ulander/yaghvc/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.0.0
