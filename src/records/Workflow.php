<?php
namespace therefinery\lynnworkflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;
use craft\records\User;

use yii\db\ActiveQueryInterface;

class Workflow extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%lynnworkflow_workflows}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
