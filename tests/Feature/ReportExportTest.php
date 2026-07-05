<?php

use App\Services\Mock\MockData;

describe('report exports', function () {
    foreach ([
        ['period' => 'bulan_ini', 'start' => null, 'end' => null],
        ['period' => 'tahun_ini', 'start' => null, 'end' => null],
        ['period' => 'rentang', 'start' => '2026-01-01', 'end' => '2026-06-30'],
    ] as $case) {
        $label = $case['period'].($case['start'] ? "-{$case['start']}" : '');

        test("pdf export downloads as application/pdf for {$label}", function () use ($case) {
            $admin = MockData::profiles()[0];
            $query = array_filter([
                'period' => $case['period'],
                'start' => $case['start'],
                'end' => $case['end'],
            ]);

            $this->withUnencryptedCookie('ld_profile', json_encode($admin))
                ->get('/reports/export/pdf?'.http_build_query($query))
                ->assertSuccessful()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeaderMissing('Content-Length: 0');
        });

        test("pdf filename reflects the period for {$label}", function () use ($case) {
            $admin = MockData::profiles()[0];
            $query = array_filter([
                'period' => $case['period'],
                'start' => $case['start'],
                'end' => $case['end'],
            ]);

            $response = $this->withUnencryptedCookie('ld_profile', json_encode($admin))
                ->get('/reports/export/pdf?'.http_build_query($query));

            $disposition = (string) $response->headers->get('Content-Disposition');
            expect($disposition)->toContain('attachment')
                ->and($disposition)->toContain('laporan-keuangan-')
                ->and($disposition)->toContain('.pdf');
        });

        test("excel export downloads for {$label}", function () use ($case) {
            $admin = MockData::profiles()[0];
            $query = array_filter([
                'period' => $case['period'],
                'start' => $case['start'],
                'end' => $case['end'],
            ]);

            $this->withUnencryptedCookie('ld_profile', json_encode($admin))
                ->get('/reports/export/excel?'.http_build_query($query))
                ->assertSuccessful()
                ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->assertSee('<Workbook', false)
                ->assertSee('Pemasukan per Produk', false)
                ->assertSee('Pengeluaran per Kategori', false);
        });

        test("excel filename reflects the period for {$label}", function () use ($case) {
            $admin = MockData::profiles()[0];
            $query = array_filter([
                'period' => $case['period'],
                'start' => $case['start'],
                'end' => $case['end'],
            ]);

            $response = $this->withUnencryptedCookie('ld_profile', json_encode($admin))
                ->get('/reports/export/excel?'.http_build_query($query));

            expect($response->headers->get('Content-Disposition'))
                ->toStartWith('attachment; filename="laporan-keuangan-')
                ->toEndWith('.xls"');
        });
    }

    it('render passes through to the selected period range label', function () {
        $report = MockData::reportSummary('tahun_ini');
        $admin = MockData::profiles()[0];

        $response = $this->withUnencryptedCookie('ld_profile', json_encode($admin))
            ->get('/reports/export/pdf?period=tahun_ini');

        $response->assertSuccessful()->assertHeader('Content-Type', 'application/pdf');
        expect(MockData::reportSummary('tahun_ini')['rangeLabel'])->toBe($report['rangeLabel']);
    });
});
