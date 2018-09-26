<?php
namespace therefinery\lynnworkflow\controllers;

use therefinery\lynnworkflow\elements\Transition;
use therefinery\lynnworkflow\elements\db\TransitionQuery;
use therefinery\lynnworkflow\records\Transition as TransitionRecord;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

use therefinery\lynnworkflow\LynnWorkflow;

class TransitionsController extends Controller
{
    public $defaultAction = 'list';


    public function actionList()
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynnworkflow/transitions/index', array());
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    public function actionEdit($workflowId = NULL, $stateId = NULL, $transitionId = NULL)
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynnworkflow/transitions/_edit', array(
          'workflowId' => $workflowId,
          'stateId' => $stateId,
          'transitionId' => $transitionId,
        ));
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    public function actionShow($workflowId = NULL, $stateId = NULL, $transitionId = NULL)
    {
      $user = Craft::$app->getUser()->getIdentity();
      if ($user->can('manageLynnWorkflows')) {
        $this->renderTemplate('lynnworkflow/transitions/_show', array(
          'workflowId' => $workflowId,
          'stateId' => $stateId,
          'transitionId' => $transitionId,
        ));
      }
      else {
        throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
      }
    }

    /**
     * Save Transition
     *
     * Create or update an existing transition, based on POST data
     */
    public function actionSaveTransition()
    {
      $user = Craft::$app->getUser()->getIdentity();
      $session = Craft::$app->getSession();

      if ($user->can('manageLynnWorkflows')) {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $postedTransition = $request->getParam('transition');
        $workflowId = $postedTransition['workflowId'];
        $stateId = $postedTransition['stateId'];
        if ($id = $request->getParam('transitionId')) {
          $model = LynnWorkflow::$plugin->getTransitions($workflowId, $stateId)->getTransitionById($id);
        } else {
          $model = new Transition();
        }

        $attributes = $request->getParam('transition');
        foreach ($attributes as $attribute => $att_value) {
          $model->$attribute = $att_value;
        }
        if (Craft::$app->getElements()->saveElement($model)) {
          $session->setNotice(Craft::t('lynnworkflow', 'Transition saved.'));
          $url = UrlHelper::cpUrl('lynnworkflow/workflows/' . $model->workflowId . '/states/' . $model->stateId);
          return $this->redirect($url);
        } else {
          $session->setError(Craft::t('lynnworkflow', 'Could not save Transition.'));
          return null;
        }
      }
    }

    /**
     * Delete State
     *
     * Delete an existing Transition
     */
    public function actionDeleteTransition()
    {
      $user = Craft::$app->getUser()->getIdentity();
      $session = Craft::$app->getSession();

      if ($user->can('manageLynnWorkflows')) {
        $this->requirePostRequest();
        $id = Craft::$app->request->getRequiredParam('id');
        $transition = LynnWorkflow::$plugin->getTransitions()->getTransitionById($id);
        $workflowId = $transition->workflowId;
        $stateId = $transition->stateId;
        if (!$transition) {
          throw new NotFoundHttpException('Transition not found');
        }
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!Craft::$app->getElements()->deleteElementById($id)) {
          if (Craft::$app->getRequest()->getAcceptsJson()) {
              return $this->asJson(['success' => false]);
          }

          Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete transition.'));

          // Send the entry back to the template
          Craft::$app->getUrlManager()->setRouteParams([
              'transition' => $transition
          ]);

          return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Transition deleted.'));
        $url = UrlHelper::cpUrl('lynnworkflow/workflows/' . $workflowId . '/states/' . $stateId);
        return $this->redirect($url);
      }
    }

}
