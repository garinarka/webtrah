<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isModerator();
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:2', 'max:255'],
            'gender' => ['required', 'in:male,female,unknown'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_accuracy' => ['required', 'in:exact,year_month,year,unknown'],
            'death_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    if ($value && $this->birth_date && $value < $this->birth_date) {
                        $fail('Tanggal meninggal harus setelah tanggal lahir.');
                    }
                },
            ],
            'death_accuracy' => ['required', 'in:exact,year_month,year,unknown'],
            'family_unit_id' => ['required', 'exists:family_units,id'],
            'is_draft' => ['boolean'],
            // Relasi awal dari StepFamily — hanya admin yang akan memprosesnya
            'initial_relations' => ['nullable', 'array'],
            'initial_relations.*.related_id' => ['required', 'exists:people,id'],
            'initial_relations.*.type' => ['required', 'in:parent,step_parent,adopted_parent,child,step_child,adopted_child,spouse'],
            'initial_relations.*.is_biological' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'display_name.min' => 'Nama minimal 2 karakter.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'death_date.before_or_equal' => 'Tanggal meninggal tidak boleh di masa depan.',
            'family_unit_id.required' => 'Unit keluarga wajib dipilih.',
            'family_unit_id.exists' => 'Unit keluarga tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'birth_date' => $this->normalizeDateInput($this->birth_date, $this->birth_accuracy),
            'death_date' => $this->normalizeDateInput($this->death_date, $this->death_accuracy),
            'is_draft' => $this->boolean('is_draft', false),
        ]);
    }

    private function normalizeDateInput(?string $value, ?string $accuracy): ?string
    {
        if (! $value) {
            return null;
        }
        if ($accuracy === 'year' && preg_match('/^\d{4}$/', $value)) {
            return $value.'-01-01';
        }
        if ($accuracy === 'year_month' && preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value.'-01';
        }

        return $value;
    }
}
