<?php
namespace therefinery\lynnworkflow\elements\db;

use therefinery\lynnworkflow\elements\Submission;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SubmissionQuery extends ElementQuery
{
    public $ownerId;
    public $ownerSiteId;
    public $draftId;
    public $stateId;
    public $stateName;
    public $versionId;
    public $editorId;
    public $publisherId;
    public $notes;
    public $dateApproved;
    public $dateRejected;
    public $dateRevoked;

    public function ownerId($value)
    {
        $this->ownerId = $value;
        return $this;
    }

    public function draftId(int $value = null)
    {
        $this->draftId = $value;
        return $this;
    }
    public function versionId($value)
    {
      $this->versionId = $value;
      return $this;
    }
    public function stateId($value)
    {
      $this->stateId = $value;
      return $this;
    }
    public function stateName($value)
    {
      $this->stateName = $value;
      return $this;
    }

    public function ownerSiteId($value)
    {
        $this->ownerSiteId = $value;
        return $this;
    }

    public function editorId($value)
    {
        $this->editorId = $value;
        return $this;
    }

    public function publisherId($value)
    {
        $this->publisherId = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lynnworkflow_submissions');
        /*    By Default `ElementQuery` filters out drafts and revisions, we hve to add it back in */
        $this->drafts();

        $this->query->select([
            'lynnworkflow_submissions.*',
            // '{{%lynnworkflow_states}}.*',
        ]);
        $this->enabledForSite(false);
        // $this->enabledForSite(true);

        // if ($this->ownerSiteId){
        //     $this->siteId = $this->ownerSiteId;
        // }

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.ownerId', $this->ownerId));
        }

        if ($this->draftId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.draftId', $this->draftId));
        }

        if ($this->versionId) {
          $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.versionId', $this->versionId));
        }

        if ($this->stateName){
            // join with states table and select records with matchng `name`
            $this->innerJoin('{{%lynnworkflow_states}} `lynnworkflow_states`', 'lynnworkflow_submissions.`stateId` = lynnworkflow_states.`id`')
                ->andWhere(Db::parseParam('lynnworkflow_states.name', $this->stateName));
        }

        if ($this->stateId) {
          $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.stateId', $this->stateId));
        }

        if ($this->ownerSiteId) {
            // Should join with `elements_sites` table on elementId and select `siteid` that matches`ownerSiteId`
            // $this->subQuery->andWhere(Db::parseParam('elements_sites.siteId', $this->ownerSiteId));
            $this->siteId = $this->ownerSiteId;
        }

        if ($this->editorId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.editorId', $this->editorId));
        }

        if ($this->publisherId) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.publisherId', $this->publisherId));
        }

        if ($this->notes) {
            $this->subQuery->andWhere(Db::parseParam('lynnworkflow_submissions.notes', $this->notes));
        }

        if ($this->dateApproved) {
            $this->subQuery->andWhere(Db::parseDateParam('lynnworkflow_submissions.dateApproved', $this->dateApproved));
        }

        if ($this->dateRejected) {
            $this->subQuery->andWhere(Db::parseDateParam('lynnworkflow_submissions.dateRejected', $this->dateRejected));
        }

        if ($this->dateRevoked) {
            $this->subQuery->andWhere(Db::parseDateParam('lynnworkflow_submissions.dateRevoked', $this->dateRevoked));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status)
    {
        switch ($status) {
            case Submission::STATUS_APPROVED:
                return [
                    'lynnworkflow_submissions.status' => Submission::STATUS_APPROVED,
                ];
            case Submission::STATUS_PENDING:
                return [
                    'lynnworkflow_submissions.status' => Submission::STATUS_PENDING,
                ];
            case Submission::STATUS_REJECTED:
                return [
                    'lynnworkflow_submissions.status' => Submission::STATUS_REJECTED,
                ];
            case Submission::STATUS_REVOKED:
                return [
                    'lynnworkflow_submissions.status' => Submission::STATUS_REVOKED,
                ];
            default:
                return parent::statusCondition($status);
        }
    }
}
