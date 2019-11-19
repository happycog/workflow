<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Submission;
use therefinery\lynnworkflow\elements\Workflow;
use therefinery\lynnworkflow\services\Workflows;

use Craft;
use craft\elements\Entry;
use craft\base\Component;
use yii\web\ServerErrorHttpException;

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
        /**
         * JO: Test $context to see if it needs the LWF sidebar
         * Has entry attribute ($context[entry])
         * ??Entry is of type 'draft'
         */
        if (!empty($segments)
              && $segments[0] == 'entries'
              && !empty($segments[1])
              && !empty($segments[2])
          ) {
          // $entry_type = $segments[1];
          // if (!empty($segments[4]) && $segments[3] == 'drafts') {
          //   $draft_id = $segments[4];
          // }
          // else {
          //   $draft_id = '';
          // }
          $raw_entry_id = $segments[2];
          // Compute the entry ID from the raw entry.
          $parse_entry_id = explode('-', $raw_entry_id);
          // This will prevent "new" entries from showing the panel.
          if (is_numeric($parse_entry_id[0])) {
            // $entry_id = intval($parse_entry_id[0]);
            // Get the entry data.
            // $entry = Craft::$app->entries->getEntryById($entry_id); // JO: this is a fail, just use entry from context
            $entry = $context['entry'];
            // First, determine if this entry is using a workflow.
            // JO: enabledWorkflows are sections & section-types that
            $enabled_workflows = $settings->enabledWorkflows;
            $section_id = $entry->sectionId;
            $type_id = $entry->typeId;
            // See if an entry exists for the sectionId-typeId.
            // JO: workflow can be assigned to a specific entry type, or all types in a section. Plugin tests for the type first
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
        }else if(isset($context['ajax'])){
          $entry = $context['entry'];
          // First, determine if this entry is using a workflow.
          // JO: enabledWorkflows are sections & section-types that
          $enabled_workflows = $settings->enabledWorkflows;
          $section_id = $entry->sectionId;
          $type_id = $entry->typeId;
          // See if an entry exists for the sectionId-typeId.
          // JO: workflow can be assigned to a specific entry type, or all types in a section. Plugin tests for the type first
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


    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($context, $enabled_workflow, $user)
    {
      $settings = LynnWorkflow::$plugin->getSettings();
      if (!$context['entry']->id) {
          return;
      }

      // See if there's an existing submission
      $submissions = array();
      $subSQL = '';
      $draftId = (isset($context['draftId'])) ? $context['draftId'] : $context['entry']->draftId;
      if (!empty($context['versionId'])) {
        $submissions = Submission::find() // JO: uses lynnworkflow\elements\db\SubmissionQuery
          // ->ownerId($context['entry']->id) // which user should this be? the creater of the draft?
          ->versionId($context['versionId'])
          ->all();
      }
      else if ($draftId) {
        $submissionsQuery = Submission::find()
          // ->ownerId($context['entry']->id)
          ->draftId($draftId);
        $submissions = $submissionsQuery->all();
        $subSQL = $submissionsQuery->getRawSql();
      }
      $has_existing_drafts = FALSE;
      // $existing_drafts = Craft::$app->entryRevisions->getDraftsByEntryId($context['entry']->id); // Deprecated
      $existing_drafts = Entry::find()->drafts()->id($context['entry']->id)->all();
      if (!empty($existing_drafts)) {
        $has_existing_drafts = TRUE;
      }
      // Set the permission suffix.
      $permission_suffix = ':'.$context['entry']->sectionId;
      $baseCpEditUrl = 'entries/'.$context['section']->handle.'/{id}-{slug}';

      // create a diff for entries with previous versions
      $diff = FALSE;

      // Check if entry type has an URL, which is required to produce a diff
      $entry = $context['entry'];
      // $sectionSiteSettings = $entry->getSection()->getSiteSettings();
      // if ($has_existing_drafts && isset($context['draftId']) && isset($sectionSiteSettings[$entry->siteId]) && $sectionSiteSettings[$entry->siteId]->hasUrls) {
      //   $diff = $this->prepareForDiff($context);
      // }
      

      return Craft::$app->view->renderTemplate('lynnworkflow/_includes/workflow-pane', array(
          'baseCpEditUrl' => $baseCpEditUrl,
          'permissionSuffix' => $permission_suffix,
          'entry' => $context['entry'],
          'section' => $context['section'],
          'context' => $context,
          'submissions' => $submissions,
          'enabledWorkflow' => $enabled_workflow,
          'currentUser' => $user,
          'hasExistingDrafts' => $has_existing_drafts,
          'orgEntryId' => $context['entryId'],
          'draftId' => $draftId,
          'ajax' => isset($context['ajax']) ? $context['ajax'] : false,

          // ,'wfsettings' => $settings
          // ,'subSQL' => $subSQL
          // ,'sectionSiteSettings' => $sectionSiteSettings
      ));
  }

  /**
   * The following two functions have been moved to `SubmissionsConrtoller` and can be removed.
   */
  /**
   * Prepares an entry's live and draft content to be passed to a diffing function
   * 
   * Returns rendered content for a given entry context. $context should
   * contain keys for 'entryId' and 'draftId', for which the function renders
   * site-side versions of the page and extracts main content for each.
   * The output should be fit to be passed to a diffing function like diff_match_patch.js
   * 
   * @param  Array $context A rendering context for a CP edit entity
   * @return Array          'live' and 'draft' versions of the entry
   */
  public function prepareForDiff($context){
    $diff = array(
      'live' => 'Current Content\nSecond Line',
      'draft' => 'Draft Content\nSecond Line'
    );

    // temporarily set rendering mode to 'site'
    $view = Craft::$app->getView();
    $templateMode = $view->getTemplateMode();
    // $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

    // render a copy of the live content
    $live_model = Craft::$app->entries->getEntryById($context['entryId']);
    $section = $live_model->getSection();
    $type = $live_model->getType();
    if (!$section || !$type) {
      // we need to have a section to render the content
      Craft::log('Attempting to preview an entry that doesn’t have a section/type', LogLevel::Error);
      throw new HttpException(404);
    }

    $diff['siteId'] = $live_model->siteId;
    $diff['section'] = $live_model->getSection()->getSiteSettings()[1]->siteId;
    $diff['template'] = $live_model->getSection()->getSiteSettings()[1]->template;

    $diff['live'] = strval($this->_templateEntry($live_model, $templateMode));

    // render a copy of the draft content
    $draft_model = Craft::$app->getEntryRevisions()->getDraftById($context['draftId']); //deprecated
    // $draft_model = \craft\elements\Entry::find()->draftId($context['draftId'])->one(); // `draftId()` not defined

    $diff['draft'] = strval($this->_templateEntry($draft_model, $templateMode));

    // reset template mode to 'control panel'
    $view->setTemplateMode($templateMode);

    return $diff;
  }

  /**
   * Based on EntryController::_showEntry a private function used by CraftCMS to render previews.
   * Function has been copied and modified to provide a rendering of a live or draft entry for
   * purposes of performing a diff.
   * @param  Object $entry        An entry or draft mobel
   * @param  String $templateMode Template mode to fall-back to in case of error, usually CP
   * @return String               The contents of the rendered page, striped of header and footer
   */
  private function _templateEntry($entry, $templateMode)
    {
        $sectionSiteSettings = $entry->getSection()->getSiteSettings();
        $view = Craft::$app->getView();

        if (!isset($sectionSiteSettings[$entry->siteId]) || !$sectionSiteSettings[$entry->siteId]->hasUrls) {
          $view->setTemplateMode($templateMode);
            throw new ServerErrorHttpException('The entry ' . $entry->id . ' doesn’t have a URL for the site ' . $entry->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($entry->siteId);

        if (!$site) {
          $view->setTemplateMode($templateMode);
            throw new ServerErrorHttpException('Invalid site ID: ' . $entry->siteId);
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));

        if (!$entry->postDate) {
            $entry->postDate = new \DateTime();
        }

        // Have this entry override any freshly queried entries with the same ID/site ID
        // Craft::$app->getElements()->setPlaceholderElement($entry);
        // JO: Disabled due to weird side effects

        $view->getTwig()->disableStrictVariables();
        // return 'template entry';

        // $rendered = $view->renderTemplate($sectionSiteSettings[$entry->siteId]->template, [
        $rendered = $view->renderTemplate('lynnworkflow/_diff/layout', [
            'entry' => $entry,
            'forDiff' => TRUE
        ]);

        // Now manipulate the result to strip everything outside of
        // <main id="content" role="main"> </main>
        // $rendered = strstr($rendered, '<main id="page-maincontent">');
        // $rendered = strstr($rendered, '</main>', TRUE);
        return $rendered;
    }

}
