<?php
namespace therefinery\lynnworkflow\controllers;

use therefinery\lynnworkflow\elements\State;
use therefinery\lynnworkflow\elements\db\StateQuery;
use therefinery\lynnworkflow\records\State as StateRecord;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

use therefinery\lynnworkflow\LynnWorkflow;

class StatesController extends Controller
{
    public $defaultAction = 'list';


    public function actionList()
    {
      $this->requirePermission('manageLynnWorkflows');
      
      $this->renderTemplate('lynnworkflow/states/index', array());
    }

    public function actionEdit($workflowId = NULL, $stateId = NULL)
    {
      $this->requirePermission('manageLynnWorkflows');
      
      $this->renderTemplate('lynnworkflow/states/_edit', array(
        'workflowId' => $workflowId,
        'stateId' => $stateId,
      ));
    }

    public function actionShow($workflowId = NULL, $stateId = NULL)
    {
      $this->requirePermission('manageLynnWorkflows');
      $this->renderTemplate('lynnworkflow/states/_show', array(
        'workflowId' => $workflowId,
        'stateId' => $stateId,
      ));
    }

    /**
     * Save State
     *
     * Create or update an existing state, based on POST data
     */
    public function actionSaveState()
    {
      $this->requirePermission('manageLynnWorkflows');
      $session = Craft::$app->getSession();

      $this->requirePostRequest();
      $request = Craft::$app->getRequest();
      $postedState = $request->getParam('state');
      $workflowId = $postedState['workflowId'];
      if ($id = $request->getParam('stateId')) {
        $model = LynnWorkflow::$plugin->getStates($workflowId)->getStateById($id);
      } else {
        $model = new State();
      }

      $attributes = $request->getParam('state');
      foreach ($attributes as $attribute => $att_value) {
        $model->$attribute = $att_value;
      }
      if (Craft::$app->getElements()->saveElement($model)) {
        $session->setNotice(Craft::t('lynnworkflow', 'State saved.'));
        $url = UrlHelper::cpUrl('lynnworkflow/workflows/' . $model->workflowId . '/states/' . $model->id);
        return $this->redirect($url);
      } else {
        $session->setError(Craft::t('lynnworkflow', 'Could not save State.'));
        return null;
      }
      
    }

    /**
     * Delete State
     *
     * Delete an existing State
     */
    public function actionDeleteState()
    {
      $this->requirePermission('manageLynnWorkflows');
      $session = Craft::$app->getSession();

      $this->requirePostRequest();
      $id = Craft::$app->request->getRequiredParam('id');
      $state = LynnWorkflow::$plugin->getStates()->getStateById($id);
      $workflowId = $state->workflowId;
      if (!$state) {
        throw new NotFoundHttpException('State not found');
      }
      $currentUser = Craft::$app->getUser()->getIdentity();
      if (!Craft::$app->getElements()->deleteElementById($id)) {
        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => false]);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete state.'));

        // Send the entry back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'state' => $state
        ]);

        return null;
      }

      if (Craft::$app->getRequest()->getAcceptsJson()) {
          return $this->asJson(['success' => true]);
      }

      Craft::$app->getSession()->setNotice(Craft::t('app', 'State deleted.'));
      $url = UrlHelper::cpUrl('lynnworkflow/workflows/' . $workflowId);
      return $this->redirect($url);
    }

}
