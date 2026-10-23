<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

class Product
{
    /** @var array<int, object> */
    public static array $products = [];

    public static function find(int $id): ?object
    {
        return self::$products[$id] ?? null;
    }
}

class ConfigurableOption
{
    public static function find(int $id): object
    {
        return (object) ['optionname' => 'sites|Number of Sites'];
    }
}

class Server
{
    public static function getServerByHostname(string $hostname): ?object
    {
        return null;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): ServerWhereBuilder
    {
        return new ServerWhereBuilder();
    }
}

class ServerWhereBuilder
{
    public function first(): object
    {
        return (object) ['id' => 1, 'hostname' => 'panel.example'];
    }
}

namespace WHMCS\Module\Server\PanelAlpha\Apis;

class PanelAlphaApi
{
    /** @var callable|null */
    public static $getServiceHandler;

    public static function fromModel(object $server): self
    {
        return new self();
    }

    public function getService(int $serviceId): array
    {
        if (self::$getServiceHandler !== null) {
            return (self::$getServiceHandler)($serviceId);
        }

        return ['instances_count' => 1];
    }
}
