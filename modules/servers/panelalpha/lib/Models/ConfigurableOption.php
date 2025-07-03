<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static ConfigurableOption|null find(int $id)
 * @property int $id
 * @property string $optionname
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
}
