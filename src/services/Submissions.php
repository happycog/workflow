<?php
namespace therefinery\lynnworkflow\services;

use craft\elements\Entry;
use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\elements\User;

class Submissions extends Component
{
    // Public Methods
    // =========================================================================

    public function createFromDraft(Entry $draft)
    {
        $enabledWorkflow = LynnWorkflow::getInstance()->workflows->getWorkflowForSection($draft->sectionId, $draft->typeId);

        if (!$enabledWorkflow) {
            return false;
        }

        $model = new Submission();
        $model->ownerId = $draft->getSource()->id  ?? null;
        $model->draftId = $draft->draftId;
        $model->versionId = $draft->getSource()->getCurrentRevision()->id  ?? null;
        $model->editorId = Craft::$app->getUser()->getIdentity()->id  ?? null;
        $model->stateId = $enabledWorkflow->defaultState;
        $model->dateCreated = new \DateTime();
        $model->siteId = $draft->siteId;
        Craft::$app->getElements()->saveElement($model, true, false);
    }

    public function getSubmissionById(int $id, $siteId = '*')
    {
        // return Craft::$app->getElements()->getElementById($id, Submission::class);
        $submissionQuery = Submission::find()->ownerSiteId($siteId)->id($id);
        $sql = $submissionQuery->getRawSql();
        // var_dump($sql);
        return $submissionQuery->one();
    }

    public function transitionSubmission(Submission $model, $draft, $publish)
    {
        $settings = LynnWorkflow::$plugin->getSettings();


        // Publish if necessary
        if ($publish) {
            $draft->enabled = true;
            Craft::$app->getElements()->saveElement($model);
            // Craft::$app->entryRevisions->publishDraft($draft); // DEPRECATED
            Craft::$app->drafts->applyDraft($draft);
            $result = TRUE;
        }
        else {
          // Update our submission result.
          $result = Craft::$app->getElements()->saveElement($model);
        }
        return $result;
    }
}
