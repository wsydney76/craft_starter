<?php

namespace modules\main;

use Craft;
use craft\elements\Entry;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineRulesEvent;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use craft\models\FieldLayout;
use modules\main\fieldlayoutelements\NewRow;
use modules\main\validators\BiographicalDateValidator;
use modules\main\validators\BodyContentValidator;
use modules\main\validators\CastValidator;
use modules\main\validators\CurrentYearValidator;
use modules\main\validators\StationValidator;
use yii\base\Event;
use yii\base\Module;

class MainModule extends Module
{
    public function init()
    {

        Craft::setAlias('@modules/main', $this->getBasePath());

        // Set the controllerNamespace based on whether this is a console or web request
        $this->controllerNamespace = Craft::$app->request->isConsoleRequest ?
            'modules\\main\\console\\controllers' :
            'modules\\main\\controllers';

        // Add Drafts Warning to UI Elements
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_UI_ELEMENTS, function(DefineFieldLayoutElementsEvent $event) {
            if ($event->sender->type == 'craft\\elements\\Entry') {
                $event->elements[] = new NewRow();
            }
        }
        );

        // Validation Rules

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_RULES, function(DefineRulesEvent $event) {
            /** @var Entry $entry */
            $entry = $event->sender;
            if ($entry->resaving || $entry->propagating || $entry->scenario != Entry::STATUS_LIVE) {
                return;
            }
            $event->rules[] = [['bodyContent'], BodyContentValidator::class];
        });

        // Validate entries on all sites (fixes open Craft bug)
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_VALIDATE, function($event) {

            /** @var Entry $entry */
            $entry = $event->sender;

            if ($entry->hasErrors()) {
                return;
            }

            if ($entry->resaving || $entry->propagating || $entry->scenario != Entry::STATUS_LIVE) {
                return;
            }

            foreach ($entry->getLocalized()->all() as $localizedEntry) {
                $localizedEntry->scenario = Entry::SCENARIO_LIVE;
                if ($localizedEntry->hasErrors()) {
                    break;
                }

                if (!$localizedEntry->validate()) {
                    $entry->addError(
                        $entry->type->hasTitleField ? 'title' : 'slug',
                        Craft::t('site', 'Error validating entry in') .
                        ' "' . $localizedEntry->site->name . '". ' .
                        implode(' ', $localizedEntry->getErrorSummary(false)));
                    //  $event->isValid = false;
                }
            }
        });
    }
}
