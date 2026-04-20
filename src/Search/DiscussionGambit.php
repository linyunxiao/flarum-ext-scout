<?php

namespace ClarkWinkelmann\Scout\Search;

use ClarkWinkelmann\Scout\ScoutStatic;
use Flarum\Database\DatabaseSearchState;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Expression;

/**
 * Fulltext filter that uses Scout to search discussions and posts
 *
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class DiscussionGambit extends AbstractFulltextFilter
{
    public function search(SearchState $state, string $value): void
    {
        // Cast to DatabaseSearchState to access getQuery()
        if (!($state instanceof DatabaseSearchState)) {
            return;
        }

        $discussionBuilder = ScoutStatic::makeBuilder(Discussion::class, $value);

        $discussionIds = $discussionBuilder->keys()->all();

        $postBuilder = ScoutStatic::makeBuilder(Post::class, $value);

        $postIds = $postBuilder->keys()->all();
        $postIdsCount = count($postIds);

        // We could replace the "where field" with "where false" everywhere when there are no IDs, but it's easier to
        // keep a FIELD() statement and just hard-code some values to prevent SQL errors
        // we know nothing will be returned anyway, so it doesn't really matter what impact it has on the query
        $postIdsSql = $postIdsCount > 0 ? str_repeat(', ?', count($postIds)) : ', 0';

        $query = $state->getQuery();
        $grammar = $query->getGrammar();

        $allMatchingPostsQuery = Post::whereVisibleTo($state->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('FIELD(id' . $postIdsSql . ') as priority', $postIds)
            ->where('posts.type', 'comment')
            ->whereIn('id', $postIds);

        // Using wrap() instead of wrapTable() in join subquery to skip table prefixes
        // Using raw() in the join table name to use the same prefixless name
        $bestMatchingPostQuery = Post::query()
            ->select('posts.discussion_id')
            ->selectRaw('min(matching_posts.priority) as min_priority')
            ->join(
                new Expression('(' . $allMatchingPostsQuery->toSql() . ') ' . $grammar->wrap('matching_posts')),
                $query->raw('matching_posts.discussion_id'),
                '=',
                'posts.discussion_id'
            )
            ->groupBy('posts.discussion_id')
            ->addBinding($allMatchingPostsQuery->getBindings(), 'join');

        // Code based on Flarum\Discussion\Search\FulltextFilter
        $subquery = Post::whereVisibleTo($state->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('id as most_relevant_post_id')
            ->join(
                new Expression('(' . $bestMatchingPostQuery->toSql() . ') ' . $grammar->wrap('best_matching_posts')),
                $query->raw('best_matching_posts.discussion_id'),
                '=',
                'posts.discussion_id'
            )
            ->whereIn('id', $postIds)
            ->whereRaw('FIELD(id' . $postIdsSql . ') = best_matching_posts.min_priority', $postIds)
            ->addBinding($bestMatchingPostQuery->getBindings(), 'join');

        $query
            ->where(function (\Illuminate\Database\Query\Builder $query) use ($discussionIds) {
                $query
                    ->whereNotNull('most_relevant_post_id')
                    ->orWhereIn('id', $discussionIds);
            })
            ->selectRaw('COALESCE(posts_ft.most_relevant_post_id, ' . $grammar->wrapTable('discussions') . '.first_post_id) as most_relevant_post_id')
            ->leftJoin(
                new Expression('(' . $subquery->toSql() . ') ' . $grammar->wrap('posts_ft')),
                $query->raw('posts_ft.discussion_id'),
                '=',
                'discussions.id'
            )
            ->groupBy('discussions.id')
            ->addBinding($subquery->getBindings(), 'join');

        $state->setDefaultSort(function ($query) use ($postIdsSql, $postIds) {
            $query->orderByRaw('FIELD(id' . $postIdsSql . ')', $postIds);
        });
    }
}
