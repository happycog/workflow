<?php

namespace therefinery\lynnworkflow\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m190731_212003_submission_3_2 migration.
 *
 *  Database migration needed for converting CMS 3.1 drafts into 3.2 drafts that uses a different table
 * 
 *  Migrating
 *  Delete all existing Submissions
 *  Run `php ./craft migrate/up --plugin=lynnworkflow` to apply changes
 * 
 */
class m190731_212003_submission_3_2 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Place migration code here...
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['draftId'], $this);

        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'draftId'), '{{%lynnworkflow_submissions}}', 'draftId', '{{%drafts}}', 'id', 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190731_212003_submission_3_2 cannot be reverted.\n";
        return false;
    }
}
