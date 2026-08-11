<?php

namespace App\Http\Requests;

use App\Services\PeriodResolver;
use App\Support\AppTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;

/**
 * Validasi Perbandingan Periode (Dashboard 8.8) untuk dua rentang independen.
 * Aturan tanggal mengikuti {@see PeriodFilterRequest}.
 */
class PeriodComparisonRequest extends BaseFormRequest
{
    /** @var list<string> */
    private const PRESETS = ['hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini', 'bulan_lalu', 'tahun_lalu', 'rentang'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $today = AppTimezone::todayDateString();
        $presets = implode(',', self::PRESETS);
        $minDate = AppTimezone::TANGGAL_MULAI_USAHA;

        return [
            'a' => ['required', 'string', 'in:'.$presets],
            'b' => ['required', 'string', 'in:'.$presets],
            'a_start' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$today],
            'a_end' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$today],
            'b_start' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$today],
            'b_end' => ['nullable', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$today],
        ];
    }

    public function messages(): array
    {
        $minDate = AppTimezone::TANGGAL_MULAI_USAHA;

        return [
            'a.in' => 'Periode pembanding A tidak valid.',
            'b.in' => 'Periode pembanding B tidak valid.',
            'a_start.after_or_equal' => 'Tanggal awal periode A tidak boleh sebelum '.$minDate.'.',
            'a_start.before_or_equal' => 'Tanggal awal periode A tidak boleh melebihi hari ini.',
            'a_end.after_or_equal' => 'Tanggal akhir periode A tidak boleh sebelum '.$minDate.'.',
            'a_end.before_or_equal' => 'Tanggal akhir periode A tidak boleh melebihi hari ini.',
            'b_start.after_or_equal' => 'Tanggal awal periode B tidak boleh sebelum '.$minDate.'.',
            'b_start.before_or_equal' => 'Tanggal awal periode B tidak boleh melebihi hari ini.',
            'b_end.after_or_equal' => 'Tanggal akhir periode B tidak boleh sebelum '.$minDate.'.',
            'b_end.before_or_equal' => 'Tanggal akhir periode B tidak boleh melebihi hari ini.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateSide($validator, 'a');
            $this->validateSide($validator, 'b');
        });
    }

    private function validateSide(Validator $validator, string $side): void
    {
        $start = $this->input($side.'_start');
        $end = $this->input($side.'_end');

        if (! $start || ! $end) {
            return;
        }

        $label = strtoupper($side);
        $tz = AppTimezone::name();
        $startDate = CarbonImmutable::parse($start, $tz)->startOfDay();
        $endDate = CarbonImmutable::parse($end, $tz)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            $validator->errors()->add($side.'_start', "Tanggal awal periode {$label} tidak boleh melebihi tanggal akhir.");

            return;
        }

        $days = $startDate->diffInDays($endDate) + 1;
        if ($days > PeriodResolver::MAX_RANGE_DAYS) {
            $validator->errors()->add(
                $side.'_end',
                "Rentang periode {$label} maksimal ".PeriodResolver::MAX_RANGE_DAYS.' hari; rentang dipilih '.$days.' hari.',
            );
        }
    }
}
