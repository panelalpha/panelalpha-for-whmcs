<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $column, string $value)
 * @method static findOrFail(int $serverid)
 * @property string $type
 * @property int $port
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
            'WHMCS\Module\Server\PanelAlpha\Models\ServerGroup',
            'tblservergroupsrel',
            'serverid',
            'groupid'
        );
    }

    /**
     * @return void
     */
    public function setDefaultPort(): void
    {
        if ($this->type === 'panelalpha' && !$this->port) {
            $this->port = 8443;
            $this->save();
        }
    }
}