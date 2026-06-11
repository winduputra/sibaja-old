<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class InaprocRekapNasionalBrowser
{
    public function scrape(string $url, int $timeoutSeconds = 90): string
    {
        $nodeBinary = config('inaproc_rekap_nasional.node_binary', 'node');
        $runnerPath = base_path(config('inaproc_rekap_nasional.runner_path', 'scripts/scrape-inaproc-rekap-nasional.js'));
        $waitText = config('inaproc_rekap_nasional.wait_until_text', 'Realisasi Swakelola');

        $process = new Process([
            $nodeBinary,
            $runnerPath,
            '--url=' . $url,
            '--timeout=' . ($timeoutSeconds * 1000),
            '--wait-text=' . $waitText,
        ]);
        $process->setTimeout($timeoutSeconds + 30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $payload = json_decode($process->getOutput(), true);

        if (!is_array($payload) || empty($payload['innerText'])) {
            throw new RuntimeException('Runner Playwright tidak mengembalikan innerText yang valid.');
        }

        return $payload['innerText'];
    }
}
