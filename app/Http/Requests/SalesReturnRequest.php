<?php

namespace App\Http\Requests;

use App\Models\Income;
use App\Models\SalesReturn;
use Illuminate\Validation\Rule;

class SalesReturnRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $incomeId = $this->input('id_penjualan');

        return [
            'id_penjualan' => ['required', 'integer', Rule::exists('incomes', 'id')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_penjualan.required' => 'Penjualan asal wajib dipilih.',
            'id_penjualan.exists' => 'Penjualan asal tidak ditemukan.',
            'tanggal.required' => 'Tanggal retur wajib diisi.',
            'tanggal.before_or_equal' => 'Tanggal retur tidak boleh melebihi hari ini.',
            'jumlah.required' => 'Jumlah retur wajib diisi.',
            'jumlah.min' => 'Jumlah retur minimal 1.',
        ];
    }

    /**
     * Validasi batas jumlah yang sudah diretur untuk penjualan ini.
     * Dipanggil controller di luar rules() karena butuh query.
     */
    public function ensureJumlahWithinLimit(): Income
    {
        $income = Income::findOrFail($this->input('id_penjualan'));
        $alreadyReturned = (int) SalesReturn::where('income_id', $income->id)->sum('jumlah');
        $max = max(0, (int) $income->jumlah - $alreadyReturned);
        $requested = (int) $this->input('jumlah');

        if ($requested > $max) {
            abort(response()->json([
                'message' => "Jumlah retur melebihi sisa penjualan (sisa: {$max}).",
                'errors' => ['jumlah' => ["Jumlah retur melebihi sisa penjualan (sisa: {$max})."]],
            ], 422));
        }

        return $income;
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'income_id' => (int) $this->input('id_penjualan'),
            'tanggal' => $this->input('tanggal'),
            'jumlah' => (int) $this->input('jumlah'),
            'alasan' => $this->input('alasan'),
        ];
    }
}
