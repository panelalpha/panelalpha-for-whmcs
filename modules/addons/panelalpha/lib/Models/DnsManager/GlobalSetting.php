<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models\DnsManager;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class GlobalSetting extends EloquentModel
{
    /** @var string */
    protected $table = 'DNSManager3_GlobalSetting';

    /** @var bool */
    public $timestamps = false;

    /** @var array */
    protected $guarded = ['id'];

    /** @var array */
    protected $fillable = ['key', 'value'];

    /** @var bool */
    protected $softDelete = false;

    public static function get($key)
    {
        /** @var GlobalSetting|null $setting */
        $setting = static::where('key', $key)->first();
        if ($setting === null) {
            throw new \Exception("Global setting with key {$key} not found");
        }
        return $setting->value;
    }
}