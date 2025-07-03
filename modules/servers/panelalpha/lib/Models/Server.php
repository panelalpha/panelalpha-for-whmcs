<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $column, string $value)
 * @method static findOrFail(int $serverid)
 * @property string $type
 * @property int $port
 * @property string $hostname
 * @property string $username
 */
class Server extends Model
{
    protected $table = 'tblservers';

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

    public $timestamps = false;

    public function groups()
    {
        return $this->belongsToMany(
            ServerGroup::class,
            'tblservergroupsrel',
            'serverid',
            'groupid'
        );
    }

    public function setHostname()
    {
        $this->hostname = $this->username . '://' . $this->hostname;
        $this->username = "";
        $this->save();
    }

    /**
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