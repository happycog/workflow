<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Json;
use craft\models\EntryDraft;

// use yii\web\UserEvent;

class Drafts extends Component
{
    // Public Methods
    // =========================================================================

    public function getAllDrafts()
    {
        $results = $this->_getDraftsQuery()->all();

        $drafts = [];

        foreach ($results as $result) {
            $result['data'] = Json::decode($result['data']);
            $drafts[] = new EntryDraft($result);
        }

        return $drafts;
    }


    // Private Methods
    // =========================================================================

    private function _getDraftsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'entryId',
                'sectionId',
                'creatorId',
                'siteId',
                'name',
                'notes',
                'data',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%entrydrafts}}']);
    }
}
