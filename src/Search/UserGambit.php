<?php

namespace ClarkWinkelmann\Scout\Search;

use ClarkWinkelmann\Scout\ScoutStatic;
use Flarum\Database\DatabaseSearchState;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\SearchState;
use Flarum\User\User;

/**
 * Fulltext filter that uses Scout to search users
 *
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class UserGambit extends AbstractFulltextFilter
{
    public function search(SearchState $state, string $value): void
    {
        // Cast to DatabaseSearchState to access getQuery()
        if (!($state instanceof DatabaseSearchState)) {
            return;
        }

        $builder = ScoutStatic::makeBuilder(User::class, $value);

        $ids = $builder->keys();

        $state->getQuery()->whereIn('id', $ids);

        $state->setDefaultSort(function ($query) use ($ids) {
            if (!count($ids)) {
                return;
            }

            $query->orderByRaw('FIELD(id' . str_repeat(', ?', count($ids)) . ')', $ids);
        });
    }
}
