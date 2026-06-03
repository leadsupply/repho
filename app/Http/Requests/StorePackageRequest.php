<?php

namespace App\Http\Requests;

use App\Enums\PackageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'download_dists' => $this->boolean('download_dists'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'repository_url' => ['required', 'url', 'max:500', 'regex:#^https?://.+/.+#'],
            'type' => ['required', Rule::enum(PackageType::class)],
            'credential_id' => ['nullable', 'exists:credentials,id'],
            'download_dists' => ['boolean'],
            'repository_ids' => ['required', 'array', 'min:1'],
            'repository_ids.*' => ['exists:repositories,id'],
        ];
    }
}
