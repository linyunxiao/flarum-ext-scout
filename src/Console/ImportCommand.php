<?php

namespace ClarkWinkelmann\Scout\Console;

use Illuminate\Console\Command;

class ImportCommand extends Command
{
    protected $signature = 'scout:import
            {model : Class name of model to bulk import}
            {--c|chunk= : The number of records to import at a time}';

    protected $description = 'Import the given model into the search index';

    use ModifiedImportTrait;

    public function handle()
    {
        $class = $this->argument('model');

        $this->import($class);

        $this->info('All [' . $class . '] records have been imported.');
    }
}
