<?php

namespace ClarkWinkelmann\Scout\Console;

use ClarkWinkelmann\Scout\ScoutStatic;
use Illuminate\Support\LazyCollection;

/**
 * We can't override the trait method directly because ImportCommand overrides it
 * So we re-declare the entire trait and change only the method we need
 * Also updated for Scout 10+ compatibility
 */
trait ModifiedImportTrait
{
    protected function import($model): void
    {
        ScoutStatic::makeAllSearchable($model);
    }
}
