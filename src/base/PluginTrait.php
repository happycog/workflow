<?php
namespace therefinery\lynnworkflow\base;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\services\Drafts;
use therefinery\lynnworkflow\services\Service;
use therefinery\lynnworkflow\services\Submissions;
use therefinery\lynnworkflow\services\Workflows;
use therefinery\lynnworkflow\services\States;
use therefinery\lynnworkflow\services\Transitions;

use Craft;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getDrafts()
    {
        return $this->get('drafts');
    }

    public function getService()
    {
        return $this->get('service');
    }

    public function getSubmissions()
    {
        return $this->get('submissions');
    }

    public function getWorkflows()
    {
        return $this->get('workflows');
    }
    public function getStates($workflowId = NULL)
    {
      return $this->get('states', $workflowId);
    }
    public function getTransitions($workflowId = NULL, $stateId = NULL)
    {
      return $this->get('transitions', $workflowId, $stateId);
    }


    private function _setPluginComponents()
    {
        $this->setComponents([
            'drafts' => Drafts::class,
            'service' => Service::class,
            'submissions' => Submissions::class,
            'workflows' => Workflows::class,
            'states' => States::class,
            'transitions' => Transitions::class,
        ]);
    }

}
