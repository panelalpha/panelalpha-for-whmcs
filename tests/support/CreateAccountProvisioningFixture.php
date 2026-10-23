<?php

class CreateAccountProvisioningFixture
{
    /** @var object|null */
    public static $service;

    /** @var list<object> */
    public static array $clientPanelalphaServices = [];

    /** @var array<int, array<string, string>> */
    public static array $customFieldValues = [];

    /** @var list<array{method: string, args: array}> */
    public static array $apiCalls = [];

    /** @var array<string, mixed> */
    public static array $trialServicePayload = [];

    /** @var array<int, array<string, mixed>> */
    public static array $userByIdPayload = [];

    /** @var array<string, array<string, mixed>> */
    public static array $userByEmailPayload = [];

    public static function reset(): void
    {
        self::$service = null;
        self::$clientPanelalphaServices = [];
        self::$customFieldValues = [];
        self::$apiCalls = [];
        self::$trialServicePayload = [];
        self::$userByIdPayload = [];
        self::$userByEmailPayload = [];
    }

    public static function getCustomFieldValue(int $serviceId, string $fieldName): ?string
    {
        return self::$customFieldValues[$serviceId][$fieldName] ?? null;
    }

    public static function recordApiCall(string $method, array $args): void
    {
        self::$apiCalls[] = ['method' => $method, 'args' => $args];
    }
}
