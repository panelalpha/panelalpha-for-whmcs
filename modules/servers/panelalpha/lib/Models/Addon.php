<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;
use WHMCS\Database\Capsule;

/**
 * @method static findOrFail(mixed $id)
 * @property mixed|null $selectedPackage
 * @property $packageId
 * @property $id
 */
class Addon extends Model
{
    protected $table = 'tbladdons';

    protected $fillable = [
        'packages',
        'name',
        'description',
        'billingcycle',
        'tax',
        'showorder',
        'downloads',
        'autoactivate',
        'suspendproduct',
        'welcomeemail',
        'weight'
    ];

    /**
     * @return int
     */
    public function getPackageIdAttribute(): int
    {
        return (int)Capsule::table('tblmodule_configuration')
            ->where('entity_type', 'addon')
            ->where('entity_id', $this->id)
            ->where('setting_name', 'configoption1')
            ->value('value');
    }

    public function setSelectedPackage(array $packages)
    {
        $selectedPackage = null;
        foreach ($packages as $package) {
            if ($package->id == $this->packageId) {
                $selectedPackage = $package;
                break;
            }
        }
        if (!$selectedPackage) {
            $selectedPackage = $packages[0];
        }
        $this->selectedPackage = $selectedPackage;;
    }

    public function getSelectedPackage()
    {
        return $this->selectedPackage;
    }

    public function getSelectedPackagePlugins(): array
    {
        return array_map(function ($plugin) {
            return $plugin->name;
        }, $this->selectedPackage->plugins ?? []);
    }

    public function getSelectedPackageThemes(): array
    {
        return array_map(function ($theme) {
            return $theme->name;
        }, $this->selectedPackage->themes ?? []);
    }
}
