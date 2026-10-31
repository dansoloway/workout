<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use App\Enums\TennisPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'partner' => ['required_if:type,'.ActivityType::Tennis->value, 'nullable', Rule::enum(TennisPartner::class)],
            'duration_minutes' => [Rule::requiredIf(fn () => $this->activityType()?->tracksMinutes() ?? false), 'nullable', 'integer', 'min:1', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'partner.required_if' => 'Choose who you played with.',
            'duration_minutes.required' => $this->activityType()?->minutesPrompt() ?? 'Enter the minutes.',
        ];
    }

    private function activityType(): ?ActivityType
    {
        return ActivityType::tryFrom((string) $this->input('type'));
    }
}
