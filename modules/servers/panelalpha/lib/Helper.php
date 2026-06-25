<?php

namespace WHMCS\Module\Server\PanelAlpha;

use Exception;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;

class Helper
{
    public static array $defaultConfigurableOptions = [
        'server_location',
        'sites',
    ];

    private static array $hostingAccountConfigExcludedKeys = [
        'Service ID',
        'User ID',
        'Instance Name',
        'server_location',
        'sites',
        'location',
        'Location',
    ];

    /**
     * Maps checkout location labels to hostname geo tokens (e.g. cp11.ams1 → amsterdam).
     *
     * @var array<string, list<string>>
     */
    private static array $locationLabelKeywords = [
        'frankfurt' => ['fra'],
        'amsterdam' => ['ams'],
        'warsaw' => ['waw'],
        'los angeles' => ['lax'],
        'toronto' => ['tor'],
        'singapore' => ['sgp'],
        'new york' => ['nyc'],
        'dubai' => ['dxb'],
        'sydney' => ['syd'],
        'sao paulo' => ['bra'],
        'são paulo' => ['bra'],
        'zurich' => ['zrh'],
        'seoul' => ['sel'],
        'madrid' => ['mad'],
        'johannesburg' => ['jnb'],
        'istanbul' => ['ist'],
        'london' => ['lon', 'lhr'],
        'dallas' => ['dfw', 'dal'],
        'mumbai' => ['bom', 'mum'],
        'tokyo' => ['tyo', 'nrt'],
        'riyadh' => ['ruh'],
        'oslo' => ['osl'],
    ];

    /**
     *  @param int $length 
     */
    public static function generateSecurePassword(int $length = 16): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()-_=+[]{};:,.<>?';

        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];

        $allChars = $lowercase . $uppercase . $numbers . $specialChars;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * @param $productId
     * @param $serviceId
     * @param $fieldName
     * @param $fieldValue
     * @return void
     * @throws Exception
     */
    public static function setServiceCustomFieldValue($productId, $serviceId, $fieldName, $fieldValue)
    {
        $customField = CustomField::where('type', 'product')
            ->where('relid', $productId)
            ->where(function ($query) use ($fieldName) {
                $query->where('fieldname', $fieldName);
                $query->orWhere('fieldname', 'like', $fieldName . '|%');
            })->first();

        if (!$customField) {
            throw new Exception("Custom field `{$fieldName}` not found for product #{$productId}");
        }

        CustomFieldValue::updateOrCreate([
            'fieldid' => $customField->id,
            'relid' => $serviceId,
        ], [
            'value' => $fieldValue,
        ]);
    }

    /**
     * @param int|null $serviceId
     * @param string $fieldName
     * @return mixed|null
     */
    public static function getCustomField(?int $serviceId, string $fieldName)
    {
        $value = CustomField::join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.fieldid', '=', 'tblcustomfields.id')
            ->where('tblcustomfieldsvalues.relid', $serviceId)
            ->where('tblcustomfields.type', 'product')
            ->where(function ($query) use ($fieldName) {
                $query->where('tblcustomfields.fieldname', $fieldName);
                $query->orWhere('tblcustomfields.fieldname', 'like', $fieldName . '|%');
            })
            ->value('value');

        if (empty($value)) {
            return null;
        }

        $explodedValue = explode('|', $value);
        return $explodedValue[0];
    }

    /**
     * @param int $addonId
     * @param string $fieldName
     * @param string $fieldValue
     * @return void
     * @throws Exception
     */
    public static function setAddonCustomFieldValue(int $addonId, string $fieldName, string $fieldValue)
    {
        $customField = CustomField::where('type', 'addon')
            ->where('relid', $addonId)
            ->where(function ($query) use ($fieldName) {
                $query->where('fieldname', $fieldName);
                $query->orWhere('fieldname', 'like', $fieldName . '|%');
            })->first();

        if (!$customField) {
            throw new Exception("Custom field `{$fieldName}` not found for addon #{$addonId}");
        }

        CustomFieldValue::updateOrCreate([
            'fieldid' => $customField->id,
            'relid' => $addonId,
        ], [
            'value' => $fieldValue,
        ]);
    }

    public static function isServerGroupWithPanelAlphaServer(): bool
    {
        $serverGroups = ServerGroup::get();
        foreach ($serverGroups as $serverGroup) {
            foreach ($serverGroup->servers as $server) {
                if ($server->type === 'panelalpha') {
                    return true;
                }
            }
        }
        return false;
    }

    public static function showPageNotFound(): void
    {
        gracefulCoreRequiredFileInclude("/includes/clientareafunctions.php");
        $response = new \WHMCS\ClientArea();
        $response->setPageTitle("404 - Page Not Found");
        $response->setTemplate("error/page-not-found");
        $response->skipMainBodyContainer();
        $response = $response->withStatus(404);
        (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
        exit;
    }

    /**
     * @param array $params
     * @return string
     */
    public static function getInstanceName(array $params): string
    {
        $instanceName = self::getCustomField($params['serviceid'], 'Instance Name');
        if (!empty($instanceName)) {
            return $instanceName;
        }

        $instanceName = $params['configoption9'];
        if (!empty($instanceName)) {
            return $instanceName;
        }

        return "";
    }

    /**
     * @return string|null
     */
    public static function getVersion(): ?string
    {
        $filepath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'whmcs.json';

        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        $info = json_decode($content, true);
        return $info['version'];
    }

    public static function getFormattedPlans(array $plans): array
    {
        return array_map(static function (array $plan) {
            $configurableOptions = [];
            $hostingAccountConfig = [];

            if (!empty($plan['account_config_fields'])) {
                foreach ($plan['account_config_fields'] as $config) {
                    $value = $plan['account_config'][$config['name']] ?? null;

                    $hostingAccountConfig[] = [
                        'name' => $config['name'],
                        'type' => $config['type'],
                        'value' => $value,
                    ];

                    if (in_array('billable', $config['flags'] ?? [], true)) {
                        $configurableOptions[] = $config['name'];
                    }
                }
            } else {
                foreach ($plan['account_config'] as $key => $value) {
                    $config = self::getConfig('configurable-options.' . $key);
                    if ($config !== null) {
                        $type = 'text';
                        if ($config['type'] === 1) {
                            $type = 'select';
                        } else if ($config['type'] === 3) {
                            $type = 'checkbox';
                        }

                        $hostingAccountConfig[] = [
                            'name' => $key,
                            'type' => $type,
                            'value' => $value,
                        ];
                        $configurableOptions[] = $key;
                    }
                }
            }

            $plan['hosting_account_config'] = $hostingAccountConfig;
            $plan['hosting_account_config_json'] = json_encode($hostingAccountConfig, JSON_THROW_ON_ERROR);

            $availableConfigurableOptions = [
                ...$configurableOptions,
                'sites',
            ];
            if ($plan['server_assign_rule'] !== 'specific_server') {
                $availableConfigurableOptions[] = 'server_location';
            }

            $plan['configurable_options'] = $availableConfigurableOptions;
            $plan['configurable_options_json'] = json_encode($availableConfigurableOptions, JSON_THROW_ON_ERROR);

            return $plan;
        }, $plans);
    }

    public static function getConfig(string $key): array|string|null
    {
        $configParts = explode('.', $key, 2);
        $filename = $configParts[0];
        $configKey = $configParts[1];

        if (empty($filename) || empty($configKey)) {
            throw new Exception("Invalid config key format. Both filename and key must be provided.");
        }

        $configFile = dirname(__DIR__) . "/config/{$filename}.php";

        if (!file_exists($configFile)) {
            throw new Exception("Config file '{$filename}.php' does not exist");
        }

        $configData = include $configFile;
        return $configData[$configKey] ?? null;
    }

    public static function getInstanceLimit(array $params): ?int
    {
        $instanceLimit = $params['configoptions']['sites'] ?? $params['customfields']['sites'] ?? null;
        return $instanceLimit ? (int)$instanceLimit : null;
    }

    public static function parseServerLocationValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode('|', (string) $value, 2);
        $candidate = trim($parts[0]);

        if ($candidate === '' || !ctype_digit($candidate)) {
            return null;
        }

        $id = (int) $candidate;

        return $id > 0 ? $id : null;
    }

    /**
     * @return list<string>
     */
    private static function getServerLocationCandidateValues(array $params): array
    {
        $candidates = [];
        $sources = [
            $params['configoptions'] ?? [],
            $params['customfields'] ?? [],
        ];
        $keys = ['server_location', 'location', 'Location'];

        foreach ($sources as $source) {
            foreach ($keys as $key) {
                if (!empty($source[$key])) {
                    $candidates[] = (string) $source[$key];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param array<int, array{id?: int, name?: string}> $servers
     */
    public static function resolveServerLocationFromLabel(string $label, array $servers): ?int
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $parsed = self::parseServerLocationValue($label);
        if ($parsed !== null) {
            return $parsed;
        }

        $searchTexts = array_unique(array_filter(array_map(
            static fn (string $text) => strtolower(trim($text)),
            [$label, ...explode('|', $label)]
        )));

        foreach ($servers as $server) {
            $serverId = (int) ($server['id'] ?? 0);
            $serverName = strtolower((string) ($server['name'] ?? ''));
            if ($serverId <= 0 || $serverName === '') {
                continue;
            }

            foreach ($searchTexts as $text) {
                if ($text === '') {
                    continue;
                }

                if (str_contains($serverName, $text) || str_contains($text, $serverName)) {
                    return $serverId;
                }
            }
        }

        foreach ($servers as $server) {
            $serverId = (int) ($server['id'] ?? 0);
            $serverName = strtolower((string) ($server['name'] ?? ''));
            if ($serverId <= 0 || $serverName === '') {
                continue;
            }

            foreach (self::$locationLabelKeywords as $keyword => $patterns) {
                $keywordFound = false;
                foreach ($searchTexts as $text) {
                    if (str_contains($text, $keyword)) {
                        $keywordFound = true;
                        break;
                    }
                }

                if (!$keywordFound) {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (str_contains($serverName, $pattern)) {
                        return $serverId;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, array{id?: int, name?: string}>|null $servers
     */
    public static function getServerLocation(array $params, ?array $servers = null): ?int
    {
        foreach (self::getServerLocationCandidateValues($params) as $value) {
            $parsed = self::parseServerLocationValue($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if ($servers === null) {
            return null;
        }

        foreach (self::getServerLocationCandidateValues($params) as $value) {
            $resolved = self::resolveServerLocationFromLabel($value, $servers);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    public static function normalizeConfigurableOptionValue(mixed $value): mixed
    {
        if (!is_string($value) || !str_contains($value, '|')) {
            return $value;
        }

        return explode('|', $value, 2)[0];
    }

    public static function getHostingAccountConfig(array $params): array
    {
        $config = [];
        if ($params['configoption11']) {
            $decodedConfig = html_entity_decode($params['configoption12'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
            parse_str($decodedConfig, $config);
        }
        $config = [
            ...$config,
            ...$params['customfields'],
            ...$params['configoptions'],
        ];

        $locationValue = $config['location'] ?? $config['Location'] ?? null;
        if ($locationValue !== null && self::parseServerLocationValue($locationValue) === null) {
            $geoAffinity = self::normalizeConfigurableOptionValue($locationValue);
            if (is_string($geoAffinity) && preg_match('/^[a-z]{3}$/i', $geoAffinity)) {
                $config['geo_affinity'] = $geoAffinity;
            }
        }

        $filtered = array_filter(
            $config,
            static function ($value, $key) {
                return !in_array($key, self::$hostingAccountConfigExcludedKeys, true);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return array_map([self::class, 'normalizeConfigurableOptionValue'], $filtered);
    }
}
