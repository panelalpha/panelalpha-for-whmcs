<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS configurable option group (tblproductconfiggroups).
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property-read Collection<int, ConfigurableOption> $configurableOptions
 * @property-read Collection<int, Product> $products
 *
 * @method static ConfigurableOptionGroup updateOrCreate(array $attributes, array $values = [])
 */
class ConfigurableOptionGroup extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblproductconfiggroups';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Configurable options that belong to this group.
     *
     * @return HasMany
     */
    public function configurableOptions()
    {
        return $this->hasMany(ConfigurableOption::class, 'gid', 'id');
    }

    /**
     * Products this group is linked to.
     *
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'tblproductconfiglinks',
            'gid',
            'pid'
        );
    }
}
