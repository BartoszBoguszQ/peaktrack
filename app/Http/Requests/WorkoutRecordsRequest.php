<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discipline' => ['nullable', 'string', 'in:Run,Ride,Swim'],
            'include_endurance' => ['nullable', 'boolean'],
            'include_strength' => ['nullable', 'boolean'],
        ];
    }

    public function discipline(): ?string
    {
        $value = $this->input('discipline');

        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    public function includeEndurance(): bool
    {
        return (bool) $this->boolean('include_endurance', true);
    }

    public function includeStrength(): bool
    {
        return (bool) $this->boolean('include_strength', true);
    }
}
