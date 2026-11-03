
<?php

namespace App\Services;

use RuntimeException;

class InaprocRekapNasionalTextParser
{
    public function parse(string $text): array
    {
        $normalized = $this->normalize($text);

        $penyedia = $this->parseSection($normalized, 'Penyedia');
        $swakelola = $this->parseSection($normalized, 'Swakelola');

        $totalRealisasi = $penyedia['realisasi'] + $swakelola['realisasi'];
        $totalPerencanaan = $penyedia['perencanaan'] + $swakelola['perencanaan'];

        return [
            'penyedia_realisasi' => $penyedia['realisasi'],
            'penyedia_perencanaan' => $penyedia['perencanaan'],
            'penyedia_persentase' => $this->percentage($penyedia['realisasi'], $penyedia['perencanaan']),

            'swakelola_realisasi' => $swakelola['realisasi'],
            'swakelola_perencanaan' => $swakelola['perencanaan'],
            'swakelola_persentase' => $this->percentage($swakelola['realisasi'], $swakelola['perencanaan']),

            'total_realisasi' => $totalRealisasi,
            'total_perencanaan' => $totalPerencanaan,
            'total_persentase' => $this->percentage($totalRealisasi, $totalPerencanaan),

            'raw_text_hash' => hash('sha256', $normalized),
        ];
    }

    private function parseSection(string $text, string $type): array
    {
        $pattern = '/Total Nilai Perencanaan ' . preg_quote($type, '/') .
            ':\s*Rp\s*([\d.]+).*?Total Nilai Realisasi ' . preg_quote($type, '/') .
            ':\s*Rp\s*([\d.]+)/isu';

        if (!preg_match($pattern, $text, $matches)) {
            throw new RuntimeException(
                "Tidak menemukan nilai perencanaan/realisasi {$type} pada hasil render Inaproc."
            );
        }

        return [
            'perencanaan' => $this->rupiahToNumber($matches[1]),
            'realisasi' => $this->rupiahToNumber($matches[2]),
        ];
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/[ \t]+/u', ' ', $text));
    }

    private function rupiahToNumber(string $value): float
    {
        return (float) str_replace('.', '', $value);
    }

    private function percentage(float $realisasi, float $perencanaan): float
    {
        if ($perencanaan <= 0) {
            return 0.0;
        }

        return round(($realisasi / $perencanaan) * 100, 2);
    }
}
