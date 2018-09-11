<?php
namespace therefinery\lynnworkflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;
use craft\records\User;

use yii\db\ActiveQueryInterface;

class State extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%lynnworkflow_states}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
