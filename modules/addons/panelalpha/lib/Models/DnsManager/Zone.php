<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models\DnsManager;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @method static Zone|null find(int $zoneId)
 */
class Zone extends EloquentModel
{
    /** @var string */
    protected $table = 'DNSManager3_Zone';

    /** @var bool */
    public $timestamps = true;

    /** @var array */
    protected $guarded = ['id'];

    /** @var array */
    protected $fillable = ['id', 'clientid', 'serverid', 'name', 'ip', 'type', 'relid', 'status', 'connectedWithType', 'connectedWithRelid', 'created_at', 'updated_at', 'is_locked', 'is_slave'];

    /** @var bool */
    protected $softDelete = false;
}