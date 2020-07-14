<?php

namespace App\Http\Controllers\V3;

use App\Http\HttpHelper;
use Illuminate\Support\Facades\DB;
use Jikan\Request\Producer\ProducerRequest;
use Illuminate\Http\Request;

class ProducerController extends Controller
{
    private $request;
    const MAX_RESULTS_PER_PAGE = 25;

    public function main(Request $request, int $id, int $page = 1)
    {

        $this->request = $request;

        $results = DB::table('anime')
            ->where('producers.mal_id', $id)
            ->orderBy('title');

        $results = $results
            ->paginate(
                self::MAX_RESULTS_PER_PAGE,
                [
                    'mal_id', 'url', 'title', 'image_url', 'synopsis', 'type', 'airing_start', 'episodes', 'members', 'genres', 'source', 'producers', 'score', 'licensors', 'rating'
                ],
                null,
                $page
            );

        $items = $this->applyBackwardsCompatibility($results, 'anime');

        return response()->json($items);

//        $producer = $this->jikan->getProducer(new ProducerRequest($id, $page));
//        return response($this->serializer->serialize($producer, 'json'));
    }

    private function applyBackwardsCompatibility($data, $type)
    {
        $fingerprint = HttpHelper::resolveRequestFingerprint($this->request);

        $meta = [
            'request_hash' => $fingerprint,
            'request_cached' => true,
            'request_cache_expiry' => 0,
            'last_page' => $data->lastPage(),
            'meta' => [
                'mal_id' => 0,
                'type' => $type,
                'name' => '',
                'url' => ''
            ],
            'item_count' => $data->total()
        ];

        $items = $data->items() ?? [];
        foreach ($items as &$item) {

            if (isset($item['aired']['from'])) {
                $item['airing_start'] = $item['aired']['from'];
            }

            if (isset($item['published']['from'])) {
                $item['publishing_start'] = $item['aired']['from'];
            }

            if (isset($item['serializations'])) {
                $serializations = [];
                foreach ($item['serializations'] as $serialization) {
                    $serializations[] = $serialization['name'];
                }

                $item['serialization'] = $serializations;
            }

            if (isset($item['serializations'])) {
                $serializations = [];
                foreach ($item['serializations'] as $serialization) {
                    $serializations[] = $serialization['name'];
                }

                $item['serialization'] = $serializations;
            }

            if (isset($item['licensors'])) {
                $licensors = [];
                foreach ($item['licensors'] as $licensor) {
                    $licensors[] = $licensor['name'];
                }

                $item['licensors'] = $licensors;
            }

            if ($type === 'anime') {

                $item['kids'] = false;
                if (isset($item['rating'])) {
                    if ($item['rating'] === 'G - All Ages' || $item['rating'] === 'PG - Children') {
                        $item['kids'] = true;
                    }
                }

                $item['r18'] = false;
                if (isset($item['rating'])) {
                    if ($item['rating'] === 'R+ - Mild Nudity' || $item['rating'] === 'Rx - Hentai') {
                        $item['r18'] = true;
                    }
                }
            }

            unset($item['_id'], $item['oid'], $item['expiresAt'], $item['aired'], $item['published'], $item['serializations']);
        }

        $items = [$type => $items];

        return $meta+$items;
    }
}
