<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddItemRequest extends FormRequest
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
        /** @var Collection $collection */
        $collection = $this->route("collection");

        return [
            "media_item_id" => [
                "required",
                "uuid",
                Rule::exists("media_items", "id")->whereNull("deleted_at"),
                Rule::unique("collection_media_item", "media_item_id")
                    ->where("collection_id", $collection->id),
            ],
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
            "media_item_id.exists" => "The selected media item does not exist.",
            "media_item_id.unique" => "This media item is already in the collection.",
        ];
    }
}
