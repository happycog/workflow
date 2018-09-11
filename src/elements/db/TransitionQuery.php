<?php
namespace therefinery\lynnworkflow\elements\db;

use therefinery\lynnworkflow\elements\Transition;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class TransitionQuery extends ElementQuery
{

    public $id;
    public $name;
    public $description;
    public $workflowId;
    public $stateId;
    public $groups;
    public $notifyAuthor;
    public $notificationRecipients;
    public $notificationText;
    public $targetState;
    public $dateCreated;
    public $dateUpdated;
    public $uid;


    public function id($value)
    {
        $this->id = $value;
        return $this;
    }

    public function name($value)
    {
        $this->name = $value;
        return $this;
    }

    public function workflowId($value)
    {
        $this->workflowId = $value;
        return $this;
    }

    public function stateId($value)
    {
        $this->stateId = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lynnworkflow_transitions');

        $this->query->select([
            'lynnworkflow_transitions.*',
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_transitions.id', $this->id));
        }
        if ($this->workflowId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_transitions.workflowId', $this->workflowId));
        }
        if ($this->stateId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_transitions.stateId', $this->stateId));
        }

        return parent::beforePrepare();
    }

}
