<?php

namespace App\Http\Requests;

use App\Enums\RepositoryAuthType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepositoryRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('repositories')->ignore($this->route('repository')), 'regex:/^[a-z0-9-]+$/'],
            'auth_type' => ['required', Rule::enum(RepositoryAuthType::class)],
            'auth_username' => ['nullable', 'required_if:auth_type,basic', 'string', 'max:255'],
            'auth_password' => ['nullable', 'required_if:auth_type,basic', 'string', 'max:255'],
            'auth_token' => ['nullable', 'required_if:auth_type,token', 'string', 'max:255'],
        ];
    }
}
