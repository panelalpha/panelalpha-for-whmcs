<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $configoption1
 * @property string $configoption2
 * @property string $configoption3
 * @property string $configoption4
 * @property string $configoption5
 * @property string $configoption6
 * @property bool $showdomainoptions
 * @property string $configoption7
 * @property string $configoption8
 * @property string $configoption9
 * @property string $configoption10
 * @property string $configoption11
 * @property string $configoption12
 * @property int $id
 * @property string $servertype
 * @property ServerGroup $serverGroup
 * @property Collection $configurableOptionGroups
 * @method static Product findOrFail(mixed $id)
 * @method static Product|null find(int $id)
 * @method save()
 */
class Product extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'tblproducts';

    protected $primaryKey = 'id';

    /**
     * Eloquent guarded parameters
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Eloquent fillable parameters
     * @var array
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
        'configoption3',    // default instance name
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

    public function serverGroup()
    {
        return $this->belongsTo(ServerGroup::class, 'servergroup');
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class, 'relid')->where('type', 'product');
    }

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
     * @param $plans
     * @return array|null
     */
    public function getPlanAssignedToProduct($plans): ?array
    {
        $selectedPlan = null;
        foreach ($plans as $plan) {
            if ($plan['id'] === (int)$this->configoption1) {
                $selectedPlan = $plan;
            }
        }
        if (!$selectedPlan) {
            $selectedPlan = $plans[0];
        }
        return $selectedPlan;
    }

    /**
     * @return Server
     * @throws Exception
     */
    public function getServer(): Server
    {
        $server = $this->serverGroup?->servers->first();

        if (!$server) {
            throw new Exception('No server assigned to this product.');
        }
        return $server;
    }

    public function saveConfigOption(string $key, string $value): void
    {
        $this->{'configoption' . $key} = $value;
        $this->save();
    }

    /**
     * @return void
     */
    public function setShowDomainOption(): void
    {
        $automaticInstallInstance = $this->isAutomaticInstallInstanceEnabled();
        $onboardingType = $this->getOnboardingType();
        $onboardingAskForDomain = $this->getOnboardingAskForDomain();;

        if ($automaticInstallInstance && $onboardingType === 'Standard') {
            $this->showdomainoptions = true;
            $this->save();
            return;
        }

        if (
            $automaticInstallInstance
            && ($onboardingType === 'Quick' || $onboardingType === 'Super Quick')
            && $onboardingAskForDomain
        ) {
            $this->showdomainoptions = true;
            $this->save();
            return;
        }

        $this->showdomainoptions = false;
        $this->save();
    }

    /**
     * @param string $metric
     * @param string $status
     * @return void
     */
    public function setUsageItemHiddenStatus(string $metric, string $status): void
    {
        UsageItem::where('metric', $metric)
            ->where('rel_id', $this->id)
            ->update(['is_hidden' => $status]);
    }

    public function getConfigurableOptions(): \Illuminate\Support\Collection
    {
        return $this->configurableOptionGroups->flatMap(function ($group) {
            return $group->configurableOptions;
        });
    }

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

    public function getPanelAlphaPlanId(): int
    {
        return (int)$this->configoption1;
    }

    public function isAutomaticInstallInstanceEnabled(): bool
    {
        return $this->configoption2 === 'on';
    }

    public function getThemeName(): string
    {
        return $this->configoption3 ?? "";
    }

    public function getOnboardingType(): ?string
    {
        return $this->configoption6 ?? null;
    }

    public function getOnboardingAskForDomain(): bool
    {
        return $this->configoption7 === 'on';
    }

    public function hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption(): bool
    {
        return $this->configoption10 === 'on';
    }

    public function hasCustomAccountConfiguration(): bool
    {
        return (bool)$this->configoption11;
    }

    public function getCustomAccountConfiguration(): array
    {
        $decodedConfig = html_entity_decode($this->configoption12, ENT_QUOTES | ENT_HTML401, 'UTF-8');
        parse_str($decodedConfig, $config);
        return $config;
    }
}
