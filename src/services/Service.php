<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Submission;
use therefinery\lynnworkflow\elements\Workflow;
use therefinery\lynnworkflow\services\Workflows;

use Craft;
use craft\base\Component;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function renderEntrySidebar(&$context)
    {
        $settings = LynnWorkflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser) {
            return;
        }

        // Pull the segments from the request.
        $segments = Craft::$app->getRequest()->getSegments();

        // Have to assume here...
        if (!empty($segments)
          && $segments[0] == 'entries'
          && !empty($segments[1])
          && !empty($segments[2])) {
          $entry_type = $segments[1];
          if (!empty($segments[4]) && $segments[3] == 'drafts') {
            $draft_id = $segments[4];
          }
          else {
            $draft_id = '';
          }
          $raw_entry_id = $segments[2];
          // Compute the entry ID from the raw entry.
          $parse_entry_id = explode('-', $raw_entry_id);
          // This will prevent "new" entries from showing the panel.
          if (is_numeric($parse_entry_id[0])) {
            $entry_id = $parse_entry_id[0];
            // Get the entry data.
            $entry = Craft::$app->entries->getEntryById($entry_id);
            // First, determine if this entry is using a workflow.
            $enabled_workflows = $settings->enabledWorkflows;
            $section_id = $entry->sectionId;
            $type_id = $entry->typeId;
            // See if an entry exists for the sectionId-typeId.
            if (!empty($enabled_workflows[$section_id . '-' . $type_id])) {
              $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id . '-' . $type_id], Workflow::class);
            }
            else if (!empty($enabled_workflows[$section_id])) {
              $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id], Workflow::class);
            }
            else {
              // No matched settings, don't display it.
              return false;
            }
            // Now we can render it.
            return $this->_renderEntrySidebarPanel($context, $enabled_workflow, $currentUser);
          }
        }
    }


    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($context, $enabled_workflow, $user)
    {
      $settings = LynnWorkflow::$plugin->getSettings();
      if (!$context['entry']->id) {
          return;
      }

      // See if there's an existing submission
      $draftId = (isset($context['draftId'])) ? $context['draftId'] : ':empty:';
      if (!empty($context['versionId'])) {
        $submissions = Submission::find()
          ->ownerId($context['entry']->id)
          ->versionId($context['versionId'])
          ->all();
      }
      else {
        $submissions = Submission::find()
          ->ownerId($context['entry']->id)
          ->draftId($draftId)
          ->all();
      }
      $has_existing_drafts = FALSE;
      $existing_drafts = Craft::$app->entryRevisions->getDraftsByEntryId($context['entry']->id);
      if (!empty($existing_drafts)) {
        $has_existing_drafts = TRUE;
      }
      // Set the permission suffix.
      $permission_suffix = ':'.$context['entry']->sectionId;
      $baseCpEditUrl = 'entries/'.$context['section']->handle.'/{id}-{slug}';

      return Craft::$app->view->renderTemplate('lynn-workflow/_includes/workflow-pane', array(
          'baseCpEditUrl' => $baseCpEditUrl,
          'permissionSuffix' => $permission_suffix,
          'entry' => $context['entry'],
          'section' => $context['section'],
          'context' => $context,
          'submissions' => $submissions,
          'enabledWorkflow' => $enabled_workflow,
          'currentUser' => $user,
          'hasExistingDrafts' => $has_existing_drafts,
      ));
  }

}
