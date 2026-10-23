<?php

namespace WHMCS;

class Session
{
    /** @var array<string, mixed> */
    private static array $data = [];

    public static function resetForTest(): void
    {
        self::$data = [];
    }

    public static function get(string $key)
    {
        return self::$data[$key] ?? null;
    }

    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
    }

    public static function delete(string $key): void
    {
        unset(self::$data[$key]);
    }
}
