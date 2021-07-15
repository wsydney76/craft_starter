<?php

namespace craft\contentmigrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\elements\User;
use function var_dump;

/**
 * m200501_120937_elementindexes_settings migration.
 */
class m200501_120937_elementindexes_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $sections = Craft::$app->sections->getAllSections();
        $s = [];
        foreach ($sections as $section) {
            $s[$section->handle] = 'section:' . $section->uid;
        }

        $f = [];
        $fields = Craft::$app->fields->getAllFields();
        foreach ($fields as $field) {
            /** @var Field $field */
            $f[$field->handle] = 'field:' . $field->id;
        }

        $entrySettings = [
            'sourceOrder' => [
                ['key', '*'],
                ['heading', 'Site'],
                ['key', 'singles'],
                ['key', $s['page']],
                ['key', $s['post']],
                ['heading', 'Categories'],
                ['key', $s['topic']],
            ],
            'sources' => [
                '*' => ['tableAttributes' => ['section', 'postDate', 'link']],
                'singles' => ['tableAttributes' => ['drafts', $f['featuredImage'], 'link']],
                $s['page'] => ['tableAttributes' => ['drafts', 'hasProvisionalDraft', $f['featuredImage'], $f['teaser'], 'postDate', 'link']],
                $s['post'] => ['tableAttributes' => ['drafts', 'hasProvisionalDraft', $f['featuredImage'], $f['teaser'], 'author', 'postDate', 'link']],
                $s['topic'] => ['tableAttributes' => ['drafts', $f['featuredImage'], $f['teaser'], 'author', 'postDate', 'link']]

            ]
        ];

        Craft::$app->elementIndexes->saveSettings('craft\\elements\\Entry', $entrySettings);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "There is nothing to be reverted.\n";
        return true;
    }
}
