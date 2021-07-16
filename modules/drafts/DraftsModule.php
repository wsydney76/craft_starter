<?php

namespace modules\drafts;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\i18n\PhpMessageSource;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

class DraftsModule extends Module
{
    public function init()
    {

        Craft::setAlias('@modules/drafts', $this->getBasePath());

        parent::init();

        if (!Craft::$app->request->isCpRequest) {
            return;
        }

        // Set template root for cp requests
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['drafts'] = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        }
        );
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['drafts'] = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        }
        );

        // Register translation category
        Craft::$app->i18n->translations['drafts'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => __DIR__ . '/messages',
            'allowOverrides' => true,
        ];

        // Inject template into entries edit screen
        $user = Craft::$app->user->identity;
        if ($user) {
            Craft::$app->view->hook('cp.entries.edit.meta', function(array $context) {
                $entry = $context['entry'];
                if ($entry === null) {
                    return '';
                }
                return Craft::$app->view->renderTemplate(
                    'drafts/draft_hints',
                    ['entry' => $entry]);
            });
        }

        // Register element index column
        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['hasProvisionalDraft'] = ['label' => Craft::t('drafts', 'Edited')];
        }
        );
        Event::on(
            Entry::class,
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {

            if ($event->attribute == 'hasProvisionalDraft') {
                $event->handled = true;
                /** @var Entry $entry */
                $entry = $event->sender;
                $event->html = '';
                $hasProvisionalDraft = Entry::find()
                    ->draftOf($entry)
                    ->provisionalDrafts(true)
                    ->draftCreator(Craft::$app->user->identity)
                    ->site('*')
                    ->exists();
                if ($hasProvisionalDraft) {
                    $event->html = '<span class="status active"></span>';
                }
            }
        }
        );
    }
}
