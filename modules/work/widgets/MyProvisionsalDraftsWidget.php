<?php

namespace modules\work\widgets;

use Craft;
use craft\base\Widget;
use craft\elements\Entry;

class MyProvisionsalDraftsWidget extends Widget
{
    public static function displayName(): string
    {
        return Craft::t('work', 'My Open Edits');
    }

    public static function icon()
    {
        return Craft::getAlias('@appicons/draft.svg');
    }

    public function getBodyHtml()
    {
        $entries = Entry::find()
            ->drafts(true)
            ->provisionalDrafts(true)
            ->draftCreator(Craft::$app->user->identity)
            ->site('*')
            ->unique()
            ->anyStatus()
            ->orderBy('dateUpdated desc')
            ->all();

        return Craft::$app->view->renderTemplate('work/myprovisionaldrafts_widget', [
            'entries' => $entries
        ]);
    }
}
