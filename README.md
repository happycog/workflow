# Lynn Workflow plugin for Craft CMS 3.x

Workflow solution for Lynn University

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Manually add the repository to your composer.json since this plugin is not listed on packagist

		"repositories": [
			{
				"type": "github",
				"url": "git@github.com:the-refinery/workflow.git"
			}
		]

3. Then install the plugin:

		composer require therefinery/lynnworkflow

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for Lynn Workflow.


## Thrid party libraries

### diff-match-patch.js
Lib that can diff two strings. Used in the CraftCMS 2 version of the plugin
https://github.com/google/diff-match-patch

## Other notes
Updates to plugin that invoive DB changes will require writing a Migration (https://docs.craftcms.com/v3/extend/migrations.html#creating-migrations)