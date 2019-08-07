<?php
namespace therefinery\lynnworkflow\services;

use therefinery\lynnworkflow\LynnWorkflow;
use therefinery\lynnworkflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\elements\User;

class Submissions extends Component
{
    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Submission::class);
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
