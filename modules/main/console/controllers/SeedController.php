<?php

namespace modules\main\console\controllers;

use Craft;

use craft\console\Controller;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\elements\User;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use DateInterval;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use yii\base\BaseObject;
use function var_dump;
use const PHP_EOL;

class SeedController extends Controller
{
    public const START_DATE_INTERVAL = 'P40D';
    public const NUM_ENTRIES = 5;
    public $_startDate;
    public $_faker;

    public function init(): void
    {
        parent::init();

        $startDate = new DateTime();
        $interval = new DateInterval(self::START_DATE_INTERVAL);
        $this->_startDate = $startDate->sub($interval);
    }

    public function actionCreatePosts($num = self::NUM_ENTRIES)
    {
        $faker = Factory::create('de_DE');
        $section = Craft::$app->sections->getSectionByHandle('post');
        if (!$section) {
            return;
        }
        $type = $section->getEntryTypes()[0];
        $user = User::find()->admin()->one();

        /** @var Matrix $bodyContentField */
        $bodyContentField = Craft::$app->fields->getFieldByHandle('bodyContent');
        if (!$bodyContentField) {
            return;
        }

        $blockTypes = $bodyContentField->getBlockTypes();
        $textBlockType = ArrayHelper::where($blockTypes, 'handle', 'text', true, false)[0];
        $imageBlockType = ArrayHelper::where($blockTypes, 'handle', 'image', true, false)[0];
        $headingBlockType = ArrayHelper::where($blockTypes, 'handle', 'heading', true, false)[0];

        $ids = [];

        for ($i = 0; $i <= $num; $i++) {
            $entry = new Entry();
            $entry->sectionId = $section->id;
            $entry->typeId = $type->id;
            $entry->authorId = $user->id;
            $entry->postDate = $faker->dateTimeInInterval('-1 days', '-2 months');

            $title = $faker->text(40);
            $this->stdout('Creating ' . $title . PHP_EOL);
            $entry->title = $title;
            $entry->setFieldValue('teaser', $faker->text(50));

            $asset = Asset::find()
                ->volume('images')
                ->kind('image')
                ->width('> 1900')
                ->orderBy('RAND()')
                ->one();

            if ($asset) {
                $entry->setFieldValue('featuredImage', [$asset->id]);
            }

            if (Craft::$app->elements->saveElement($entry)) {

                $ids[] = $entry->id;

               $this->createTextBlock($entry, $bodyContentField->id, $textBlockType->id, $faker);
                $this->createHeadingBlock($entry, $bodyContentField->id, $headingBlockType->id, $faker);
                $this->createImageBlock($entry, $bodyContentField->id, $imageBlockType->id, $faker);
                $this->createHeadingBlock($entry, $bodyContentField->id, $headingBlockType->id, $faker);
                $this->createTextBlock($entry, $bodyContentField->id, $textBlockType->id, $faker);
            }
        }

        $this->stdout('Updating search index... ');

        $query = Entry::find()->id($ids)->site('*');
        Craft::$app->elements->resaveElements($query, true, true, true);

        $this->stdout('done');
    }

    protected function createTextBlock($entry, $fieldId, $blockId, $faker) {
        $block = new MatrixBlock();
        $block->ownerId = $entry->id;
        $block->fieldId = $fieldId;
        $block->typeId = $blockId;
        $block->setFieldValue('text', $faker->paragraphs(3, true));

        Craft::$app->elements->saveElement($block);
    }
    protected function createHeadingBlock($entry, $fieldId, $blockId, $faker) {
        $block = new MatrixBlock();
        $block->ownerId = $entry->id;
        $block->fieldId = $fieldId;
        $block->typeId = $blockId;
        $block->setFieldValue('text', $faker->text(40));
        $block->setFieldValue('tag', 'h2');

        Craft::$app->elements->saveElement($block);
    }

    protected function createImageBlock($entry, $fieldId, $blockId, $faker) {
        $block = new MatrixBlock();
        $block->ownerId = $entry->id;
        $block->fieldId = $fieldId;
        $block->typeId = $blockId;

        $asset = Asset::find()
            ->volume('images')
            ->kind('image')
            ->width('> 900')
            ->orderBy('RAND()')
            ->one();

        if ($asset) {
            $block->setFieldValue('image', [$asset->id]);
            Craft::$app->elements->saveElement($block);
        }

    }
}
