<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\models\EntryDraft; // deprecated v3.2

// use yii\web\UserEvent;

class Drafts extends Component
{
    // Public Methods
    // =========================================================================

    public function getAllDrafts()
    {
        // $results = $this->_getDraftsQuery()->all();

        // $drafts = [];

        // foreach ($results as $result) {
        //     $result['data'] = Json::decode($result['data']);
        //     $drafts[] = new EntryDraft($result);  // deprecated v3.2
        // }
        
        $drafts = Entry::find()->drafts()->all();

        return $drafts;
    }


    // Private Methods
    // =========================================================================

    // private function _getDraftsQuery(): Query
    // {
    //     return (new Query())
    //         ->select([
    //             'id',
    //             'entryId',
    //             'sectionId',
    //             'creatorId',
    //             'siteId',
    //             'name',
    //             'notes',
    //             'data',
    //             'dateCreated',
    //             'dateUpdated',
    //             'uid',
    //         ])
    //         ->from(['{{%entrydrafts}}']);
    // }
}
