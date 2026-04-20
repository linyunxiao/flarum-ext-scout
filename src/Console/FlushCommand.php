<?php

namespace ClarkWinkelmann\Scout\Console;

use ClarkWinkelmann\Scout\ScoutStatic;
use Illuminate\Console\Command;

class FlushCommand extends Command
{
    protected $signature = 'scout:flush
            {model : Class name of model to flush}';

    protected $description = 'Flush all of the model\'s records from the index';

    public function handle()
    {
        $class = $this->argument('model');

        ScoutStatic::removeAllFromSearch($class);

        $this->info('All [' . $class . '] records have been flushed.');
    }
}
