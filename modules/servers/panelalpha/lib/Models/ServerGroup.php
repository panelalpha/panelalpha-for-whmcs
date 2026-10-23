<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * WHMCS server group (tblservergroups).
 *
 * @property int $id
 * @property string $name
 * @property-read Collection<int, Server> $servers
 *
 * @method static Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static ServerGroup findOrFail(int $id)
 */
class ServerGroup extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblservergroups';

    /**
     * Servers assigned to this group.
     *
     * @return BelongsToMany
     */
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
     * Get the first enabled PanelAlpha server of this group.
     *
     * @return array<string, mixed> Server attributes.
     * @throws Exception When the group has no servers or no enabled PanelAlpha server.
     */
    public function getFirstServer()
    {
        $serversAssignedToGroup = $this->servers;
        if ($serversAssignedToGroup->isEmpty()) {
            throw new Exception('No servers assigned to group');
        }

        foreach ($serversAssignedToGroup as $server) {
            if ($server->type === 'panelalpha' && !$server->disabled) {
                return $server->toArray();
            }
        }
        throw new Exception('No PanelAlpha servers in group');
    }
}
