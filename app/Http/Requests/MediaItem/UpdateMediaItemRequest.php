<?php

namespace App\Http\Requests\MediaItem;

use App\Enums\MediaType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => ["sometimes", "string", "max:255"],
            "type" => ["sometimes", Rule::enum(MediaType::class)],
            "description" => ["nullable", "string", "max:65535"],
            "cover_image" => ["nullable", "string", "max:2048"],
            "release_date" => ["nullable", "date"],
            "genre_ids" => ["sometimes", "array"],
            "genre_ids.*" => ["uuid", "exists:genres,id"],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            "type.Illuminate\\Validation\\Rules\\Enum" => "The type must be one of: game, film, series.",
            "genre_ids.*.exists" => "One or more of the selected genres do not exist.",
        ];
    }
}
