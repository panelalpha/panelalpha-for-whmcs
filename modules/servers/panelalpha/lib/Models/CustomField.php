<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $string1)
 * @method static join(string $table, string $column, string $operator, string $column)
 */
class CustomField extends Model
{
    protected $table = 'tblcustomfields';

    protected $fillable = [
        'type',
        'relid',
        'fieldname',
        'fieldtype',
        'showorder',
        'adminonly'
    ];

    public static function createProductCustomFieldsIfNotExist(int $productId, ?array $configFields)
    {
        $customFields = CustomField::where('relid', $productId)
            ->where('type', 'product')
            ->get();

        if ($customFields->isEmpty()) {
            $isAutoInstallInstance = !empty($configFields) && $configFields[2] === 'on';

            CustomField::insert([
                [
                    'type' => 'product',
                    'relid' => $productId,
                    'fieldname' => 'Instance Name',
                    'fieldtype' => 'text',
                    'showorder' => $isAutoInstallInstance ? 'on' : '',
                    'adminonly' => ''
                ],
                [
                    'type' => 'product',
                    'relid' => $productId,
                    'fieldname' => 'Service ID',
                    'fieldtype' => 'text',
                    'showorder' => '',
                    'adminonly' => 'on',
                ],
                [
                    'type' => 'product',
                    'relid' => $productId,
                    'fieldname' => 'User ID',
                    'fieldtype' => 'text',
                    'showorder' => '',
                    'adminonly' => 'on'
                ]
            ]);
        }
    }
}
