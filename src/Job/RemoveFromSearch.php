<?php

namespace ClarkWinkelmann\Scout\Job;

use ClarkWinkelmann\Scout\ScoutModelWrapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RemoveFromSearch implements ShouldQueue
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

        $this->models->first()->searchableUsing()->delete($this->models);
    }

    protected function restoreCollection($value)
    {
        if (!$value->class || count($value->id) === 0) {
            return new EloquentCollection;
        }

        return new EloquentCollection(
            collect($value->id)->map(function ($id) use ($value) {
                $model = new ScoutModelWrapper(new $value->class);

                $keyName = $this->getUnqualifiedScoutKeyName(
                    $model->getScoutKeyName()
                );

                $model->getRealModel()->forceFill([$keyName => $id]);

                return $model;
            })
        );
    }
}
