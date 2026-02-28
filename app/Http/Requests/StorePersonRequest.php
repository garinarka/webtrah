<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_person');
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
            'family_unit_id' => ['required', 'exists:family_units,id'],
            'is_draft'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'display_name.min'      => 'Nama minimal 2 karakter.',
            'display_name.max'      => 'Nama maksimal 255 karakter.',
            'gender.required'       => 'Jenis kelamin wajib dipilih.',
            'gender.in'             => 'Jenis kelamin tidak valid.',
            'birth_date.date'       => 'Format tanggal lahir tidak valid.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'birth_accuracy.required'    => 'Akurasi tanggal lahir wajib dipilih.',
            'death_date.date'        => 'Format tanggal meninggal tidak valid.',
            'death_date.before_or_equal' => 'Tanggal meninggal tidak boleh di masa depan.',
            'family_unit_id.required' => 'Unit keluarga wajib dipilih.',
            'family_unit_id.exists'   => 'Unit keluarga tidak ditemukan.',
        ];
    }

    /**
     * Prepare the data for validation — normalize empty strings to null.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'birth_date'  => $this->birth_date ?: null,
            'death_date'  => $this->death_date ?: null,
            'is_draft'    => $this->boolean('is_draft', false),
        ]);
    }
}
