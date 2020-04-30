<?php
namespace therefinery\lynnworkflow\controllers;

use therefinery\lynnworkflow\elements\Submission;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;

use craft\helpers\DateTimeHelper;
use DateTime;
use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\assetbundles\LynnWorkflowAsset;


class SubmissionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSend()
    {
        $settings = LynnWorkflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->ownerId = $request->getParam('entryId');
        $submission->versionId = $request->getParam('versionId');
        $submission->stateId = $request->getParam('stateId');
        $submission->ownerSiteId = $request->getParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);
        $submission->draftId = $request->getParam('draftId');
        $submission->editorId = $currentUser->id;
        $submission->status = Submission::STATUS_PENDING;
        $submission->dateApproved = null;

        $isNew = !$submission->id;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('lynnworkflow', 'Could not submit for approval.'));
            return null;
        }

        if ($isNew) {
            // Trigger notification to publisher
            if ($settings->publisherNotifications) {
                LynnWorkflow::$plugin->getSubmissions()->sendPublisherNotificationEmail($submission);
            }
        }

        $session->setNotice(Craft::t('lynnworkflow', 'Entry submitted for approval.'));

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }

    public function actionRevoke()
    {
        $settings = LynnWorkflow::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REVOKED;
        $submission->dateRevoked = new \DateTime;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('lynnworkflow', 'Could not revoke submission.'));
            return null;
        }

        $session->setNotice(Craft::t('lynnworkflow', 'Submission revoked.'));

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }

    public function actionApprove()
    {
        $settings = LynnWorkflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_APPROVED;
        $submission->publisherId = $currentUser->id;
        $submission->dateApproved = new \DateTime;
        $submission->notes = $request->getParam('notes');

        $draftId = $request->getParam('draftId');

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('lynnworkflow', 'Could not approve and publish.'));
            return null;
        }

        // Check if we're approving a draft - we publish it too.
        if ($draftId) {
            $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId); // DEPRECATED
            // $draft =  \craft\elements\Entry::find()->draftId($draftId)->one(); // v3.2



            if (!Craft::$app->getEntryRevisions()->publishDraft($draft)) { // DEPRECATED
                Craft::$app->getSession()->setError(Craft::t('lynnworkflow', 'Couldn’t publish draft.'));
                return null;
            }
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            LynnWorkflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('lynnworkflow', 'Entry approved and published.'));

        // Redirect page to the entry as its not a form submission - check for draft
        if ($draftId) {
            // If we've published a draft the url has changed
            return $this->redirect($draft->cpEditUrl);
        } else {
            return $this->redirect($request->referrer);
        }
    }

    public function actionReject()
    {
        $settings = LynnWorkflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->publisherId = $currentUser->id;
        $submission->dateRejected = new \DateTime;
        $submission->notes = $request->getParam('notes');

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('lynnworkflow', 'Could not reject submission.'));
            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            LynnWorkflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }

    /**
     * An AJAX loadable sidebar that should appear after an Entry is "Save As Draft"
     * @param  INT $entryId 
     * @param  INT $draftId 
     * @return String          Rendered Sidebar
     */
    public function actionSidebar($sbEntryId = NULL, $sbEdraftId = NULL){
        // $this->requireAdmin();
        $this->requireLogin();

        $context = array();
        // $context['entry'] = Craft::$app->getEntryRevisions()->getDraftById($sbEdraftId);
        $context['entry'] = Entry::find()->draftId($sbEdraftId)->anyStatus()->site('*')->one();
        $context['entryId'] = $sbEntryId;
        $context['draftId'] = $sbEdraftId;
        $context['section'] = $context['entry']->section;
        $context['ajax'] = true;

        $sidebar = LynnWorkflow::getInstance()->service->renderEntrySidebar($context);

        return $sidebar;
    }

    /**
     * Present a plain webpage showing a diff between a draft and the publishe entry
     * @param  Int $entryId An entry ID
     * @param  INT $draftId A draft ID
     * @return String          The rendered template for a diff page
     */
    public function actionDiff($diffEntryId = NULL, $draftId = NULL)
    {
        // $pageInfo = Craft::$app->entries->getEntryById($diffEntryId);
        $pageInfo = Entry::find()->id($diffEntryId)->anyStatus()->site('*')->one();

        $this->view->registerAssetBundle(LynnWorkflowAsset::class);

        return $this->view->renderPageTemplate('lynnworkflow/_diff/display', array(
            'diffEntryId' => $diffEntryId,
            'diffDraftId' => $draftId,
            'diff' => $this->prepareForDiff($diffEntryId, $draftId),
            'title' => $pageInfo->title
        ));
    }

    // actionDiff helper methods
    // 
    public function prepareForDiff($entryId = NULL, $draftId = NULL){
        $diff = array(
          'live' => 'Current Content\nSecond Line',
          'draft' => 'Draft Content\nSecond Line'
        );

        // temporarily set rendering mode to 'site'
        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        // $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

        // render a copy of the live content
        // $live_model = Craft::$app->entries->getEntryById($entryId);
        $live_model = Entry::find()->id($entryId)->anyStatus()->site('*')->one();
        $section = $live_model->getSection();
        $type = $live_model->getType();
        if (!$section || !$type) {
          // we need to have a section to render the content
          Craft::log('Attempting to preview an entry that doesn’t have a section/type', LogLevel::Error);
          throw new HttpException(404);
        }

        $diff['siteId'] = $live_model->siteId;
        $diff['section'] = $live_model->getSection()->getSiteSettings()[$live_model->siteId]->siteId;
        $diff['template'] = $live_model->getSection()->getSiteSettings()[$live_model->siteId]->template;

        $diff['live'] = strval($this->_templateEntry($live_model, $templateMode));

        // render a copy of the draft content
        // $draft_model = Craft::$app->getEntryRevisions()->getDraftById($draftId); //deprecated
        // $draft_model = \craft\elements\Entry::find()->draftId($context['draftId'])->one(); // `draftId()` not defined
        $draft_model = Entry::find()->draftId($draftId)->anyStatus()->site('*')->one();

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


    // Private Methods
    // =========================================================================

    private function _setSubmissionFromPost(): Submission
    {
        $request = Craft::$app->getRequest();
        $submissionId = $request->getParam('submissionId');

        if ($submissionId) {
            $submission = LynnWorkflow::$plugin->getSubmissions()->getSubmissionById($submissionId);

            if (!$submission) {
                throw new \Exception(Craft::t('lynnworkflow', 'No submission with the ID “{id}”', ['id' => $submissionId]));
            }
        } else {
            $submission = new Submission();
        }

        return $submission;
    }

}
