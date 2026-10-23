<?php

namespace WHMCS\Module\Server\PanelAlpha;

use Exception;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;

/**
 * Shared helpers for the PanelAlpha server module.
 */
class Helper
{
    /**
     * Configurable options every PanelAlpha product can have.
     *
     * @var string[]
     */
    public static array $defaultConfigurableOptions = [
        'server_location',
        'sites',
    ];

    /**
     * Keys that are not sent to PanelAlpha as hosting account config.
     *
     * @var string[]
     */
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
     * Generate a random password with at least one lowercase letter, uppercase letter,
     * digit and special character.
     *
     * @param int $length Password length, at least 4.
     * @return string
     * @throws Exception When no secure random source is available.
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
     * Save a product custom field value for a service.
     *
     * The field name may also match "Name|Friendly label" style field names.
     *
     * @param int|string $productId
     * @param int|string $serviceId
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return void
     * @throws Exception When the product has no such custom field.
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
     * Get a product custom field value of a service.
     *
     * For "value|label" values only the part before the pipe is returned.
     *
     * @param int|null $serviceId
     * @param string $fieldName
     * @return string|null Null when the value is empty.
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
     * Save an addon custom field value.
     *
     * @param int $addonId
     * @param string $fieldName
     * @param string $fieldValue
     * @return void
     * @throws Exception When the addon has no such custom field.
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

    /**
     * Whether any server group contains a PanelAlpha server.
     *
     * @return bool
     */
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

    /**
     * Render the WHMCS 404 page and stop the request.
     *
     * @return void
     */
    public static function showPageNotFound(): void
    {
        gracefulCoreRequiredFileInclude('/includes/clientareafunctions.php');
        $response = new \WHMCS\ClientArea();
        $response->setPageTitle('404 - Page Not Found');
        $response->setTemplate('error/page-not-found');
        $response->skipMainBodyContainer();
        $response = $response->withStatus(404);
        (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
        exit;
    }

    /**
     * Get the instance name from the "Instance Name" custom field, or the product default.
     *
     * @param array<string, mixed> $params WHMCS module parameters.
     * @return string Empty string when neither is set.
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

        return '';
    }

    /**
     * Read the module version from whmcs.json.
     *
     * @return string|null Null when the file does not exist.
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

    /**
     * Add hosting account config and allowed configurable options to each plan.
     *
     * Plans with "account_config_fields" use their field list. Older plans fall back
     * to the keys known in config/configurable-options.php.
     *
     * @param array<int, array<string, mixed>> $plans PanelAlpha plans from the API.
     * @return array<int, array<string, mixed>>
     * @throws \JsonException
     * @throws Exception When the config file cannot be read.
     */
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
                        } elseif ($config['type'] === 3) {
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

    /**
     * Read a value from a module config file using "file.key" notation.
     *
     * Example: getConfig('configurable-options.sites') reads key "sites" from
     * config/configurable-options.php.
     *
     * @param string $key
     * @return array|string|null Null when the key does not exist.
     * @throws Exception When the key format is wrong or the file does not exist.
     */
    public static function getConfig(string $key): array|string|null
    {
        $configParts = explode('.', $key, 2);
        $filename = $configParts[0];
        $configKey = $configParts[1];

        if (empty($filename) || empty($configKey)) {
            throw new Exception('Invalid config key format. Both filename and key must be provided.');
        }

        $configFile = dirname(__DIR__) . "/config/{$filename}.php";

        if (!file_exists($configFile)) {
            throw new Exception("Config file '{$filename}.php' does not exist");
        }

        $configData = include $configFile;
        return $configData[$configKey] ?? null;
    }

    /**
     * Get the number of sites from the "sites" configurable option or custom field.
     *
     * @param array<string, mixed> $params WHMCS module parameters.
     * @return int|null
     */
    public static function getInstanceLimit(array $params): ?int
    {
        $instanceLimit = $params['configoptions']['sites'] ?? $params['customfields']['sites'] ?? null;
        return $instanceLimit ? (int)$instanceLimit : null;
    }

    /**
     * Parse a server location value such as "12" or "12|Frankfurt" into a server ID.
     *
     * @param mixed $value
     * @return int|null Null when the value does not start with a positive integer.
     */
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
     * Collect non-empty location values from configurable options and custom fields.
     *
     * @param array<string, mixed> $params WHMCS module parameters.
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
     * Match a location label (for example "Frankfurt, DE") to a PanelAlpha server ID.
     *
     * First the label is matched against server names, then known city names are
     * matched against geo tokens in the server name.
     *
     * @param string $label
     * @param array<int, array{id?: int, name?: string}> $servers
     * @return int|null
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
     * Get the server ID the client picked as location.
     *
     * Numeric values are used first. Labels are only resolved when $servers is given.
     *
     * @param array<string, mixed> $params WHMCS module parameters.
     * @param array<int, array{id?: int, name?: string}>|null $servers
     * @return int|null
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

    /**
     * Strip the label from a "value|label" option value.
     *
     * @param mixed $value
     * @return mixed The part before the pipe, or the value unchanged.
     */
    public static function normalizeConfigurableOptionValue(mixed $value): mixed
    {
        if (!is_string($value) || !str_contains($value, '|')) {
            return $value;
        }

        return explode('|', $value, 2)[0];
    }

    /**
     * Build the hosting account config sent to PanelAlpha.
     *
     * Merges the product custom config (advanced mode), custom fields and configurable
     * options, in that order, then drops module-only keys.
     *
     * @param array<string, mixed> $params WHMCS module parameters.
     * @return array<string, mixed>
     */
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

        // A three letter location value (for example "ams") is a geo affinity, not a server ID.
        $locationValue = $config['location'] ?? $config['Location'] ?? null;
        if ($locationValue !== null && self::parseServerLocationValue($locationValue) === null) {
            $geoAffinity = self::normalizeConfigurableOptionValue($locationValue);
            if (is_string($geoAffinity) && preg_match('/^[a-z]{3}$/i', $geoAffinity)) {
                $config['geo_affinity'] = $geoAffinity;
            }
        }

        $filtered = array_filter(
            $config,
            static function ($key) {
                return !in_array($key, self::$hostingAccountConfigExcludedKeys, true);
            },
            ARRAY_FILTER_USE_KEY
        );

        return array_map([self::class, 'normalizeConfigurableOptionValue'], $filtered);
    }
}
