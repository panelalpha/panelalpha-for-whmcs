<?php

namespace WHMCS\Module\Server\PanelAlpha;

use Exception;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;

class Helper
{
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
     * @return mixed
     */
    public static function getCustomField(?int $serviceId, string $fieldName)
    {
        return CustomField::join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.fieldid', '=', 'tblcustomfields.id')
            ->where('tblcustomfieldsvalues.relid', $serviceId)
            ->where('tblcustomfields.type', 'product')
            ->where('tblcustomfields.fieldname', $fieldName)
            ->value('value');
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
}