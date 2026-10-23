<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS configurable option (tblproductconfigoptions).
 *
 * @property int $id
 * @property int $gid
 * @property string $optionname
 * @property int $optiontype
 * @property-read Collection<int, ConfigurableOptionSub> $options
 *
 * @method static ConfigurableOption|null find(int $id)
 * @method static ConfigurableOption firstOrCreate(array $attributes, array $values = [])
 */
class ConfigurableOption extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblproductconfigoptions';

    /**
     * @var string[]
     */
    protected $fillable = [
        'gid',
        'optionname',
        'optiontype',
        'qtyminimum',
        'qtymaximum',
        'order',
        'hidden',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Sub options (choices) of this option.
     *
     * @return HasMany
     */
    public function options()
    {
        return $this->hasMany(ConfigurableOptionSub::class, 'configid', 'id');
    }
}
