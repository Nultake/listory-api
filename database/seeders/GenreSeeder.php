<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    /**
     * The default genres covering games, films, and series.
     *
     * @var list<string>
     */
    private const array GENRES = [
        "Action",
        "Adventure",
        "Animation",
        "Comedy",
        "Crime",
        "Documentary",
        "Drama",
        "Fantasy",
        "Horror",
        "Mystery",
        "Platformer",
        "Puzzle",
        "Racing",
        "Romance",
        "RPG",
        "Sci-Fi",
        "Shooter",
        "Simulation",
        "Sports",
        "Strategy",
        "Thriller",
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::GENRES as $name) {
            Genre::query()->firstOrCreate(
                ["slug" => Str::slug($name)],
                ["name" => $name],
            );
        }
    }
}
