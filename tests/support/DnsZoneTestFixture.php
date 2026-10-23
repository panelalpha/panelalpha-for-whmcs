<?php

class DnsZoneTestFixture
{
    /** @var array<int, object> */
    public static array $zonesById = [];

    /** @var array<string, object> */
    public static array $clientsByEmail = [];

    public static function reset(): void
    {
        self::$zonesById = [];
        self::$clientsByEmail = [];
    }
}
