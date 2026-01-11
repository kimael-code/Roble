# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-11

### Added
- GitHub Actions CI/CD pipeline with automated testing, linting, and versioning
- PostgreSQL 16 service container for CI/CD tests
- Dependency caching for Composer and npm in workflows
- Comprehensive CI/CD documentation in README
- Conventional Commits guide translated to English
- Auto-versioning workflow using semantic versioning
- GitHub Releases automation

### Changed
- Translated README.md from Spanish to English
- Translated CONVENTIONAL_COMMITS.md to English
- Added language disclaimer for Spanish UI
- Updated install.sh usage documentation (production-only)
- Enhanced test workflow with PostgreSQL support
- Enhanced lint workflow with dependency caching
- Updated contributor information with GitHub profile and portfolio

### Fixed
- Corrected RegistrationTest mock expectations (3 failing tests)
- Configured version workflow to depend on successful test execution
- PostgreSQL connection configuration for CI/CD environment

### Technical Details
- **Tests:** 199/199 passing (100%)
- **CI/CD Platform:** GitHub Actions
- **Database:** PostgreSQL 16 (production and CI/CD)
- **PHP Version:** 8.4
- **Node Version:** 22

---

## [1.0.0] - Initial Release (Not Tagged)

Initial development version of Roble - Monolithic web application starter kit.

### Features
- Laravel 11 + Inertia.js + Vue 3 + Tailwind CSS
- Multi-database support (PostgreSQL)
- Role-based access control (RBAC)
- Activity logging and monitoring
- Two-factor authentication
- User management system
- Organization management
- Maintenance mode
- Real-time notifications (Laravel Reverb)
- PDF export functionality
- Docker support

[1.1.0]: https://github.com/kimael-code/Roble/releases/tag/v1.1.0
