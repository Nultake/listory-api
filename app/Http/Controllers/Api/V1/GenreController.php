<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GenreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $genres = Genre::query()
            ->orderBy("name")
            ->paginate(50);

        return GenreResource::collection($genres);
    }
}
