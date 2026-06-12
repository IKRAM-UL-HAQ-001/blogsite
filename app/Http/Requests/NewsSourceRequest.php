<?php

namespace App\Http\Requests;

use App\Models\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $newsSource = $this->route('news_source');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(NewsSource::TYPES))],
            'url' => [
                'required',
                'url',
                'max:2048',
                Rule::unique('news_sources', 'url')->ignore($newsSource?->id),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'is_active' => 'status',
        ];
    }
}
