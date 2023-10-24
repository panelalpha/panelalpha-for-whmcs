<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $column, string $value)
 */
class ServerGroup extends Model
{
    protected $table = 'tblservergroups';

    public function servers()
    {
        return $this->belongsToMany(
            'WHMCS\Module\Server\PanelAlpha\Models\Server',
            'tblservergroupsrel',
            'groupid',
            'serverid'
        );
    }

    public function activeServer()
    {
        return $this->servers()
            ->where('active', 1)
            ->type('type', 'panelalpha')
            ->first()
            ->toArray();
    }
}