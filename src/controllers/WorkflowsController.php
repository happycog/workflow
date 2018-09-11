<?php
namespace therefinery\lynnworkflow\controllers;

use therefinery\lynnworkflow\elements\Workflow;
use therefinery\lynnworkflow\elements\State;
use therefinery\lynnworkflow\elements\db\WorkflowQuery as WFQuery;
use therefinery\lynnworkflow\records\Workflow as WorkflowRecord;

use Craft;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use craft\helpers\UrlHelper;
use yii\web\ServerErrorHttpException;

use therefinery\lynnworkflow\LynnWorkflow;

class WorkflowsController extends Controller
{
    public $defaultAction = 'list';

    public function actionList()
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynn-workflow/workflows/index', array());
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    public function actionEdit($workflowId = NULL)
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynn-workflow/workflows/_edit', array(
          'workflowId' => $workflowId,
        ));
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    public function actionShow($workflowId = NULL)
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynn-workflow/workflows/_show', array(
          'workflowId' => $workflowId,
        ));
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    /**
     * Save Workflow
     *
     * Create or update an existing workflow, based on POST data
     */
    public function actionSaveWorkflow()
    {
      $user = Craft::$app->getUser()->getIdentity();
      $session = Craft::$app->getSession();

      if ($user->can('manageLynnWorkflows')) {
        $this->requirePostRequest();
        $create_new_defaults = FALSE;
        $workflow = $this->_setWorkflowFromPost();

        $isNew = !$workflow->id;
        if ($isNew) {
          $create_new_defaults = TRUE;
        }

        $attributes = Craft::$app->request->getParam('workflow');
        foreach ($attributes as $attribute => $att_value) {
          $workflow->$attribute = $att_value;
        }
        if (!Craft::$app->getElements()->saveElement($workflow)) {
          $session->setError(Craft::t('lynn-workflow', 'Could not submit for approval.'));
          return null;
        }

        if ($create_new_defaults) {
          // Create default "Draft" and "Published" states.
          $new_workflow_id = $workflow->id;

          $draft_state = new State();
          $draft_state->name = 'Draft';
          $draft_state->workflowId = $new_workflow_id;
          $draft_state->description = '';
          $draft_state->weight = 0;
          $draft_state->viewGroups = $workflow->groups;
          $draft_state->editGroups = $workflow->groups;
          $draft_state->deleteGroups = $workflow->groups;


          if (Craft::$app->getElements()->saveElement($draft_state)) {
              $session->setNotice(Craft::t('lynn-workflow', 'Default draft saved.'));
              // Now set this as the default state on the workflow.
              $draft_state_id = $draft_state->id;
              $workflow->defaultState = $draft_state_id;
              $save_default = Craft::$app->getElements()->saveElement($workflow);
          } else {
            $session->setNotice(Craft::t('lynn-workflow', 'Couldn\'t save the default draft state.'));
          }

          $published_state = new State();
          $published_state->name = 'Published';
          $published_state->workflowId = $new_workflow_id;
          $published_state->description = '';
          $published_state->weight = 1;
          $published_state->viewGroups = $workflow->groups;
          $published_state->editGroups = $workflow->groups;
          $published_state->deleteGroups = $workflow->groups;

          if (Craft::$app->getElements()->saveElement($published_state)) {
            $session->setNotice(Craft::t('lynn-workflow', 'Default published state saved.'));
          } else {
            $session->setNotice(Craft::t('lynn-workflow', 'Couldn\'t save the default published state.'));
          }
        }

        $session->setNotice(Craft::t('lynn-workflow', 'Workflow saved.'));
        $url = UrlHelper::cpUrl('lynn-workflow/workflows/' . $workflow->id);
        return $this->redirect($url);
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    /**
     * Delete Workflow
     *
     * Delete an existing workflow
     */
    public function actionDeleteWorkflow()
    {
      $this->requirePostRequest();

      $workflowId = Craft::$app->request->getRequiredParam('id');
      $workflow = LynnWorkflow::$plugin->getWorkflows()->getWorkflowById($workflowId);

      if (!$workflow) {
          throw new NotFoundHttpException('Entry not found');
      }

      $currentUser = Craft::$app->getUser()->getIdentity();

      //$this->requirePermission('manageLynnWorkflows');

      if (!Craft::$app->getElements()->deleteElementById($workflowId)) {
          if (Craft::$app->getRequest()->getAcceptsJson()) {
              return $this->asJson(['success' => false]);
          }

          Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete workflow.'));

          // Send the entry back to the template
          Craft::$app->getUrlManager()->setRouteParams([
              'workflow' => $workflow
          ]);

          return null;
      }

      if (Craft::$app->getRequest()->getAcceptsJson()) {
          return $this->asJson(['success' => true]);
      }

      Craft::$app->getSession()->setNotice(Craft::t('app', 'Workflow deleted.'));

      $url = UrlHelper::cpUrl('lynn-workflow/workflows');
      return $this->redirect($url);
    }

    // Private Methods
    // =========================================================================

    private function _setWorkflowFromPost(): Workflow
    {
        $request = Craft::$app->getRequest();
        $workflowId = $request->getParam('workflowId');

        if ($workflowId) {
            $workflow = LynnWorkflow::$plugin->getWorkflows()->getWorkflowById($workflowId);

            if (!$workflow) {
                throw new \Exception(Craft::t('lynn-workflow', 'No submission with the ID “{id}”', ['id' => $workflowId]));
            }
        } else {
            $workflow = new Workflow();
        }

        return $workflow;
    }
}
