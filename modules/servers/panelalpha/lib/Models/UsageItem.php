<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $column, int $value)
 * @method static insert(array $array)
 */
class UsageItem extends Model
{
    protected $table = 'tblusage_items';

    protected $fillable = [
        'rel_type',
        'rel_id',
        'module_type',
        'module',
        'metric',
        'included',
        'is_hidden'
    ];


    protected static $usageItems = [
        'active_instances',
        'remote_backups_size'
    ];

    public static function getUsageItems(int $productId)
    {
        return self::where('rel_id', $productId)
            ->where('module_type', 'servers')
            ->where('module', 'panelalpha')
            ->get();
    }

    public static function createUsageItems(int $productId)
    {
        foreach (self::$usageItems as $usageItem) {
            self::insert([
                'rel_type' => 'Product',
                'rel_id' => $productId,
                'module_type' => 'servers',
                'module' => 'panelalpha',
                'metric' => $usageItem,
                'included' => '0.00000',
                'is_hidden' => 1
            ]);
        }
    }

    public static function setHiddenField(string $key,  string $value)
    {
        self::where('metric', $key)
            ->where('rel_id', $_REQUEST['id'])
            ->update(['is_hidden' => $value]);
    }
}