<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Transition;

use Craft;
use craft\db\Query;
use craft\base\Component;
use craft\elements\User;


class Transitions extends Component
{
    // Public Methods
    // =========================================================================

    public function getTransitionById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Transition::class);
    }

    public function getAllTransitions($workflowId = NULL, $stateId = NULL)
    {
        $results = $this->_getAllTransitionsQuery($workflowId, $stateId)->all();

        $transitions = [];

        foreach ($results as $result) {
            $transitions[] = new Transition($result);
        }

        return $transitions;
    }


    // Private Methods
    // =========================================================================

    private function _getAllTransitionsQuery($workflowId = NULL, $stateId = NULL): Query
    {
        $query = (new Query())
            ->select([
                '*'
            ]);
            if (isset($workflowId)) {
              $query->where([
                'workflowId' => $workflowId,
              ]);
            }
            if (isset($stateId)) {
              $query->where([
                'stateId' => $stateId,
              ]);
            }
      $query->from(['{{%lynnworkflow_transitions}}']);
      return $query;
    }
}
