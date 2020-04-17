# Lynn Workflow Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## v1.1.11 - 2020-04-17
### Removed
- Code used to provide multisite capability (now in multisite branch)

### Fixed
- Assured that diff could work with PTC entries by having queries search all sites

## v1.1.11 alpha - 2020-04-01
### Fixed
- Assured that all drafts can be displayed in Overview tab
- Overview tab: get correct CP URL for Title columns
- Entry sidebar: template value checks and fallbacks for situations when notes don't have propper values

### Added
- Overview tab: ability to select only Submissions that are ready for review. Includes selecting submissions by State Name, because state IDs vary by workflow. 

## v1.1.10 - 2020-03-03
### Fixed
- Temp fix to allow Users who can't use workflows to view drafts.

## v1.1.9 - 2020-01-08
### Fixed
- Updated templates to replace deprecated Craft/twig functions

## v1.1.8 - 2020-01-06
### Fixed
- AJAX sidebar loading: assured that `Craft.js` reloaded the UI so that sidebar action buttons woould be registered
- `actionSidebar` Loaded draft entry into contect instead of current

## v1.1.7 - 2019-12-11
### Fixed
- Updated Entry queries that prevented draft (and their titles) from being loaded in Submissions page when the draft in question had some type of Unpublished status (enabled toggle off, non-current published date, etc.). Same problem affected Transitions and prevented them from sending notifications and redirecting user after post.

## v1.1.6 - 2019-11-21
### Fixed
- Typo: code comments that prevented diffs from appearing

## v1.1.5 - 2019-11-20
### Fixed
- Diff page conflict with Guide plugin. Found another spot in code where `entryId` was triggering Guide.

## v1.1.4 - 2019-11-20
### Fixed
- Diff page conflict with Guide plugin. Guide tries to alter pages that contain a `entryId` variable and add a Guide widget. Renamed variable to `diffEntryId`

## v1.1.3 - 2019-11-19
### Fixed
- Race condition between sidebar script and Crafts built-in save function that caused diff link to appear without draftId

## v1.1.2 - 2019-11-15
### Added
- Added variable `{{ submission.cpEditUrl }}` to notifications field. Site admin will have to update the 'Notification Text' for each transition.
- Added instructions for transition notification fields listing available variables
- Added 'lynnworkflow_pane.js' to hold all client-side code for workflow pane on editor pages. Has code that monkeypatches history API to add an `locationchange` event that is triggered when the CP Editor updates the URL without reloading the page.
- New action for SubmissionsController `actionDiff` provides a full page diff. Link that opens it appears in sidebar pane if view is a draft
- New action `actionSidebar` for SubmissionsController provides a means to load a LynnWorkflow sidebar in the editor via AJAX when a new draft is created

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
