<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Workflow;

use Craft;
use craft\db\Query;
use craft\base\Component;
use craft\elements\User;


class Workflows extends Component
{
    // Public Methods
    // =========================================================================

    public function getWorkflowById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Workflow::class);
    }

    public function getAllWorkflows()
    {
        $results = $this->_getAllWorkflowsQuery()->all();

        $workflows = [];

        foreach ($results as $result) {
            $workflows[] = new Workflow($result);
        }

        return $workflows;
    }


    // Private Methods
    // =========================================================================

    private function _getAllWorkflowsQuery(): Query
    {
        return (new Query())
            ->select([
                '*'
            ])
            ->from(['{{%lynnworkflow_workflows}}']);
    }
}
