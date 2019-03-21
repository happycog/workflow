<?php
namespace therefinery\lynnworkflow\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class LynnWorkflowAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@therefinery/lynnworkflow/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/diff_match_patch.js',
        ];

        $this->css = [
            'css/workflow.css',
        ];

        parent::init();
    }
}
