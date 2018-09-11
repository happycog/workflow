<?php
namespace therefinery\lynnworkflow\elements\db;

use therefinery\lynnworkflow\elements\Workflow;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class WorkflowQuery extends ElementQuery
{
    public $id;
    public $name;
    public $description;
    public $bypass;
    public $groups;
    public $defaultState;
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

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lynnworkflow_workflows');

        $this->query->select([
            'lynnworkflow_workflows.*',
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_workflows.id', $this->id));
        }

        return parent::beforePrepare();
    }

}
