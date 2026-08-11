<?php

namespace App\Http\Requests;

use App\Support\AppTimezone;

class CapitalInjectionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal setoran wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal setoran tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.' (usaha mulai beroperasi 2018).',
            'tanggal.before_or_equal' => 'Tanggal setoran tidak boleh melebihi hari ini.',
            'nominal.required' => 'Nominal setoran wajib diisi.',
            'nominal.min' => 'Nominal setoran harus lebih dari 0.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'tanggal' => $this->input('tanggal'),
            'nominal' => (float) $this->input('nominal'),
            'keterangan' => $this->input('keterangan'),
        ];
    }
}
