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
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'max:30'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'calories' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],

            'exercises' => ['array'],
            'exercises.*.exercise_id' => ['nullable', 'integer', 'exists:exercises,id'],
            'exercises.*.source' => ['nullable', 'string', 'max:50'],
            'exercises.*.external_id' => ['nullable', 'string', 'max:80'],
            'exercises.*.name' => ['required_with:exercises', 'string', 'max:120'],
            'exercises.*.order_no' => ['nullable', 'integer', 'min:1'],

            'exercises.*.sets' => ['array'],
            'exercises.*.sets.*.set_no' => ['nullable', 'integer', 'min:1'],
            'exercises.*.sets.*.reps' => ['nullable', 'integer', 'min:1'],
            'exercises.*.sets.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'exercises.*.sets.*.rir' => ['nullable', 'integer', 'min:0', 'max:10'],
            'exercises.*.sets.*.rest_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'date',
            'type' => 'type',
            'duration_seconds' => 'duration',
            'distance_km' => 'distance',
            'calories' => 'calories',
            'notes' => 'notes',

            'exercises' => 'exercises',
            'exercises.*.exercise_id' => 'exercise',
            'exercises.*.source' => 'exercise source',
            'exercises.*.external_id' => 'external exercise id',
            'exercises.*.name' => 'exercise name',
            'exercises.*.order_no' => 'exercise order',

            'exercises.*.sets' => 'sets',
            'exercises.*.sets.*.set_no' => 'set number',
            'exercises.*.sets.*.reps' => 'reps',
            'exercises.*.sets.*.weight_kg' => 'weight',
            'exercises.*.sets.*.rir' => 'RIR',
            'exercises.*.sets.*.rest_seconds' => 'rest',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Date is required.',
            'date.date' => 'Date must be a valid date.',

            'type.required' => 'Workout type is required.',
            'type.string' => 'Workout type must be text.',
            'type.max' => 'Workout type is too long.',

            'duration_seconds.integer' => 'Duration must be a whole number of seconds.',
            'duration_seconds.min' => 'Duration cannot be negative.',

            'distance_km.numeric' => 'Distance must be a number.',
            'distance_km.min' => 'Distance cannot be negative.',

            'calories.integer' => 'Calories must be a whole number.',
            'calories.min' => 'Calories cannot be negative.',

            'notes.string' => 'Notes must be text.',

            'exercises.array' => 'Exercises must be a list.',

            'exercises.*.exercise_id.integer' => 'Selected exercise is invalid.',
            'exercises.*.exercise_id.exists' => 'Selected exercise does not exist.',

            'exercises.*.source.string' => 'Exercise source is invalid.',
            'exercises.*.source.max' => 'Exercise source is too long.',

            'exercises.*.external_id.string' => 'External exercise id is invalid.',
            'exercises.*.external_id.max' => 'External exercise id is too long.',

            'exercises.*.name.required_with' => 'Exercise name is required.',
            'exercises.*.name.string' => 'Exercise name must be text.',
            'exercises.*.name.max' => 'Exercise name is too long.',

            'exercises.*.order_no.integer' => 'Exercise order must be a number.',
            'exercises.*.order_no.min' => 'Exercise order must be at least 1.',

            'exercises.*.sets.array' => 'Sets must be a list.',

            'exercises.*.sets.*.set_no.integer' => 'Set number must be a number.',
            'exercises.*.sets.*.set_no.min' => 'Set number must be at least 1.',

            'exercises.*.sets.*.reps.integer' => 'Reps must be a number.',
            'exercises.*.sets.*.reps.min' => 'Reps must be at least 1.',

            'exercises.*.sets.*.weight_kg.numeric' => 'Weight must be a number.',
            'exercises.*.sets.*.weight_kg.min' => 'Weight cannot be negative.',

            'exercises.*.sets.*.rir.integer' => 'RIR must be a number.',
            'exercises.*.sets.*.rir.min' => 'RIR must be at least 0.',
            'exercises.*.sets.*.rir.max' => 'RIR cannot be greater than 10.',

            'exercises.*.sets.*.rest_seconds.integer' => 'Rest must be a number.',
            'exercises.*.sets.*.rest_seconds.min' => 'Rest cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validatorInstance) {
            $payload = $this->all();

            if (($payload['type'] ?? null) !== 'Strength') {
                return;
            }

            if (empty($payload['exercises'])) {
                $validatorInstance->errors()->add('exercises', 'Add at least one exercise.');
                return;
            }

            foreach (($payload['exercises'] ?? []) as $exerciseIndex => $exercisePayload) {
                if (empty($exercisePayload['sets'])) {
                    $validatorInstance->errors()->add("exercises.$exerciseIndex.sets", 'Each exercise must have at least one set.');
                    continue;
                }

                foreach (($exercisePayload['sets'] ?? []) as $setIndex => $setPayload) {
                    if (!isset($setPayload['reps']) || (int) $setPayload['reps'] < 1) {
                        $validatorInstance->errors()->add("exercises.$exerciseIndex.sets.$setIndex.reps", 'Reps must be at least 1.');
                    }
                }
            }
        });
    }
}
