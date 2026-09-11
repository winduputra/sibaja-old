<?php

namespace App\Support;

use Illuminate\Console\Command;
use RuntimeException;

final class LegacyIsbImportDisabled
{
    public const MESSAGE = 'Sumber ISB lama sudah dinonaktifkan; tidak ada data yang diubah.';

    public static function forCommand(Command $command): int
    {
        $command->error(self::MESSAGE);

        return Command::FAILURE;
    }

    public static function forService(): void
    {
        throw new RuntimeException(self::MESSAGE);
    }
}
