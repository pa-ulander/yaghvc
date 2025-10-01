## Changelog

All notable changes to this project are documented in this file.

### [Unreleased]

### [1.2.0] - 2025-10-01

**Focus:** Design Pattern Refactoring.  
Introducing three complementary design patterns.  
Aim is to make it easier to add new features.  
Ie new badge styles, logo types, config options etc.

#### Added
- **Value Object Pattern Implementation:**
  - Created `BadgeRequest` composite value object combining profile identifier, badge configuration, and base count.
  - Created `ProfileIdentifier` value object with GitHub username/repository validation & cache key generation.
  - Created `BadgeConfiguration` value object with immutable badge rendering configuration and style validation.
  - Comprehensive tests for BadgeConfiguration, ProfileIdentifier & BadgeRequest.
  
- **Chain of Responsibility Pattern for Logo Processing:**
  - Implemented modular logo processing handler chain with 5 specialized handlers:
    - `CacheLogoHandler` - Caches processed logos for performance optimization
    - `RawBase64LogoHandler` - Normalizes raw base64 strings to data URIs
    - `UrlDecodedLogoHandler` - Handles percent-encoded data URIs
    - `SlugLogoHandler` - Resolves simple-icons slugs to SVG files
    - `DataUriLogoHandler` - Terminal validator for data URIs (png|jpg|gif|svg+xml)
  - Created `LogoRequest` and `LogoResult` value objects for type-safe logo processing
  - Implemented `LogoProcessorChainFactory` for handler chain building
  - Added comprehensive handler tests ensuring graceful degradation

- **Strategy Pattern for Badge Rendering:**
  - Created `BadgeRendererStrategyInterface` for swappable rendering strategies
  - Implemented factory-based badge renderer creation with style mapping
  - Added post-processing pipeline (logo embedding, color swaps, abbreviation)

#### Changed
- **Code Quality Improvements:**
  - Refactored `ProfileViewsController` from 220 to 130 lines
  - Reduced `LogoProcessor::prepare()` from 170 to 35 lines
  - Removed 5 helper methods from controller (logic moved to value objects)
  - Increased total tests from 366 to 440
  - Eased up on testcoverage. Set lowest limit to 88%. Pretty much a sweet spot in this little app. 

- **Architecture:**
  - Type safety enforced at controller boundaries with immutable value objects
  - Each handler now has single responsibility with clear extension points
  - Factory pattern enables easy addition of new badge styles
  - Single source of truth for validation logic

#### Technical Details
- Bumped PHPStan Level to 9.
- 100% backward compatibility - no breaking changes.
- Existing API endpoint and query parameters work identically

**For complete details, see:** `releasenotes-v1.2.0.md`

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

[Unreleased]: https://github.com/pa-ulander/yaghvc/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.2.0
[1.1.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.1.0
[1.0.0]: https://github.com/pa-ulander/yaghvc/releases/tag/v1.0.0
