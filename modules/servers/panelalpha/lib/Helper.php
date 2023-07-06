<?php

namespace WHMCS\Module\Server\PanelAlpha;

use Exception;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;

class Helper
{
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
     * @param int $serviceId
     * @param string $fieldName
     * @return mixed
     */
    public static function getCustomField(int $serviceId, string $fieldName)
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

    public static function getServer(int $serverGroupId)
    {
        $serverGroup = ServerGroup::find($serverGroupId);
        if (!$serverGroup) {
            return Server::getPanelAlphaServer();
        }
        $serversAssignedToGroup = $serverGroup->servers;
        if ($serversAssignedToGroup->isEmpty()) {
            return Server::getPanelAlphaServer();
        }
        $count = $serversAssignedToGroup->count();
        if ($count === 1) {
            return $serversAssignedToGroup[0]->toArray();
        } else {
            return $serverGroup->activeServer();
        }
    }
}