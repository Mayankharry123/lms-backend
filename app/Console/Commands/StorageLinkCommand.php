<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    protected $signature = 'storage:link';

    protected $description = 'Create the symbolic link from public/storage to storage/app/public';

    public function handle(): int
    {
        $target = storage_path('app/public');
        $link = base_path('public/storage');

        if (!is_dir($target)) {
            if (!mkdir($target, 0755, true) && !is_dir($target)) {
                $this->error("Unable to create directory [{$target}].");

                return self::FAILURE;
            }
        }

        if (is_link($link)) {
            $this->info('The [public/storage] link already exists.');

            return self::SUCCESS;
        }

        if (file_exists($link)) {
            $this->error('The [public/storage] path already exists and is not a symbolic link.');

            return self::FAILURE;
        }

        $relativeTarget = $this->relativePath(base_path('public'), $target);

        if (!@symlink($relativeTarget, $link) && !@symlink($target, $link)) {
            $this->error('The symbolic link could not be created.');
            $this->line("Run manually from project root:");
            $this->line("  ln -s ../storage/app/public public/storage");

            return self::FAILURE;
        }

        $this->info('The [public/storage] link has been connected to [storage/app/public].');

        return self::SUCCESS;
    }

    protected function relativePath(string $from, string $to): string
    {
        $from = rtrim(str_replace('\\', '/', realpath($from) ?: $from), '/');
        $to = rtrim(str_replace('\\', '/', realpath($to) ?: $to), '/');

        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        while (count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        return str_repeat('../', count($fromParts)) . implode('/', $toParts);
    }
}
