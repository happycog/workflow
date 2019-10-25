# Lynn Workflow Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## v1.1.1 - 2019-10-25
### Changed
- Diffs: replaced site-side templates with minimal templates to improve rendering time. Empty fields are not shown.
- Diffs: Diffs are now displayed in an overlay box with more space for content

## v1.1.0 - 2019-08-07
### Changed
- Updated to support CraftCMS v3.2+ which uses new APIs and DB schema to manage Drafts and Revisions.
- Does not work with versions before 3.2

### Added
- DB migration for the lynnworkflow_submissions table

Tested with CraftCMS 3.2.0 Beta 2 and 3.2.9

## 1.0.11 - 2019-03-21
### Added
- ported diff feature from CraftCMS 2 version of plugin

## 1.0.4 - 2018-10-15
### Fixed
- Meta data fixes

## 1.0.3 - 2018-10-01
### Fixed
- Fix issue with workbench to include status

## 1.0.2 - 2018-09-27
### Fixed
- Fix deprecation errors

## 1.0.1 - 2018-09-26
### Fixed
- Fix template errors

## 1.0.0 - 2018-08-06
### Added
- Initial release
