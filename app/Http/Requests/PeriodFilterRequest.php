<?php

namespace App\Http\Requests;

use App\Services\PeriodResolver;
use App\Support\AppTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;

/**
 * Validasi filter periode yang dipakai Dashboard dan Laporan Keuangan.
 *
 * Aturan:
 * - Tanggal tidak boleh melebihi hari ini (tidak ada data masa depan).
 * - Tanggal awal tidak boleh lebih besar daripada tanggal akhir.
 * - Panjang rentang dibatasi {@see PeriodResolver::MAX_RANGE_DAYS} hari.
 */
class PeriodFilterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $today = AppTimezone::todayDateString();

        return [
            'period' => ['nullable', 'string', 'in:'.implode(',', array_keys(PeriodResolver::OPTIONS))],
            'start' => ['nullable', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.$today],
            'end' => ['nullable', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.$today],
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'Periode yang dipilih tidak valid.',
            'start.date' => 'Tanggal awal tidak valid.',
            'start.after_or_equal' => 'Tanggal awal tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.'.',
            'start.before_or_equal' => 'Tanggal awal tidak boleh melebihi hari ini.',
            'end.date' => 'Tanggal akhir tidak valid.',
            'end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.'.',
            'end.before_or_equal' => 'Tanggal akhir tidak boleh melebihi hari ini.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = $this->input('start');
            $end = $this->input('end');

            if (! $start || ! $end) {
                return;
            }

            $tz = AppTimezone::name();
            $startDate = CarbonImmutable::parse($start, $tz)->startOfDay();
            $endDate = CarbonImmutable::parse($end, $tz)->startOfDay();

            if ($startDate->greaterThan($endDate)) {
                $validator->errors()->add('start', 'Tanggal awal tidak boleh melebihi tanggal akhir.');

                return;
            }

            $days = $startDate->diffInDays($endDate) + 1;
            if ($days > PeriodResolver::MAX_RANGE_DAYS) {
                $validator->errors()->add(
                    'end',
                    'Rentang periode maksimal '.PeriodResolver::MAX_RANGE_DAYS.' hari; rentang dipilih '.$days.' hari.',
                );
            }
        });
    }

    public function periodKey(): string
    {
        return (string) ($this->input('period') ?: 'bulan_ini');
    }

    public function startDate(): ?string
    {
        $value = $this->input('start');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function endDate(): ?string
    {
        $value = $this->input('end');

        return $value !== null && $value !== '' ? (string) $value : null;
    }
}
