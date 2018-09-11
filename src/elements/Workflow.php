<?php
namespace therefinery\lynnworkflow\elements;

use therefinery\lynnworkflow\elements\actions\SetStatus;
use therefinery\lynnworkflow\elements\db\WorkflowQuery;
use therefinery\lynnworkflow\records\Workflow as WorkflowRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

class Workflow extends Element
{
    // Public Properties
    // =========================================================================

    public $id;
    public $name;
    public $description;
    public $bypass;
    public $groups;
    public $defaultState;
    public $dateCreated;
    public $dateUpdated;
    public $uid;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('lynn-workflow', 'Lynn Workflow Workflow');
    }

    public static function refHandle()
    {
        return 'workflow';
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
        return new WorkflowQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('lynn-workflow', 'All workflows'),
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('lynn-workflow', 'Are you sure you want to delete the selected workflows?'),
            'successMessage' => Craft::t('lynn-workflow', 'Workflows deleted.'),
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
        if (!empty($this->bypass)) {
          $this->bypass = Json::decodeIfJson($this->bypass);
        }
        if (!empty($this->groups)) {
          $this->groups = Json::decodeIfJson($this->groups);
        }
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = WorkflowRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid workflow ID: ' . $this->id);
            }
        } else {
            $record = new WorkflowRecord();
            $record->id = $this->id;
        }

        $record->name = $this->name;
        $record->description = $this->description;
        $record->bypass = $this->bypass;
        $record->groups = $this->groups;
        $record->defaultState = $this->defaultState;
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
            'id' => ['label' => Craft::t('lynn-workflow', 'Workflow ID')],
            'name' => ['label' => Craft::t('lynn-workflow', 'Name')],
            'description' => ['label' => Craft::t('lynn-workflow', 'Description')],
            'bypass' => ['label' => Craft::t('lynn-workflow', 'Access Bypass')],
            'groups' => ['label' => Craft::t('lynn-workflow', 'Groups')],
            'defaultState' => ['label' => Craft::t('lynn-workflow', 'Default State')],
            'dateCreated' => ['label' => Craft::t('lynn-workflow', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('lynn-workflow', 'Date Updated')],
            'uid' => ['label' => Craft::t('lynn-workflow', 'UID')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('lynn-workflow', 'Entry'),
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
