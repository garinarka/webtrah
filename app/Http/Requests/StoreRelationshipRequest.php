<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isModerator();
    }

    public function rules(): array
    {
        return [
            'person_id'    => ['required', 'uuid', 'exists:people,id'],
            'related_id'   => [
                'required',
                'uuid',
                'exists:people,id',
                // tidak boleh sama dengan person_id
                Rule::notIn([$this->person_id]),
            ],
            'type'         => ['required', Rule::in([
                'parent',
                'child',
                'spouse',
                'step_parent',
                'step_child',
                'adopted_parent',
                'adopted_child',
            ])],
            'is_biological' => ['boolean'],
            'started_at'   => ['nullable', 'date'],
            'ended_at'     => ['nullable', 'date', 'after_or_equal:started_at'],
            'ended_reason' => [
                Rule::requiredIf(fn() => !empty($this->ended_at)),
                'nullable',
                Rule::in(['divorce', 'death', 'annulment', 'separation', 'disownment']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.required'   => 'ID orang wajib diisi.',
            'related_id.required'  => 'Pilih orang yang terkait.',
            'related_id.not_in'    => 'Seseorang tidak bisa berelasi dengan dirinya sendiri.',
            'type.required'        => 'Jenis relasi wajib dipilih.',
            'type.in'              => 'Jenis relasi tidak valid.',
            'ended_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }
}
