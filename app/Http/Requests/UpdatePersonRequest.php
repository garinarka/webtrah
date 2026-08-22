<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $person = $this->route('person');

        return $this->user()->can('update', $person);
    }

    public function rules(): array
    {
        $isAdmin = $this->user()->isAdmin();

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
            'family_unit_id' => ['nullable', 'exists:family_units,id'],
            'is_draft' => ['boolean'],
            // Admin langsung save → edit_reason tidak wajib
            // Moderator & user → wajib karena masuk approval queue, KECUALI simpan draft
            'edit_reason' => $isAdmin || $this->boolean('is_draft')
                ? ['nullable', 'string', 'max:500']
                : ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'display_name.min' => 'Nama minimal 2 karakter.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'death_date.before_or_equal' => 'Tanggal meninggal tidak boleh di masa depan.',
            'family_unit_id.exists' => 'Unit keluarga tidak ditemukan.',
            'edit_reason.required' => 'Alasan perubahan wajib diisi.',
            'edit_reason.min' => 'Alasan perubahan minimal 5 karakter.',
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
