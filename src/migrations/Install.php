<?php
namespace therefinery\lynnworkflow\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();
    }

    public function createTables()
    {
        $this->createTable('{{%lynnworkflow_submissions}}', [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer()->notNull(),
            'versionId' => $this->integer(),
            'draftId' => $this->integer(),
            'editorId' => $this->integer(),
            'stateId' => $this->integer(),
            'notes' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%lynnworkflow_states}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'description' => $this->text(),
            'workflowId' => $this->integer(),
            'weight' => $this->integer(),
            'viewGroups' => $this->text(),
            'editGroups' => $this->text(),
            'deleteGroups' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%lynnworkflow_transitions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'description' => $this->text(),
            'workflowId' => $this->integer(),
            'stateId' => $this->integer(),
            'groups' => $this->text(),
            'notifyAuthor' => $this->text(),
            'notificationRecipients' => $this->text(),
            'notificationText' => $this->text(),
            'targetState' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%lynnworkflow_workflows}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'description' => $this->text(),
            'bypass' => $this->text(),
            'groups' => $this->text(),
            'defaultState' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function dropTables()
    {
        $this->dropTable('{{%lynnworkflow_submissions}}');
        $this->dropTable('{{%lynnworkflow_states}}');
        $this->dropTable('{{%lynnworkflow_transitions}}');
        $this->dropTable('{{%lynnworkflow_workflows}}');
    }

    public function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_submissions}}', 'id', false), '{{%lynnworkflow_submissions}}', 'id', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_submissions}}', 'ownerId', false), '{{%lynnworkflow_submissions}}', 'ownerId', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_submissions}}', 'draftId', false), '{{%lynnworkflow_submissions}}', 'draftId', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_submissions}}', 'versionId', false), '{{%lynnworkflow_submissions}}', 'versionId', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_submissions}}', 'editorId', false), '{{%lynnworkflow_submissions}}', 'editorId', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_states}}', 'id', false), '{{%lynnworkflow_states}}', 'id', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_transitions}}', 'id', false), '{{%lynnworkflow_transitions}}', 'id', false);
        $this->createIndex($this->db->getIndexName('{{%lynnworkflow_workflows}}', 'id', false), '{{%lynnworkflow_workflows}}', 'id', false);
    }

    public function addForeignKeys()
    {
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'id'), '{{%lynnworkflow_submissions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'draftId'), '{{%lynnworkflow_submissions}}', 'draftId', '{{%entrydrafts}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'editorId'), '{{%lynnworkflow_submissions}}', 'editorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'stateId'), '{{%lynnworkflow_submissions}}', 'stateId', '{{%lynnworkflow_states}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_submissions}}', 'ownerId'), '{{%lynnworkflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_states}}', 'workflowId'), '{{%lynnworkflow_states}}', 'workflowId', '{{%lynnworkflow_workflows}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_transitions}}', 'workflowId'), '{{%lynnworkflow_transitions}}', 'workflowId', '{{%lynnworkflow_workflows}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_transitions}}', 'stateId'), '{{%lynnworkflow_transitions}}', 'stateId', '{{%lynnworkflow_states}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%lynnworkflow_transitions}}', 'targetState'), '{{%lynnworkflow_transitions}}', 'targetState', '{{%lynnworkflow_states}}', 'id', 'CASCADE', null);
    }

    public function dropForeignKeys()
    {
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['id'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['draftId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['editorId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['stateId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_submissions}}', ['ownerId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_states}}', ['workflowId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_transitions}}', ['workflowId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_transitions}}', ['stateId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%lynnworkflow_transitions}}', ['targetState'], $this);
    }
}
