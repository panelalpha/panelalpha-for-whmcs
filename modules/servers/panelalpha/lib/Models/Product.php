<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;
use WHMCS\Module\Server\PanelAlpha\Helper;

/**
 * @property string $configoption1
 * @property string $configoption2
 * @property string $configoption3
 * @property string $configoption4
 * @property string $configoption5
 * @property string $configoption6
 * @property bool $showdomainoptions
 * @method static findOrFail(mixed $id)
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
        'configoption1',
        'configoption2',
        'configoption3',
        'configoption4',
        'configoption5',
        'configoption6',
        'configoption7',
        'configoption8',
        'configoption9',
        'configoption10',
        'configoption11',
        'configoption12',
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

    public function serverGroup()
    {
        return $this->belongsTo('WHMCS\Module\Server\PanelAlpha\Models\ServerGroup', 'servergroup');
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class, 'relid')->where('type', 'product');
    }

    public function setConfigOptionsEnabledWhenProductCreated()
    {
        if (!$this->configoption1) {
            $this->configoption2 = 'on';
            $this->configoption4 = 'on';
            $this->configoption5 = 'on';
        }
    }

    public function getPlanAssignedToProduct($plans)
    {
        $selectedPlan = null;
        foreach ($plans as $plan) {
            if ($plan->id === (int)$this->configoption1) {
                $selectedPlan = $plan;
            }
        }
        if (!$selectedPlan) {
            $selectedPlan = $plans[0];
        }
        return $selectedPlan;
    }

    /**
     * @return array
     */
    public function getServer()
    {
        return $this->serverGroup->getFirstServer();
    }

    /**
     * @param array $config
     * @return void
     */
    public function saveConfigOptions(array $config): void
    {
        foreach ($config as $key => $value) {
            $this->{'configoption' . $key} = $value;
        }
        $this->save();
    }

    /**
     * @return void
     */
    public function setShowDomainOption(): void
    {
        $automaticInstallInstance = $this->configoption2 === 'on';
        $onboardingType = $this->configoption6;

        if ($automaticInstallInstance && $onboardingType === 'Standard') {
            $this->showdomainoptions = true;
        } else {
            $this->showdomainoptions = false;
        }
        $this->save();
    }
}