<?php

namespace modules\work\controllers;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\web\Controller;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbExexption;
use yii\web\ForbiddenHttpException;

class ContentController extends Controller
{
    public function actionTransfer()
    {
        $session = Craft::$app->session;
        $request = Craft::$app->request;
        $security = Craft::$app->security;
        $user = Craft::$app->user->identity;

        if (!$user->can('transferprovisionaldrafts')) {
            throw new ForbiddenHttpException();
        }

        $draftId = $request->getRequiredBodyParam('draftId');
        $draftId = $security->validateData($draftId);
        if (!$draftId) {
            throw new InvalidArgumentException();
        }

        try {
            $recordsUpdated = Db::update(Table::DRAFTS, [
                'creatorId' => $user->id
            ], [
                'id' => $draftId
            ], [], false);
        } catch (DbExexption $e) {
            $session->setError(Craft::t('work', 'Could not update drafts table') . ': ' . $e->getMessage());
            Craft::error('Could not update drafts table: ' . $e->getMessage(), 'work');
            return $this->redirectToPostedUrl();
        }

        if (!$recordsUpdated) {
            $session->setError(Craft::t('work', 'Could not update drafts table'));
            return $this->redirectToPostedUrl();
        }

        $session->setNotice(Craft::t('work', 'Provisional draft transfered'));
        return $this->redirectToPostedUrl();
    }
}
