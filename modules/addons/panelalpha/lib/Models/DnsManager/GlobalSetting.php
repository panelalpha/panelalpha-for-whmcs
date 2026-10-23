<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models\DnsManager;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Global setting of the DNS Manager addon (DNSManager3_GlobalSetting).
 */
class GlobalSetting extends EloquentModel
{
    /** @var string */
    protected $table = 'DNSManager3_GlobalSetting';

    /** @var bool */
    public $timestamps = false;

    /** @var array<int, string> */
    protected $guarded = ['id'];

    /** @var array<int, string> */
    protected $fillable = ['key', 'value'];

    /** @var bool */
    protected $softDelete = false;

    /**
     * Returns the value of a DNS Manager global setting.
     *
     * @param string $key Setting key.
     * @return string|null
     * @throws \Exception When the setting does not exist.
     */
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
