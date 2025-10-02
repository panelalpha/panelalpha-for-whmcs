<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static Service findOrFail(int $id)
 * @method static Illuminate\Database\Eloquent\Collection all()
 * @method static Illuminate\Database\Eloquent\Builder active()
 * @method static Illuminate\Database\Eloquent\Builder panelalpha()
 * @method bool save()
 *
 * @property string $username
 * @property string $domain
 * @property Product $product
 * @property Server $serverModel
 * @property int|null $panelalphaServiceId
 */
class Service extends Model
{
    protected $table = 'tblhosting';

    protected $fillable = [
        'userid',
        'orderid',
        'packageid',
        'server',
        'regdate',
        'domain',
        'paymentmethod',
        'firstpaymentamount',
        'amount',
        'billingcycle',
        'nextduedate',
        'nextinvoicedate',
        'termination_date',
        'completed_date',
        'domainstatus',
        'username',
        'password',
        'notes',
        'subscriptionid',
        'promoid',
        'suspendreason',
        'overideautosuspend',
        'overidesuspenduntil',
        'dedicatedip',
        'assignedips',
        'ns1',
        'ns2',
        'diskusage',
        'disklimit',
        'bwusage',
        'bwlimit',
        'lastupdate'
    ];

    public static function getFirstServiceForClient(int $clientId)
    {
        return self::join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblservers', 'tblservers.type', '=', 'tblproducts.servertype')
            ->where('tblservers.type', 'panelalpha')
            ->where('tblclients.id', $clientId)
            ->where('tblhosting.domainstatus', 'Active')
            ->select([
                'tblproducts.configoption5',
                'tblhosting.*'
            ])
            ->first();
    }

    public static function getService(int $panelalphaServiceId): ?Service
    {
        $query = CustomFieldValue::with(['customField', 'service.product'])
            ->whereHas('customField', function ($query) {
                $query->where('fieldname', 'Service ID');
                $query->where('type', 'product');
            })
            ->whereHas('service.product', function ($query) {
                $query->where('servertype', 'panelalpha');
            })
            ->where('value', $panelalphaServiceId)
            ->first();

        return $query->service;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "packageid");
    }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, "relid");
    }

    public function serverModel()
    {
        return $this->belongsTo(Server::class, "server");
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }

    /**
     * @return int|null
     */
    public function getPanelalphaServiceIdAttribute(): ?int
    {
        return !empty($this->getCustomFieldValue('Service ID'))
            ? (int) $this->getCustomFieldValue('Service ID')
            : null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('domainstatus', 'Active');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePanelalpha($query)
    {
        return $query->whereHas('product', function ($query) {
            $query->where('servertype', 'panelalpha');
        });
    }

    /**
     * @param string $fieldName
     * @return string|null
     */
    public function getCustomFieldValue(string $fieldName): ?string
    {
        return $this->customFieldValues()->whereHas('customField', function ($query) use ($fieldName) {
            $query->where('fieldname', $fieldName);
        })->first()?->value;
    }
}
