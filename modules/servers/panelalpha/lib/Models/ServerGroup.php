<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Exception;
use \Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $column, string $value)
 * @property $servers
 */
class ServerGroup extends Model
{
    protected $table = 'tblservergroups';

    public function servers()
    {
        return $this->belongsToMany(
            Server::class,
            'tblservergroupsrel',
            'groupid',
            'serverid'
        );
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getFirstServer()
    {
        $serversAssignedToGroup = $this->servers;
        if ($serversAssignedToGroup->isEmpty()) {
            throw new Exception('No servers assigned to group');
        }

        foreach ($serversAssignedToGroup as $server) {
            if ($server->type === 'panelalpha' && $server->active) {
                return $server->toArray();
            }
        }
        throw new Exception('No PanelAlpha servers in group');
    }
}