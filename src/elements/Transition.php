<?php
namespace therefinery\lynnworkflow\elements;

use therefinery\lynnworkflow\elements\actions\SetStatus;
use therefinery\lynnworkflow\elements\db\TransitionQuery;
use therefinery\lynnworkflow\records\Transition as TransitionRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

class Transition extends Element
{
    // Public Properties
    // =========================================================================

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




    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('lynn-workflow', 'Lynn Workflow Transition');
    }

    public static function refHandle()
    {
        return 'transition';
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function find(): ElementQueryInterface
    {
        return new TransitionQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('lynn-workflow', 'All transitions'),
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('lynn-workflow', 'Are you sure you want to delete the selected transition?'),
            'successMessage' => Craft::t('lynn-workflow', 'Transitions deleted.'),
        ]);

        $actions[] = SetStatus::class;

        return $actions;
    }


    // Public Methods
    // -------------------------------------------------------------------------
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // Set various strings to real data.
        if (!empty($this->groups)) {
          $this->groups = Json::decodeIfJson($this->groups);
        }
        if (!empty($this->notificationRecipients)) {
          $this->notificationRecipients = Json::decodeIfJson($this->notificationRecipients);
        }
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = TransitionRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid transition ID: ' . $this->id);
            }
        } else {
            $record = new TransitionRecord();
            $record->id = $this->id;
        }


        $record->name = $this->name;
        $record->description = $this->description;
        $record->workflowId = $this->workflowId;
        $record->stateId = $this->stateId;
        $record->groups = $this->groups;
        $record->notifyAuthor = $this->notifyAuthor;
        $record->notificationRecipients = $this->notificationRecipients;
        $record->notificationText = $this->notificationText;
        $record->targetState = $this->targetState;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;
        $record->uid = $this->uid;

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('lynn-workflow', 'State ID')],
            'name' => ['label' => Craft::t('lynn-workflow', 'Name')],
            'description' => ['label' => Craft::t('lynn-workflow', 'Description')],
            'workflowId' => ['label' => Craft::t('lynn-workflow', 'Workflow ID')],
            'stateId' => ['label' => Craft::t('lynn-workflow', 'State ID')],
            'groups' => ['label' => Craft::t('lynn-workflow', 'Groups')],
            'notifyAuthor' => ['label' => Craft::t('lynn-workflow', 'Notify Author')],
            'notificationRecipients' => ['label' => Craft::t('lynn-workflow', 'Notification Recipients')],
            'notificationText' => ['label' => Craft::t('lynn-workflow', 'Notification Text')],
            'targetState' => ['label' => Craft::t('lynn-workflow', 'Target State')],
            'dateCreated' => ['label' => Craft::t('lynn-workflow', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('lynn-workflow', 'Date Updated')],
            'uid' => ['label' => Craft::t('lynn-workflow', 'UID')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('lynn-workflow', 'ID'),
            'workflowId' => Craft::t('lynn-workflow', 'Workflow ID'),
            'stateId' => Craft::t('lynn-workflow', 'State ID'),
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            // case 'publisher':
            //     $publisher = $this->getPublisher();
            //
            //     if ($publisher) {
            //         return "<a href='" . $publisher->cpEditUrl . "'>" . $publisher . "</a>";
            //     } else {
            //         return '-';
            //     }
            // case 'editor': {
            //     $editor = $this->getEditor();
            //
            //     if ($editor) {
            //         return "<a href='" . $editor->cpEditUrl . "'>" . $editor . "</a>";
            //     } else {
            //         return '-';
            //     }
            // }
            // case 'dateApproved':
            // case 'dateRejected': {
            //     return '-';
            // }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}
