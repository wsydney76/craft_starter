<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\models\Section;
use function array_diff;
use function copy;
use function explode;
use function is_dir;
use function pathinfo;
use function scandir;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * m210814_092818_starter_assets migration.
 */
class m210814_092818_starter_assets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->installStarterContent();

        $this->setFeaturedImages();

        $this->setIcon();

        $this->setUserPhotos();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "There is nothing to revert.\n";
        return true;
    }

    protected function setUserPhotos()
    {
        $sourceDir = Craft::parseEnv('@storage/rebrand/userphotos');

        $files = scandir($sourceDir);
        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            $path = $sourceDir . DIRECTORY_SEPARATOR . $file;
            $pathInfo = pathinfo($path);
            $username = $pathInfo['filename'];

            $user = User::find()->username($username)->one();
            if ($user) {
                // saveUserPhoto deletes the file, so making a temporary copy
                $tempPath = Assets::tempFilePath($pathInfo['extension']);
                copy($path, $tempPath);
                Craft::$app->users->saveUserPhoto($tempPath, $user);
            }
        }
    }

    protected function installStarterContent()
    {
        $sourceDir = Craft::parseEnv('@storage/rebrand/startercontent');
        $destDir = Craft::parseEnv('@webroot/images/startercontent');

        FileHelper::copyDirectory($sourceDir, $destDir);

        $files = scandir($destDir);
        $files = array_diff($files, ['.', '..']);

        $volume = Craft::$app->volumes->getVolumeByHandle('images');
        if ($volume) {
            foreach ($files as $file) {
                $path = 'startercontent/' . $file;
                $alt = ucfirst(explode('-', $file)[0]);
                $asset = Craft::$app->assetIndexer->indexFile($volume, $path);

                $asset->title = $alt;
                $asset->setFieldValue('copyright', 'Pixabay');
                $asset->setFieldValue('altText', $alt);
                Craft::$app->elements->saveElement($asset);

                $asset = Asset::find()->filename($file)->site('de')->one();
                if ($asset) {
                    $asset->setFieldValue('altText', $alt);
                    Craft::$app->elements->saveElement($asset);
                }
            }
        }
    }

    protected function setFeaturedImages()
    {

        $global = GlobalSet::find()->handle('siteInfo')->one();
        if ($global) {
            $this->setFeaturedImage($global);
        }

        $sections = Craft::$app->sections->getSectionsByType(Section::TYPE_SINGLE);
        foreach ($sections as $section) {
            $entry = Entry::find()->section($section->handle)->one();
            if ($entry) {
                $this->setFeaturedImage($entry);
            }
        }
    }

    protected function setFeaturedImage($element) {
        $asset = Asset::find()
            ->volume('images')
            ->kind('image')
            ->width('> 1900')
            ->orderBy('RAND()')
            ->one();

        if (!$asset) {
            return;
        }

        $element->setFieldValue('featuredImage', [$asset->id]);

        Craft::$app->elements->saveElement($element);
    }

    protected function setIcon()
    {
        $sourceDir = Craft::parseEnv('@storage/rebrand/icon');
        $destDir = Craft::parseEnv('@webroot/images/icons');
        if (!is_dir($destDir)) {
            FileHelper::createDirectory($destDir);
        }
        $files = scandir($sourceDir);
        $files = array_diff($files, ['.', '..']);
        if (!$files) {
            return;
        }
        foreach ($files as $file) {
            $path = $destDir . DIRECTORY_SEPARATOR . $file;
            copy($sourceDir . DIRECTORY_SEPARATOR . $file, $path);

            $volume = Craft::$app->volumes->getVolumeByHandle('images');
            if (!$volume) {
                return;
            }
            $asset = Craft::$app->assetIndexer->indexFile($volume, 'icons/' . $file);

            $global = GlobalSet::find()->handle('siteInfo')->one();
            if (!$global) {
                return;
            }

            $global->setFieldValue('siteIcon', [$asset->id]);

            if (Craft::$app->elements->saveElement($global)) {
                break;
            }
        }
    }
}
