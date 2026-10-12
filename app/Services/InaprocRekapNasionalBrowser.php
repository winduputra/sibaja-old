<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class InaprocRekapNasionalBrowser
{
    public function scrape(string $url, int $timeoutSeconds = 90): string
    {
        $waitText = config('inaproc_rekap_nasional.wait_until_text', 'Realisasi Swakelola');

        $payload = $this->run([
            '--url=' . $url,
            '--timeout=' . ($timeoutSeconds * 1000),
            '--wait-text=' . $waitText,
        ], $timeoutSeconds);

        if (!is_array($payload) || empty($payload['innerText'])) {
            throw new RuntimeException('Runner Playwright tidak mengembalikan innerText yang valid.');
        }

        return $payload['innerText'];
    }

    public function extractFirst(string $url, string $selector, int $timeoutSeconds = 90): string
    {
        $payload = $this->run([
            '--url=' . $url,
            '--timeout=' . ($timeoutSeconds * 1000),
            '--selector=' . $selector,
        ], $timeoutSeconds);

        if (!is_array($payload) || !array_key_exists('textContent', $payload) || $payload['textContent'] === null) {
            throw new RuntimeException('Runner Playwright tidak mengembalikan textContent yang valid.');
        }

        return (string) $payload['textContent'];
    }

    private function run(array $arguments, int $timeoutSeconds): array
    {
        $nodeBinary = config('inaproc_rekap_nasional.node_binary', 'node');
        $runnerPath = base_path(config('inaproc_rekap_nasional.runner_path', 'scripts/scrape-inaproc-rekap-nasional.js'));
        $process = new Process(array_merge([$nodeBinary, $runnerPath], $arguments));
        $process->setTimeout($timeoutSeconds + 30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $payload = json_decode($process->getOutput(), true);

        if (!is_array($payload)) {
            throw new RuntimeException('Runner Playwright tidak mengembalikan JSON yang valid.');
        }

        return $payload;
    }
}
