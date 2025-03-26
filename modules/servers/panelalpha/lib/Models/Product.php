<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $configoption1
 * @property string $configoption2
 * @property string $configoption3
 * @property string $configoption4
 * @property string $configoption5
 * @property string $configoption6
 * @property bool $showdomainoptions
 * @property string $configoption7
 * @property string $configoption8
 * @property int $id
 * @property string $servertype
 * @property ServerGroup $serverGroup
 * @method static findOrFail(mixed $id)
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
        $server = $this->serverGroup->servers->first();

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
        $automaticInstallInstance = $this->configoption2 === 'on';
        $onboardingType = $this->configoption6;
        $onboardingAskForDomain = $this->configoption7;

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
}
