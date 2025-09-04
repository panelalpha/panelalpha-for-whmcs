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

    public static function generateRandomString(int $length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $randomString;
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
            ->where('tblcustomfields.fieldname', $fieldName)
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

            $plan['hosting_account_config'] = $hostingAccountConfig;
            $plan['hosting_account_config_json'] = json_encode($hostingAccountConfig, JSON_THROW_ON_ERROR);

            $availableConfigurableOptions = [
                ... $configurableOptions,
                ...self::$defaultConfigurableOptions,
            ];

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

    public static function getServerLocation(array $params): ?int
    {
        $serverLocation = $params['configoptions']['server_location'] ?? $params['customfields']['server_location'] ?? null;
        return $serverLocation ? (int)$serverLocation : null;
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

        return array_filter($config, function ($value, $key)  {
            return !in_array($key, ['Service ID', 'User ID', 'Instance Name', 'server_location', 'sites']);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
