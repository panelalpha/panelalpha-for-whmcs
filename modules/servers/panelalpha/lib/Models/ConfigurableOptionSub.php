<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WHMCS configurable option choice (tblproductconfigoptionssub).
 *
 * @property int $id
 * @property int $configid
 * @property string $optionname
 * @property int $sortorder
 * @property bool $hidden
 *
 * @method static ConfigurableOptionSub firstOrCreate(array $attributes, array $values = [])
 */
class ConfigurableOptionSub extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblproductconfigoptionssub';

    /**
     * @var string[]
     */
    protected $fillable = [
        'configid',
        'optionname',
        'sortorder',
        'hidden',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;
}
