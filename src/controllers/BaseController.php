<?php
namespace therefinery\lynnworkflow\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\DateTimeHelper;
use DateTime;
use craft\base\ElementTrait;
use craft\elements\User;
use craft\elements\Entry;
use craft\mail\Message;

use therefinery\lynnworkflow\LynnWorkflow;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionDrafts()
    {
        $drafts = LynnWorkflow::$plugin->getDrafts()->getAllDrafts();

        return $this->renderTemplate('lynnworkflow/drafts', [
            'entries' => $drafts,
        ]);
    }

    public function actionSettings()
    {
        $this->requirePermission('manageLynnWorkflows');
        $settings = LynnWorkflow::$plugin->getSettings();
        $workflows = LynnWorkflow::$plugin->getWorkflows()->getAllWorkflows();

        return $this->renderTemplate('lynnworkflow/settings', [
            'settings' => $settings,
            'workflowList' => $workflows,
        ]);
    }

    public function actionExecuteTransition()
    {
        $session = Craft::$app->getSession();
        $this->requirePostRequest();
        $user = Craft::$app->getUser()->getIdentity();
        $submission_id = Craft::$app->request->getParam('submissionId');
        $fields = Craft::$app->request->getParam('fields');
        

        $entry_id = Craft::$app->request->getParam('entryId');
        $draft_id = Craft::$app->request->getParam('draftId');
        $state_info = Craft::$app->request->getParam('targetState');
        $state_info_arr = explode("-", $state_info);
        $transition_id = $state_info_arr[0];
        $target_state_id = $state_info_arr[1];
        $workflow_id = Craft::$app->request->getParam('workflowId');
        $state_id = Craft::$app->request->getParam('stateId');
        // $draft = Craft::$app->entryRevisions->getDraftById($draft_id);
        $draft =  Entry::find()->draftId($draft_id)->anyStatus()->site('*')->one();
        $author_id = $draft->creatorId;


        $title = Craft::$app->request->getParam('title');
        if (!empty($fields['articleHeadline'])) {
          $headline = $fields['articleHeadline'];
        }
        else {
          if (!empty($title)) {
            $headline = $title;
          }
          else if (isset($draft->title)) {
            // entries with auto-generated titles have empty 'title' parameters, try fetching them from draft
            $headline = $draft->title;
          }
          else {
            $headline = 'Submission';
          }
        }


        // Pull the current transition info.
        $current_transition = LynnWorkflow::$plugin->getTransitions($workflow_id, $state_id)->getTransitionById($transition_id);
        $current_state = LynnWorkflow::$plugin->getStates($workflow_id)->getStateById($state_id);
        $target_state = LynnWorkflow::$plugin->getStates($workflow_id)->getStateById($target_state_id);
        $current_transition_name = $current_transition->name;
        $current_state_name = $current_state->name;
        $target_state_name = $target_state->name;
        $notes = Craft::$app->request->getParam('notes');
        $cleaned_notes['current_transition'] = $current_transition_name;
        $cleaned_notes['current_state'] = $current_state_name;
        $cleaned_notes['target_state'] = $target_state_name;
        $cleaned_notes['note'] = $notes;
        $editor_id = $user->id;
        // Instantiate the model.
        $model = LynnWorkflow::$plugin->getSubmissions()->getSubmissionById($submission_id);
        $model->editorId = $editor_id;
        $model->dateUpdated = new DateTime;
        $model->notes = $cleaned_notes;
        $model->stateId = $target_state_id;
        // First save the draft if any changes were made.
        // @TODO FIXME
        $saved = TRUE;
        //$saved = $this->saveDraftChanges();
        // Check if we're approving a draft - we publish it too.
        if ($saved && $draft_id) {
            // $draft = Craft::$app->entryRevisions->getDraftById($draft_id);
            $draft = Entry::find()->draftId($draft_id)->anyStatus()->site('*')->one(); // v3.2
        } else {
            $draft = null;
        }
        // Get the state name from the stateId.
        $publish = FALSE;
        if ($target_state_name == 'Published') {
          $publish = TRUE;
        }
        if (LynnWorkflow::$plugin->getSubmissions()->transitionSubmission($model, $draft, $publish)) {
            // Now we should handle emails.
            $custom_recipients = Craft::$app->request->getParam('customRecipients');
            $email_config = array(
              'current_transition' => $current_transition,
              'workflow_id' => $workflow_id,
              'author_id' => $author_id,
              'custom_recipients' => $custom_recipients,
              'headline' => $headline,
              'current_transition_name' => $current_transition_name,
              'current_state_name' => $current_state_name,
              'target_state_name' => $target_state_name,
              'notes' => $notes,
              'cpEditUrl' => $draft->cpEditUrl
            );
            $this->sendNotification($email_config);
            $session->setNotice(Craft::t('lynnworkflow', 'Entry transitioned successfully.'));
        } else {
            $session->setNotice(Craft::t('lynnworkflow', 'An error occured during the transition.'));
        }

        // Redirect page to the entry as its not a form submission - check for draft
        if ($publish) {
            // If we've published a draft the url has changed
            return $this->redirect($draft->cpEditUrl);
        } else {
            return $this->redirect(Craft::$app->request->referrer);
        }

    }

    public function sendNotification($config) {
      $current_transition = $config['current_transition'];
      $workflow_id = $config['workflow_id'];
      $author_id = $config['author_id'];
      $custom_recipients = $config['custom_recipients'];
      $headline = $config['headline'];
      $current_transition_name = $config['current_transition_name'];
      $current_state_name = $config['current_state_name'];
      $target_state_name = $config['target_state_name'];
      $notes = $config['notes'];
      $cpEditUrl = $config['cpEditUrl'];
      // Handle email (this should go below)
      $recipients = array();
      // First get the group recipients.
      $recipient_groups = $current_transition->notificationRecipients;
      if (!empty($recipient_groups) && $recipient_groups[0] != 'none') {
        // Foreach through groups to find email addresses.
        if ($recipient_groups[0] == '*') {
          // Groups are whatever was configured in the workflow.
          $workflow = LynnWorkflow::$plugin->getWorkflows()->getWorkflowById($workflow_id);
          $recipient_groups = $workflow->groups;
        }
        foreach ($recipient_groups as $group_id) {
          $find_users = User::find()->groupId($group_id)->all();
          foreach ($find_users as $found_user) {
            $target_email = $found_user->email;
            $recipients[] = $target_email;
          }
        }
      }
      $notify_author = $current_transition->notifyAuthor;
      // Pull the author information (we'll need it soon)
      $author = Craft::$app->users->getUserById($author_id);
      $author_email = $author->email;
      if ($notify_author) {
        // Notify the author as well.
        if (!empty($author_email)) {
          $recipients[] = $author_email;
        }
      }

      // Now get any custom recipients:
      if (!empty($custom_recipients)) {
        $custom_recipients = explode(',', $custom_recipients);
        foreach ($custom_recipients as $custom_recipient) {
          $potential_recipient = strip_tags(trim($custom_recipient));
          if (filter_var($potential_recipient, FILTER_VALIDATE_EMAIL)) {
            // OK to use these.
            $recipients[] = $potential_recipient;
          }
        }
      }

      if (!empty($recipients)) {
      $recipients = array_unique($recipients);

        // Now get the text required.
        $notification_text = $current_transition->notificationText;
        // Set variables appropriately.
        $notification_variables = array(
          'submission' => array(
            'author' => $author->firstName . ' ' . $author->lastName,
            'title' => $headline,
            'transitionName' => $current_transition_name,
            'currentState' => $current_state_name,
            'targetState' => $target_state_name,
            'cpEditUrl' => $cpEditUrl
          ),
        );
        $view = Craft::$app->getView();
        $textBody = $view->renderString(
          $notification_text . "\r\n" . 'Additional notes: ' . $notes,
          $notification_variables
        );
        foreach ($recipients as $recipient) {
          $message = new Message();
          $message->setSubject('Workflow: Entry ' . $headline . ', transitioned to ' . $target_state_name);
          $message->setTextBody($textBody);
          $message->setTo($recipient);

          Craft::$app->getMailer()
              ->send($message);
        }
      }
    }


}
