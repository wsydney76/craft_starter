<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use modules\work\records\TransferHistoryRecord;

/**
 * m210716_184419_create_work_transferhistory_table migration.
 */
class m210716_184419_create_work_transferhistory_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(TransferHistoryRecord::tableName(), [
            'id' => $this->primaryKey(),
            'draftId' => $this->integer()->notNull(),
            'fromUserId' => $this->integer()->notNull(),
            'toUserId' => $this->integer()->notNull(),
            'fromUserName' => $this->string(255),
            'toUserName' => $this->string(255),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->string(255)
        ], 'ENGINE=InnoDB');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(TransferHistoryRecord::tableName());
        return true;
    }
}
