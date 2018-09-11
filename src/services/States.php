<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\State;

use Craft;
use craft\db\Query;
use craft\base\Component;
use craft\elements\User;


class States extends Component
{
    // Public Methods
    // =========================================================================

    public function getStateById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, State::class);
    }

    public function getAllStates($workflowId = NULL)
    {
        $results = $this->_getAllStatesQuery($workflowId)->all();

        $states = [];

        foreach ($results as $result) {
            $states[] = new State($result);
        }

        return $states;
    }


    // Private Methods
    // =========================================================================

    private function _getAllStatesQuery($workflowId = NULL): Query
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
      $query->from(['{{%lynnworkflow_states}}']);
      return $query;
    }
}
