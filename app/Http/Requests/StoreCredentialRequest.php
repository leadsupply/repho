<?php

namespace App\Http\Requests;

use App\Enums\PackageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PackageType::class)],
            'token' => ['required', 'string'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
