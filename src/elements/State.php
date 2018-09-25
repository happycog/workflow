<?php
namespace therefinery\lynnworkflow\elements;

use therefinery\lynnworkflow\elements\actions\SetStatus;
use therefinery\lynnworkflow\elements\db\StateQuery;
use therefinery\lynnworkflow\records\State as StateRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

class State extends Element
{
    // Public Properties
    // =========================================================================

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



    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('lynnworkflow', 'Lynn Workflow State');
    }

    public static function refHandle()
    {
        return 'state';
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
        return new StateQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('lynnworkflow', 'All states'),
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('lynnworkflow', 'Are you sure you want to delete the selected states?'),
            'successMessage' => Craft::t('lynnworkflow', 'States deleted.'),
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
        if (!empty($this->viewGroups)) {
          $this->viewGroups = Json::decodeIfJson($this->viewGroups);
        }
        if (!empty($this->editGroups)) {
          $this->editGroups = Json::decodeIfJson($this->editGroups);
        }
        if (!empty($this->deleteGroups)) {
          $this->deleteGroups = Json::decodeIfJson($this->deleteGroups);
        }
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = StateRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid state ID: ' . $this->id);
            }
        } else {
            $record = new StateRecord();
            $record->id = $this->id;
        }

        $record->name = $this->name;
        $record->description = $this->description;
        $record->workflowId = $this->workflowId;
        $record->weight = $this->weight;
        $record->viewGroups = $this->viewGroups;
        $record->editGroups = $this->editGroups;
        $record->deleteGroups = $this->deleteGroups;
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
            'id' => ['label' => Craft::t('lynnworkflow', 'State ID')],
            'name' => ['label' => Craft::t('lynnworkflow', 'Name')],
            'description' => ['label' => Craft::t('lynnworkflow', 'Description')],
            'workflowId' => ['label' => Craft::t('lynnworkflow', 'Workflow ID')],
            'weight' => ['label' => Craft::t('lynnworkflow', 'Weight')],
            'viewGroups' => ['label' => Craft::t('lynnworkflow', 'View Groups')],
            'editGroups' => ['label' => Craft::t('lynnworkflow', 'Edit Groups')],
            'deleteGroups' => ['label' => Craft::t('lynnworkflow', 'Delete Groups')],
            'dateCreated' => ['label' => Craft::t('lynnworkflow', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('lynnworkflow', 'Date Updated')],
            'uid' => ['label' => Craft::t('lynnworkflow', 'UID')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('lynnworkflow', 'ID'),
            'workflowId' => Craft::t('lynnworkflow', 'Workflow ID'),
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
