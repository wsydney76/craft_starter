<?php

namespace modules\work;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\i18n\PhpMessageSource;
use craft\services\UserPermissions;
use craft\web\View;
use modules\work\behaviors\WorkEntryBehavior;
use yii\base\Event;
use yii\base\Module;

class WorkModule extends Module
{
    public function init()
    {

        Craft::setAlias('@modules/work', $this->getBasePath());

        // Set the controllerNamespace based on whether this is a console or web request
        $this->controllerNamespace = Craft::$app->request->isConsoleRequest ?
            'modules\\work\\console\\controllers' :
            'modules\\work\\controllers';

        parent::init();

        if (!Craft::$app->request->isCpRequest) {
            return;
        }

        // Set template root for cp requests
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['work'] = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        }
        );


        // Register translation category
        Craft::$app->i18n->translations['work'] = [
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
                    'work/draft_hints',
                    ['entry' => $entry]);
            });
        }

        // Register Behavior
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
            $event->behaviors[] = WorkEntryBehavior::class;
        });

        // Create Permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Work Module'] = [
                'transferprovisionaldrafts' => [
                    'label' => Craft::t('work', 'Transfer other users provisional draft to own account')
                ]
            ];
        }
        );

        // Register element index column
        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['hasProvisionalDraft'] = ['label' => Craft::t('work', 'Edited')];
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
