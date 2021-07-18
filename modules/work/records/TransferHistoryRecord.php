<?php

namespace modules\work\records;

use craft\db\ActiveRecord;

class TransferHistoryRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%work_transferhistory}}';
    }
}
