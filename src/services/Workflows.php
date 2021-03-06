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


    /**
     * @param int $sectionId
     * @param int $typeId
     * @return false
     */
    public function getWorkflowForSection($sectionId, $typeId)
    {
        $settings = LynnWorkflow::$plugin->getSettings();
        foreach([
                    $sectionId . '-' . $typeId,
                    $sectionId
                ] as $key) {
            $workflowId = $settings->enabledWorkflows[$key] ?? false;
            if ($workflowId){
                return Craft::$app->getElements()->getElementById($workflowId, Workflow::class);
            }
        }
        return null;
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
