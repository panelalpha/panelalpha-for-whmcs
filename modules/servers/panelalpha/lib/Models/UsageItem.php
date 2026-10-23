<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * WHMCS usage billing item (tblusage_items).
 *
 * @property int $id
 * @property string $rel_type
 * @property int $rel_id
 * @property string $module_type
 * @property string $module
 * @property string $metric
 * @property string $included
 * @property int $is_hidden
 *
 * @method static Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static bool insert(array $values)
 */
class UsageItem extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblusage_items';

    /**
     * @var string[]
     */
    protected $fillable = [
        'rel_type',
        'rel_id',
        'module_type',
        'module',
        'metric',
        'included',
        'is_hidden'
    ];

    /**
     * Metrics the module reports to WHMCS usage billing.
     *
     * @var string[]
     */
    protected static $usageItems = [
        'active_instances',
        'remote_backups_size',
        'disk_usage',
        'visitors',
        'bandwidth',
    ];

    /**
     * Get the usage items of a product, creating missing ones as hidden.
     *
     * @param int $productId
     * @return UsageItem[] One item per metric, in the order of $usageItems.
     */
    public static function getUsageItems(int $productId)
    {
        $usageItems = [];
        foreach (self::$usageItems as $usageItemName) {
            $item = self::where('rel_id', $productId)
                ->where('metric', $usageItemName)
                ->where('module_type', 'servers')
                ->where('module', 'panelalpha')
                ->first();

            if (!$item) {
                self::insert([
                    'rel_type' => 'Product',
                    'rel_id' => $productId,
                    'module_type' => 'servers',
                    'module' => 'panelalpha',
                    'metric' => $usageItemName,
                    'included' => '0.00000',
                    'is_hidden' => 1
                ]);
                $item = self::where('rel_id', $productId)
                    ->where('metric', $usageItemName)
                    ->where('module_type', 'servers')
                    ->where('module', 'panelalpha')
                    ->first();
            }
            $usageItems[] = $item;
        }
        return $usageItems;
    }
}
