<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static ConfigurableOption firstOrCreate(array $array, int[] $array1)
 *
 * @property int $id
 * @property int configid
 * @property string $optionname
 * @property int sortorder
 * @property bool hidden
 */
class ConfigurableOptionSub extends Model
{
    protected $table = 'tblproductconfigoptionssub';

    protected $fillable = [
        'configid',
        'optionname',
        'sortorder',
        'hidden',
    ];

    public $timestamps = false;
}
