<?php

namespace App\Http\Requests\Review;

use App\Enums\MediaType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReviewRequest extends FormRequest
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
            "filter" => ["sometimes", "array"],
            "filter.media_item_id" => ["sometimes", "uuid"],
            "filter.type" => ["sometimes", Rule::enum(MediaType::class)],
            "sort" => [
                "sometimes",
                "string",
                Rule::in([
                    "created_at", "-created_at",
                    "updated_at", "-updated_at",
                    "rating", "-rating",
                ]),
            ],
            "include" => ["sometimes", "string"],
            "per_page" => ["sometimes", "integer", "min:1", "max:100"],
        ];
    }
}
