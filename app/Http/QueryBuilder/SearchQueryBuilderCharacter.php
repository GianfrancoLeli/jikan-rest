<?php

namespace App\Http\QueryBuilder;

use App\Http\HttpHelper;
use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Builder;


class SearchQueryBuilderCharacter implements SearchQueryBuilderInterface
{

    const MAX_RESULTS_PER_PAGE = 25;

    const ORDER_BY = [
        'mal_id', 'name', 'member_favorites'
    ];

    public static function query(Request $request, Builder $results) : Builder
    {
        $query = $request->get('q');
        $orderBy = $request->get('order_by');
        $sort = self::mapSort($request->get('sort'));
        $letter = $request->get('letter');

        if (!empty($query) && is_null($letter)) {

            $results = $results
                ->where('name', 'like', "%{$query}%")
                ->where('name_kanji', 'like', "%{$query}%")
                ->where('nicknames', 'like', "%{$query}%");
        }

        if (!is_null($letter)) {
            $results = $results
                ->where('name', 'like', "{$letter}%");
        }

        if (empty($query)) {
            $results = $results
                ->orderBy('mal_id');
        }


        if (!is_null($orderBy)) {
            $results = $results
                ->orderBy($orderBy, $sort ?? 'asc');
        }

        return $results;
    }

    public static function mapSort(?string $sort = null) : ?string
    {
        $sort = strtolower($sort);

        return $sort === 'desc' ? 'desc' : 'asc';
    }
}