<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models;

class ClientWhereBuilder
{
    private string $column;

    private mixed $value;

    public function __construct(string $column, mixed $value)
    {
        $this->column = $column;
        $this->value = $value;
    }

    public function first(): ?object
    {
        if ($this->column === 'email' && is_string($this->value)) {
            return \DnsZoneTestFixture::$clientsByEmail[$this->value] ?? null;
        }

        return null;
    }
}

class Client
{
    public static function where(string $column, mixed $operator = null, mixed $value = null): ClientWhereBuilder
    {
        if ($value === null) {
            $value = $operator;
        }

        return new ClientWhereBuilder($column, $value);
    }
}

namespace WHMCS\Module\Addon\PanelAlpha\Models\DnsManager;

class Zone
{
    public int $id = 0;

    public int $clientid = 0;

    public string $name = '';

    public static function find(int $zoneId): ?self
    {
        $data = \DnsZoneTestFixture::$zonesById[$zoneId] ?? null;
        if ($data === null) {
            return null;
        }
        $zone = new self();
        foreach ((array) $data as $key => $value) {
            $zone->{$key} = $value;
        }

        return $zone;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): ZoneWhereBuilder
    {
        if ($value === null) {
            $value = $operator;
        }

        return new ZoneWhereBuilder($column, $value);
    }
}

class ZoneWhereBuilder
{
    /** @var array<int, array{0: string, 1: mixed}> */
    private array $wheres = [];

    public function __construct(string $column, mixed $value)
    {
        $this->wheres[] = [$column, $value];
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
        }
        $this->wheres[] = [$column, $value];

        return $this;
    }

    public function first(): ?object
    {
        foreach (\DnsZoneTestFixture::$zonesById as $zone) {
            $matches = true;
            foreach ($this->wheres as [$column, $value]) {
                if (!isset($zone->{$column}) || $zone->{$column} != $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $found = new Zone();
                foreach ((array) $zone as $key => $value) {
                    $found->{$key} = $value;
                }

                return $found;
            }
        }

        return null;
    }
}
