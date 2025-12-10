<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'max:50'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }

    public function selectedTypes(): ?array
    {
        $types = $this->input('types');

        if (!is_array($types)) {
            return null;
        }

        $filtered = [];

        foreach ($types as $value) {
            if (is_string($value) && $value !== '') {
                $filtered[] = $value;
            }
        }

        return $filtered === [] ? null : array_values($filtered);
    }

    public function dateRange(): array
    {
        return [
            'from' => $this->input('from_date'),
            'to' => $this->input('to_date'),
        ];
    }
}
