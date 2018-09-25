<?php
namespace therefinery\lynnworkflow\widgets;

use therefinery\lynnworkflow\elements\Submission;

use Craft;
use craft\base\Widget;

class Submissions extends Widget
{
    // Properties
    // =========================================================================

    public $limit = 10;


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('lynnworkflow', 'Lynn Workflow Submissions');
    }

    public static function iconPath(): string
    {
        return Craft::getAlias('@therefinery/lynnworkflow/icon-mask.svg');
    }

    public function getBodyHtml()
    {
        $submissions = Submission::find()
            ->limit($this->limit)
            ->all();

        return Craft::$app->getView()->renderTemplate('lynnworkflow/_components/widgets/body', [
            'submissions' => $submissions,
        ]);
    }

    public function getSettingsHtml()
    {

        return \Craft::$app->getView()->renderTemplate('lynnworkflow/_components/widgets/settings', [
            'widget' => $this,
        ]);
    }

}
