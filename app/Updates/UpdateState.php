<?php

namespace App\Updates;

/**
 * What the updater knows, in storage/app/updates/state.json: the channel and the
 * nightly switch, the last check, the last run and its log. Storage is shared by
 * every release, so the state outlives the code that wrote it.
 */
final class UpdateState
{
    public function __construct(private readonly string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $state = json_decode((string) @file_get_contents($this->path), true);

        return is_array($state) ? $state : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * Merge values into the state, atomically: the admin page reads it while a run writes it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function merge(array $values): array
    {
        $dir = dirname($this->path);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $lock = fopen($this->path.'.lock', 'c');
        flock($lock, LOCK_EX);

        try {
            $state = array_replace($this->all(), $values);
            $tmp = tempnam($dir, '.state');
            file_put_contents($tmp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            @chmod($tmp, 0664);
            rename($tmp, $this->path);

            return $state;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
