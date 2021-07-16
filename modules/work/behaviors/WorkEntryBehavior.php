<?php

namespace modules\work\behaviors;

use Craft;
use craft\elements\Entry;
use modules\work\records\TransferHistoryRecord;
use yii\base\Behavior;

class WorkEntryBehavior extends Behavior
{
    public function canTransfer()
    {
        /** @var Entry $entry */
        $entry = $this->owner;
        $user = Craft::$app->user->identity;

        if (!$entry->isProvisionalDraft) {
            return false;
        }

        if (!$user->can('transferprovisionaldrafts')) {
            return false;
        }

        if ($entry->creatorId == $user->id) {
            return false;
        }

        $hasOwnProvisionalDraft = Entry::find()
            ->draftOf($entry->getCanonical())
            ->drafts(true)
            ->provisionalDrafts(true)
            ->site('*')
            ->draftCreator($user)
            ->exists();
        if ($hasOwnProvisionalDraft) {
            return false;
        }

        return true;

    }

    public function getTransferHistory() {
        /** @var Entry $entry */
        $entry = $this->owner;
        if (! $entry->isProvisionalDraft) {
            return [];
        }
        return TransferHistoryRecord::find()
            ->where(['draftId' => $entry->draftId])
            ->orderBy('dateCreated desc')
            ->all();
    }
}
