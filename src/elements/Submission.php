<?php
namespace therefinery\lynnworkflow\elements;

use therefinery\lynnworkflow\elements\actions\SetStatus;
use therefinery\lynnworkflow\elements\db\SubmissionQuery;
use therefinery\lynnworkflow\records\Submission as SubmissionRecord;
use therefinery\lynnworkflow\elements\State;


use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

class Submission extends Element
{
    // Constants
    // =========================================================================

    const STATUS_APPROVED = 'approved';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REVOKED = 'revoked';


    // Public Properties
    // =========================================================================

    public $ownerId;
    public $draftId;
    public $versionId;
    public $stateId;
    public $editorId;
    public $publisherId;
    public $status;
    public $notes;
    public $dateApproved;
    public $dateRejected;
    public $dateRevoked;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('lynnworkflow', 'Lynn Workflow Submission');
    }

    public static function refHandle()
    {
        return 'submission';
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
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_APPROVED => Craft::t('lynnworkflow', 'Approved'),
            self::STATUS_PENDING => Craft::t('lynnworkflow', 'Pending'),
            self::STATUS_REJECTED => Craft::t('lynnworkflow', 'Rejected'),
            self::STATUS_REVOKED => Craft::t('lynnworkflow', 'Revoked')
        ];
    }

    public static function find(): ElementQueryInterface
    {
        return new SubmissionQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('lynnworkflow', 'All submissions'),
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('lynnworkflow', 'Are you sure you want to delete the selected submissions?'),
            'successMessage' => Craft::t('lynnworkflow', 'Submissions deleted.'),
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
        if (!empty($this->notes)) {
          $this->notes = Json::decodeIfJson($this->notes);
        }
    }
    public function getStatus()
    {
        return $this->status;
    }

    public function getCpEditUrl()
    {
        $cpEditUrl = $url = $this->getOwner()->cpEditUrl;

        if ($this->draftId) {
            if (Craft::$app->getIsMultiSite()) {
                $cpEditUrl = explode('/', $cpEditUrl);
                array_pop($cpEditUrl);
                $cpEditUrl = implode('/', $cpEditUrl);
            }

            $url = $cpEditUrl . '/drafts/' . $this->draftId;
        }

        return $url;
    }

    public function getOwner()
    {
        if ($this->ownerId !== null) {
            return Craft::$app->getEntries()->getEntryById($this->ownerId);
        }
    }

    public function getEditor()
    {
        if ($this->editorId !== null) {
            return Craft::$app->getUsers()->getUserById($this->editorId);
        }
    }

    public function getPublisher()
    {
        if ($this->publisherId !== null) {
            return Craft::$app->getUsers()->getUserById($this->publisherId);
        }
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = SubmissionRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid submission ID: ' . $this->id);
            }
        } else {
            $record = new SubmissionRecord();
            $record->id = $this->id;
        }
        // Update notes as necessary.
        // Ensure that notes are saved correctly.
        $notes_to_save = array();
        if (!empty($record->notes)) {
          $notes_to_save = Json::decodeIfJson($record->notes);
        }
        if (!empty($this->notes)) {
          $encoded_notes = $this->notes;
          $notes_to_save[] = $encoded_notes;
        }
        $this->notes = $notes_to_save;

        $record->ownerId = $this->ownerId;
        $record->draftId = $this->draftId;
        $record->versionId = $this->versionId;
        $record->editorId = $this->editorId;
        $record->stateId = $this->stateId;
        $record->notes = $this->notes;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;


        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('lynnworkflow', 'ID')],
            'draftTitle' => ['label' => Craft::t('lynnworkflow', 'Draft Title')],
            'editor' => ['label' => Craft::t('lynnworkflow', 'Editor')],
            'dateCreated' => ['label' => Craft::t('lynnworkflow', 'Date Submitted')],
            'stateId' => ['label' => Craft::t('lynnworkflow', 'Current State')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'dateCreated' => Craft::t('lynnworkflow', 'Date Submitted'),
            'stateId' => Craft::t('lynnworkflow', 'Current State'),
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'draftTitle': {
              $draft = Craft::$app->entryRevisions->getDraftById($this->draftId);
              $edit_url = $this->getCpEditUrl();
              return '<a href="' . $edit_url . '">' . $draft->title . '</a>';
            }
            case 'stateId': {
              $stateId = $this->stateId;
              $current_state = Craft::$app->getElements()->getElementById($stateId, State::class);
              if (!empty($current_state->name)) {
                return $current_state->name;
              }
              else {
                return NULL;
              }
            }
            case 'editor': {
                $editor = $this->getEditor();

                if ($editor) {
                    return "<a href='" . $editor->cpEditUrl . "'>" . $editor . "</a>";
                } else {
                    return '-';
                }
            }
            case 'dateApproved':
            case 'dateRejected': {
                return '-';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}
