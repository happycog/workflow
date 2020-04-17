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
    public $siteId = 1;


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

    public function getTitle(): string
    {
        $siteName = Craft::$app->sites->getSiteById($this->siteId)->name;
        return $siteName . ' ' .Craft::t('lynnworkflow', 'Workflow Submissions');
    }

    public function getBodyHtml()
    {
        $submissionQuery = Submission::find()
            ->ownerSiteId($this->siteId)
            ->limit($this->limit);

        $submissions = $submissionQuery->all();
        $sql = $submissionQuery->getRawSql();

        return Craft::$app->getView()->renderTemplate('lynnworkflow/_components/widgets/body', [
            'submissions' => $submissions,
            'sql' => $sql,
            'siteId' => $this->siteId
        ]);
    }

    public function getSettingsHtml()
    {

        return \Craft::$app->getView()->renderTemplate('lynnworkflow/_components/widgets/settings', [
            'widget' => $this,
        ]);
    }

}
