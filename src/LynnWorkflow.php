<?php
namespace therefinery\lynnworkflow;

use therefinery\lynnworkflow\base\PluginTrait;
use therefinery\lynnworkflow\models\Settings;
use therefinery\lynnworkflow\variables\LynnWorkflowVariable;
use therefinery\lynnworkflow\widgets\Submissions as SubmissionsWidget;
use therefinery\lynnworkflow\elements\Submission;
use therefinery\lynnworkflow\elements\Workflow;
use therefinery\lynnworkflow\services\Workflows;


use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\DraftEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\EntryRevisions; // deprecated
use craft\services\Drafts;
use craft\elements\Entry;
use craft\services\SystemMessages;
use craft\web\UrlManager;
use craft\helpers\DateTimeHelper;
use DateTime;
use craft\web\twig\variables\CraftVariable;

use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\View;
use craft\events\TemplateEvent;

use yii\base\Event;
use yii\web\User;

class LynnWorkflow extends Plugin
{
    public $hasCpSettings = true;
    // Traits
    // =========================================================================

    use PluginTrait;

    public $schemaVersion = '0.2.0';


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();

        // Register our CP routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register Widgets
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = SubmissionsWidget::class;
        });

        Craft::$app->view->hook('cp.entries.edit.details', [$this->getService(), 'renderEntrySidebar']);

        // Setup Variables class (for backwards compatibility)
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('lynnworkflow', LynnWorkflowVariable::class);
        });

        
        Event::on( // https://craftcms.stackexchange.com/questions/26797/user-events-in-craft-3
          Drafts::class,
          Drafts::EVENT_AFTER_CREATE_DRAFT,
          function (DraftEvent $event) {
            $user = Craft::$app->getUser()->getIdentity();
            
            // Get settings for workflows.
            $settings = LynnWorkflow::$plugin->getSettings();
            $draft = $event->draft; // `createDraft` function saves a draft with ID then calls EVENT_AFTER_CREATE_DRAFT
            $draft_id = $draft->draftId;
            $siteId = $draft->siteId;
            
            // Is this a new draft (aka, it doesn't have an entry in submissions?)
            $existing_submission = Submission::find()
              ->draftId($draft_id)
              ->all();
            if (empty($existing_submission)) {
              // Create a submission record for this new draft
              
              // Get the ID number for the latest version if exists (stored in Submission)
              // CMS v3.5+ uses 'sourceId' in draft post requests
              if (Craft::$app->request->getParam('sourceId')) {
                $entry_id = Craft::$app->request->getParam('sourceId');
              } else{
                $entry_id = Craft::$app->request->getParam('entryId');
              }
              $version_id = NULL;
              $latestVersion = Entry::find()->revisionOf($entry_id)->addOrderBy('id DESC')->one();
              if (!empty($versions)) {
                $version_id = $latestVersion->versionId;
              }

              $section_id = $draft->sectionId;
              $type_id = $draft->typeId;

              // Check to see if there's a valid workflow for this section/type:
              $enabled_workflows = $settings->enabledWorkflows;
              $enabled_workflow = FALSE;
              // See if an entry exists for the sectionId-typeId.
              if (!empty($enabled_workflows[$section_id . '-' . $type_id])) {
                $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id . '-' . $type_id], Workflow::class);
              }
              else if (!empty($enabled_workflows[$section_id])) {
                $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id], Workflow::class);
              }
              if ($enabled_workflow) {
                // Get the default state.
                $default_workflow_state = $enabled_workflow->defaultState;

                // Determine the default state id from the workflow.
                $model = new Submission();
                $model->ownerId = $entry_id;
                $model->draftId = $draft_id;
                $model->versionId = $version_id;
                $model->editorId = $user->id;
                $model->stateId = $default_workflow_state;
                $model->dateCreated = new DateTime();
                $model->siteId = $siteId;
                Craft::$app->getElements()->saveElement($model, true, false); // $propagate=false Whether the element should be saved across all of its supported sites
              }
            }
            else { // empty($existing_submission
              // Should we do something when there's a state?
            }
          }
        );
        // Do something after we're installed
        // Event::on(
        //     EntryRevisions::class,
        //     EntryRevisions::EVENT_AFTER_SAVE_DRAFT, // deprecated in Craft 3.2
        //     function (Event $event) {
        //       $user = Craft::$app->getUser()->getIdentity();
        //       // Get settings for workflows.
        //       $settings = LynnWorkflow::$plugin->getSettings();
        //       $draft = $event->draft;
        //       $draft_id = $draft->draftId;
        //       // Is this a new draft (aka, it doesn't have an entry in submissions?)
        //       $existing_submission = Submission::find()
        //                 ->draftId($draft_id)
        //                 ->all();
        //       if (empty($existing_submission)) {
        //         $entry_id = Craft::$app->request->getParam('entryId');
        //         $latest_version = Craft::$app->entryRevisions->getVersionsByEntryId($entry_id, FALSE, 1, TRUE);
        //         $version_id = NULL;
        //         if (!empty($latest_version)) {
        //           $latest_version = current($latest_version);
        //           $version_id = $latest_version->versionId;
        //         }
        //         $section_id = $draft->sectionId;
        //         $type_id = $draft->typeId;
        //         // Check to see if there's a valid workflow for this section/type:
        //         $enabled_workflows = $settings->enabledWorkflows;
        //         $enabled_workflow = FALSE;
        //         // See if an entry exists for the sectionId-typeId.
        //         if (!empty($enabled_workflows[$section_id . '-' . $type_id])) {
        //           $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id . '-' . $type_id], Workflow::class);
        //         }
        //         else if (!empty($enabled_workflows[$section_id])) {
        //           $enabled_workflow = Craft::$app->getElements()->getElementById($enabled_workflows[$section_id], Workflow::class);
        //         }
        //         if ($enabled_workflow) {
        //           // Get the default state.
        //           $default_workflow_state = $enabled_workflow->defaultState;

        //           // Determine the default state id from the workflow.
        //           $model = new Submission();
        //           $model->ownerId = $entry_id;
        //           $model->draftId = $draft_id;
        //           $model->versionId = $version_id;
        //           $model->editorId = $user->id;
        //           $model->stateId = $default_workflow_state;
        //           $model->dateCreated = new DateTime();
        //           Craft::$app->getElements()->saveElement($model);
        //         }
        //       }
        //       else {
        //         // Should we do something when there's a state?
        //       }
        //     }
        // );

        /**
         * Creates a custom permission type for admin users to access
         * Workflow and settings tabs in plugin
         * Access to the plugin itself is set with General Premission 'Access LynnWorkflow'
         */
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions['Lynn Workflows'] = [
                    'manageLynnWorkflows' => [
                        'label' => 'Manage Lynn Workflows',
                    ],
                ];
            }
        );

        /**
         * defines the tabs based on user permisions
         * 
         * See here for a how-to: https://github.com/craftcms/cms/issues/2326
         */
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function(TemplateEvent $e) {
          // Craft::dump($e);
            if (
                substr($e->template, 0, 12) === 'lynnworkflow' 
            ) {
              if (Craft::$app->user->checkPermission('manageLynnWorkflows')) {
                // Admin only tabs
                $e->variables['tabs'] = [
                  'lynnworkflow' => ['label' => 'Overview', 'url' => UrlHelper::url('lynnworkflow')],
                  'Workflows' => ['label' => 'Workflows', 'url' => UrlHelper::url('lynnworkflow/workflows')],
                  'drafts' => ['label' => 'Drafts', 'url' => UrlHelper::url('lynnworkflow/drafts')],
                  'settings' => ['label' => 'Settings', 'url' => UrlHelper::url('lynnworkflow/settings')],
                  // 'transitions' => ['label' => 'Transitions', 'url' => UrlHelper::url('lynnworkflow/transitions')],
                ];
              }else{
                // regular user tabs
                $e->variables['tabs'] = [
                  'lynnworkflow' => ['label' => 'Overview', 'url' => UrlHelper::url('lynnworkflow')],
                  'drafts' => ['label' => 'Drafts', 'url' => UrlHelper::url('lynnworkflow/drafts')],
                ];
              }
            }
        });
    }

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $rules = [
          'lynnworkflow/drafts' => 'lynnworkflow/base/drafts',
          'lynnworkflow/settings' => 'lynnworkflow/base/settings',
          'lynnworkflow/workbench' => 'lynnworkflow/base/workbench',
          'lynnworkflow/workflows' => 'lynnworkflow/workflows/list',
          'lynnworkflow/workflows/new' => 'lynnworkflow/workflows/edit',
          'lynnworkflow/workflows/<workflowId:\d+>' => 'lynnworkflow/workflows/show',
          'lynnworkflow/workflows/<workflowId:\d+>/edit' => 'lynnworkflow/workflows/edit',
          'lynnworkflow/workflows/<workflowId:\d+>/states/new' => 'lynnworkflow/states/edit',
          'lynnworkflow/workflows/<workflowId:\d+>/states/<stateId:\d+>' => 'lynnworkflow/states/show',
          'lynnworkflow/workflows/<workflowId:\d+>/states/<stateId:\d+>/edit' => 'lynnworkflow/states/edit',
          'lynnworkflow/workflows/<workflowId:\d+>/states/<stateId:\d+>/transitions/new' => 'lynnworkflow/transitions/edit',
          'lynnworkflow/workflows/<workflowId:\d+>/states/<stateId:\d+>/transitions/<transitionId:\d+>' => 'lynnworkflow/transitions/show',
          'lynnworkflow/workflows/<workflowId:\d+>/states/<stateId:\d+>/transitions/<transitionId:\d+>/edit' => 'lynnworkflow/transitions/edit',
          'lynnworkflow/submissions/diff/<diffEntryId:\d+>/<draftId:\d+>' => 'lynnworkflow/submissions/diff',
          'lynnworkflow/submissions/sidebar/<sbEntryId:\d+>/<sbEdraftId:\d+>' => 'lynnworkflow/submissions/sidebar'
        ];

        $event->rules = array_merge($event->rules, $rules);
    }


    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('lynnworkflow/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('lynnworkflow/settings', [
            'settings' => $this->getSettings()
        ]);
    }

}
