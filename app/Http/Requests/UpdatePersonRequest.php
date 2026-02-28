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
        return [
            'display_name'   => ['required', 'string', 'min:2', 'max:255'],
            'gender'         => ['required', 'in:male,female,unknown'],
            'birth_date'     => ['nullable', 'date', 'before_or_equal:today'],
            'birth_accuracy' => ['required', 'in:exact,year_month,year,unknown'],
            'death_date'     => [
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
            'edit_reason'    => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'display_name.min'      => 'Nama minimal 2 karakter.',
            'gender.required'       => 'Jenis kelamin wajib dipilih.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'death_date.before_or_equal' => 'Tanggal meninggal tidak boleh di masa depan.',
            'edit_reason.required'  => 'Alasan perubahan wajib diisi.',
            'edit_reason.min'       => 'Alasan perubahan minimal 5 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'birth_date' => $this->birth_date ?: null,
            'death_date' => $this->death_date ?: null,
        ]);
    }
}
