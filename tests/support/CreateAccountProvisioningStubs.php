<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

class Service
{
    public int $id = 0;

    /** @var Product|null */
    public $product;

    /** @var object|null */
    public $serverModel;

    public string $username = '';

    public string $domain = '';

    public function save(): bool
    {
        return true;
    }

    public static function findOrFail(int $id): self
    {
        $service = \CreateAccountProvisioningFixture::$service;
        if ($service === null) {
            throw new \Exception('Service fixture not configured');
        }

        return $service;
    }

    public static function query(): ServiceQueryBuilder
    {
        return new ServiceQueryBuilder();
    }
}

class ServiceQueryBuilder
{
    private ?int $userId = null;

    private ?int $excludeServiceId = null;

    private ?int $serverId = null;

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if ($column === 'userid') {
            $this->userId = (int) ($value ?? $operator);
        }
        if ($column === 'server') {
            $this->serverId = (int) $operator;
        }
        if ($column === 'id' && $operator === '!=') {
            $this->excludeServiceId = (int) $value;
        }

        return $this;
    }

    public function panelalpha(): self
    {
        return $this;
    }

    /** @return list<Service> */
    public function get(): array
    {
        $services = [];
        foreach (\CreateAccountProvisioningFixture::$clientPanelalphaServices as $row) {
            if ($this->userId !== null && (int) $row->userid !== $this->userId) {
                continue;
            }
            if ($this->excludeServiceId !== null && (int) $row->id === $this->excludeServiceId) {
                continue;
            }
            if ($this->serverId !== null && (int) ($row->server ?? 0) !== $this->serverId) {
                continue;
            }
            $service = new Service();
            $service->id = (int) $row->id;
            $services[] = $service;
        }

        return $services;
    }
}

class Product
{
    public int $id = 1;

    public function createCustomFieldsIfNotExists(): void
    {
    }

    public function isAutomaticInstallInstanceEnabled(): bool
    {
        return false;
    }

    public function getPanelAlphaPlanId(): int
    {
        return 3;
    }
}

namespace WHMCS\Module\Server\PanelAlpha\Apis;

class PanelAlphaApi
{
    public function __construct(array $params = [])
    {
    }

    public static function fromModel(object $server): self
    {
        return new self();
    }

    public function getService(int|string $serviceId): array
    {
        \CreateAccountProvisioningFixture::recordApiCall('getService', [(int) $serviceId]);

        return \CreateAccountProvisioningFixture::$trialServicePayload;
    }

    public function getUserById(int $id): ?array
    {
        \CreateAccountProvisioningFixture::recordApiCall('getUserById', [$id]);

        return \CreateAccountProvisioningFixture::$userByIdPayload[$id] ?? null;
    }

    public function getUser(string $email): ?array
    {
        \CreateAccountProvisioningFixture::recordApiCall('getUser', [$email]);

        return \CreateAccountProvisioningFixture::$userByEmailPayload[$email] ?? null;
    }

    public function unsuspendAccount(int $userId, int|string $serviceId): void
    {
        \CreateAccountProvisioningFixture::recordApiCall('unsuspendAccount', [$userId, (int) $serviceId]);
    }

    public function changePlan(int $userId, int|string $serviceId, string $planId, ?int $instanceLimit, ?array $hostingAccountConfig): void
    {
        \CreateAccountProvisioningFixture::recordApiCall('changePlan', [
            $userId,
            (int) $serviceId,
            $planId,
            $instanceLimit,
            $hostingAccountConfig,
        ]);
    }

    public function unsetServiceAsTrial(int $serviceId): array
    {
        \CreateAccountProvisioningFixture::recordApiCall('unsetServiceAsTrial', [$serviceId]);

        return [];
    }

    public function updateUser(int $id, array $data): ?array
    {
        \CreateAccountProvisioningFixture::recordApiCall('updateUser', [$id, $data]);

        return $data;
    }

    public function createUser(array $data): ?array
    {
        \CreateAccountProvisioningFixture::recordApiCall('createUser', [$data]);

        return ['id' => 501, 'email' => $data['email']];
    }

    public function getLoginUrl(): array
    {
        return ['url' => 'https://panel.example/login'];
    }

    public function getPlan(int $id): array
    {
        return ['id' => $id, 'server_group_id' => null];
    }

    public function getServers(?int $serverGroupId = null): array
    {
        return [];
    }

    public function createService(
        int $userId,
        int $planId,
        ?int $instanceLimit,
        ?int $serverLocation,
        ?array $hostingAccountConfig
    ): ?array {
        \CreateAccountProvisioningFixture::recordApiCall('createService', [
            $userId,
            $planId,
            $instanceLimit,
            $serverLocation,
            $hostingAccountConfig,
        ]);

        return ['id' => 9001];
    }
}

class LocalApi
{
    public static function sendUserEmail(string $templateName, string $language, array $params): void
    {
        \CreateAccountProvisioningFixture::recordApiCall('sendUserEmail', [$templateName, $language, $params]);
    }
}

namespace WHMCS\Module\Server\PanelAlpha;

class Helper
{
    public static function getCustomField(?int $serviceId, string $fieldName)
    {
        if ($serviceId === null) {
            return null;
        }

        return \CreateAccountProvisioningFixture::getCustomFieldValue($serviceId, $fieldName);
    }

    public static function setServiceCustomFieldValue($productId, $serviceId, $fieldName, $fieldValue): void
    {
        $serviceId = (int) $serviceId;
        if (!isset(\CreateAccountProvisioningFixture::$customFieldValues[$serviceId])) {
            \CreateAccountProvisioningFixture::$customFieldValues[$serviceId] = [];
        }
        \CreateAccountProvisioningFixture::$customFieldValues[$serviceId][$fieldName] = (string) $fieldValue;
    }

    public static function getInstanceLimit(array $params): ?int
    {
        $instanceLimit = $params['configoptions']['sites'] ?? null;

        return $instanceLimit ? (int) $instanceLimit : null;
    }

    public static function getHostingAccountConfig(array $params): array
    {
        return $params['configoptions'] ?? [];
    }

    public static function getServerLocation(array $params, array $servers): ?int
    {
        return null;
    }

    public static function generateSecurePassword(int $length = 16): string
    {
        return 'FixedTestPassword1!';
    }
}
