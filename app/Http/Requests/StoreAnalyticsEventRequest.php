<?php

namespace App\Http\Requests;

use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyticsEventRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::in(AnalyticsEvent::TRACKABLE_NAMES)],
            'target' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9:_-]+$/i'],
            'value' => ['nullable', 'integer', 'min:0', 'max:1800000'],
        ];
    }
}
