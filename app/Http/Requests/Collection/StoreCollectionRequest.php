<?php

namespace App\Http\Requests\Collection;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
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
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string", "max:65535"],
            "cover_image" => ["nullable", "string", "max:2048"],
            "is_public" => ["sometimes", "boolean"],
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
            "name.required" => "The collection name is required.",
        ];
    }
}
