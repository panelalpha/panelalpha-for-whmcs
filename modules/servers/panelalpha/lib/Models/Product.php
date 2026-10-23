<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

/**
 * WHMCS product (tblproducts). PanelAlpha settings are kept in the configoptionN columns.
 *
 * @property int $id
 * @property string $name
 * @property string $servertype
 * @property int $servergroup
 * @property int $showdomainoptions
 * @property string $configoption1
 * @property string $configoption2
 * @property string $configoption3
 * @property string $configoption4
 * @property string $configoption5
 * @property string $configoption6
 * @property string $configoption7
 * @property string $configoption8
 * @property string $configoption9
 * @property string $configoption10
 * @property string $configoption11
 * @property string $configoption12
 * @property-read ServerGroup|null $serverGroup
 * @property-read Collection<int, CustomField> $customFields
 * @property-read Collection<int, ConfigurableOptionGroup> $configurableOptionGroups
 *
 * @method static Product findOrFail(mixed $id)
 * @method static Product|null find(int $id)
 */
class Product extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblproducts';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
        'gid',
        'name',
        'description',
        'hidden',
        'showdomainoptions',
        'welcomeemail',
        'stockcontrol',
        'qty',
        'proratabilling',
        'proratadate',
        'proratachargenextmonth',
        'paytype',
        'allowqty',
        'subdomain',
        'autosetup',
        'servertype',
        'servergroup',
        'configoption1',    // panelalpha plan id
        'configoption2',    // automatic install instance enabled
        'configoption3',    // default theme
        'configoption4',    // manual termination
        'configoption5',    // panelalpha sso in main menu
        'configoption6',    // onboarding type
        'configoption7',    // ask for domain on onboarding
        'configoption8',    // show instance name on order form
        'configoption9',    // default instance name
        'configoption10',   // automatic set number of sites on upgrade from trial
        'configoption11',   // advanced mode
        'configoption12',   // custom hosting account config values
        'configoption13',
        'configoption14',
        'configoption15',
        'configoption16',
        'configoption17',
        'configoption18',
        'configoption19',
        'configoption20',
        'configoption21',
        'configoption22',
        'configoption23',
        'configoption24',
        'freedomain',
        'freedomainpaymentterms',
        'freedomaintlds',
        'recurringcycles',
        'autoterminatedays',
        'autoterminateemail',
        'configoptionsupgrade',
        'billingcycleupgrade',
        'upgradeemail',
        'overagesenabled',
        'overagesdisklimit',
        'overagesbwlimit',
        'overagesdiskprice',
        'overagesbwprice',
        'tax',
        'affitiatepaytype',
        'affiliateonetime',
        'affiliatepayamount',
        'order',
        'retired',
        'is_featured'
    ];

    /**
     * Product custom fields the module needs, keyed by field name.
     *
     * @var array<string, array{type: string, fieldname: string, fieldtype: string, showorder: string, adminonly: string}>
     */
    private static array $customFields = [
        'Instance Name' => [
            'type' => 'product',
            'fieldname' => 'Instance Name',
            'fieldtype' => 'text',
            'showorder' => 'on',
            'adminonly' => ''
        ],
        'Service ID' => [
            'type' => 'product',
            'fieldname' => 'Service ID',
            'fieldtype' => 'text',
            'showorder' => '',
            'adminonly' => 'on',
        ],
        'User ID' => [
            'type' => 'product',
            'fieldname' => 'User ID',
            'fieldtype' => 'text',
            'showorder' => '',
            'adminonly' => 'on'
        ]
    ];

    /**
     * Server group assigned to this product.
     *
     * @return BelongsTo
     */
    public function serverGroup()
    {
        return $this->belongsTo(ServerGroup::class, 'servergroup');
    }

    /**
     * Product custom fields.
     *
     * @return HasMany
     */
    public function customFields()
    {
        return $this->hasMany(CustomField::class, 'relid')->where('type', 'product');
    }

    /**
     * Configurable option groups linked to this product.
     *
     * @return BelongsToMany
     */
    public function configurableOptionGroups()
    {
        return $this->belongsToMany(
            ConfigurableOptionGroup::class,
            'tblproductconfiglinks',
            'pid',
            'gid'
        );
    }

    /**
     * Create the module custom fields (Instance Name, Service ID, User ID) when they are missing.
     *
     * @return void
     */
    public function createCustomFieldsIfNotExists(): void
    {
        foreach (self::$customFields as $customFieldName => $values) {
            $customField = CustomField::where('relid', $this->id)
                ->where('type', 'product')
                ->where('fieldname', $customFieldName)
                ->first();

            if (!$customField) {
                CustomField::insert([
                    'type' => $values['type'],
                    'relid' => $this->id,
                    'fieldname' => $customFieldName,
                    'fieldtype' => $values['fieldtype'],
                    'showorder' => $values['showorder'],
                    'adminonly' => $values['adminonly']
                ]);
            }
        }
    }

    /**
     * Turn on the default options for a product that has no plan assigned yet.
     *
     * The model is not saved here.
     *
     * @return void
     */
    public function setConfigOptionsEnabledWhenProductCreated(): void
    {
        if (!$this->configoption1) {
            $this->configoption2 = 'on';
            $this->configoption4 = 'on';
            $this->configoption5 = 'on';
        }
    }

    /**
     * Find the plan assigned to this product, or fall back to the first plan.
     *
     * @param array<int, array<string, mixed>> $plans PanelAlpha plans from the API.
     * @return array<string, mixed>|null
     */
    public function getPlanAssignedToProduct($plans): ?array
    {
        foreach ($plans as $plan) {
            if ($plan['id'] === (int) $this->configoption1) {
                return $plan;
            }
        }

        return $plans[0] ?? null;
    }

    /**
     * Get the first server of the product's server group.
     *
     * @return Server|null
     */
    public function getServer(): ?Server
    {
        return $this->serverGroup?->servers->first();
    }

    /**
     * Set a configoptionN value and save the product.
     *
     * @param string $key Option number, for example "3" for configoption3.
     * @param string $value
     * @return void
     */
    public function saveConfigOption(string $key, string $value): void
    {
        $this->{'configoption' . $key} = $value;
        $this->save();
    }

    /**
     * Show the domain field on the order form only when the onboarding flow needs a domain.
     *
     * @return void
     */
    public function setShowDomainOption(): void
    {
        if (!$this->isAutomaticInstallInstanceEnabled()) {
            $this->showdomainoptions = 0;
            $this->save();
            return;
        }

        $onboardingType = $this->getOnboardingType();
        $onboardingAskForDomain = $this->getOnboardingAskForDomain();

        if ($onboardingType === 'Standard') {
            $this->showdomainoptions = 1;
            $this->save();
            return;
        }

        if (($onboardingType === 'Quick' || $onboardingType === 'Super Quick')
            && $onboardingAskForDomain === 'on') {
            $this->showdomainoptions = 1;
            $this->save();
            return;
        }

        $this->showdomainoptions = 0;
        $this->save();
    }

    /**
     * Show or hide a usage item (metric) for this product.
     *
     * @param string $metric Metric name, for example "disk_usage".
     * @param string $status Value for the is_hidden column.
     * @return void
     */
    public function setUsageItemHiddenStatus(string $metric, string $status): void
    {
        UsageItem::where('metric', $metric)
            ->where('rel_id', $this->id)
            ->update(['is_hidden' => $status]);
    }

    /**
     * Get all configurable options from all groups linked to this product.
     *
     * @return SupportCollection<int, ConfigurableOption>
     */
    public function getConfigurableOptions(): SupportCollection
    {
        return $this->configurableOptionGroups->flatMap(function ($group) {
            return $group->configurableOptions;
        });
    }

    /**
     * Find the first configurable option whose name contains the given key.
     *
     * @param string $key
     * @return ConfigurableOption|null
     */
    public function getConfigurableOptionByName(string $key): ?ConfigurableOption
    {
        foreach ($this->configurableOptionGroups as $configurableOptionGroup) {
            foreach ($configurableOptionGroup->configurableOptions as $configurableOption) {
                if (str_contains($configurableOption->optionname, $key)) {
                    return $configurableOption;
                }
            }
        }
        return null;
    }

    /**
     * @return int PanelAlpha plan ID (configoption1).
     */
    public function getPanelAlphaPlanId(): int
    {
        return (int)$this->configoption1;
    }

    /**
     * Whether an instance is created automatically on account creation (configoption2).
     *
     * @return bool
     */
    public function isAutomaticInstallInstanceEnabled(): bool
    {
        return $this->configoption2 === 'on';
    }

    /**
     * @return string Default theme for new instances (configoption3), or an empty string.
     */
    public function getThemeName(): string
    {
        return $this->configoption3 ?? '';
    }

    /**
     * @return string|null Onboarding type (configoption6), for example "Standard" or "Quick".
     */
    public function getOnboardingType(): ?string
    {
        return $this->configoption6 ?? null;
    }

    /**
     * @return string|null "on" when the onboarding asks for a domain (configoption7).
     */
    public function getOnboardingAskForDomain(): ?string
    {
        return $this->configoption7 ?? null;
    }

    /**
     * Whether the number of sites is set automatically on upgrade from a trial (configoption10).
     *
     * @return bool
     */
    public function hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption(): bool
    {
        return $this->configoption10 === 'on';
    }

    /**
     * Whether advanced mode with custom hosting account config is on (configoption11).
     *
     * @return bool
     */
    public function hasCustomAccountConfiguration(): bool
    {
        return (bool)$this->configoption11;
    }

    /**
     * Get the custom hosting account config (configoption12) as an array.
     *
     * The value is stored as an HTML-encoded query string.
     *
     * @return array<string, mixed>
     */
    public function getCustomAccountConfiguration(): array
    {
        $decodedConfig = html_entity_decode($this->configoption12, ENT_QUOTES | ENT_HTML401, 'UTF-8');
        parse_str($decodedConfig, $config);
        return $config;
    }
}
