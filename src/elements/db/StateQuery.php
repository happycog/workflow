<?php
namespace therefinery\lynnworkflow\elements\db;

use therefinery\lynnworkflow\elements\State;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class StateQuery extends ElementQuery
{
    public $id;
    public $name;
    public $description;
    public $workflowId;
    public $weight;
    public $viewGroups;
    public $editGroups;
    public $deleteGroups;
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

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lynnworkflow_states');

        $this->query->select([
            'lynnworkflow_states.*',
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_states.id', $this->id));
        }
        if ($this->workflowId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_states.workflowId', $this->workflowId));
        }

        return parent::beforePrepare();
    }

}
