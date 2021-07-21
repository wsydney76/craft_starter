<?php

namespace modules\work\behaviors;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use modules\work\records\TransferHistoryRecord;
use yii\base\Behavior;
use function array_diff;
use function array_diff_assoc;
use function array_map;

class WorkEntryBehavior extends Behavior
{

    /**
     * This is a workaround for a Craft bug that won't be fixed
     * https://github.com/craftcms/cms/issues/9596
     *
     * @param Field $field
     * @param ElementInterface $canonicalElement
     * @return mixed
     * @throws \craft\errors\InvalidFieldException
     */
    public function isFieldModifiedForDiff(Field $field, ElementInterface $canonicalElement)
    {
        /** @var Entry $entry */
        /** @var MatrixBlock $block */

        $entry = $this->owner;
        if (!$field instanceof Matrix) {
            return $entry->isFieldModified($field->handle);
        }

        // Field changed detected for current site?
        if ($entry->isFieldModified($field->handle)) {
            return true;
        }


        $blocks = $entry->getFieldValue($field->handle)->anyStatus()->all();

        // Sub-Field changed?
        foreach ($blocks as $block) {
            foreach ($block->fieldLayout->fields as $matrixField) {
                if ($block->isFieldModified($matrixField->handle)) {
                    return true;
                }
            }
        }

        $canonicalBlocks = $canonicalElement->getFieldValue($field->handle)->anyStatus()->all();

        // Block(s) added / deleted?
        if (count($blocks) != count($canonicalBlocks)) {
            return true;
        }

        $ids = array_map(function($b) {
            return $b->canonicalId;
        }, $blocks);
        $canonicalIds = array_map(function($b) {
            return $b->id;
        }, $canonicalBlocks);

        // Same count, but different order, or same count of blocks has been added/removed
        if (array_diff_assoc($ids, $canonicalIds)) {
            return true;
        }

        // Status changed
        foreach ($blocks as $block) {
            $canonicalBlock = $block->getCanonical();
            if ($block->status != $canonicalBlock->status) {
                return true;
            }
        }

        return $this->owner->isFieldModified($field->handle);
    }

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

    public function getTransferHistory()
    {
        /** @var Entry $entry */

        $entry = $this->owner;
        if (!$entry->isProvisionalDraft) {
            return [];
        }

        if (!Craft::$app->db->tableExists(TransferHistoryRecord::tableName())) {
            return [];
        }

        return TransferHistoryRecord::find()
            ->where(['draftId' => $entry->draftId])
            ->orderBy('dateCreated desc')
            ->all();
    }
}
