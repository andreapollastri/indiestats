<?php

namespace App\Console\Commands;

use App\Updates\UpdateFailed;
use App\Updates\Updater;
use Illuminate\Console\Command;

class AppUpdate extends Command
{
    protected $signature = 'app:update
        {action=status : status, check, apply, pending, channel or auto}
        {value? : stable or beta (channel), on or off (auto)}
        {--auto : The nightly run: it does nothing while automatic updates are off}
        {--force : Install the newest build of the channel even when it is the installed one}';

    protected $description = 'Keep the application on the newest build of its channel (stable: release tags, beta: main)';

    public function handle(Updater $updater): int
    {
        $updater->onOutput(fn (string $line) => $this->line($line));

        try {
            return match ($this->argument('action')) {
                'status' => $this->json($updater->state()),
                'check' => $this->json($updater->check()),
                'apply' => $this->finished($updater->apply((bool) $this->option('auto'), (bool) $this->option('force')), $updater),
                'pending' => $this->pending($updater),
                'channel' => $this->channel($updater),
                'auto' => $this->automatic($updater),
                default => $this->usage(),
            };
        } catch (UpdateFailed $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** From the scheduler, every minute: the update an administrator asked for. */
    private function pending(Updater $updater): int
    {
        $status = $updater->pending();

        return $status === null ? self::SUCCESS : $this->finished($status, $updater);
    }

    private function channel(Updater $updater): int
    {
        if (! in_array($this->argument('value'), ['stable', 'beta'], true)) {
            return $this->usage();
        }

        $updater->setChannel($this->argument('value'));

        return $this->json(['channel' => $updater->channel(), 'target' => $updater->state()['target'] ?? null]);
    }

    private function automatic(Updater $updater): int
    {
        if (! in_array($this->argument('value'), ['on', 'off'], true)) {
            return $this->usage();
        }

        $updater->setAutomatic($this->argument('value') === 'on');

        return $this->json(['auto' => $updater->automatic()]);
    }

    private function finished(string $status, Updater $updater): int
    {
        $message = (string) ($updater->state()['message'] ?? '');

        match ($status) {
            'skipped' => $this->line('Automatic updates are off.'),
            'failed' => $this->error($message),
            default => $this->info($message),
        };

        return $status === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    /** @param  array<string, mixed>  $data */
    private function json(array $data): int
    {
        unset($data['log']);
        $this->line((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function usage(): int
    {
        $this->error('Usage: app:update status | check | apply [--auto] [--force] | pending | channel stable|beta | auto on|off');

        return self::INVALID;
    }
}
