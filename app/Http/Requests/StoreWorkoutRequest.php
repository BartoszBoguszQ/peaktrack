<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required','date'],
            'type' => ['required','string','max:30'],
            'duration_seconds' => ['nullable','integer','min:0'],
            'distance_km' => ['nullable','numeric','min:0'],
            'calories' => ['nullable','integer','min:0'],
            'notes' => ['nullable','string'],
            'exercises' => ['array'],
            'exercises.*.exercise_id' => ['nullable','integer','exists:exercises,id'],
            'exercises.*.external_source' => ['nullable','string','max:50'],
            'exercises.*.external_id' => ['nullable','string','max:80'],
            'exercises.*.name' => ['required_with:exercises','string','max:120'],
            'exercises.*.order_no' => ['nullable','integer','min:1'],
            'exercises.*.sets' => ['array'],
            'exercises.*.sets.*.set_no' => ['nullable','integer','min:1'],
            'exercises.*.sets.*.reps' => ['nullable','integer','min:1'],
            'exercises.*.sets.*.weight_kg' => ['nullable','numeric','min:0'],
            'exercises.*.sets.*.rir' => ['nullable','integer','min:0','max:10'],
            'exercises.*.sets.*.rest_seconds' => ['nullable','integer','min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validatorInstance) {
            $payload = $this->all();
            if (($payload['type'] ?? null) === 'Strength') {
                if (empty($payload['exercises'])) {
                    $validatorInstance->errors()->add('exercises', 'Add at least one exercise.');
                    return;
                }
                foreach ($payload['exercises'] as $exerciseIndex => $exercisePayload) {
                    if (empty($exercisePayload['sets'])) {
                        $validatorInstance->errors()->add("exercises.$exerciseIndex.sets", 'Each exercise must have at least one set.');
                        continue;
                    }
                    foreach ($exercisePayload['sets'] as $setIndex => $setPayload) {
                        if (!isset($setPayload['reps']) || (int) $setPayload['reps'] < 1) {
                            $validatorInstance->errors()->add("exercises.$exerciseIndex.sets.$setIndex.reps", 'Reps must be at least 1.');
                        }
                    }
                }
            }
        });
    }
}
