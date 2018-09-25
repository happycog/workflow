<?php
namespace therefinery\lynnworkflow\controllers;

use therefinery\lynnworkflow\elements\Submission;

use Craft;
use craft\web\Controller;

use craft\helpers\DateTimeHelper;
use DateTime;
use therefinery\lynnworkflow\LynnWorkflow;

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
            $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);

            if (!Craft::$app->getEntryRevisions()->publishDraft($draft)) {
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
