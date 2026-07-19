# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2026-07-19

### Added

- Added a URL inventory dashboard with filtering, sorting, pagination, and import-status tracking.
- Added CSV URL imports with column mapping, row filters, duplicate updates, and progressive chunk processing.
- Added tools to assign URLs to batches, unassign or remove them, and manually associate URLs with imported pages.
- Added automatic URL status synchronization from batch import logs.
- Added transformers for converting relative file, image, and link URLs to absolute URLs in attributes and HTML content.

### Changed

- Kept URL inventory assignments synchronized with batch source paths.
- Normalized `.` and `..` path segments before importing files.
- Updated the package version to 1.2.0.
