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

5. Update site templates for use with Diff function. Templates that use the `redirect` twig tag will cause the Edit form to redirect the editor to the page indicated. To prevent this from happening, place a guard around any redirect tags

	{% if forDiff is defined and forDiff %}
		<main id="content" role="main">
		{{ entry.storySourceURI }}
		</main>
	{% else %}
	{% redirect ''~entry.storySourceURI %}
	{% endif %}

The `forDiff` variable is passed to any entry that is being rendered for the purpose of diffing live and draft content. You can add checks for this variable any time you want to provide alternative templates to the diff function.

Also note that the diff function only looks inside the page's `main#content` tag for content to diff. If that behavior needs to change in the future for a new templating scheme, edit the `services/Service:_templateEntry` function.

## Third party libraries

### diff-match-patch.js
Lib that can diff two strings. Used in the CraftCMS 2 version of the plugin
https://github.com/google/diff-match-patch

## Other notes
Updates to plugin that invoive DB changes will require writing a Migration (https://docs.craftcms.com/v3/extend/migrations.html#creating-migrations)