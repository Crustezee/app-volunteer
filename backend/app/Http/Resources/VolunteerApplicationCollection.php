<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VolunteerApplicationCollection extends ResourceCollection
{
    public $collects = VolunteerApplicationResource::class;

    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(
        Request $request,
        array $paginated,
        array $default
    ): array {
        return [
            'links' => [
                'first' => $paginated['first_page_url'],
                'last' => $paginated['last_page_url'],
                'previous' => $paginated['prev_page_url'],
                'next' => $paginated['next_page_url'],
            ],
            'meta' => [
                'currentPage' => $paginated['current_page'],
                'from' => $paginated['from'],
                'lastPage' => $paginated['last_page'],
                'path' => $paginated['path'],
                'perPage' => $paginated['per_page'],
                'to' => $paginated['to'],
                'total' => $paginated['total'],
            ],
        ];
    }
}
