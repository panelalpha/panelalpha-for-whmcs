<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static ConfigurableOption|null find(int $id)
 * @method static ConfigurableOption firstOrCreate(array $array, int[] $array1)
 *
 * @property int $id
 * @property string $optionname
 * @property string $optiontype
 */
class ConfigurableOption extends Model
{
    protected $table = 'tblproductconfigoptions';

    protected $fillable = [
        'gid',
        'optionname',
        'optiontype',
        'qtyminimum',
        'qtymaximum',
        'order',
        'hidden',
    ];

    public $timestamps = false;

    public function options()
    {
        return $this->hasMany(ConfigurableOptionSub::class, 'configid', 'id');
    }
}
