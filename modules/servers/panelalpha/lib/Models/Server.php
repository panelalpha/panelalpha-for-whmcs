<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * WHMCS server (tblservers).
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $hostname
 * @property string $username
 * @property string $password
 * @property string $accesshash
 * @property string $secure
 * @property int $port
 * @property int $disabled
 * @property-read Collection<int, ServerGroup> $groups
 *
 * @method static Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static Server findOrFail(int $id)
 */
class Server extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblservers';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'ipaddress',
        'assignedips',
        'hostname',
        'monthlycost',
        'noc',
        'statusaddress',
        'nameserver1',
        'nameserver1ip',
        'nameserver2',
        'nameserver2ip',
        'nameserver3',
        'nameserver3ip',
        'nameserver4',
        'nameserver4ip',
        'nameserver5',
        'nameserver5ip',
        'maxaccounts',
        'type',
        'username',
        'password',
        'accesshash',
        'secure',
        'port',
        'active',
        'disabled'
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Server groups this server belongs to.
     *
     * @return BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(
            ServerGroup::class,
            'tblservergroupsrel',
            'serverid',
            'groupid'
        );
    }

    /**
     * Move the protocol from the username field into the hostname and save.
     *
     * PanelAlpha has no username, so the server form (server.js) stores the
     * protocol (http or https) in the username field. Here it is prepended
     * to the hostname and the username is cleared.
     *
     * @return void
     */
    public function setHostname()
    {
        $this->hostname = $this->username . '://' . $this->hostname;
        $this->username = '';
        $this->save();
    }

    /**
     * Find the first server whose hostname contains the given value.
     *
     * @param string $hostname
     * @return Server|null
     */
    public static function getServerByHostname(string $hostname): ?Server
    {
        $servers = self::get();
        foreach ($servers as $server) {
            if (str_contains($server->hostname, $hostname)) {
                return $server;
            }
        }
        return null;
    }
}
