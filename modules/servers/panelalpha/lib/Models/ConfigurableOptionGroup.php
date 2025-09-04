<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static ConfigurableOptionGroup updateOrCreate(string[] $values, string[] $values)
 *
 * @property int $id
 */
class ConfigurableOptionGroup extends Model
{
    protected $table = 'tblproductconfiggroups';

    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;

    public function configurableOptions()
    {
        return $this->hasMany(ConfigurableOption::class, 'gid', 'id');
    }

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
