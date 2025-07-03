<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurableOptionGroup extends Model
{
    protected $table = 'tblproductconfiggroups';

    protected $fillable = [
        'name',
        'description',
    ];

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
