<?php

namespace App\Http\Requests\Review;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
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
            "rating" => ["sometimes", "integer", "min:1", "max:10"],
            "comment" => ["nullable", "string", "max:65535"],
            "has_spoiler" => ["sometimes", "boolean"],
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
            "rating.min" => "The rating must be between 1 and 10.",
            "rating.max" => "The rating must be between 1 and 10.",
        ];
    }
}
