<?php

namespace ClarkWinkelmann\Scout\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MakeSearchable implements ShouldQueue
{
    use Queueable, SerializesModels;

    use SerializesAndRestoresWrappedModelIdentifiers {
        SerializesAndRestoresWrappedModelIdentifiers::getSerializedPropertyValue insteadof SerializesModels;
        SerializesAndRestoresWrappedModelIdentifiers::getRestoredPropertyValue insteadof SerializesModels;
        SerializesAndRestoresWrappedModelIdentifiers::restoreCollection insteadof SerializesModels;
    }

    public $models;

    public function __construct(Collection $models)
    {
        $this->models = $models;
    }

    public function handle()
    {
        if ($this->models->isEmpty()) {
            return;
        }

        $this->models->first()->searchableUsing()->update($this->models);
    }
}
