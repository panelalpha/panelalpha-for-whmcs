<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;
use WHMCS\Database\Capsule;

/**
 * WHMCS product addon (tbladdons).
 *
 * @property int $id
 * @property string $name
 * @property int $packageId PanelAlpha package ID stored in the addon module settings.
 * @property array|null $selectedPackage PanelAlpha package picked by setSelectedPackage().
 *
 * @method static Addon findOrFail(mixed $id)
 */
class Addon extends Model
{
    /**
     * @var string
     */
    protected $table = 'tbladdons';

    /**
     * @var string[]
     */
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
     * Read the PanelAlpha package ID (configoption1) from the addon module configuration.
     *
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

    /**
     * Pick the package assigned to this addon, or the first package when none matches.
     *
     * @param array<int, array<string, mixed>> $packages PanelAlpha packages from the API.
     * @return void
     */
    public function setSelectedPackage(array $packages): void
    {
        $selectedPackage = null;
        foreach ($packages as $package) {
            if ($package['id'] == $this->packageId) {
                $selectedPackage = $package;
                break;
            }
        }
        if (!$selectedPackage) {
            $selectedPackage = $packages[0] ?? null;
        }
        $this->selectedPackage = $selectedPackage;
    }

    /**
     * Get the package picked by setSelectedPackage().
     *
     * @return array<string, mixed>|null
     */
    public function getSelectedPackage()
    {
        return $this->selectedPackage;
    }

    /**
     * Get the plugin names of the selected package.
     *
     * @return string[]
     */
    public function getSelectedPackagePlugins(): array
    {
        return array_map(function ($plugin) {
            return $plugin['name'];
        }, $this->selectedPackage['plugins'] ?? []);
    }

    /**
     * Get the theme names of the selected package.
     *
     * @return string[]
     */
    public function getSelectedPackageThemes(): array
    {
        return array_map(function ($theme) {
            return $theme['name'];
        }, $this->selectedPackage['themes'] ?? []);
    }
}
