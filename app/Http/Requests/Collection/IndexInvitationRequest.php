<?php

namespace App\Http\Requests\Collection;

use App\Enums\InvitationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInvitationRequest extends FormRequest
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
            "filter.status" => ["sometimes", Rule::enum(InvitationStatus::class)],
            "per_page" => ["sometimes", "integer", "min:1", "max:100"],
        ];
    }
}
