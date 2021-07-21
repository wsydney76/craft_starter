<?php

namespace modules\work\behaviors;

use yii\base\Behavior;

class WorkMatrixBehavior extends Behavior
{
    public function isFieldModifiedForDiff($field, $canonicalElement)
    {
        return $this->owner->isFieldModified($field->handle);
    }
}
