<?php
namespace therefinery\lynnworkflow\variables;

use therefinery\lynnworkflow\elements\db\SubmissionQuery;
use therefinery\lynnworkflow\elements\db\WorkflowQuery;
use therefinery\lynnworkflow\elements\db\StateQuery;
use therefinery\lynnworkflow\elements\db\TransitionQuery;
use therefinery\lynnworkflow\elements\Submission;
use therefinery\lynnworkflow\elements\Workflow;
use therefinery\lynnworkflow\elements\State;
use therefinery\lynnworkflow\elements\Transition;

use Craft;

class LynnWorkflowVariable
{
    public function submissions($criteria = null): SubmissionQuery
    {
        $query = Submission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
    public function getAllWorkflows($criteria = null): WorkflowQuery
    {
        $query = Workflow::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function getWorkflowById($id)
    {
      return Craft::$app->getElements()->getElementById($id, Workflow::class);
    }


    public function getAllStates($workflowId): StateQuery
    {
        $query = State::find();
        $query->workflowId($workflowId);

        return $query;
    }


    public function getStateById($id)
    {
      return Craft::$app->getElements()->getElementById($id, State::class);
    }

    public function getAllTransitions($workflowId, $stateId): TransitionQuery
    {
        $query = Transition::find();
        $query->workflowId($workflowId);
        $query->stateId($stateId);

        return $query;
    }


    public function getTransitionById($id)
    {
      return Craft::$app->getElements()->getElementById($id, Transition::class);
    }

}
