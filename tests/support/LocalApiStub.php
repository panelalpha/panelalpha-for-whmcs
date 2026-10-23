<?php

class LocalApiTestFixture
{
    /** @var array<int, array{command: string, data: array}> */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    public static function handle(string $command, array $data): array
    {
        self::$calls[] = ['command' => $command, 'data' => $data];

        if ($command === 'CreateSsoToken') {
            return [
                'result' => 'success',
                'redirect_url' => 'https://whmcs.example/sso/' . ($data['sso_redirect_path'] ?? ''),
            ];
        }

        if ($command === 'AddCancelRequest') {
            return ['result' => 'success'];
        }

        if ($command === 'dnsmanager') {
            return ['result' => 'success', 'zone' => []];
        }

        return ['result' => 'error', 'message' => 'unexpected command'];
    }
}

if (!function_exists('localAPI')) {
    function localAPI($command, $data = [], $admin = null)
    {
        return LocalApiTestFixture::handle($command, $data);
    }
}
