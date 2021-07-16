<?php

namespace modules;

use craft\behaviors\DraftBehavior;
use craft\elements\Entry;
use modules\work\behaviors\WorkEntryBehavior;

/**
 * Class CustomEntry
 *
 * @mixin DraftBehavior
 * @mixin WorkEntryBehavior
 */
class CustomEntry extends Entry
{

}
